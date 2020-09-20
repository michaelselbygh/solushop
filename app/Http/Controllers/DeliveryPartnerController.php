<?php

namespace App\Http\Controllers;

use Spatie\Activitylog\Contracts\Activity;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Auth;
use PDF;
use Mail;

use App\Mail\Alert;

use App\AccountTransaction;
use App\Customer;
use App\Count;
use App\SalesAssociate;
use App\DeliveredItem;
use App\DeliveryPartner;
use App\Order;
use App\OrderItem;
use App\PickedUpItem;
use App\SMS;
use App\Vendor;

class DeliveryPartnerController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:delivery-partner');
    }

    public function index()
    {
        $dashboard["transactions"] = AccountTransaction::where([
            ['trans_debit_account', "=", Auth::guard('delivery-partner')->user()->id]
        ])->get()->toArray();

        return view('portal.main.delivery-partner.dashboard')
            ->with("dashboard", $dashboard);
    }

    public function showPickups(){
        return view('portal.main.delivery-partner.pick-ups')
                ->with('pick_up_items',  OrderItem::whereIn('oi_state', [2])->with("sku.product.images", "sku.product.vendor")->get()->toArray());
    }

    public function processPickups(Request $request){
        switch ($request->pick_up_action) {
            case 'mark_item':
                /*--- Change order Item State ---*/
                OrderItem::
                    where([
                        ['id', '=', $request->picked_up_item_id]
                    ])
                    ->update([
                        'oi_state' => 3,
                    ]);
                
                $order_item = OrderItem::where('id', $request->picked_up_item_id)->first()->toArray();
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
                $picked_up_item->pui_marked_by_id           = Auth::guard('delivery-partner')->user()->id;
                $picked_up_item->pui_marked_by_description  = Auth::guard('delivery-partner')->user()->first_name." ".Auth::guard('delivery-partner')->user()->last_name;
                $picked_up_item->save();

                /*--- Log Activity ---*/
                activity()
                ->causedBy(DeliveryPartner::where('id', Auth::guard('delivery-partner')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Order Item Picked Up';
                })
                ->log(Auth::guard('delivery-partner')->user()->first_name." ".Auth::guard('delivery-partner')->user()->last_name." marked ordered item [ ".$order_item["id"]." ] ".$order_item["oi_quantity"]." ".$order_item["oi_name"]." as picked up.");

                /*--- Return with success message ---*/
                return redirect()->back()->with("success_message", $order_item["oi_quantity"]." ".$order_item["oi_name"]." marked as picked up successfully.");
                break;

            case 'download_pick_up_guide':
                //get order items information
                $data["pick_ups"] = OrderItem::orderBy('oi_name', 'asc')->where('oi_state', 2)->with('sku.product.vendor')->get()->toArray();

                //get vendor information
                $data["vendors"] =  DB::select(
                    "SELECT distinct vendors.id, vendors.phone, vendors.alt_phone, vendors.name, vendors.address from vendors, order_items, stock_keeping_units, products where oi_state = '2' and order_items.oi_sku = stock_keeping_units.id and stock_keeping_units.sku_product_id = products.id and products.product_vid = vendors.id order by vendors.name"
                );

                /*--- Log Activity ---*/
                activity()
                ->causedBy(DeliveryPartner::where('id', Auth::guard('delivery-partner')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Pick-Up Guide Download';
                })
                ->log(Auth::guard('delivery-partner')->user()->first_name." ".Auth::guard('delivery-partner')->user()->last_name." downloaded Pick-Up Guide ".date('m-d-Y').".pdf");

                $pdf = PDF::loadView('portal.guides.pick-up', array('data' => $data));
                return $pdf->download('Pick-Up Guide '.date('m-d-Y').'.pdf');

                break;
            
            default:
                return redirect()->back()->with("error_message", "Something went wrong, please try again.");
                break;
        }
    }

    public function showDeliveries(){
        return view('portal.main.delivery-partner.deliveries')
                ->with('delivery_items',  OrderItem::whereIn('oi_state', [2, 3])->with("sku.product.images")->get()->toArray());
    }

    public function processDeliveries(Request $request){
        switch ($request->delivery_action) {
            case 'mark_item':
                    /*--- Change order Item State ---*/
                    OrderItem::
                        where([
                            ['id', '=', $request->delivered_item_id]
                        ])
                        ->update([
                            'oi_state' => 4,
                        ]);
                    
                    $order_item = OrderItem::where('id', $request->delivered_item_id)->with('sku.product.vendor')->first()->toArray();
                    $order = Order::where('id', $order_item['oi_order_id'])->with('customer', 'coupon.sales_associate.badge_info')->first()->toArray();
    
                    /*--- Change Order State (where necessary) ---*/
                    $order_items_count = OrderItem::where('oi_order_id', $order_item['oi_order_id'])->get()->count();
                    $delivered_order_items_count = OrderItem::where('oi_order_id', $order_item['oi_order_id'])->whereIn('oi_state', [4])->get()->count();
                    
    
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
                                $count->account = round($count->account + $order_items[$i]->oi_selling_price, 2);
                            
                                /*---- Record Transaction ----*/
                                $transaction = new AccountTransaction;
                                $transaction->trans_type                = "Order Item Payment";
                                $transaction->trans_amount              = $order_items[$i]->oi_selling_price;
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
    
                    /*--- Log Activity ---*/
                    activity()
                    ->causedBy(SalesAssociate::where('id', Auth::guard('delivery-partner')->user()->id)->get()->first())
                    ->tap(function(Activity $activity) {
                        $activity->subject_type = 'System';
                        $activity->subject_id = '0';
                        $activity->log_name = 'Order Item Delivered';
                    })
                    ->log(Auth::guard('delivery-partner')->user()->first_name." ".Auth::guard('delivery-partner')->user()->last_name." marked ordered item [ ".$order_item["id"]." ] ".$order_item["oi_quantity"]." ".$order_item["oi_name"]." as delivered.");
    
                    $count = null;
                    $count['pickups'] = count(OrderItem::whereIn('oi_state', [2])->get());
                    $count['deliveries'] = count(OrderItem::whereIn('oi_state', [3])->get());
                    return redirect()->back()->with("success_message", $order_item["oi_quantity"]." ".$order_item["oi_name"]." marked as picked up successfully.");
                    break;
    

            case 'download_delivery_guide':
                //get order items information
                $data["deliveries"] = OrderItem::orderBy('oi_name', 'asc')->whereIn('oi_state', [2, 3])->with('order.customer')->get()->toArray();

                //get customers information
                $data["customers"] =  DB::select(
                    "SELECT distinct customers.id, customers.phone, customer_addresses.ca_town, customer_addresses.ca_address, customers.first_name, customers.last_name from customers, customer_addresses, order_items, orders where (oi_state = '2' OR oi_state = '3') and order_items.oi_order_id = orders.id and orders.order_customer_id = customers.id and orders.order_address_id = customer_addresses.id order by customers.first_name"
                );

                /*--- Log Activity ---*/
                activity()
                ->causedBy(DeliveryPartner::where('id', Auth::guard('delivery-partner')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Delivery Guide Download';
                })
                ->log(Auth::guard('delivery-partner')->user()->first_name." ".Auth::guard('delivery-partner')->user()->last_name." downloaded Delivery Guide ".date('m-d-Y').".pdf");

                $pdf = PDF::loadView('portal.guides.delivery', array('data' => $data));
                return $pdf->download('Delivery Guide '.date('m-d-Y').'.pdf');
                break;
            
            default:
                return redirect()->back()->with("error_message", "Something went wrong, please try again.");
                break;
        }
    }
}
