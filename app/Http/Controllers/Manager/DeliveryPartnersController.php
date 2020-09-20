<?php

namespace App\Http\Controllers\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Activitylog\Contracts\Activity;
use Illuminate\Support\Facades\Validator;
use Mail;
use App\Mail\Alert;
use App\AccountTransaction;
use App\Count;
use App\DeliveryPartner;
use App\Manager;

use Auth;

class DeliveryPartnersController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:manager');
    }

    
    public function getDeliveryPartnerCount(Request $request){
        $count['partners'] = count(DeliveryPartner::get());
        $count['balance'] = DeliveryPartner::sum('balance');
        return response()->json([
            'count' => $count
        ]);
    }

    public function getDeliveryPartnerRecords(Request $request){
        $count['partners'] = count(DeliveryPartner::get());
        $count['balance'] = DeliveryPartner::sum('balance');
        return response()->json([
            'count' => $count,
            'records' => DeliveryPartner::get()
        ]);
    }

    public function getPartnerCount(Request $request){
        $count['updated'] = DeliveryPartner::where('id', '=', $request->id)->first()->updated_at;
        $count['transactions'] = AccountTransaction::where([
            ['trans_credit_account_type', '=', '9'],
            ['trans_credit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_credit_account_type', '=', '10'],
                ['trans_credit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_debit_account_type', '=', '9'],
                ['trans_debit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_debit_account_type', '=', '10'],
                ['trans_debit_account', '=', $request->id]
        ])
        ->get()->count();

        return response()->json([
            'count' => $count
        ]);
    }

    public function getPartnerRecords(Request $request){
        $count['updated'] = DeliveryPartner::where('id', '=', $request->id)->first()->updated_at;
        $count['transactions'] = AccountTransaction::where([
            ['trans_credit_account_type', '=', '9'],
            ['trans_credit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_credit_account_type', '=', '10'],
                ['trans_credit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_debit_account_type', '=', '9'],
                ['trans_debit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_debit_account_type', '=', '10'],
                ['trans_debit_account', '=', $request->id]
        ])
        ->get()->count();

        return response()->json([
            'count' => $count,
            'transactions' => AccountTransaction::where([
                ['trans_credit_account_type', '=', '9'],
                ['trans_credit_account', '=', $request->id]
            ])->orWhere(
                [
                    ['trans_credit_account_type', '=', '10'],
                    ['trans_credit_account', '=', $request->id]
            ])->orWhere(
                [
                    ['trans_debit_account_type', '=', '9'],
                    ['trans_debit_account', '=', $request->id]
            ])->orWhere(
                [
                    ['trans_debit_account_type', '=', '10'],
                    ['trans_debit_account', '=', $request->id]
            ])
            ->get(),
            'records' => DeliveryPartner::where('id', '=', $request->id)->first()
        ]);
    }

    public function updatePartnerRecord(Request $request){
        $partner = DeliveryPartner::where('id', $request->id)->first();
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'dp_company' => 'required',
            'email' => 'required|email',
            'payment_details' => 'required'
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

        $partner->first_name = $request->first_name;
        $partner->last_name = $request->last_name;
        $partner->dp_company = $request->dp_company;
        $partner->email = $request->email;
        $partner->payment_details = $request->payment_details;

        /*--- log activity ---*/
        activity()
        ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
        ->tap(function(Activity $activity) {
            $activity->subject_type = 'System';
            $activity->subject_id = '0';
            $activity->log_name = 'Delivery Partner Details Updated';
        })
        ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." updated the details of delivery partner, ".$partner->first_name." ".$partner->last_name);

        $partner->save();

        /* get new count */
        $count['updated'] = DeliveryPartner::where('id', '=', $request->id)->first()->updated_at;
        $count['transactions'] = AccountTransaction::where([
            ['trans_credit_account_type', '=', '9'],
            ['trans_credit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_credit_account_type', '=', '10'],
                ['trans_credit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_debit_account_type', '=', '9'],
                ['trans_debit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_debit_account_type', '=', '10'],
                ['trans_debit_account', '=', $request->id]
        ])
        ->get()->count();
                

        return response()->json([
            "type" => "success",
            "message" => $partner->first_name."'s details updated.",
            "count" => $count
        ]);
    }
    
    public function recordPartnerPayment(Request $request){
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

        $partner = DeliveryPartner::where('id', $request->id)->first();
        $count = Count::first();

        /*--- Deduct paid amount from the current partner balance  ---*/
        $partner->balance = round($partner->balance - $request->amount, 2);
        
        /*--- Add paid amount to company balance  ---*/
        $count->account = round($count->account - $request->amount, 2);

        /*--- Record Transaction  ---*/
        $transaction = new AccountTransaction;
        $transaction->trans_type                = "Delivery Partner Pay-Out ";
        $transaction->trans_amount              = $request->amount;
        $transaction->trans_credit_account_type = 1;
        $transaction->trans_credit_account      = "INT-SC001";
        $transaction->trans_debit_account_type  = 10;
        $transaction->trans_debit_account       = $partner->id;
        $transaction->trans_description         = "Delivery Partner Pay-Out of GH¢ ".$request->amount." to ".$partner->first_name." ".$partner->last_name;
        $transaction->trans_date                = date("Y-m-d G:i:s");
        $transaction->trans_recorder            = Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name;
        $transaction->save();
        
        /*--- log activity ---*/
        activity()
        ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
        ->tap(function(Activity $activity) use ($request) {
            $activity->subject_type = 'System';
            $activity->subject_id = '0';
            $activity->log_name = 'Delivery Partner Pay-Out';
        })
        ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." recorded a ".$request->type." of GH¢ ".$request->amount." to partner, ".$partner->first_name." ".$partner->last_name);

        /*--- Save partner account balance  ---*/
        $partner->save();

        /*--- Save company account balance  ---*/
        $count->save();

        /*--- Send email  ---*/
        $data = array(
            'subject' => 'Partner Pay-Out - Solushop Ghana',
            'name' => $partner->first_name,
            'message' => "Hi ".ucwords(strtolower($partner->first_name)).", a Pay-Out of GHS ".$request->amount." has been recorded to you. Your new balance is GHS ".$partner->balance
        );

        Mail::to(strtolower($partner->email), ucwords(strtolower($partner->first_name)))
            ->queue(new Alert($data));

        /* get new counts */
        $count['updated'] = DeliveryPartner::where('id', '=', $request->id)->first()->updated_at;
        $count['transactions'] = AccountTransaction::where([
            ['trans_credit_account_type', '=', '9'],
            ['trans_credit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_credit_account_type', '=', '10'],
                ['trans_credit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_debit_account_type', '=', '9'],
                ['trans_debit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_debit_account_type', '=', '10'],
                ['trans_debit_account', '=', $request->id]
        ])
        ->get()->count();

        return response()->json([
            "type" => "success",
            "message" => "GH¢ ".$request->amount." pay out successful",
            'count' => $count,
            'transactions' => AccountTransaction::where([
                ['trans_credit_account_type', '=', '9'],
                ['trans_credit_account', '=', $request->id]
            ])->orWhere(
                [
                    ['trans_credit_account_type', '=', '10'],
                    ['trans_credit_account', '=', $request->id]
            ])->orWhere(
                [
                    ['trans_debit_account_type', '=', '9'],
                    ['trans_debit_account', '=', $request->id]
            ])->orWhere(
                [
                    ['trans_debit_account_type', '=', '10'],
                    ['trans_debit_account', '=', $request->id]
            ])
            ->get(),
            "records" => DeliveryPartner::where('id', $request->id)->first()
            
        ]);

        
    }

    public function addDeliveryPartner(Request $request){
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'dp_company' => 'required',
            'email' => 'required|email',
            'payment_details' => 'required'
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
        if (DeliveryPartner::where('email', $request->email)->first()) {
            $errors[0] =  "Email already associated with a Partner";
            return response()->json([
                "type" => "error",
                "message" => $errors
            ]);
        }

        /*--- store partner data ---*/
        $partner = new DeliveryPartner;
        $partner->first_name        = ucwords(strtolower($request->first_name));
        $partner->last_name         = ucwords(strtolower($request->last_name));
        $partner->email             = $request->email;
        $partner->dp_company        = $request->dp_company;
        $partner->payment_details   = $request->payment_details;
        $partner->passcode          = $passcode = rand(1000, 9999);
        $partner->password          = bcrypt($passcode);
        $partner->payment_details   = ucwords($request->payment_details);
        $partner->balance           = 0;
        $partner->save();


         /*--- log activity ---*/
         activity()
         ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
         ->tap(function(Activity $activity) {
             $activity->subject_type = 'System';
             $activity->subject_id = '0';
             $activity->log_name = 'Delivery Partner Registration';
         })
         ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." added ".ucwords(strtolower($request->first_name))." ".ucwords(strtolower($request->last_name))." as a delivery partner");

         return response()->json([
            "type" => "success",
            "message" => $partner->first_name."'s details added.",
        ]);

    }
}
