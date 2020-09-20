<?php

namespace App\Http\Controllers\Manager;

use Illuminate\Http\Request;
use Spatie\Activitylog\Contracts\Activity;
use Illuminate\Support\Facades\Validator;

use App\Http\Controllers\Controller;

use App\AccountTransaction;
use App\Count;
use App\CustomerAddress;
use App\Customer;
use App\Manager;
use App\Order;

use Auth;

class CustomersController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:manager');
    }

    
    public function getCustomersCount(Request $request){
        $count['customers'] = count(Customer::get());
        $count['balance'] = Customer::sum('milkshake');
        return response()->json([
            'count' => $count
        ]);
    }

    public function getCustomersRecords(Request $request){
        $count['customers'] = count(Customer::get());
        $count['balance'] = Customer::sum('milkshake');
        return response()->json([
            'count' => $count,
            'records' => Customer::with('milk', 'chocolate')->get()
        ]);
    }

    public function getCustomerCount(Request $request){
        return response()->json([
            'updated' => Customer::where('id', '=', $request->id)->first()->updated_at,
            'addresses' => CustomerAddress::where('ca_customer_id', '=', $request->id)->get()->count(),
            'orders' => Order::where('order_customer_id', '=', $request->id)->get()->count()
        ]);
    }

    public function getCustomerRecords(Request $request){
        return response()->json([
            'updated' => Customer::where('id', '=', $request->id)->first()->updated_at,
            'addresses' => CustomerAddress::where('ca_customer_id', '=', $request->id)->get()->count(),
            'orders' => Order::where('order_customer_id', '=', $request->id)->get()->count(),
            'records' => Customer::where('id', '=', $request->id)->with('addresses', 'orders.order_state', 'orders.order_items.sku.product.images', 'milk', 'chocolate')->first()
        ]);
    }

    public function updateCustomerRecord(Request $request){
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'phone' => 'required|digits:12'
        ]);

        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->all() as $key => $value) {
                array_set($errors, $key, $value);
            }
            return response()->json([
                "type" => "error",
                "message" => $errors
            ]);
        }

        Customer::where('id', '=', $request->id)->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        /*--- log activity ---*/
        activity()
        ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
        ->tap(function(Activity $activity) {
            $activity->subject_type = 'System';
            $activity->subject_id = '0';
            $activity->log_name = 'Customer Details Updated';
        })
        ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." updated the details of customer, ".$request->first_name." ".$request->last_name);

        return response()->json([
            "type" => "success",
            "message" => $request->first_name."'s details updated.",
            "updated" => Customer::where('id', '=', $request->id)->first()->updated_at
        ]);
    }
    
    public function recordCustomerPayment(Request $request){
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->all() as $key => $value) {
                array_set($errors, $key, $value);
            }
            return response()->json([
                "type" => "error",
                "message" => $errors
            ]);
        }

        $customer = Customer::where('id', $request->id)->with('milk', 'chocolate')->first();
        $count = Count::first();

        switch ($request->type) {
            case 'Pay-In':
                /*--- Add paid amount to the current balance  ---*/
                $newCustomerBalance     = round((($customer->milk->milk_value * $customer->milkshake) - $customer->chocolate->chocolate_value) + $request->amount, 2);
                $newCustomerMilkshake   = ($newCustomerBalance + $customer->chocolate->chocolate_value) / $customer->milk->milk_value;
                $customer->milkshake    = $newCustomerMilkshake;
                
                /*--- Deduct paid amount from company balance  ---*/
                $count->account = round($count->account - $request->amount, 2);

                /*--- Record Transaction  ---*/
                $transaction = new AccountTransaction;
                $transaction->trans_type                = "Customer ".$request->type;
                $transaction->trans_amount              = $request->amount;
                $transaction->trans_credit_account_type = 1;
                $transaction->trans_credit_account      = "INT-SC001";
                $transaction->trans_debit_account_type  = 5;
                $transaction->trans_debit_account       = $request->id;
                $transaction->trans_description         = "Customer ".$request->type." of GH¢ ".$request->amount." to ".$customer->first_name." ".$customer->last_name." (".$request->description.")";
                $transaction->trans_date                = date("Y-m-d G:i:s");
                $transaction->trans_recorder            = Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name;
                $transaction->save();
                
                /*--- log activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) use ($request) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Customer Wallet '.$request->type;
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." recorded a ".$request->type." of GH¢ ".$request->amount." to customer, ".$customer->first_name." ".$customer->last_name);

                /*--- Save customer account balance  ---*/
                $customer->save();

                /*--- Save company account balance  ---*/
                $count->save();

                /*--- Send sms  ---*/
                send_sms($customer->phone, "Hi ".$customer->first_name.", you have received GHS ".$request->amount.". Your new wallet balance is GHS ".$newCustomerBalance);
                

                return response()->json([
                    "type" => "success",
                    "message" => "GH¢ ".$request->amount." ".$request->type." successful",
                    "updated" => Customer::where('id', '=', $request->id)->first()->updated_at,
                    "milkshake" => $newCustomerMilkshake
                ]);
                break;

            case 'Pay-Out':
                /*--- Deduct paid amount from the current balance  ---*/
                $newCustomerBalance     = round((($customer->milk->milk_value * $customer->milkshake) - $customer->chocolate->chocolate_value) - $request->amount, 2);
                $newCustomerMilkshake   = ($newCustomerBalance + $customer->chocolate->chocolate_value) / $customer->milk->milk_value;
                $customer->milkshake    = $newCustomerMilkshake;
                
                /*--- Add paid amount to company balance  ---*/
                $count->account = round($count->account + $request->amount, 2);

                /*--- Record Transaction  ---*/
                $transaction = new AccountTransaction;
                $transaction->trans_type                = "Customer ".$request->type;
                $transaction->trans_amount              = $request->amount;
                $transaction->trans_credit_account_type = 5;
                $transaction->trans_credit_account      = $request->id;
                $transaction->trans_debit_account_type  = 1;
                $transaction->trans_debit_account       = "INT-SC001";
                $transaction->trans_description         = "Customer ".$request->type." of GH¢ ".$request->amount." to ".$customer->first_name." ".$customer->last_name." (".$request->description.")";
                $transaction->trans_date                = date("Y-m-d G:i:s");
                $transaction->trans_recorder            = Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name;
                $transaction->save();
                
                /*--- log activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) use ($request) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Customer Wallet '.$request->type;
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." recorded a ".$request->type." of GH¢ ".$request->amount." to customer, ".$customer->first_name." ".$customer->last_name);

                /*--- Save customer account balance  ---*/
                $customer->save();

                /*--- Save company account balance  ---*/
                $count->save();

                /*--- Send sms  ---*/
                send_sms($customer->phone, "Hi ".$customer->first_name.", you have been debited GHS ".$request->amount.". Your new wallet balance is GHS ".$newCustomerBalance);

                return response()->json([
                    "type" => "success",
                    "message" => "GH¢ ".$request->amount." ".$request->type." successful",
                    "updated" => Customer::where('id', '=', $request->id)->first()->updated_at,
                    "milkshake" => $newCustomerMilkshake
                ]);
                break;
            
            default:
                # code...
                break;
        }
        $errors[0] = $request->pay_type." failed. Please try again";
        return response()->json([
            "type" => "error",
            "message" => $errors,
            "updated" => Customer::where('id', '=', $request->id)->first()->updated_at
        ]);
    }
}
