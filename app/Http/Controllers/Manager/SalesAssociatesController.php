<?php

namespace App\Http\Controllers\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Activitylog\Contracts\Activity;
use Illuminate\Support\Facades\Validator;

use Auth;

use App\AccountTransaction;
use App\Coupon;
use App\Count;
use App\Manager;
use App\Order;
use App\SalesAssociate;
use App\SMS;

use Mail;
use App\Mail\Alert;

class SalesAssociatesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:manager');
    }

    
    public function getSalesAssociateCount(Request $request){
        $count['associates'] = count(SalesAssociate::get());
        $count['balance'] = SalesAssociate::sum('balance');
        return response()->json([
            'count' => $count
        ]);
    }

    public function getSalesAssociateRecords(Request $request){
        $count['associates'] = count(SalesAssociate::get());
        $count['balance'] = SalesAssociate::sum('balance');
        return response()->json([
            'count' => $count,
            'records' => SalesAssociate::get()
        ]);
    }

    public function addSalesAssociate(Request $request){
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'phone' => 'required|digits:10',
            'mode_of_payment' => 'required',
            'payment_details' => 'required',
            'address' => 'required', 
            'id_type' => 'required',
            'id_file' => 'required'
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

        //check for email existence in system
        if (SalesAssociate::where('email', $request->email)->first()) {
            $errors[0] =  "Email already associated with an Associate";
            return response()->json([
                "type" => "error",
                "message" => $errors
            ]);
        }

         //check for phone existence in system
         if (SalesAssociate::where('phone', $request->phone)->first()) {
            $errors[0] =  "Phone already associated with an Associate";
            return response()->json([
                "type" => "error",
                "message" => $errors
            ]);
        }

        /*--- generate coupon ---*/
        //Random numeric character permitted characters
        $permitted_chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        
        //part one
        $coupon_id   = 'S'.
        substr(str_shuffle($permitted_chars), 7, 2).
        date('d').
        "-".
        substr(str_shuffle($permitted_chars), 7, 2).
        date('m').
        'S'.
        "-".
        substr(str_shuffle($permitted_chars), 7, 1).
        substr(date('Y'), 0, 2).
        substr(str_shuffle($permitted_chars), 7, 2).
        "-".
        substr(str_shuffle($permitted_chars), 7, 1).
        substr(date('Y'), 2, 2);

        //part two
        $count = Count::first();
        $coupon_id .= substr("000".$count->coupon_count, strlen(strval($count->coupon_count)));

        /*--- save id file ---*/
        $identification_file = $request->file('id_file');
        $identification_file_ext = $identification_file->getClientOriginalExtension();
        $file = $identification_file;
        // $file->move("/var/www/vhosts/solushop.com.gh/httpdocs/portal/s-team-member-id/", $coupon_id.".".$identification_file_ext);
        $file->move("C:/wamp/www/solushop-laravel/public/portal/s-team-member-id", $coupon_id.".".$identification_file_ext);

        /*--- save coupon ---*/
        $coupon = new Coupon;
        $coupon->coupon_code = $coupon_id;
        $coupon->coupon_value = 0.01;
        $coupon->coupon_owner = $request->email;
        $coupon->coupon_state = 1;
        $coupon->coupon_expiry_date = "NA";
        $coupon->save();


        /*--- store associate data ---*/
        $associate = new SalesAssociate;
        $associate->first_name        = ucwords(strtolower($request->first_name));
        $associate->last_name         = ucwords(strtolower($request->last_name));
        $associate->phone             = $request->phone;
        $associate->email             = $request->email;
        $associate->passcode          = $passcode = rand(100000, 999999);
        $associate->password          = bcrypt($passcode);
        $associate->address           = ucwords($request->address);
        $associate->badge             = 1;
        $associate->id_type           = $request->id_type;
        $associate->id_file           = $coupon_id.".".$identification_file_ext;
        $associate->mode_of_payment   = $request->mode_of_payment;
        $associate->payment_details   = ucwords($request->payment_details);
        $associate->balance           = 0;
        $associate->save();


        /*--- update counts ---*/
        $count->coupon_count++;
        $count->save();
        
        /*--- notify associate ---*/
        send_sms($request->phone, "Hi ".ucwords(strtolower($request->first_name)).", you have been accepted as a Sales Associate on Solushop.\n\nEmail: ".$request->email."\nPassword : $passcode\nLogin here : https://www.solushop.com.gh/portal/sales-associate");

        $data = array(
            'subject' => 'Sales Associate Confirmation - Solushop Ghana',
            'name' => ucwords(strtolower($request->first_name)),
            'message' => "You have been accepted as a Sales Associate on Solushop.<br><br>Email: ".$request->email."<br>Password : $passcode<br>Login here : <a href='https://www.solushop.com.gh/portal/sales-associate'> Sales Associate Portal </a>"
        );

        Mail::to($request->email, ucwords(strtolower($request->first_name)))
            ->queue(new Alert($data));


        /*--- log activity ---*/
        activity()
        ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
        ->tap(function(Activity $activity) {
            $activity->subject_type = 'System';
            $activity->subject_id = '0';
            $activity->log_name = 'Sales Associate Registration';
        })
        ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." added ".ucwords(strtolower($request->first_name))." ".ucwords(strtolower($request->last_name))." as a sales associate");

        return response()->json([
            "type" => "success",
            "message" => $associate->first_name."'s details added.",
        ]);

    }

    public function getAssociateCount(Request $request){
        $count['updated'] = SalesAssociate::where('id', '=', $request->id)->first()->updated_at;
        $count['transactions'] = AccountTransaction::where([
            ['trans_credit_account_type', '=', '7'],
            ['trans_credit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_credit_account_type', '=', '8'],
                ['trans_credit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_debit_account_type', '=', '7'],
                ['trans_debit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_debit_account_type', '=', '8'],
                ['trans_debit_account', '=', $request->id]
        ])
        ->get()->count();

        return response()->json([
            'count' => $count
        ]);
    }

    public function getAssociateRecords(Request $request){
        $associate = SalesAssociate::where('id', '=', $request->id)->with('badge_info')->first();
        $associate["sales"] = Order::
        whereIn('order_state', [3, 4, 5, 6])
        ->where('order_scoupon', substr($associate["id_file"], 0, 24))
        ->sum('order_subtotal');

        $count['updated'] = SalesAssociate::where('id', '=', $request->id)->first()->updated_at;
        $count['transactions'] = AccountTransaction::where([
            ['trans_credit_account_type', '=', '7'],
            ['trans_credit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_credit_account_type', '=', '8'],
                ['trans_credit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_debit_account_type', '=', '7'],
                ['trans_debit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_debit_account_type', '=', '8'],
                ['trans_debit_account', '=', $request->id]
        ])
        ->get()->count();

        return response()->json([
            'count' => $count,
            'transactions' => AccountTransaction::where([
                ['trans_credit_account_type', '=', '7'],
                ['trans_credit_account', '=', $request->id]
            ])->orWhere(
                [
                    ['trans_credit_account_type', '=', '8'],
                    ['trans_credit_account', '=', $request->id]
            ])->orWhere(
                [
                    ['trans_debit_account_type', '=', '7'],
                    ['trans_debit_account', '=', $request->id]
            ])->orWhere(
                [
                    ['trans_debit_account_type', '=', '8'],
                    ['trans_debit_account', '=', $request->id]
            ])
            ->get(),
            'records' => $associate
        ]);
    }

    public function updateAssociateRecord(Request $request){
        $associate = SalesAssociate::where('id', $request->id)->first();
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'phone' => 'required|digits:12',
            'mode_of_payment' => 'required',
            'payment_details' => 'required',
            'address' => 'required',
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

        $associate->first_name = $request->first_name;
        $associate->last_name = $request->last_name;
        $associate->email = $request->email;
        $associate->phone = $request->phone;
        $associate->mode_of_payment = $request->mode_of_payment;
        $associate->payment_details = $request->payment_details;
        $associate->address = $request->address;

        /*--- log activity ---*/
        activity()
        ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
        ->tap(function(Activity $activity) {
            $activity->subject_type = 'System';
            $activity->subject_id = '0';
            $activity->log_name = 'Sales Associate Details Updated';
        })
        ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." updated the details of Sales Associate, ".$associate->first_name." ".$associate->last_name);

        $associate->save();

        /* get new count */
        $count['updated'] = SalesAssociate::where('id', '=', $request->id)->first()->updated_at;
        $count['transactions'] = AccountTransaction::where([
            ['trans_credit_account_type', '=', '7'],
            ['trans_credit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_credit_account_type', '=', '8'],
                ['trans_credit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_debit_account_type', '=', '7'],
                ['trans_debit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_debit_account_type', '=', '8'],
                ['trans_debit_account', '=', $request->id]
        ])
        ->get()->count();
                

        return response()->json([
            "type" => "success",
            "message" => $associate->first_name."'s details updated.",
            "count" => $count
        ]);
    }
    
    public function recordAssociatePayment(Request $request){
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01'
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

        $associate = SalesAssociate::where('id', $request->id)->first();
        $count = Count::first();

        /*--- Deduct paid amount from the current partner balance  ---*/
        $associate->balance = round($associate->balance - $request->amount, 2);
        
        /*--- Add paid amount to company balance  ---*/
        $count->account = round($count->account - $request->amount, 2);

        /*--- Record Transaction  ---*/
        $transaction = new AccountTransaction;
        $transaction->trans_type                = "Sales Associate Pay-Out ";
        $transaction->trans_amount              = $request->amount;
        $transaction->trans_credit_account_type = 1;
        $transaction->trans_credit_account      = "INT-SC001";
        $transaction->trans_debit_account_type  = 8;
        $transaction->trans_debit_account       = $associate->id;
        $transaction->trans_description         = "Sales Associate Pay-Out of GH¢ ".$request->amount." to ".$associate->first_name." ".$associate->last_name;
        $transaction->trans_date                = date("Y-m-d G:i:s");
        $transaction->trans_recorder            = Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name;
        $transaction->save();
        
        /*--- log activity ---*/
        activity()
        ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
        ->tap(function(Activity $activity) use ($request) {
            $activity->subject_type = 'System';
            $activity->subject_id = '0';
            $activity->log_name = 'Sales Associate Pay-Out';
        })
        ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." recorded a Pay-Out of GH¢ ".$request->amount." to partner, ".$associate->first_name." ".$associate->last_name);

        /*--- Save partner account balance  ---*/
        $associate->save();

        /*--- Save company account balance  ---*/
        $count->save();

        /*--- notify associate ---*/
        send_sms($associate->phone, "Hi ".ucwords(strtolower($associate->first_name)).", a Pay-Out of GHS ".$request->amount." has been recorded to you. Your new balance is GHS ".$associate->balance);

        $data = array(
            'subject' => 'Associate Pay-Out - Solushop Ghana',
            'name' => $associate->first_name,
            'message' => "Hi ".ucwords(strtolower($associate->first_name)).", a Pay-Out of GHS ".$request->amount." has been recorded to you. Your new balance is GHS ".$associate->balance
        );

        Mail::to(strtolower($associate->email), ucwords(strtolower($associate->name)))
            ->queue(new Alert($data));

        /* get new counts */
        $count['updated'] = SalesAssociate::where('id', '=', $request->id)->first()->updated_at;
        $count['transactions'] = AccountTransaction::where([
            ['trans_credit_account_type', '=', '7'],
            ['trans_credit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_credit_account_type', '=', '8'],
                ['trans_credit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_debit_account_type', '=', '7'],
                ['trans_debit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_debit_account_type', '=', '8'],
                ['trans_debit_account', '=', $request->id]
        ])
        ->get()->count();

        $associate = SalesAssociate::where('id', '=', $request->id)->with('badge_info')->first();
        $associate["sales"] = Order::
        whereIn('order_state', [3, 4, 5, 6])
        ->where('order_scoupon', substr($associate["id_file"], 0, 24))
        ->sum('order_subtotal');

        return response()->json([
            "type" => "success",
            "message" => "GH¢ ".$request->amount." pay out successful",
            'count' => $count,
            'transactions' => AccountTransaction::where([
                ['trans_credit_account_type', '=', '7'],
                ['trans_credit_account', '=', $request->id]
            ])->orWhere(
                [
                    ['trans_credit_account_type', '=', '8'],
                    ['trans_credit_account', '=', $request->id]
            ])->orWhere(
                [
                    ['trans_debit_account_type', '=', '7'],
                    ['trans_debit_account', '=', $request->id]
            ])->orWhere(
                [
                    ['trans_debit_account_type', '=', '8'],
                    ['trans_debit_account', '=', $request->id]
            ])
            ->get(),
            'records' => $associate
            
        ]);

        
    }
}
