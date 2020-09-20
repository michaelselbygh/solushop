<?php

namespace App\Http\Controllers\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Activitylog\Contracts\Activity;
use Illuminate\Support\Facades\Validator;

use App\AccountTransaction;
use App\Count;
use App\Vendor;
use App\DeliveryPartner;
use App\SalesAssociate;
use App\Manager;

use Auth;

class AccountsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:manager');
    }
    
    public function getAccountsCount(Request $request){
        $balances["total"] = Count::sum('account');
        $balances["vendors"] = Vendor::sum('balance');
        $balances["sales_associates"] = SalesAssociate::sum('balance');
        $balances["delivery_partners"] = DeliveryPartner::sum('balance') ;
        $balances["available"] =  $balances["total"] - $balances["vendors"] - $balances["sales_associates"];

        return response()->json([
            'count' => count(AccountTransaction::get()),
            'balances' => $balances
        ]);
    }

    public function getAccountsRecords(Request $request){
        $balances["total"] = Count::sum('account');
        $balances["vendors"] = Vendor::sum('balance');
        $balances["sales_associates"] = SalesAssociate::sum('balance');
        $balances["delivery_partners"] = DeliveryPartner::sum('balance') ;
        $balances["available"] =  $balances["total"] - $balances["vendors"] - $balances["sales_associates"] - $balances["delivery_partners"];

        return response()->json([
            'count' => count(AccountTransaction::get()),
            'balances' => $balances,
            'records' => AccountTransaction::get()
        ]);
    }

    public function recordAccountsPayment(Request $request){
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

        $count = Count::first();

        switch ($request->type) {
            case 'Pay-In':
                /*--- Deduct paid amount from company balance  ---*/
                $count->account = round($count->account + $request->amount, 2);

                /*--- Record Transaction  ---*/
                $transaction = new AccountTransaction;
                $transaction->trans_type                = "Accounts ".$request->type;
                $transaction->trans_amount              = $request->amount;
                $transaction->trans_credit_account_type = 2;
                $transaction->trans_credit_account      = "EXT";
                $transaction->trans_debit_account_type  = 1;
                $transaction->trans_debit_account       = "INT-SC001";
                $transaction->trans_description         = "Accounts ".$request->type." of GH¢ ".$request->amount." - ".$request->description;
                $transaction->trans_date                = date("Y-m-d G:i:s");
                $transaction->trans_recorder            = Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name;
                $transaction->save();
                
                /*--- log activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) use ($request) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Accounts '.$request->type;
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." recorded a ".$request->type." of GH¢ ".$request->amount);


                /*--- Save company account balance  ---*/
                $count->save();
                
                return response()->json([
                    "type" => "success",
                    "message" => "GH¢ ".$request->amount." ".$request->type." successful",
                    "balance" => $count->account,
                ]);
                break;

            case 'Pay-Out':
                
                /*--- Add paid amount to company balance  ---*/
                $count->account = round($count->account - $request->amount, 2);

                /*--- Record Transaction  ---*/
                $transaction = new AccountTransaction;
                $transaction->trans_type                = "Accounts ".$request->type;
                $transaction->trans_amount              = $request->amount;
                $transaction->trans_credit_account_type = 1;
                $transaction->trans_credit_account      = "INT-SC001";
                $transaction->trans_debit_account_type  = 2;
                $transaction->trans_debit_account       = "EXT";
                $transaction->trans_description         = "Accounts ".$request->type." of GH¢ ".$request->amount." - ".$request->description;
                $transaction->trans_date                = date("Y-m-d G:i:s");
                $transaction->trans_recorder            = Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name;
                $transaction->save();
                
                /*--- log activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) use ($request) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Accounts '.$request->type;
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." recorded a ".$request->type." of GH¢ ".$request->amount);

                /*--- Save company account balance  ---*/
                $count->save();


                return response()->json([
                    "type" => "success",
                    "message" => "GH¢ ".$request->amount." ".$request->type." successful",
                    "balance" => $count->account
                ]);
                break;
            
            default:
                # code...
                break;
        }
        $errors[0] =  $request->pay_type." failed. Please try again";
        return response()->json([
            "type" => "error",
            "message" => $errors
        ]);
    }
}
