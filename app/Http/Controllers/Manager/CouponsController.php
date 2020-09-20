<?php

namespace App\Http\Controllers\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Activitylog\Contracts\Activity;
use Illuminate\Support\Facades\Validator;

use Auth;

use App\Coupon;
use App\Count;
use App\Manager;

class CouponsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:manager');
    }

    
    public function getCouponsCount(Request $request){
        return response()->json([
            'na' => count(Coupon::where('coupon_state', 1)->get()),
            'available' => count(Coupon::where('coupon_state', 2)->get()),
            'redeemed' => count(Coupon::where('coupon_state', 3)->get()),
            'expired' => count(Coupon::where('coupon_state', 4)->get())
        ]);
    }

    public function getCouponsRecords(Request $request){
        return response()->json([
            'na' => count(Coupon::where('coupon_state', 1)->get()),
            'available' => count(Coupon::where('coupon_state', 2)->get()),
            'redeemed' => count(Coupon::where('coupon_state', 3)->get()),
            'expired' => count(Coupon::where('coupon_state', 4)->get()),
            'records' => Coupon::with('state')->get()
        ]);
    }

    public function generateCoupon(Request $request){
        $validator = Validator::make($request->all(), [
            'expiry' => 'required|date',
            'value' => 'required|numeric|gt:0'
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


        /*--- Generate Coupon ---*/
        $permitted_chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        
        //part one
        $coupon_id   = 'S'.
        substr(str_shuffle($permitted_chars), 7, 2).
        date('d').
        "-".
        substr(str_shuffle($permitted_chars), 7, 2).
        date('m').
        'W'.
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


        $coupon = new Coupon;
        $coupon->coupon_code = $coupon_id;
        $coupon->coupon_value = $request->value;
        $coupon->coupon_owner = "SOLUSHOP";
        $coupon->coupon_state = 2;
        $coupon->coupon_expiry_date = $request->expiry;
        $coupon->save();

        $count->save();

        /*--- log activity ---*/
        activity()
        ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
        ->tap(function(Activity $activity) {
            $activity->subject_type = 'System';
            $activity->subject_id = '0';
            $activity->log_name = 'Coupon Generated';
        })
        ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." generated a coupon ".$coupon_id." worth GH¢".$request->value);

        return response()->json([
            "type" => "success",
            "message" => "GH¢ ".$request->value." coupon generated."
        ]);
    }
}
