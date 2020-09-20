<?php

namespace App\Http\Controllers;


use PDF;
use Auth;
use Mail;
use Image;
use App\SMS;
use App\Count;
use App\Order;
use Throwable;
use App\Coupon;
use App\Vendor;
use App\Manager;

use App\Message;
use App\Product;
use App\SABadge;
use App\CartItem;
use App\Customer;
use App\OrderItem;
use App\Mail\Alert;
use App\ActivityLog;
use App\MessageFlag;
use App\Conversation;
use App\PickedUpItem;
use App\ProductImage;
use App\WishlistItem;
use App\DeliveredItem;
use App\DeletedMessage;
use App\SalesAssociate;
use App\DeliveryPartner;
use App\ProductCategory;
use App\StockKeepingUnit;
use App\AccountTransaction;
use App\VendorSubscription;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Spatie\Activitylog\Contracts\Activity;



class ManagerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:manager');
    }

    public function showCustomers(){
        return view('portal.main.manager.customers');
    }

    public function showCustomer($customerID){
        if (is_null(Customer::where('id', $customerID)->first())) {
            return redirect()->route("manager.show.customers")->with("error", "Customer not found");
        }

        $customer['id'] = $customerID;

        return view('portal.main.manager.view-customer')
                ->with('customer', $customer);
    }

    public function showOrders(){
        return view('portal.main.manager.orders');
    }

    public function showOrder($orderID){
        $order['id'] = $orderID;

        return view('portal.main.manager.view-order')
                    ->with('order',$order);
    }

    public function processOrder(Request $request, $orderID){
        switch ($request->order_action) {
            case 'confirm_order_payment':
                $order = Order::
                    where("id", $orderID)
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
                    $transaction->trans_description         = $log = "Order Payment of GH¢ ".(round((0.99 * $order["order_subtotal"]), 2) + $order["order_shipping"])." from ".$order["customer"]["first_name"]." ".$order["customer"]["last_name"]." for order $orderID";
                } else {
                    $transaction->trans_description         = $log = "Order Payment of GH¢ ".($order["order_subtotal"] + $order["order_shipping"])." from ".$order["customer"]["first_name"]." ".$order["customer"]["last_name"]." for order $orderID";
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
                    $transaction->trans_description         = $log = "Accrual of GH¢ ".round($order["coupon"]["sales_associate"]["badge_info"]["sab_commission"] * $order["order_subtotal"], 2)." to ".$sales_associate->first_name." ".$sales_associate->last_name." for order $orderID";
                    $transaction->trans_date                = date("Y-m-d G:i:s");
                    $transaction->trans_recorder            = Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name;
                    $transaction->save();

                    //notify sales associate of update
                    $sms = new SMS;
                    $sms->sms_message = "Hi ".$sales_associate->first_name.", order $orderID made with your coupon has been confirmed. Your new balance is GHS ".$sales_associate->balance;
                    $sms->sms_phone = $sales_associate->phone;
                    $sms->sms_state = 1;
                    $sms->save();

                    $data = array(
                        'subject' => 'S.A. Order Confirmed  - Solushop Ghana',
                        'name' => $sales_associate->first_name,
                        'message' => "Order $orderID made with your coupon has been confirmed. Your new balance is GHS ".$sales_associate->balance
                    );
        
                    Mail::to($sales_associate->email, $sales_associate->first_name)
                        ->queue(new Alert($data));


                    //notify management
                    $managers = Manager::where('sms', 0)->get();
                    foreach ($managers as $manager) {

                        $data = array(
                            'subject' => 'New Order - Solushop Ghana',
                            'name' => $manager->first_name,
                            'message' => "This email is to inform you that a new order $orderID has been received. If you are not required to take any action during order processing, please treat this email as purely informational.<br><br>Customer: ".$order["customer"]["first_name"]." ".$order["customer"]["last_name"]."<br>Phone: 0".substr($order["customer"]["phone"], 3)
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
                Order::where('id', $orderID)
                    ->update([
                        "order_state" => 3,
                    ]);

                /*--- Notify Customer ---*/
                $sms = new SMS;
                $sms->sms_message = "Hi ".$order["customer"]["first_name"]." your order $orderID has been confirmed and is being processed.";
                $sms->sms_phone = $order["customer"]["phone"];
                $sms->sms_state = 1;
                $sms->save();

                $data = array(
                    'subject' => 'Order Confirmed - Solushop Ghana',
                    'name' => $order["customer"]["first_name"],
                    'message' => "Your order $orderID has been confirmed and is being processed."
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
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." confirmed payment receipt for order ".$orderID);
                
                return redirect()->back()->with("success_message", "Order payment receipt confirmed.");

                break;

            case 'confirm_order':
                $order = Order::
                        where("id", $orderID)
                        ->with('order_items.sku.product.images', 'order_items.sku.product.vendor', 'order_items.order_item_state', 'customer', 'order_state', 'address', 'coupon.sales_associate.badge_info')
                        ->first()
                        ->toArray();

                /*--- Update Order and Order Items ---*/
                //update order
                Order::where('id', $orderID)
                    ->update([
                        "order_state" => 3,
                    ]);

                //update order
                OrderItem::where('oi_order_id', $orderID)
                    ->update([
                        "oi_state" => 2,
                    ]);
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
                    $transaction->trans_description         = $log = "Accrual of GH¢ ".round($order["coupon"]["sales_associate"]["badge_info"]["sab_commission"] * $order["order_subtotal"], 2)." to ".$sales_associate->first_name." ".$sales_associate->last_name." for order $orderID";
                    $transaction->trans_date                = date("Y-m-d G:i:s");
                    $transaction->trans_recorder            = Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name;
                    $transaction->save();

                    //notify sales associate of update
                    $sms = new SMS;
                    $sms->sms_message = "Hi ".$sales_associate->first_name.", order $orderID made with your coupon has been confirmed. Your new balance is GHS ".$sales_associate->balance;
                    $sms->sms_phone = $sales_associate->phone;
                    $sms->sms_state = 1;
                    $sms->save();

                    $data = array(
                        'subject' => 'S.A. Order Confirmed - Solushop Ghana',
                        'name' => $sales_associate->first_name,
                        'message' => "Order $orderID made with your coupon has been confirmed. Your new balance is GHS ".$sales_associate->balance
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

                //update order
                Order::where('id', $orderID)
                    ->update([
                        "order_state" => 3,
                    ]);

                /*--- Notify Customer ---*/
                $sms = new SMS;
                $sms->sms_message = "Hi ".$order["customer"]["first_name"]." your order $orderID has been confirmed and is being processed.";
                $sms->sms_phone = $order["customer"]["phone"];
                $sms->sms_state = 1;
                $sms->save();

                $data = array(
                    'subject' => 'Order Confirmed - Solushop Ghana',
                    'name' => $order["customer"]["first_name"],
                    'message' => "Your order $orderID has been confirmed and is being processed."
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
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." confirmed order ".$orderID);
                
                return redirect()->back()->with("success_message", "Order confirmed.");
                break;

            case 'cancel_order_no_refund':
                /*--- Update Order Items---*/
                OrderItem::where([
                    ['oi_order_id', '=', $orderID]
                ])->update([
                    'oi_state' => 5
                ]);
            
                /*--- Update Order ---*/
                Order::where([
                    ['id', '=', $orderID]
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
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." cancelled order $orderID.");
                
                return redirect()->back()->with("success_message", "Order cancelled successfully.");
                break;

            case 'cancel_order_partial_refund':
                /*--- Update Order Items---*/
                OrderItem::where([
                    ['oi_order_id', '=', $orderID]
                ])->update([
                    'oi_state' => 5
                ]);
            
                /*--- Update Order ---*/
                Order::where([
                    ['id', '=', $orderID]
                ])
                ->update([
                    'order_state' => 7
                ]);

                $order = Order::where('id', $orderID)->first();

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
                    $transaction->trans_description         = $log = "Partial Refund of GH¢ ".(0.99 * $order->order_subtotal)." to ".$customer->first_name." ".$customer->last_name." for order $orderID";
                } else {
                    $transaction->trans_description         = $log = "Partial Refund of GH¢ ".$order->order_subtotal." to ".$customer->first_name." ".$customer->last_name." for order $orderID";
                }
                
                $transaction->trans_date                = date("Y-m-d G:i:s");
                $transaction->trans_recorder            = Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name;
                $transaction->save();
                /*--- Notify customer ---*/
                $sms_message="Sorry ".$customer->first_name.", your order $orderID has been cancelled. A refund of GHS ";
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
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." cancelled order $orderID.");

                return redirect()->back()->with("success_message", "Order cancelled successfully.");
                break;

            case 'cancel_order_full_refund':
                /*--- Update Order Items---*/
                OrderItem::where([
                    ['oi_order_id', '=', $orderID]
                ])->update([
                    'oi_state' => 5
                ]);
            
                /*--- Update Order ---*/
                Order::where([
                    ['id', '=', $orderID]
                ])
                ->update([
                    'order_state' => 7
                ]);

                $order = Order::where('id', $orderID)->first();

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
                    $transaction->trans_description         = $log = "Full Refund of GH¢ ".(0.99 * $order->order_subtotal + $order->order_shipping)." to ".$customer->first_name." ".$customer->last_name." for order $orderID";
                } else {
                    $transaction->trans_description         = $log = "Full Refund of GH¢ ".$order->order_subtotal + $order->order_shipping." to ".$customer->first_name." ".$customer->last_name." for order $orderID";
                }
                
                $transaction->trans_date                = date("Y-m-d G:i:s");
                $transaction->trans_recorder            = Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name;
                $transaction->save();

                /*--- Notify customer ---*/
                $sms_message="Hi ".$customer->first_name.", your order $orderID has been cancelled. A full refund of GHS ";
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
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." cancelled order $orderID.");

                return redirect()->back()->with("success_message", "Order cancelled successfully.");
                break;

            case 'record_shipping':
                /*--- Record shipping charge on order ---*/
                $order = Order::where('id', $orderID)->first();
                $order->dp_shipping = $request->shipping_amount;
                $order->save();

                /*--- Accrue shipping charge to partner ---*/
                if ($request->shipping_amount > 0) {
                    $partner = DeliveryPartner::where('id', $request->delivery_partner)->first();
                    $partner->balance += $request->shipping_amount;

                    $transaction = new AccountTransaction;
                    $transaction->trans_type                = "Delivery Partner Accrual";
                    $transaction->trans_amount              = $request->shipping_amount;
                    $transaction->trans_credit_account_type = 1;
                    $transaction->trans_credit_account      = "INT-SC001";
                    $transaction->trans_debit_account_type  = 9;
                    $transaction->trans_debit_account       = $partner->id;
                    $transaction->trans_description         = $log = "Accrual of GH¢ ".$request->shipping_amount." to ".$partner->first_name." ".$partner->last_name." for order $orderID";
                    $transaction->trans_date                = date("Y-m-d G:i:s");
                    $transaction->trans_recorder            = Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name;
                    $transaction->save();

                    $partner->save();
                }

                /*--- log activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Order Shipping Charge Entered';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." recorded an ".$log);
                
                return redirect()->back()->with("success_message", "Shipping charge recorded successfully.");

                break;
            
            default:
                # code...
                break;
        }
    }

    public function showMessages(){
        return view('portal.main.manager.conversations');
    }

    public function showFlaggedMessages(){
        return view("portal.main.manager.messages");
    }

    public function showConversation($conversationID){
        $conversation['id'] = $conversationID;
        return view("portal.main.manager.view-conversation")
            ->with("conversation", $conversation);
    }

    public function showProducts(){
        return view("portal.main.manager.products");
    }

    



    public function showAddProduct(){
       return view("portal.main.manager.add-product");
    }


    public function showProduct($productID){
        if (is_null(Product::where('id', $productID)->first())) {
            return redirect()->back()->with("error_message", "Product not found");
        }

        $product['id'] = $productID;

        return view("portal.main.manager.product")
            ->with("product", $product);
    }

    public function processProduct(Request $request, $productID){
        switch ($request->product_action) {
            case 'update_details':
                /*--- Validate form data  ---*/
                $validator = Validator::make($request->all(), [
                    'vendor' => 'required',
                    'name' => 'required',
                    'features' => 'required',
                    'category' => 'required',
                    'settlement_price' => 'required',
                    'selling_price' => 'required',
                    'discount' => 'required',
                    'dd' => 'required',
                    'dc' => 'required',
                    'type' => 'required'
                ]);

                if ($validator->fails()) {
                    $messageType = "error_message";
                    $messageContent = "";

                    foreach ($validator->messages()->getMessages() as $field_name => $messages)
                    {
                        $messageContent .= $messages[0]." "; 
                    }

                    return redirect()->back()->with($messageType, $messageContent);
                }

                $product = Product::where('id', $productID)->first();

                /*--- Validate Product Slug ---*/
                if(trim(strtolower($request->name)) != trim(strtolower($product->product_name))){
                    if((Product::where([
                        ['product_vid', '=', $request->vendor],
                        ['product_name', '=', $request->name],
                        ['id', '<>', $productID]
                    ])->get()->count()) > 0){
                        $product_slug_count = Product::where([
                            ['product_vid', '=', $request->vendor],
                            ['product_name', '=', $request->name],
                            ['id', '<>', $productID]
                        ])->get()->count();
                        $product_slug_count++;
                        $product_slug = str_slug($request->name)."-".$product_slug_count;
                    }else{
                        $product_slug = str_slug($request->name);
                    }
                }else{
                    $product_slug = $product->product_slug;
                }


                /*--- Update Details ---*/
                
                $product->product_vid = $request->vendor;
                $product->product_name = ucwords(strtolower($request->name));
                $product->product_slug = $product_slug;
                $product->product_features = $request->features;
                $product->product_cid = $request->category;
                $product->product_settlement_price = $request->settlement_price;
                $product->product_selling_price = $request->selling_price;
                $product->product_discount = $request->discount;
                $product->product_dd = $request->dd;
                $product->product_dc = $request->dc;
                $product->product_description = $request->description;
                $product->product_tags = $request->tags;
                $product->product_type = $request->type;
                $product->save();


                /*--- log activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Product Details Updated';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." updated details of product ".$productID);
                return redirect()->back()->with("success_message", "Product ".$productID." details updated successfully.");
                break;

            case 'update_stock':
                /*--- update old stock ---*/
                for ($i=0; $i < $request->skuCount; $i++) { 
                    $sku = StockKeepingUnit::where('id', $request->input('sku'.$i))->first();
                    $sku->sku_stock_left = $request->input('stock'.$i);
                    $sku->save();
                }

                /*--- add new stock (if any) ---*/
                if ($request->newSKUCount > $request->skuCount) {
                     //select product
                     $product = Product::where('id', $productID)->first();
                    for ($i=$request->skuCount; $i < $request->newSKUCount; $i++) { 
                        if ((ucfirst(trim($request->input('variantDescription'.$i))) != "None") AND ($request->input('stock'.$i) >= 0)) {
                            //insert sku
                            $count = Count::first();
                            $count->sku_count++;

                            $sku = new StockKeepingUnit;
                            $sku->id                        = "S-".($count->sku_count);
                            $sku->sku_product_id            = $product->id;
                            $sku->sku_variant_description   = $request->input('variantDescription'.$i);
                            $sku->sku_selling_price         = $product->product_selling_price;
                            $sku->sku_settlement_price      = $product->product_settlement_price;
                            $sku->sku_discount              = $product->product_discount;
                            $sku->sku_stock_left            = $request->input('stock'.$i);
                            $sku->save();

                            //update count
                            $count->save();
                        }
                    }

                }

                /*--- log activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Product Stock Updated';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." updated stock of product ".$productID);
                return redirect()->back()->with("success_message", "Product ".$productID." stock updated successfully.");
                break;

            case 'delete_image':
                //select image
                $image = ProductImage::where('id', $request->image_id)->first();

                //delete files
                $main_image_path = "app/assets/img/products/main/";
                $thumbnail_image_path = "app/assets/img/products/thumbnails/";
                $original_image_path = "app/assets/img/products/original/";

                File::delete($main_image_path.$image->pi_path.'.jpg');
                File::delete($thumbnail_image_path.$image->pi_path.'.jpg');
                File::delete($original_image_path.$image->pi_path.'.jpg');

                //delete image
                $image->delete();


                /*--- log activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Product Image Deleted';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." deleted image ".$request->image_id." of product ".$productID);
                return redirect()->back()->with("success_message", "Image ".$request->image_id." deleted successfully.");
                break;
            
            case 'add_images':

                //validate images
                for ($i=0; $i < sizeof($request->product_images); $i++) { 
                    if(!($request->product_images[$i]->getClientOriginalExtension() == "jpg" OR $request->product_images[$i]->getClientOriginalExtension() == "jpeg")){
                        return back()->with("error_message", "Images must be of type jpg");
                    }

                    list($width, $height) = getimagesize($request->product_images[$i]);
                    if ($width != $height or $height < 600) {
                        return back()->with("error_message", "Images must be minimum height 600px with aspect ratio of 1");
                    }

                    if(filesize($request->product_images[$i]) > 5000000){
                        return back()->with("error_message", "One or more images exceed the allowed size for upload.");
                    }
                }

                //process images
                for ($i=0; $i < sizeof($request->product_images); $i++) { 
                    
                    $product_image = new ProductImage;
                    $product_image->pi_product_id = $productID;
                    $product_image->pi_path = $productID.rand(1000, 9999);

                    $img = Image::make($request->product_images[$i]);

                    //save original image
                    $img->save('app/assets/img/products/original/'.$product_image->pi_path.'.jpg');

                    //save main image
                    $img->resize(600, 600);
                    $img->insert('portal/images/watermark/stamp.png', 'center');
                    $img->save('app/assets/img/products/main/'.$product_image->pi_path.'.jpg');

                    //save thumbnail
                    $img->resize(300, 300);
                    $img->save('app/assets/img/products/thumbnails/'.$product_image->pi_path.'.jpg');

                    //store image details
                    $product_image->save();
                }

                /*--- log activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Product Image(s) Uploaded';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." uploaded images for product ".$productID);
                return redirect()->back()->with("success_message", "Upload Successful.");
                break;

            default:
                return redirect()->back()->with("error_message", "Something went wrong. Please try again.");
                break;
        }
    }

    public function showVendors(){
        return view("portal.main.manager.vendors");
    }

    public function showAddVendor(){
        return view("portal.main.manager.add-vendor");
    }

    public function showVendor($vendorID){

        $vendor['id'] = $vendorID;
        return view("portal.main.manager.vendor")
            ->with('vendor', $vendor);
    }

    

    public function showLogisticsActive(){
        return view('portal.main.manager.active-logistics');
    }

    public function showLogisticsHistory(){
        return view('portal.main.manager.history-logistics');
    }

    public function processActivePickups(Request $request){
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
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Pick-Up Guide Download';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." downloaded Pick-Up Guide ".date('m-d-Y').".pdf");

                $pdf = PDF::loadView('portal.guides.pick-up', array('data' => $data));
                return $pdf->download('Pick-Up Guide '.date('m-d-Y').'.pdf');

                break;
            
            default:
                return redirect()->back()->with("error_message", "Something went wrong, please try again.");
                break;
        }
    }

    public function processActiveDeliveries(Request $request){
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
                $order = Order::where('id', $order_item['oi_order_id'])->with('customer')->first()->toArray();

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
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Order Item Delivered';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." marked ordered item [ ".$order_item["id"]." ] ".$order_item["oi_quantity"]." ".$order_item["oi_name"]." as delivered.");

                /*--- Return with success message ---*/
                return redirect()->back()->with("success_message", $order_item["oi_quantity"]." ".$order_item["oi_name"]." marked as delivered successfully.");
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
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Delivery Guide Download';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." downloaded Delivery Guide ".date('m-d-Y').".pdf");

                $pdf = PDF::loadView('portal.guides.delivery', array('data' => $data));
                return $pdf->download('Delivery Guide '.date('m-d-Y').'.pdf');
                break;
            
            default:
                return redirect()->back()->with("error_message", "Something went wrong, please try again.");
                break;
        }
    }

    public function showCoupons(){
        return view('portal.main.manager.coupons');
    }

    public function showGenerateCoupon(){
        return view('portal.main.manager.generate-coupon');
    }

    public function showSalesAssociates(){
        return view('portal.main.manager.sales-associates');
    }

    public function showAddSalesAssociate(){
        return view('portal.main.manager.add-sales-associate');
    }

    public function showSalesAssociate($memberID){

       $associate['id'] = $memberID;

        return view('portal.main.manager.view-sales-associate')
                ->with('associate', $associate);
    }

    public function processSalesAssociate(Request $request, $memberID){
        //select sales associates details
        $associate = SalesAssociate::where('id', $memberID)->with('badge_info')->first();
        
        switch ($request->sa_action) {
            case 'update_details':
                /*--- validate ---*/
                $validator = Validator::make($request->all(), [
                    'first_name' => 'required',
                    'last_name' => 'required',
                    'email' => 'required|email',
                    'phone' => 'required|digits:10',
                    'mode_of_payment' => 'required',
                    'payment_details' => 'required',
                    'residential_address' => 'required',
                ]);
        
                if ($validator->fails()) {
                    $messageType = "error_message";
                    $messageContent = "";
        
                    foreach ($validator->messages()->getMessages() as $field_name => $messages)
                    {
                        $messageContent .= $messages[0]." "; 
                    }
        
                    return redirect()->back()->withInput(['first_name', 'last_name', 'email', 'phone', 'mode_of_payment', 'payment_details', 'residential_address'])->with($messageType, $messageContent);
                }

                //update details
                $associate->first_name = $request->first_name;
                $associate->last_name = $request->last_name;
                $associate->email = $request->email;
                $associate->phone = "233".substr($request->phone, 1);
                $associate->mode_of_payment = $request->mode_of_payment;
                $associate->payment_details = $request->payment_details;
                $associate->address = $request->residential_address;


                /*--- log activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Sales Associate Details Update';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." updated the details of sales associate, ".$associate->first_name." ".$associate->last_name);

                $success_message = $associate->first_name." ".$associate->last_name."'s details updated successfully.";
                $associate->save();
                
                return redirect()->back()->with("success_message", $success_message);
                break;

            case 'record_payout':
                
                /*--- Record transaction ---*/
                $transaction = new AccountTransaction;
                $transaction->trans_type                = "Sales Associate Payout";
                $transaction->trans_amount              = $request->pay_out_amount;
                $transaction->trans_credit_account_type = 1;
                $transaction->trans_credit_account      = "INT-SC001";
                $transaction->trans_debit_account_type  = 8;
                $transaction->trans_debit_account       = $associate->id;
                $transaction->trans_description         = "Payout of GH¢ ".$request->pay_out_amount." to ".$associate->first_name." ".$associate->last_name;
                $transaction->trans_date                = date("Y-m-d G:i:s");
                $transaction->trans_recorder            = Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name;
                $transaction->save();

                /*--- Update Associate Balance ---*/
                $associate->balance -= $request->pay_out_amount;

                /*--- Update Main Account Balance ---*/
                $counts = Count::first();
                $counts->account = round($counts->account - $request->pay_out_amount, 2);
                $counts->save();

                /*--- Notify vendor ---*/
                $sms = new SMS;
                $sms->sms_message = "Dear ".$associate->first_name.", a payout of GHS ".$request->pay_out_amount." has been recorded to you. Your new balance is GHS ".$associate->balance;
                $sms->sms_phone = $associate->phone;
                $sms->sms_state = 1;
                $sms->save();

                $data = array(
                    'subject' => 'Sales Associate Payout - Solushop Ghana',
                    'name' => $associate->first_name,
                    'message' => "A payout of GHS ".$request->pay_out_amount." has been recorded to you. Your new balance is GHS ".$associate->balance
                );

                Mail::to($associate->email, $associate->first_name)
                    ->queue(new Alert($data));

                /*--- log activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Sales Associate Payout';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." recorded a payout of GH¢ ".$request->pay_out_amount." to sales associate, ".$associate->first_name." ".$associate->last_name);

                $success_message = "Payout of GH¢ ".$request->pay_out_amount." to ".$associate->first_name." ".$associate->last_name." recorded successfully.";
                $associate->save();
                
                return redirect()->back()->with("success_message", $success_message);
                
                break;
            default:
                return redirect()->back()->with("error_message", "Something went wrong. Please try again.");
                break;
        }
    }

    public function showDeliveryPartners(){
        return view('portal.main.manager.delivery-partners');
    }

    public function showAddDeliveryPartner(){
        return view('portal.main.manager.add-delivery-partner');
    }

    public function showDeliveryPartner($partnerID){

        $partner["id"] = $partnerID;

        return view('portal.main.manager.view-delivery-partner')
                ->with('partner', $partner);
    }

    public function showAccounts(){
        $accounts["transactions"] = AccountTransaction::all()->toArray();
        $accounts["balance"]["total"] = Count::sum('account');
        $accounts["balance"]["vendors"] = Vendor::sum('balance');
        $accounts["balance"]["sales-associates"] = SalesAssociate::sum('balance');
        $accounts["balance"]["delivery-partners"] = DeliveryPartner::sum('balance');
        $accounts["balance"]["available"] = $accounts["balance"]["total"] - $accounts["balance"]["vendors"] - $accounts["balance"]["sales-associates"];


        return view('portal.main.manager.accounts')
                ->with("accounts", $accounts);

    }

    public function showSubscriptions()
    {
        return view('portal.main.manager.subscriptions');
    }

    public function showActivityLog()
    {
        return view('portal.main.manager.activity-log');
    }

    public function showSMSReport()
    {
        return view('portal.main.manager.sms-report');
    }

    public function index()
    {
        return view('portal.main.manager.dashboard');
    }

    //guides
    public function showDeliveryGuide(){
         //get order items information
         $data["deliveries"] = OrderItem::orderBy('oi_name', 'asc')->whereIn('oi_state', [2, 3])->with('order.customer')->get()->toArray();

         //get customers information
         $data["customers"] =  DB::select(
             "SELECT distinct customers.id, customers.phone, customer_addresses.ca_town, customer_addresses.ca_address, customers.first_name, customers.last_name from customers, customer_addresses, order_items, orders where (oi_state = '2' OR oi_state = '3') and order_items.oi_order_id = orders.id and orders.order_customer_id = customers.id and orders.order_address_id = customer_addresses.id order by customers.first_name"
         );
         
        //  return view('portal.guides.delivery')
        //     ->with('data', $data);

         $pdf = PDF::loadView('portal.guides.delivery', array('data' => $data));
         return $pdf->download('Delivery Guide '.date('m-d-Y').'.pdf');
         
    }

    public function showPickUpGuide(){
        //get order items information
        $data["pick_ups"] = OrderItem::orderBy('oi_name', 'asc')->where('oi_state', 2)->with('sku.product.vendor')->get()->toArray();

        //get vendor information
        $data["vendors"] =  DB::select(
            "SELECT distinct vendors.id, vendors.phone, vendors.alt_phone, vendors.name, vendors.address from vendors, order_items, stock_keeping_units, products where oi_state = '2' and order_items.oi_sku = stock_keeping_units.id and stock_keeping_units.sku_product_id = products.id and products.product_vid = vendors.id order by vendors.name"
        );

        $pdf = PDF::loadView('portal.guides.pick-up', array('data' => $data));
        return $pdf->download('Pick-Up Guide'.date('m-d-Y').'.pdf');
    }

    public function broadcastVendors(){
        $vendors = Vendor::all();
        try {
            for ($i=0; $i < sizeof($vendors); $i++) { 
                $sms = new SMS;
                $sms->sms_message = "Hi ".$vendors[$i]->name.", we urge you to take extra measures in order to keep ourselves and our families safe. Sanitize before handling any product due for pick-up. We owe our customers a duty to be there for them and to keep them safe. We can only do that working with you. This too shall pass. Believe!";
                $sms->sms_phone = $vendors[$i]->phone;
                $sms->sms_state = 1;
                $sms->save();
    
                // $data = array(
                //     'subject' => 'COVID19 Notice - Solushop Ghana',
                //     'name' => $vendors[$i]->name,
                //     'message' => "We urge you to take extra measures in order to keep ourselves and our families safe. Sanitize before handling any product due for pick-up. We owe our customers a duty to be there for them and to keep them safe. We can only do that working with you. This too shall pass. Believe!"
                // );
        
                // Mail::to($vendors[$i]->email, $vendors[$i]->name)
                //     ->queue(new Alert($data));
            }
        } catch (\Throwable $th) {

        }

        
    }

    public function broadcastCustomers(){
        $customers = Customer::all();

        for ($i=0; $i < sizeof($customers); $i++) { 
            $sms = new SMS;
            $sms->sms_message = "Hi ".$customers[$i]->first_name.", your health, safety and satisfaction will continue to be our topmost priority especially in these trying times. We will stock up on commodities we believe you will need the most. Should you need something that is not listed, please feel free reach us on 0506753093. This too shall pass. Just believe!\n\nSolushop. \nYour most trusted online store.\nhttps://www.solushop.com.gh";
            $sms->sms_phone = $customers[$i]->phone;
            $sms->sms_state = 1;
            $sms->save();

            // $data = array(
            //     'subject' => 'COVID19 Notice - Solushop Ghana',
            //     'name' => $customers[$i]->first_name,
            //     'message' => "Your health, safety and satisfaction will continue to be our topmost priority especially in these trying times. We will stock up on commodities we believe you will need the most. Should you need something that is not listed, please feel free reach us on 0506753093. This too shall pass. Just believe!<br><br>Solushop. Your most trusted online store.<br>https://www.solushop.com.gh"
            // );
    
            // Mail::to($customers[$i]->email, $customers[$i]->first_name)
            //     ->queue(new Alert($data));
        }

        
    }
}
