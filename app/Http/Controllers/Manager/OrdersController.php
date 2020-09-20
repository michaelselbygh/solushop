<?php

namespace App\Http\Controllers\Manager;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use Spatie\Activitylog\Contracts\Activity;

use App\AccountTransaction;
use App\Count;
use App\Customer;
use App\DeliveryPartner;
use App\Manager;
use App\Order;
use App\OrderItem;
use App\SABadge;
use App\SalesAssociate;
use App\SMS;
use App\StockKeepingUnit;
use App\Vendor;

use Auth;

use Mail;
use App\Mail\Alert;



class OrdersController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:manager');
    }
    
    public function getOrdersCount(Request $request){
        return response()->json([
            'new' => count(Order::whereIn('order_state', $request->new)->get()),
            'ongoing' => count(Order::whereIn('order_state', $request->ongoing)->get()),
            'completed' => count(Order::whereIn('order_state', $request->completed)->get()),
            'unpaid' => count(Order::whereIn('order_state', $request->unpaid)->get()),
            'total' => count(Order::get()),
        ]);
    }

    public function getOrdersRecords(Request $request){
        switch ($request->type) {
            case 'New':
                $states = [2];
                break;

            case 'Ongoing':
                $states = [3, 4, 5];
                break;

            case 'Completed':
                $states = [6];
                break;

            case 'Unpaid':
                $states = [1];
                break;
            
            default:
                $states = [1, 2, 3, 4, 5, 6, 7];
                break;
        }
        return response()->json([
            'new' => count(Order::whereIn('order_state', [2])->get()),
            'ongoing' => count(Order::whereIn('order_state', [3, 4, 5])->get()),
            'completed' => count(Order::whereIn('order_state', [6])->get()),
            'unpaid' => count(Order::whereIn('order_state', [1])->get()),
            'total' => count(Order::get()),
            'records' => Order::whereIn('order_state', $states)->with('order_state', 'order_items.sku.product.images', 'customer')->get()
        ]);
    }

    public function getOrderCount(Request $request){
        $count["order"] = Order::where('id', '=', $request->id)->first()->updated_at;
        $count["items"] = OrderItem::where('oi_order_id', '=', $request->id)->get('updated_at');

        return response()->json([
            "count" => $count,
        ]);
    }

    public function getOrderRecords(Request $request){ 

        $order =  Order::
            where("id", $request->id)
            ->with('order_items.sku.product.images','order_items.order_item_state', 'customer', 'order_state', 'address', 'coupon.sales_associate.badge_info')
            ->first()
            ->toArray();

        $order["delivery_partner"] = DeliveryPartner::get()->toArray();

        if(strtotime($order["order_state"]["id"] == 6 AND $order["updated_at"]) < strtotime('-14 days') AND $order["dp_shipping"] == NULL){
            $order["allow_shipping_entry"] = "Yes";
        }

        /*--- Nets ---*/
        if ($order["order_state"]["id"] == 6) {
            //calculate profit or loss
            $order["profit_or_loss"] = 0;
            $order["profit_or_loss_item"] = [];

            //profit from order items
            for ($i=0; $i < sizeof($order["order_items"]); $i++) { 
                $order["profit_or_loss_item"]["description"][$i] = "Profit from ".$order["order_items"][$i]["oi_quantity"]." ".$order["order_items"][$i]["oi_name"];
                $order["profit_or_loss_item"]["amount"][$i] = $order["order_items"][$i]["oi_quantity"] * ( $order["order_items"][$i]["oi_selling_price"] - $order["order_items"][$i]["oi_settlement_price"]);
                $order["profit_or_loss"] += $order["profit_or_loss_item"]["amount"][$i];
            }

            //Shipping charged from customer
            $order["profit_or_loss_item"]["description"][$i] = "Shipping fee from ".$order["customer"]["first_name"]." ".$order["customer"]["last_name"];
            $order["profit_or_loss_item"]["amount"][$i] = $order["order_shipping"];
            $order["profit_or_loss"] += $order["profit_or_loss_item"]["amount"][$i];
            $i++;

            if($order["dp_shipping"] != NULL){
                //shipping paid to delivery partner
                $order["profit_or_loss_item"]["description"][$i] = "Shipping fee paid to Delivery Partner ";
                $order["profit_or_loss_item"]["amount"][$i] = -1 * $order["dp_shipping"];
                $order["profit_or_loss"] += $order["profit_or_loss_item"]["amount"][$i];
                $i++;
            }
            

            //loss from sales coupon if set
            if(isset($order["order_scoupon"]) AND $order["order_scoupon"] != NULL AND $order["order_scoupon"] != "NULL"){
                //1% discount on sale
                $order["profit_or_loss_item"]["description"][$i] = "1% discount from Sales Coupon ".$order["coupon"]["coupon_code"];
                $order["profit_or_loss_item"]["amount"][$i] = round(-0.01 * $order["order_subtotal"], 2);
                $order["profit_or_loss"] += $order["profit_or_loss_item"]["amount"][$i];
                $i++;

                //Commission to Sales Associate
                $order["profit_or_loss_item"]["description"][$i] = (100*$order["coupon"]["sales_associate"]["badge_info"]["sab_commission"])."% commission to Sales Associate  ".$order["coupon"]["sales_associate"]["first_name"]." ".$order["coupon"]["sales_associate"]["last_name"]." ( May have changed if SA was promoted )";
                $order["profit_or_loss_item"]["amount"][$i] = round(-1 * $order["coupon"]["sales_associate"]["badge_info"]["sab_commission"] * $order["order_subtotal"], 2);
                $order["profit_or_loss"] += $order["profit_or_loss_item"]["amount"][$i];
                $i++;
            }
        }

        $count["order"] = Order::where('id', '=', $request->id)->first()->updated_at;
        $count["items"] = OrderItem::where('oi_order_id', '=', $request->id)->get('updated_at');

        return response()->json([
            'count' => $count,
            'order' => $order
        ]);
    }

    public function processOrderAction(Request $request){
        switch ($request->action) {
            case 'confirm_order_payment':
                $order = Order::
                    where("id", $request->id)
                    ->with('order_items.sku.product.images', 'order_items.sku.product.vendor', 'order_items.order_item_state', 'customer', 'order_state', 'address', 'coupon.sales_associate.badge_info')
                    ->first()
                    ->toArray();

                /*--- Update Account Balance | Record Transaction ---*/
                $count = Count::first();
                if(isset($order["order_scoupon"]) AND $order["order_scoupon"] != NULL AND $order["order_scoupon"] != "NULL"){
                    $count->account += round(( 0.99 * $order["order_subtotal"] ) + $order["order_shipping"], 2);
                }else{
                    $count->account += round($order["order_subtotal"] + $order["order_shipping"], 2);
                }
                $count->save();

                $transaction = new AccountTransaction;
                $transaction->trans_type = "Order Payment";
                if (isset($order["order_scoupon"]) AND $order["order_scoupon"] != NULL AND $order["order_scoupon"] != "NULL") {
                    $transaction->trans_amount = round(0.99 * $order["order_subtotal"], 2) + $order["order_shipping"];
                } else {
                    $transaction->trans_amount = $order["order_subtotal"] + $order["order_shipping"];
                }
                $transaction->trans_credit_account_type = 6;
                $transaction->trans_credit_account      = $order["order_customer_id"];
                $transaction->trans_debit_account_type  = 1;
                $transaction->trans_debit_account       = "INT-SC001";
                if (isset($order["order_scoupon"]) AND $order["order_scoupon"] != NULL AND $order["order_scoupon"] != "NULL") {
                    $transaction->trans_description         = $log = "Order Payment of GH¢ ".(round((0.99 * $order["order_subtotal"]), 2) + $order["order_shipping"])." from ".$order["customer"]["first_name"]." ".$order["customer"]["last_name"]." for order ".$request->id;
                } else {
                    $transaction->trans_description         = $log = "Order Payment of GH¢ ".($order["order_subtotal"] + $order["order_shipping"])." from ".$order["customer"]["first_name"]." ".$order["customer"]["last_name"]." for order ".$request->id;
                }
                $transaction->trans_date                = date("Y-m-d G:i:s");
                $transaction->trans_recorder            = Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name;
                $transaction->save();
                
                /*--- Update Order and Order Items | Reduce vendor stock | notify vendors ---*/
                //update order items sku
                for ($i=0; $i < sizeof($order["order_items"]); $i++) { 
                    $order_item_sku = StockKeepingUnit::where('id', $order["order_items"][$i]["sku"]["id"])->first();
                    $order_item_sku->sku_stock_left -= $order["order_items"][$i]["oi_quantity"];

                    //notify vendor
                    $sms = new SMS;
                    $sms->sms_message = "Purchase Alert\nItem: ".$order["order_items"][$i]["oi_name"]."\nQuantity Purchased: ".$order["order_items"][$i]["oi_quantity"]."\n Quantity Remaining: ".$order_item_sku->sku_stock_left;
                    $sms->sms_phone = $order["order_items"][$i]["sku"]["product"]["vendor"]["phone"];
                    $sms->sms_state = 1;
                    $sms->save();

                    $data = array(
                        'subject' => 'Purchase Alert - Solushop Ghana',
                        'name' => $order["order_items"][$i]["sku"]["product"]["vendor"]["name"],
                        'message' => "You have a new order.<br><br>Product: ".$order["order_items"][$i]["oi_name"]."<br>Quantity Purchased: ".$order["order_items"][$i]["oi_quantity"]."<br>Quantity Remaining: ".$order_item_sku->sku_stock_left."<br><br>"
                    );
        
                    Mail::to($order["order_items"][$i]["sku"]["product"]["vendor"]["email"], $order["order_items"][$i]["sku"]["product"]["vendor"]["name"])
                        ->queue(new Alert($data));

                    $order_item_sku->save();

                    $order_item = OrderItem::where('oi_sku', $order["order_items"][$i]["sku"]["id"])->first();
                    $order_item->oi_state = 2;
                    $order_item->save();
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
                    $transaction->trans_description         = $log = "Accrual of GH¢ ".round($order["coupon"]["sales_associate"]["badge_info"]["sab_commission"] * $order["order_subtotal"], 2)." to ".$sales_associate->first_name." ".$sales_associate->last_name." for order ".$request->id;
                    $transaction->trans_date                = date("Y-m-d G:i:s");
                    $transaction->trans_recorder            = Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name;
                    $transaction->save();

                    //notify sales associate of update
                    $sms = new SMS;
                    $sms->sms_message = "Hi ".$sales_associate->first_name.", order ".$request->id." made with your coupon has been confirmed. Your new balance is GHS ".$sales_associate->balance;
                    $sms->sms_phone = $sales_associate->phone;
                    $sms->sms_state = 1;
                    $sms->save();

                    $data = array(
                        'subject' => 'S.A. Order Confirmed  - Solushop Ghana',
                        'name' => $sales_associate->first_name,
                        'message' => "Order ".$request->id." made with your coupon has been confirmed. Your new balance is GHS ".$sales_associate->balance
                    );
        
                    Mail::to($sales_associate->email, $sales_associate->first_name)
                        ->queue(new Alert($data));


                    //notify management
                    $managers = Manager::where('sms', 0)->get();
                    foreach ($managers as $manager) {

                        $data = array(
                            'subject' => 'New Order - Solushop Ghana',
                            'name' => $manager->first_name,
                            'message' => "This email is to inform you that a new order ".$request->id." has been received. If you are not required to take any action during order processing, please treat this email as purely informational.<br><br>Customer: ".$order["customer"]["first_name"]." ".$order["customer"]["last_name"]."<br>Phone: 0".substr($order["customer"]["phone"], 3)
                        );

                        Mail::to($manager->email, $manager->first_name)
                            ->queue(new Alert($data));
                    }

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
                        $sms = new SMS;
                        $sms->sms_message = "Congrats ".$sales_associate->first_name.", you are now an Veteran Sales Associate. You can now enjoy 3% commission on all orders.";
                        $sms->sms_phone = $sales_associate->phone;
                        $sms->sms_state = 1;
                        $sms->save();
                        $sales_associate->badge = 3;

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

                //update order
                Order::where('id', $request->id)
                    ->update([
                        "order_state" => 3,
                    ]);

                /*--- Notify Customer ---*/
                $sms = new SMS;
                $sms->sms_message = "Hi ".$order["customer"]["first_name"]." your order ".$request->id." has been confirmed and is being processed.";
                $sms->sms_phone = $order["customer"]["phone"];
                $sms->sms_state = 1;
                $sms->save();

                $data = array(
                    'subject' => 'Order Confirmed - Solushop Ghana',
                    'name' => $order["customer"]["first_name"],
                    'message' => "Your order ".$request->id." has been confirmed and is being processed."
                );
    
                Mail::to($order["customer"]["email"], $order["customer"]["first_name"])
                    ->queue(new Alert($data));

                /*--- Log Activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Order Payment Receipt Confirmation';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." confirmed payment receipt for order ".$request->id);
                
                $message = "Order & Payment confirmed successfully ";

                break;

            case 'confirm_order':
                $order = Order::
                        where("id", $request->id)
                        ->with('order_items.sku.product.images', 'order_items.sku.product.vendor', 'order_items.order_item_state', 'customer', 'order_state', 'address', 'coupon.sales_associate.badge_info')
                        ->first()
                        ->toArray();

                /*--- Update Order and Order Items ---*/
                //update order
                Order::where('id', $request->id)
                    ->update([
                        "order_state" => 3,
                    ]);

                //update order
                OrderItem::where('oi_order_id', $request->id)
                    ->update([
                        "oi_state" => 2,
                    ]);


                if($order["payment_type"] == 1){
                    /*--- Update Order and Order Items | Reduce vendor stock | notify vendors ---*/
                    //update order items sku
                    for ($i=0; $i < sizeof($order["order_items"]); $i++) { 
                        $order_item_sku = StockKeepingUnit::where('id', $order["order_items"][$i]["sku"]["id"])->first();
                        $order_item_sku->sku_stock_left -= $order["order_items"][$i]["oi_quantity"];

                        //notify vendor
                        $sms = new SMS;
                        $sms->sms_message = "Purchase Alert\nItem: ".$order["order_items"][$i]["oi_name"]."\nQuantity Purchased: ".$order["order_items"][$i]["oi_quantity"]."\n Quantity Remaining: ".$order_item_sku->sku_stock_left;
                        $sms->sms_phone = $order["order_items"][$i]["sku"]["product"]["vendor"]["phone"];
                        $sms->sms_state = 1;
                        $sms->save();

                        $data = array(
                            'subject' => 'Purchase Alert - Solushop Ghana',
                            'name' => $order["order_items"][$i]["sku"]["product"]["vendor"]["name"],
                            'message' => "You have a new order.<br><br>Product: ".$order["order_items"][$i]["oi_name"]."<br>Quantity Purchased: ".$order["order_items"][$i]["oi_quantity"]."<br>Quantity Remaining: ".$order_item_sku->sku_stock_left."<br><br>"
                        );
            
                        Mail::to($order["order_items"][$i]["sku"]["product"]["vendor"]["email"], $order["order_items"][$i]["sku"]["product"]["vendor"]["name"])
                            ->queue(new Alert($data));

                        $order_item_sku->save();
                    }
                }
                

                //update order
                Order::where('id', $request->id)
                    ->update([
                        "order_state" => 3,
                    ]);

                /*--- Notify Customer ---*/
                $sms = new SMS;
                $sms->sms_message = "Hi ".$order["customer"]["first_name"]." your order ".$request->id." has been confirmed and is being processed.";
                $sms->sms_phone = $order["customer"]["phone"];
                $sms->sms_state = 1;
                $sms->save();

                $data = array(
                    'subject' => 'Order Confirmed - Solushop Ghana',
                    'name' => $order["customer"]["first_name"],
                    'message' => "Your order ".$request->id." has been confirmed and is being processed."
                );
    
                Mail::to($order["customer"]["email"], $order["customer"]["first_name"])
                    ->queue(new Alert($data));

                /*--- Log Activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Order Confirmation';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." confirmed order ".$request->id);
                
                $message = "Order confirmed successfully ";
                break;

            case 'cancel_order_no_refund':
                /*--- Update Order Items---*/
                OrderItem::where([
                    ['oi_order_id', '=', $request->id]
                ])->update([
                    'oi_state' => 5
                ]);
            
                /*--- Update Order ---*/
                Order::where([
                    ['id', '=', $request->id]
                ])
                ->update([
                    'order_state' => 7
                ]);

                /*--- Log Activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Order Cancellation (No Refund)';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." cancelled order ".$request->id);
                
                $message = "Order cancelled successfully";
                break;

            case 'cancel_order_partial_refund':
                /*--- Update Order Items---*/
                OrderItem::where([
                    ['oi_order_id', '=', $request->id]
                ])->update([
                    'oi_state' => 5
                ]);
            
                /*--- Update Order ---*/
                Order::where([
                    ['id', '=', $request->id]
                ])
                ->update([
                    'order_state' => 7
                ]);

                $order = Order::where('id', $request->id)->first();

                /*--- Deduct from main account ---*/
                // $count = Count::first();
                // if(isset($order->order_scoupon) AND $order->order_scoupon != NULL AND $order->order_scoupon != "NULL"){
                //     $count->account -= 0.99 * $order->order_subtotal;
                // }else{
                //     $count->account -= $order->order_subtotal;
                // }
                // $count->save();

                /*--- Top up customer ---*/
                $customer = Customer::where('id', $order->order_customer_id)->with('milk', 'chocolate')->first();

                if(isset($order->order_scoupon) AND $order->order_scoupon != NULL AND $order->order_scoupon != "NULL"){
                    $newCustomerBalance     = round((($customer->milk->milk_value * $customer->milkshake) - $customer->chocolate->chocolate_value) + (0.99 *  $order->order_subtotal), 2);
                }else{
                    $newCustomerBalance     = round((($customer->milk->milk_value * $customer->milkshake) - $customer->chocolate->chocolate_value) + $order->order_subtotal, 2);
                }
                $newCustomerMilkshake   = ($newCustomerBalance + $customer->chocolate->chocolate_value) / $customer->milk->milk_value;
                $customer->milkshake    = $newCustomerMilkshake;

                $transaction = new AccountTransaction;
                $transaction->trans_type                = "Partial Order Refund";
                if (isset($order->order_scoupon) AND $order->order_scoupon != NULL AND $order->order_scoupon != "NULL") {
                    $transaction->trans_amount = round(0.99 * $order->order_subtotal, 2);
                } else {
                    $transaction->trans_amount = $order->order_subtotal;
                }
                $transaction->trans_credit_account_type = 1;
                $transaction->trans_credit_account      = "INT-SC001";
                $transaction->trans_debit_account_type  = 5;
                $transaction->trans_debit_account       = $customer->id;
                if (isset($order->order_scoupon) AND $order->order_scoupon != NULL AND $order->order_scoupon != "NULL") {
                    $transaction->trans_description         = $log = "Partial Refund of GH¢ ".(0.99 * $order->order_subtotal)." to ".$customer->first_name." ".$customer->last_name." for order ".$request->id;
                } else {
                    $transaction->trans_description         = $log = "Partial Refund of GH¢ ".$order->order_subtotal." to ".$customer->first_name." ".$customer->last_name." for order ".$request->id;
                }
                
                $transaction->trans_date                = date("Y-m-d G:i:s");
                $transaction->trans_recorder            = Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name;
                $transaction->save();
                /*--- Notify customer ---*/
                $sms_message="Sorry ".$customer->first_name.", your order ".$request->id." has been cancelled. A refund of GHS ";
                if (isset($order->order_scoupon) AND $order->order_scoupon != NULL AND $order->order_scoupon != "NULL") {
                    $sms_message .= (0.99 * $order->order_subtotal);
                } else {
                    $sms_message .= $order->order_subtotal;
                }
                $sms_message .= " has been done to your Solushop Wallet. We apologize for any inconvenience caused.";
                $email_message = $sms_message;
                $sms = new SMS;
                $sms->sms_message = $sms_message;
                $sms->sms_phone = $customer->phone;
                $sms->sms_state = 1;
                $sms->save();

                $data = array(
                    'subject' => 'Order Cancelled - Solushop Ghana',
                    'name' => $customer->first_name,
                    'message' => $email_message
                );
    
                Mail::to($customer->email, $customer->first_name)
                    ->queue(new Alert($data));

                $order->save();
                $customer->save();
                

                /*--- Log Activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Order Cancellation (Partial Refund)';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." cancelled order ".$request->id);

                $message = "Order cancelled successfully";
                break;

            case 'cancel_order_full_refund':
                /*--- Update Order Items---*/
                OrderItem::where([
                    ['oi_order_id', '=', $request->id]
                ])->update([
                    'oi_state' => 5
                ]);
            
                /*--- Update Order ---*/
                Order::where([
                    ['id', '=', $request->id]
                ])
                ->update([
                    'order_state' => 7
                ]);

                $order = Order::where('id', $request->id)->first();

                /*--- Deduct from main account ---*/
                // $count = Count::first();
                // if(isset($order->order_scoupon) AND $order->order_scoupon != NULL AND $order->order_scoupon != "NULL"){
                //     $count->account -= 0.99 * $order->order_subtotal + $order->order_shipping;
                // }else{
                //     $count->account -= $order->order_subtotal + $order->order_shipping;
                // }
                // $count->save();

                /*--- Top up customer ---*/
                $customer = Customer::where('id', $order->order_customer_id)->with('milk', 'chocolate')->first();

                if(isset($order->order_scoupon) AND $order->order_scoupon != NULL AND $order->order_scoupon != "NULL"){
                    $newCustomerBalance     = round((($customer->milk->milk_value * $customer->milkshake) - $customer->chocolate->chocolate_value) + (0.99 *  $order->order_subtotal + $order->order_shipping), 2);
                }else{
                    $newCustomerBalance     = round((($customer->milk->milk_value * $customer->milkshake) - $customer->chocolate->chocolate_value) + $order->order_subtotal + $order->order_shipping, 2);
                }
                $newCustomerMilkshake   = ($newCustomerBalance + $customer->chocolate->chocolate_value) / $customer->milk->milk_value;
                $customer->milkshake    = $newCustomerMilkshake;

                $transaction = new AccountTransaction;
                $transaction->trans_type                = "Full Order Refund";
                if (isset($order->order_scoupon) AND $order->order_scoupon != NULL AND $order->order_scoupon != "NULL") {
                    $transaction->trans_amount = round(0.99 * $order->order_subtotal + $order->order_shipping, 2);
                } else {
                    $transaction->trans_amount = $order->order_subtotal + $order->order_shipping;
                }
                $transaction->trans_credit_account_type = 1;
                $transaction->trans_credit_account      = "INT-SC001";
                $transaction->trans_debit_account_type  = 5;
                $transaction->trans_debit_account       = $customer->id;
                if (isset($order->order_scoupon) AND $order->order_scoupon != NULL AND $order->order_scoupon != "NULL") {
                    $transaction->trans_description         = $log = "Full Refund of GH¢ ".(0.99 * $order->order_subtotal + $order->order_shipping)." to ".$customer->first_name." ".$customer->last_name." for order ".$request->id;
                } else {
                    $transaction->trans_description         = $log = "Full Refund of GH¢ ".($order->order_subtotal + $order->order_shipping)." to ".$customer->first_name." ".$customer->last_name." for order ".$request->id;
                }
                
                $transaction->trans_date                = date("Y-m-d G:i:s");
                $transaction->trans_recorder            = Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name;
                $transaction->save();

                /*--- Notify customer ---*/
                $sms_message="Hi ".$customer->first_name.", your order ".$request->id." has been cancelled. A full refund of GHS ";
                if (isset($order->order_scoupon) AND $order->order_scoupon != NULL AND $order->order_scoupon != "NULL") {
                    $sms_message .= (0.99 * $order->order_subtotal) + $order->order_shipping;
                } else {
                    $sms_message .= $order->order_subtotal + $order->order_shipping;
                }
                $sms_message .= " has been done to your Solushop Wallet. We apologize for any inconvenience caused.";
                $sms = new SMS;
                $sms->sms_message = $email_message = $sms_message;
                $sms->sms_phone = $customer->phone;
                $sms->sms_state = 1;
                $sms->save();

                $data = array(
                    'subject' => 'Order Cancelled - Solushop Ghana',
                    'name' => $customer->first_name,
                    'message' => $email_message
                );
    
                Mail::to($customer->email, $customer->first_name)
                    ->queue(new Alert($data));

                $order->save();
                $customer->save();
                

                /*--- Log Activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Order Cancellation (Full Refund)';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." cancelled order ".$request->id);

                $message = "Order cancelled successfully";
                break;
            
            default:
                # code...
                break;
        }

        $order =  Order::
            where("id", $request->id)
            ->with('order_items.sku.product.images','order_items.order_item_state', 'customer', 'order_state', 'address', 'coupon.sales_associate.badge_info')
            ->first()
            ->toArray();

        $order["delivery_partner"] = DeliveryPartner::get()->toArray();

        if(strtotime($order["order_state"]["id"] == 6 AND $order["updated_at"]) < strtotime('-14 days') AND $order["dp_shipping"] == NULL){
            $order["allow_shipping_entry"] = "Yes";
        }

        /*--- Nets ---*/
        if ($order["order_state"]["id"] == 6) {
            //calculate profit or loss
            $order["profit_or_loss"] = 0;
            $order["profit_or_loss_item"] = [];

            //profit from order items
            for ($i=0; $i < sizeof($order["order_items"]); $i++) { 
                $order["profit_or_loss_item"]["description"][$i] = "Profit from ".$order["order_items"][$i]["oi_quantity"]." ".$order["order_items"][$i]["oi_name"];
                $order["profit_or_loss_item"]["amount"][$i] = $order["order_items"][$i]["oi_quantity"] * ( $order["order_items"][$i]["oi_selling_price"] - $order["order_items"][$i]["oi_settlement_price"]);
                $order["profit_or_loss"] += $order["profit_or_loss_item"]["amount"][$i];
            }

            //Shipping charged from customer
            $order["profit_or_loss_item"]["description"][$i] = "Shipping fee from ".$order["customer"]["first_name"]." ".$order["customer"]["last_name"];
            $order["profit_or_loss_item"]["amount"][$i] = $order["order_shipping"];
            $order["profit_or_loss"] += $order["profit_or_loss_item"]["amount"][$i];
            $i++;

            if($order["dp_shipping"] != NULL){
                //shipping paid to delivery partner
                $order["profit_or_loss_item"]["description"][$i] = "Shipping fee paid to Delivery Partner ";
                $order["profit_or_loss_item"]["amount"][$i] = -1 * $order["dp_shipping"];
                $order["profit_or_loss"] += $order["profit_or_loss_item"]["amount"][$i];
                $i++;
            }
            

            //loss from sales coupon if set
            if(isset($order["order_scoupon"]) AND $order["order_scoupon"] != NULL AND $order["order_scoupon"] != "NULL"){
                //1% discount on sale
                $order["profit_or_loss_item"]["description"][$i] = "1% discount from Sales Coupon ".$order["coupon"]["coupon_code"];
                $order["profit_or_loss_item"]["amount"][$i] = round(-0.01 * $order["order_subtotal"], 2);
                $order["profit_or_loss"] += $order["profit_or_loss_item"]["amount"][$i];
                $i++;

                //Commission to Sales Associate
                $order["profit_or_loss_item"]["description"][$i] = (100*$order["coupon"]["sales_associate"]["badge_info"]["sab_commission"])."% commission to Sales Associate  ".$order["coupon"]["sales_associate"]["first_name"]." ".$order["coupon"]["sales_associate"]["last_name"]." ( May have changed if SA was promoted )";
                $order["profit_or_loss_item"]["amount"][$i] = round(-1 * $order["coupon"]["sales_associate"]["badge_info"]["sab_commission"] * $order["order_subtotal"], 2);
                $order["profit_or_loss"] += $order["profit_or_loss_item"]["amount"][$i];
                $i++;
            }
        }

        $count["order"] = Order::where('id', '=', $request->id)->first()->updated_at;
        $count["items"] = OrderItem::where('oi_order_id', '=', $request->id)->get('updated_at');

        return response()->json([
            'order' => $order,
            'type' => 'success',
            'message' => $message,
            "count" => $count,
        ]);
    }

    public function processOrderShipping(Request $request){
        /*--- Record shipping charge on order ---*/
        $order = Order::where('id', $request->id)->first();
        $order->dp_shipping = $request->amount;
        $order->save();

        /*--- Accrue shipping charge to partner ---*/
        $partner = DeliveryPartner::where('id', $request->partner)->first();
        $partner->balance += $request->amount;

        $transaction = new AccountTransaction;
        $transaction->trans_type                = "Delivery Partner Accrual";
        $transaction->trans_amount              = $request->amount;
        $transaction->trans_credit_account_type = 1;
        $transaction->trans_credit_account      = "INT-SC001";
        $transaction->trans_debit_account_type  = 9;
        $transaction->trans_debit_account       = $partner->id;
        $transaction->trans_description         = $log = "Accrual of GH¢ ".$request->amount." to ".$partner->first_name." ".$partner->last_name." for order ".$request->id;
        $transaction->trans_date                = date("Y-m-d G:i:s");
        $transaction->trans_recorder            = Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name;
        $transaction->save();

        $partner->save();

        /*--- log activity ---*/
        activity()
        ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
        ->tap(function(Activity $activity) {
            $activity->subject_type = 'System';
            $activity->subject_id = '0';
            $activity->log_name = 'Order Shipping Charge Entered';
        })
        ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." recorded an ".$log);
        
        $count["order"] = Order::where('id', '=', $request->id)->first()->updated_at;
        $count["items"] = OrderItem::where('oi_order_id', '=', $request->id)->get('updated_at');

        $order =  Order::
            where("id", $request->id)
            ->with('order_items.sku.product.images','order_items.order_item_state', 'customer', 'order_state', 'address', 'coupon.sales_associate.badge_info')
            ->first()
            ->toArray();

        $order["delivery_partner"] = DeliveryPartner::get()->toArray();

        if(strtotime($order["order_state"]["id"] == 6 AND $order["updated_at"]) < strtotime('-14 days') AND $order["dp_shipping"] == NULL){
            $order["allow_shipping_entry"] = "Yes";
        }

        /*--- Nets ---*/
        if ($order["order_state"]["id"] == 6) {
            //calculate profit or loss
            $order["profit_or_loss"] = 0;
            $order["profit_or_loss_item"] = [];

            //profit from order items
            for ($i=0; $i < sizeof($order["order_items"]); $i++) { 
                $order["profit_or_loss_item"]["description"][$i] = "Profit from ".$order["order_items"][$i]["oi_quantity"]." ".$order["order_items"][$i]["oi_name"];
                $order["profit_or_loss_item"]["amount"][$i] = $order["order_items"][$i]["oi_quantity"] * ( $order["order_items"][$i]["oi_selling_price"] - $order["order_items"][$i]["oi_settlement_price"]);
                $order["profit_or_loss"] += $order["profit_or_loss_item"]["amount"][$i];
            }

            //Shipping charged from customer
            $order["profit_or_loss_item"]["description"][$i] = "Shipping fee from ".$order["customer"]["first_name"]." ".$order["customer"]["last_name"];
            $order["profit_or_loss_item"]["amount"][$i] = $order["order_shipping"];
            $order["profit_or_loss"] += $order["profit_or_loss_item"]["amount"][$i];
            $i++;

            if($order["dp_shipping"] != NULL){
                //shipping paid to delivery partner
                $order["profit_or_loss_item"]["description"][$i] = "Shipping fee paid to Delivery Partner ";
                $order["profit_or_loss_item"]["amount"][$i] = -1 * $order["dp_shipping"];
                $order["profit_or_loss"] += $order["profit_or_loss_item"]["amount"][$i];
                $i++;
            }
            

            //loss from sales coupon if set
            if(isset($order["order_scoupon"]) AND $order["order_scoupon"] != NULL AND $order["order_scoupon"] != "NULL"){
                //1% discount on sale
                $order["profit_or_loss_item"]["description"][$i] = "1% discount from Sales Coupon ".$order["coupon"]["coupon_code"];
                $order["profit_or_loss_item"]["amount"][$i] = round(-0.01 * $order["order_subtotal"], 2);
                $order["profit_or_loss"] += $order["profit_or_loss_item"]["amount"][$i];
                $i++;

                //Commission to Sales Associate
                $order["profit_or_loss_item"]["description"][$i] = (100*$order["coupon"]["sales_associate"]["badge_info"]["sab_commission"])."% commission to Sales Associate  ".$order["coupon"]["sales_associate"]["first_name"]." ".$order["coupon"]["sales_associate"]["last_name"]." ( May have changed if SA was promoted )";
                $order["profit_or_loss_item"]["amount"][$i] = round(-1 * $order["coupon"]["sales_associate"]["badge_info"]["sab_commission"] * $order["order_subtotal"], 2);
                $order["profit_or_loss"] += $order["profit_or_loss_item"]["amount"][$i];
                $i++;
            }
        }

        return response()->json([
            'order' => $order,
            'type' => 'success',
            'message' => "Shipping charge recorded.",
            "count" => $count,
        ]);
    }

}
