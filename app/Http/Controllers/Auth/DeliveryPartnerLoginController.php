<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Activitylog\Contracts\Activity; 
use Auth;

use App\DeliveryPartner;

class DeliveryPartnerLoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest:delivery-partner')->except('logout');
    }

    public function showLoginForm()
    {
        return view('portal.main.login')
        ->with('entity', 'Delivery Partner');
    }

    public function login(Request $request)
    {
        //attempt to log user in
        if(Auth::guard('delivery-partner')->attempt(['email' => $request->username, 'password' => $request->password], $request->remember)){
            
            //if successful, then redirect to intended location
            /*--- log activity ---*/
            activity()
            ->causedBy(DeliveryPartner::where('id', Auth::guard('delivery-partner')->user()->id)->get()->first())
            ->tap(function(Activity $activity) {
                $activity->subject_type = 'System';
                $activity->subject_id = '0';
                $activity->log_name = 'Delivery PArtner Login';
            })
            ->log(Auth::guard('delivery-partner')->user()->first_name.' logged in as a Delivery Partner');
            
            return response()->json([
                'valid' => true,
                'message' => 'Welcome back, '.Auth::guard('delivery-partner')->user()->first_name
            ]);
        }

        /*--- log activity ---*/
        activity()
        ->tap(function(Activity $activity) {
           $activity->causer_type = 'App\DeliveryPartner';
           $activity->causer_id = '-';
           $activity->subject_type = 'System';
           $activity->subject_id = '0';
           $activity->log_name = 'Delivery Partner Login Attempt';
        })
        ->log($request->username.' attempted to log in as a Delivery Partner');
        
        //if unsuccessful then redirect back to login with the form data
        return response()->json([
            'valid' => false,
            'message' => 'Invalid login credentials.',
        ]);
    }

    public function logout(){
        /*--- log activity ---*/
        activity()
        ->causedBy(DeliveryPartner::where('id', Auth::guard('delivery-partner')->user()->id)->get()->first())
        ->tap(function(Activity $activity) {
            $activity->subject_type = 'System';
            $activity->subject_id = '0';
            $activity->log_name = 'Delivery Partner Logout';
        })
        ->log(Auth::guard('delivery-partner')->user()->email.' logged out as a delivery partner');

        Auth::guard('delivery-partner')->logout();
        return redirect(route('delivery-partner.login'));
    }
}
