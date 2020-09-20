<?php

namespace App\Http\Controllers\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Activitylog\Contracts\Activity;
use Illuminate\Support\Facades\Validator;

use Auth;

use App\AccountTransaction;
use App\Count;
use App\Manager;
use App\SMS;
use App\Vendor;

use Image;
use Mail;
use App\Mail\Alert;

class VendorsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:manager');
    }

    
    public function getVendorsCount(Request $request){
        $count['vendors'] = count(Vendor::get());
        $count['balance'] = Vendor::sum('balance');
        return response()->json([
            'count' => $count
        ]);
    }

    public function getVendorsRecords(Request $request){
        $count['vendors'] = count(Vendor::get());
        $count['balance'] = Vendor::sum('balance');
        return response()->json([
            'count' => $count,
            'records' => Vendor::with('subscription.package')->get()
        ]);
    }

    public function addVendor(Request $request){
        /*--- Validate form data  ---*/
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required|digits:10',
            'alt_phone' => 'required|digits:10',
            'mode_of_payment' => 'required',
            'payment_details' => 'required',
            'address' => 'required', 
            'header' => 'required|image|dimensions:width=1305,height=360'
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
        if (Vendor::where('email', $request->email)->first()) {
            $errors[0] = "Email already associated with a Vendor";
            return response()->json([
                "type" => "error",
                "message" => $errors,
            ]);
        }

         //check for phone existence in system
         if (Vendor::where('phone', $request->phone)->first()) {
            $errors[0] = "Phone already associated with a Vendor";
            return response()->json([
                "type" => "error",
                "message" => $errors,
            ]);
        }

        /*--- Vendor ID ---*/
        $count = Count::first();
        $vendor_id = date("dmY").substr("00000".$count->vendor_count, strlen(strval($count->vendor_count)));

        /*--- save header file ---*/
        $header_file = $request->file('header');
        if ($header_file->getClientOriginalExtension() != "jpg") {
            $errors[0] = "Header must be of type .jpg";
            return response()->json([
                "type" => "error",
                "message" => $errors,
            ]);
        }

        $img = Image::make($header_file);
        $img->save('app/assets/img/vendor-banner/'.$vendor_id.'.jpg');

        /*--- store vendor data ---*/
        $vendor = new Vendor;
        $vendor->id                = $vendor_id;
        $vendor->name              = ucwords(strtolower($request->name));
        $vendor->username          = str_slug($vendor->name , '-');
        $vendor->phone             = "233".substr($request->phone, 1);
        $vendor->alt_phone         = "233".substr($request->alt_phone, 1);
        $vendor->email             = strtolower($request->email);
        $vendor->passcode          = $passcode = rand(100000, 999999);
        $vendor->password          = bcrypt($passcode);
        $vendor->address           = ucwords($request->pick_up_address);
        $vendor->mode_of_payment   = $request->mode_of_payment;
        $vendor->payment_details   = ucwords($request->payment_details);
        $vendor->balance           = 0;
        $vendor->save();


        /*--- update counts ---*/
        $count->vendor_count++;
        $count->save();
        
        /*--- notify vendor ---*/
        send_sms("233".substr($request->phone, 1), "Hi ".ucwords(strtolower($request->name)).", you have been accepted as a Vendor on Solushop. Login to begin your journey with us.\n\nUsername: ".str_slug($request->name, '-')."\nPassword : $passcode\nLogin here : https://www.solushop.com.gh/portal/vendor");

        $data = array(
            'subject' => 'Confirmed Vendor - Solushop Ghana',
            'name' => ucwords(strtolower($request->name)),
            'message' => "You have been accepted as a Vendor on Solushop. Login to begin your journey with us.<br><br>Username: ".str_slug($request->name, '-')."<br>Password : $passcode<br>Login here : <a href='https://www.solushop.com.gh/portal/vendor'>Solushop Vendor Portal</a>"
        );

        Mail::to(strtolower($request->email), ucwords(strtolower($request->name)))
            ->queue(new Alert($data));


         /*--- log activity ---*/
         activity()
         ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
         ->tap(function(Activity $activity) {
             $activity->subject_type = 'System';
             $activity->subject_id = '0';
             $activity->log_name = 'Vendor Registration';
         })
         ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." added ".ucwords(strtolower($request->name))." as a vendor");
         

        return response()->json([
            "type" => "success",
            "message" => $request->name."'s details added.",
        ]);
    }

    public function getVendorCount(Request $request){
        $count['updated'] = Vendor::where('id', '=', $request->id)->first()->updated_at;
        $count['transactions'] = AccountTransaction::where([
            ['trans_credit_account_type', '=', '3'],
            ['trans_credit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_credit_account_type', '=', '4'],
                ['trans_credit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_debit_account_type', '=', '3'],
                ['trans_debit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_debit_account_type', '=', '4'],
                ['trans_debit_account', '=', $request->id]
        ])
        ->get()->count();

        return response()->json([
            'count' => $count
        ]);
    }

    public function getVendorRecords(Request $request){
        $vendor = Vendor::where('id', '=', $request->id)->first();

        $count['updated'] = Vendor::where('id', '=', $request->id)->first()->updated_at;
        $count['transactions'] = AccountTransaction::where([
            ['trans_credit_account_type', '=', '3'],
            ['trans_credit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_credit_account_type', '=', '4'],
                ['trans_credit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_debit_account_type', '=', '3'],
                ['trans_debit_account', '=', $request->id]
        ])->orWhere(
            [
                ['trans_debit_account_type', '=', '4'],
                ['trans_debit_account', '=', $request->id]
        ])
        ->get()->count();

        return response()->json([
            'count' => $count,
            'transactions' => AccountTransaction::where([
                ['trans_credit_account_type', '=', '3'],
                ['trans_credit_account', '=', $request->id]
            ])->orWhere(
                [
                    ['trans_credit_account_type', '=', '4'],
                    ['trans_credit_account', '=', $request->id]
            ])->orWhere(
                [
                    ['trans_debit_account_type', '=', '3'],
                    ['trans_debit_account', '=', $request->id]
            ])->orWhere(
                [
                    ['trans_debit_account_type', '=', '4'],
                    ['trans_debit_account', '=', $request->id]
            ])
            ->get(),
            'records' => $vendor
        ]);
    }

    public function updateVendorRecord(Request $request){
        $vendor = Vendor::where('id', $request->id)->first();
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required|digits:12',
            'alt_phone' => 'required|digits:12',
            'mode_of_payment' => 'required',
            'payment_details' => 'required',
            'address' => 'required',
            'header' => 'sometimes|image|dimensions:width=1305,height=360'
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

        //check for username existence in system
        if (Vendor::where([
            ['username', "=", str_slug($request->name, 2)],
            ['id', '<>', $vendor->id]
        ])->first()) {
            $errors[0] =  "Name already associated with a Vendor";
            return response()->json([
                "type" => "error",
                "message" => $errors
            ]);
        }

        //check for numbers being the same
        if ($request->phone == $request->alt_phone) {
            $errors[0] = "Main number cannot be the same as the alternate number";
            return response()->json([
                "type" => "error",
                "message" => $errors
            ]);
        }

        //check and update header if it is set
        if ($request->hasFile('header')) {
            /*--- save header file ---*/
            $header_file = $request->file('header');
            if ($header_file->getClientOriginalExtension() != "jpg") {
                $errors[0] = "Header image must be of type: .jpg";
                return response()->json([
                    "type" => "error",
                    "message" => $errors
                ]);
            }

            $img = Image::make($header_file);
            $img->save('app/assets/img/vendor-banner/'.$vendor->id.'.jpg');
        }

        $vendor->name = $request->name;
        $vendor->username = str_slug($request->name , '-');
        $vendor->email = $request->email;
        $vendor->phone = $request->phone;
        $vendor->alt_phone = $request->alt_phone;
        $vendor->mode_of_payment = $request->mode_of_payment;
        $vendor->payment_details = $request->payment_details;
        $vendor->address = $request->address;

        /*--- log activity ---*/
        activity()
        ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
        ->tap(function(Activity $activity) {
            $activity->subject_type = 'System';
            $activity->subject_id = '0';
            $activity->log_name = 'Vendor Details Updated';
        })
        ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." updated the details of Vendor, ".$vendor->name);

        $vendor->save();

        /* get new count */
        $count['updated'] = Vendor::where('id', '=', $request->id)->first()->updated_at;
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
            "message" => $vendor->name."'s details updated.",
            "count" => $count,
            "records" => Vendor::where('id', '=', $request->id)->first()
        ]);
    }
    
    public function recordVendorPayment(Request $request){
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required'
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

        $vendor = Vendor::where('id', $request->id)->first();
        $count = Count::first();

        switch ($request->type) {
            case 'Pay-Out':
                /*--- Deduct paid amount from the current partner balance  ---*/
                $vendor->balance = round($vendor->balance - $request->amount, 2);
                
                /*--- Add paid amount to company balance  ---*/
                $count->account = round($count->account - $request->amount, 2);

                /*--- Record Transaction  ---*/
                $transaction = new AccountTransaction;
                $transaction->trans_type                = "Vendor ".$request->type;
                $transaction->trans_amount              = $request->amount;
                $transaction->trans_credit_account_type = 1;
                $transaction->trans_credit_account      = "INT-SC001";
                $transaction->trans_debit_account_type  = 4;
                $transaction->trans_debit_account       = $vendor->id;
                $transaction->trans_description         = "Vendor ".$request->type." of GH¢ ".$request->amount." to ".$vendor->name;
                $transaction->trans_date                = date("Y-m-d G:i:s");
                $transaction->trans_recorder            = Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name;
                $transaction->save();
                
                /*--- log activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) use ($request) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Vendor '.$request->type;
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." recorded a ".$request->type." of GH¢ ".$request->amount." to partner, ".$vendor->name);

                /*--- Save partner account balance  ---*/
                $vendor->save();

                /*--- Save company account balance  ---*/
                $count->save();

                /*--- notify vendor ---*/
                send_sms($vendor->phone, "Hi ".ucwords(strtolower($vendor->name)).", a ".$request->type." of GHS ".$request->amount." has been recorded to you. Your new balance is GHS ".$vendor->balance);

                $data = array(
                    'subject' => 'Vendor '.$request->type.' - Solushop Ghana',
                    'name' => $vendor->name,
                    'message' => "Hi ".ucwords(strtolower($vendor->name)).", a ".$request->type." of GHS ".$request->amount." has been recorded to you. Your new balance is GHS ".$vendor->balance
                );

                Mail::to(strtolower($vendor->email), ucwords(strtolower($vendor->name)))
                    ->queue(new Alert($data));

                /* get new counts */
                $count['updated'] = Vendor::where('id', '=', $request->id)->first()->updated_at;
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

                $vendor = Vendor::where('id', '=', $request->id)->first();

                return response()->json([
                    "type" => "success",
                    "message" => "GH¢ ".$request->amount." ".$request->type." successful",
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
                    'records' => $vendor
                    
                ]);
                break;

            case 'Penalty':
                /*--- Record transaction ---*/
                $transaction = new AccountTransaction;
                $transaction->trans_type                = "Vendor Penalty";
                $transaction->trans_amount              = $request->amount;
                $transaction->trans_credit_account_type = 3;
                $transaction->trans_credit_account      = $vendor->id;
                $transaction->trans_debit_account_type  = 1;
                $transaction->trans_debit_account       = "INT-SC001";
                $transaction->trans_description         = "Penalty of GH¢ ".$request->amount." to ".$vendor->name;
                $transaction->trans_date                = date("Y-m-d G:i:s");
                $transaction->trans_recorder            = Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name;
                $transaction->save();

                /*--- Update Vendor Balance ---*/
                $vendor->balance -= $request->amount;

                /*--- Notify vendor ---*/
                send_sms($vendor->phone,"Dear ".$vendor->name.", a penalty of GHS ".$request->amount." has been recorded to you. Your new balance is GHS ".$vendor->balance );

                $data = array(
                    'subject' => 'Vendor Penalty - Solushop Ghana',
                    'name' => $vendor->name,
                    'message' => "A penalty of GHS ".$request->amount." has been recorded to you. Your new balance is GHS ".$vendor->balance
                );

                Mail::to($vendor->email, $vendor->name)
                    ->queue(new Alert($data));

                /*--- log activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Vendor Penalty';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." recorded a penalty of GH¢ ".$request->amount." to vendor, ".$vendor->name);

                 /* get new counts */
                 $count['updated'] = Vendor::where('id', '=', $request->id)->first()->updated_at;
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
 
                 $vendor = Vendor::where('id', '=', $request->id)->first();
 
                 return response()->json([
                     "type" => "success",
                     "message" => "GH¢ ".$request->amount." ".$request->type." successful",
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
                     'records' => $vendor
                     
                 ]);

                break;
            
            default:
                # code...
                break;
        }        
    }
}
