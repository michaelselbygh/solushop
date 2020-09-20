<?php

namespace App\Http\Controllers\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Activitylog\Contracts\Activity;

use App\AccountTransaction;
use App\Count;
use App\Customer;
use App\DeliveryPartner;
use App\DeliveredItem;
use App\Manager;
use App\Order;
use App\OrderItem;
use App\PickedUpItem;
use App\SABadge;
use App\SalesAssociate;
use App\SMS;
use App\StockKeepingUnit;
use App\Vendor;

use Auth;

use Mail;
use App\Mail\Alert;


class LogisticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:manager');
    }

    
    public function getActiveLogisticsCount(Request $request){
        $count['pickups'] = count(OrderItem::whereIn('oi_state', [2])->get());
        $count['deliveries'] = count(OrderItem::whereIn('oi_state', [3])->get());
        return response()->json([
            'count' => $count,
        ]);
    }

    public function getActiveLogisticsRecords(Request $request){
        $count['pickups'] = count(OrderItem::whereIn('oi_state', [2])->get());
        $count['deliveries'] = count(OrderItem::whereIn('oi_state', [3])->get());
        return response()->json([
            'count' => $count,
            'records' => OrderItem::whereIn('oi_state', [2, 3])->with("order_item_state", "sku.product.images", "sku.product.vendor")->get()
        ]);
    }


    public function processLogisticsAction(Request $request){
        
        switch($request->action){
            case 'Picked Up':
                /*--- Change order Item State ---*/
                OrderItem::
                    where([
                        ['id', '=', $request->id]
                    ])
                    ->update([
                        'oi_state' => 3,
                    ]);
                
                $order_item = OrderItem::where('id', $request->id)->first()->toArray();
                $order = Order::where('id', $order_item['oi_order_id'])->with('customer')->first()->toArray();

                /*--- Change Order State (where necessary) ---*/
                $order_items_count = OrderItem::where('oi_order_id', $order_item['oi_order_id'])->get()->count();
                $picked_up_order_items_count = OrderItem::where('oi_order_id', $order_item['oi_order_id'])->whereIn('oi_state', [3, 4])->get()->count();
                

                if ($order_items_count == $picked_up_order_items_count) {
                    Order::
                    where([
                        ['id', '=', $order_item['oi_order_id']]
                    ])
                    ->update([
                        'order_state' => 4,
                    ]);
                }

                /*--- Notify Customer ---*/
                $sms = new SMS;
                $sms->sms_message = "Hi ".$order["customer"]["first_name"]." your ordered item, ".$order_item["oi_quantity"]." ".$order_item["oi_name"]." has been picked up and is ready for delivery.";
                $sms->sms_phone = $order["customer"]["phone"];
                $sms->sms_state = 1;
                $sms->save();

                $data = array(
                    'subject' => 'Order Item Picked Up - Solushop Ghana',
                    'name' => $order["customer"]["first_name"],
                    'message' => "Your ordered item, ".$order_item["oi_quantity"]." ".$order_item["oi_name"]." has been picked up and is ready for delivery."
                );

                Mail::to($order["customer"]["email"], $order["customer"]["first_name"])
                    ->queue(new Alert($data));

                /*--- Record Pickup History ---*/
                $picked_up_item = new PickedUpItem;
                $picked_up_item->pui_order_item_id          = $order_item["id"];
                $picked_up_item->pui_marked_by_id           = Auth::guard('manager')->user()->id;
                $picked_up_item->pui_marked_by_description  = Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name;
                $picked_up_item->save();

                /*--- Log Activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Order Item Picked Up';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." marked ordered item [ ".$order_item["id"]." ] ".$order_item["oi_quantity"]." ".$order_item["oi_name"]." as picked up.");

                $count['pickups'] = count(OrderItem::whereIn('oi_state', [2])->get());
                $count['deliveries'] = count(OrderItem::whereIn('oi_state', [3])->get());
                return response()->json([
                    'type' => 'success',
                    'message' => 'Item '.$request->id.' marked as '.$request->action,
                    'count' => $count,
                    'records' =>  OrderItem::whereIn('oi_state', [2, 3])->with("order_item_state", "sku.product.images", "sku.product.vendor")->get()
                ]);
                break;

            case 'Delivered':
                /*--- Change order Item State ---*/
                OrderItem::
                    where([
                        ['id', '=', $request->id]
                    ])
                    ->update([
                        'oi_state' => 4,
                    ]);
                
                $order_item = OrderItem::where('id', $request->id)->with('sku.product.vendor')->first()->toArray();
                $order = Order::where('id', $order_item['oi_order_id'])->with('customer', 'coupon.sales_associate.badge_info')->first()->toArray();

                /*--- Change Order State (where necessary) ---*/
                $order_items_count = OrderItem::where('oi_order_id', $order_item['oi_order_id'])->get()->count();
                $delivered_order_items_count = OrderItem::where('oi_order_id', $order_item['oi_order_id'])->whereIn('oi_state', [4])->get()->count();
                
                /*--- Notify Customer ---*/
                $sms = new SMS;
                $sms->sms_message = "Hi ".$order["customer"]["first_name"]." your ordered item, ".$order_item["oi_quantity"]." ".$order_item["oi_name"]." has been delivered successfully. Thanks, come back soon.";
                $sms->sms_phone = $order["customer"]["phone"];
                $sms->sms_state = 1;
                $sms->save();

                $data = array(
                    'subject' => 'Order Item Delivered - Solushop Ghana',
                    'name' => $order["customer"]["first_name"],
                    'message' => "Your ordered item, ".$order_item["oi_quantity"]." ".$order_item["oi_name"]." has been delivered successfully. <br><br>Thanks, come back soon."
                );

                Mail::to($order["customer"]["email"], $order["customer"]["first_name"])
                    ->queue(new Alert($data));

                /*--- Accrue to Vendor || Record Transaction ---*/
                $vendor = Vendor::where('id', $order_item['sku']["product"]["vendor"]["id"])->first();
                $vendor->balance += round(($order_item['oi_settlement_price'] - $order_item['oi_discount']) * $order_item['oi_quantity'], 2);

                $transaction = new AccountTransaction;
                $transaction->trans_type                = "Vendor Accrual";
                $transaction->trans_amount              = round(($order_item['oi_settlement_price'] - $order_item['oi_discount']) * $order_item['oi_quantity'], 2);
                $transaction->trans_credit_account_type = 1;
                $transaction->trans_credit_account      = "INT-SC001";
                $transaction->trans_debit_account_type  = 3;
                $transaction->trans_debit_account       = $vendor->id;
                $transaction->trans_description         = $log = "Accrual of GH¢ ".round(($order_item['oi_settlement_price'] - $order_item['oi_discount']) * $order_item['oi_quantity'], 2)." to ".$vendor->name." for ordered item [ ".$order_item["id"]." ] ".$order_item["oi_quantity"]." ".$order_item["oi_name"];
                $transaction->trans_date                = date("Y-m-d G:i:s");
                $transaction->trans_recorder            = Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name;
                $transaction->save();


                //record transaction
                $vendor->save();

                /*--- Record Delivery History ---*/
                $delivered_item = new DeliveredItem;
                $delivered_item->di_order_item_id          = $order_item["id"];
                $delivered_item->di_marked_by_id           = Auth::guard('manager')->user()->id;
                $delivered_item->di_marked_by_description  = Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name;
                $delivered_item->save();

                
                if ($order_items_count == $delivered_order_items_count) {
                    Order::
                    where([
                        ['id', '=', $order_item['oi_order_id']]
                    ])
                    ->update([
                        'order_state' => 6,
                    ]);

                    if ($order['payment_type'] == 1) {
                        $count = Count::first();

                        //Loop through  order items and add to balance where item is POD
                        $order_items = OrderItem::where('oi_order_id', $order['id'])->get();
                        for ($i=0; $i < sizeof($order_items); $i++) { 

                            /*---- Add SP  ----*/
                            $count->account = round($count->account + ($order_items[$i]->oi_selling_price - $order_items[$i]->oi_discount) * $order_items[$i]->oi_quantity, 2);
                        
                            /*---- Record Transaction ----*/
                            $transaction = new AccountTransaction;
                            $transaction->trans_type                = "Order Item Payment";
                            $transaction->trans_amount              = ($order_items[$i]->oi_selling_price - $order_items[$i]->oi_discount) * $order_items[$i]->oi_quantity;
                            $transaction->trans_credit_account_type = 6;
                            $transaction->trans_credit_account      = $order['order_customer_id'];
                            $transaction->trans_debit_account_type  = 1;
                            $transaction->trans_debit_account       = "INT-SC001";
                            $transaction->trans_description         = "Payment of GH¢ ".$order_items[$i]->oi_selling_price." received for delivered order item ".$order_items[$i]->oi_name;
                            $transaction->trans_date                = date("Y-m-d G:i:s");
                            $transaction->trans_recorder            = Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name;
                            $transaction->save();
                        }

                        /*---- Add Shipping  ----*/
                        $count->account = round($count->account + $order['order_shipping'], 2);
                    
                        /*---- Record Transaction ----*/
                        $transaction = new AccountTransaction;
                        $transaction->trans_type                = "Order Shipping Payment";
                        $transaction->trans_amount              = $order['order_shipping'];
                        $transaction->trans_credit_account_type = 6;
                        $transaction->trans_credit_account      = $order['order_customer_id'];
                        $transaction->trans_debit_account_type  = 1;
                        $transaction->trans_debit_account       = "INT-SC001";
                        $transaction->trans_description         = "Payment of GH¢ ".$order['order_shipping']." received as shipping fee for completed order ".$order['id'];
                        $transaction->trans_date                = date("Y-m-d G:i:s");
                        $transaction->trans_recorder            = Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name;
                        $transaction->save();

                        $count->save();
                    }

                    

                     /*--- Accrue to Sales Associate if Coupon was used | Record Transaction | Promote Sales associate where necessary ---*/
                    if (isset($order["order_scoupon"]) AND $order["order_scoupon"] != NULL AND $order["order_scoupon"] != "NULL") {
                        //sales associate old total sales
                        $ots = Order::
                            where("order_scoupon", $order["order_scoupon"])
                            ->whereIn("order_state", [3, 4, 5, 6])
                            ->sum('order_subtotal');

                        //sales associate new total sales
                        $nts = $ots + $order["order_subtotal"];

                        $sales_associate = SalesAssociate::
                            where('id', $order["coupon"]["sales_associate"]["id"])
                            ->first();

                        //update sales associate balance
                        $sales_associate->balance += round($order["coupon"]["sales_associate"]["badge_info"]["sab_commission"] * $order["order_subtotal"], 2);

                        //record transaction
                        $transaction = new AccountTransaction;
                        $transaction->trans_type                = "Sales Associate Accrual";
                        $transaction->trans_amount              = round($order["coupon"]["sales_associate"]["badge_info"]["sab_commission"] * $order["order_subtotal"], 2);
                        $transaction->trans_credit_account_type = 1;
                        $transaction->trans_credit_account      = "INT-SC001";
                        $transaction->trans_debit_account_type  = 7;
                        $transaction->trans_debit_account       = $order["coupon"]["sales_associate"]["id"];
                        $transaction->trans_description         = $log = "Accrual of GH¢ ".round($order["coupon"]["sales_associate"]["badge_info"]["sab_commission"] * $order["order_subtotal"], 2)." to ".$sales_associate->first_name." ".$sales_associate->last_name." for order ".$order["id"];
                        $transaction->trans_date                = date("Y-m-d G:i:s");
                        $transaction->trans_recorder            = Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name;
                        $transaction->save();

                        //deduct discount percentage from order
                        $count = Count::first();
                        $count->account = round($count->account - (0.01 * $order["order_subtotal"]) ,2);
                        $count->save();

                        //record transaction
                        $transaction = new AccountTransaction;
                        $transaction->trans_type                = "Sales Associate Order Discount";
                        $transaction->trans_amount              = round((0.01 * $order["order_subtotal"]), 2);
                        $transaction->trans_credit_account_type = 1;
                        $transaction->trans_credit_account      = "INT-SC001";
                        $transaction->trans_debit_account_type  = 2;
                        $transaction->trans_debit_account       = "EXT";
                        $transaction->trans_description         = $log = "Deduction of GH¢ ".round((0.01 * $order["order_subtotal"]) ,2)." as sales associate discount for order ".$order["id"];
                        $transaction->trans_date                = date("Y-m-d G:i:s");
                        $transaction->trans_recorder            = Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name;
                        $transaction->save();

                        

                        //notify sales associate of update
                        $sms = new SMS;
                        $sms->sms_message = "Hi ".$sales_associate->first_name.", order ".$order["id"]." made with your coupon has been completed. Your new balance is GHS ".$sales_associate->balance;
                        $sms->sms_phone = $sales_associate->phone;
                        $sms->sms_state = 1;
                        $sms->save();

                        $data = array(
                            'subject' => 'S.A. Order Completed - Solushop Ghana',
                            'name' => $sales_associate->first_name,
                            'message' => "Order ".$order['id']." made with your coupon has been completed. Your new balance is GHS ".$sales_associate->balance
                        );
            
                        Mail::to($sales_associate->email, $sales_associate->first_name)
                            ->queue(new Alert($data));

                        //promote where necessary
                        if ($ots < 20000 && $nts >= 20000) {
                            //promote to elite
                            $sales_associate->badge = 4;

                            $sms = new SMS;
                            $sms->sms_message = "Congrats ".$sales_associate->first_name.", you are now an Elite Sales Associate. You can now enjoy 4% commission on all orders.";
                            $sms->sms_phone = $sales_associate->phone;
                            $sms->sms_state = 1;
                            $sms->save();

                            $data = array(
                                'subject' => 'Promotion to Elite! - Solushop Ghana',
                                'name' => $sales_associate->first_name,
                                'message' => "Congrats! You are now an Elite Sales Associate. You can now enjoy 4% commission on all orders."
                            );
                
                            Mail::to($sales_associate->email, $sales_associate->first_name)
                                ->queue(new Alert($data));

                        }elseif($ots < 5000 && $nts >= 5000){
                            //promote to veteran
                            $sales_associate->badge = 3;
                            $sms = new SMS;
                            $sms->sms_message = "Congrats ".$sales_associate->first_name.", you are now an Veteran Sales Associate. You can now enjoy 3% commission on all orders.";
                            $sms->sms_phone = $sales_associate->phone;
                            $sms->sms_state = 1;
                            $sms->save();

                            $data = array(
                                'subject' => 'Promotion to Veteran! - Solushop Ghana',
                                'name' => $sales_associate->first_name,
                                'message' => "Congrats! You are now an Veteran Sales Associate. You can now enjoy 3% commission on all orders."
                            );
                
                            Mail::to($sales_associate->email, $sales_associate->first_name)
                                ->queue(new Alert($data));

                        }elseif($ots == 0 && $nts > 0){
                            //promote to rookie
                            $sales_associate->badge = 2;
                            $sms = new SMS;
                            $sms->sms_message = "Congrats ".$sales_associate->first_name." on your first sale. You are now a Rookie sales associate. Keep selling to become a Veteran and enjoy 3% commission on all orders.";
                            $sms->sms_phone = $sales_associate->phone;
                            $sms->sms_state = 1;
                            $sms->save();

                            $data = array(
                                'subject' => 'Promotion to Rookie! - Solushop Ghana',
                                'name' => $sales_associate->first_name,
                                'message' => "Congrats on your first sale. You are now a Rookie sales associate. Keep selling to become a Veteran and enjoy 3% commission on all orders."
                            );
                
                            Mail::to($sales_associate->email, $sales_associate->first_name)
                                ->queue(new Alert($data));

                        }

                        $sales_associate->save();
                    }

                    


                }

                

                /*--- Log Activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Order Item Delivered';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." marked ordered item [ ".$order_item["id"]." ] ".$order_item["oi_quantity"]." ".$order_item["oi_name"]." as delivered.");

                $count = null;
                $count['pickups'] = count(OrderItem::whereIn('oi_state', [2])->get());
                $count['deliveries'] = count(OrderItem::whereIn('oi_state', [3])->get());
                return response()->json([
                    'type' => 'success',
                    'message' => 'Item '.$request->id.' marked as '.$request->action,
                    'count' => $count,
                    'records' =>  OrderItem::whereIn('oi_state', [2, 3])->with("order_item_state", "sku.product.images", "sku.product.vendor")->get()
                ]);
            break;

        }
    }

    public function getHistoryLogisticsCount(Request $request){
        $count['pickups'] = count(PickedUpItem::with('order_item')->get());
        $count['deliveries'] = count(DeliveredItem::with('order_item')->get());
        return response()->json([
            'count' => $count,
        ]);
    }

    public function getHistoryLogisticsRecords(Request $request){
        $count['pickups'] = count(PickedUpItem::with('order_item')->get());
        $count['deliveries'] = count(DeliveredItem::with('order_item')->get());
        switch($request->filter){
            case 'Pickup':
                return response()->json([
                    'count' => $count,
                    'records' => PickedUpItem::with('order_item')->get()
                ]);
                break;

            case 'Delivery':
                return response()->json([
                    'count' => $count,
                    'records' => DeliveredItem::with('order_item')->get()
                ]);
                break;

        }
    }
}
