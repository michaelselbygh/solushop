<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Activitylog\Contracts\Activity;

use Auth;
use App\Vendor;

class VendorLoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest:vendor')->except('logout');
    }

    public function showLoginForm()
    {
        return view('portal.main.login')
        ->with('entity', 'Vendor');
    }

    public function login(Request $request)
    {
        //attempt to log user in
        if(Auth::guard('vendor')->attempt(['username' => $request->username, 'password' => $request->password], $request->remember)){
            
            //if successful, then redirect to intended location
            /*--- log activity ---*/
            activity()
            ->causedBy(Vendor::where('id', Auth::guard('vendor')->user()->id)->get()->first())
            ->tap(function(Activity $activity) {
                $activity->subject_type = 'System';
                $activity->subject_id = '0';
                $activity->log_name = 'Vendor Login';
            })
            ->log(Auth::guard('vendor')->user()->name.' logged in as a vendor');
            
            return response()->json([
                'valid' => true,
                'message' => 'Welcome back, '.Auth::guard('vendor')->user()->name
            ]);
        }

        /*--- log activity ---*/
        activity()
        ->tap(function(Activity $activity) {
           $activity->causer_type = 'App\Vendor';
           $activity->causer_id = '-';
           $activity->subject_type = 'System';
           $activity->subject_id = '0';
           $activity->log_name = 'Vendor Login Attempt';
        })
        ->log($request->username.' attempted to log in as a vendor');
        
        //if unsuccessful then redirect back to login with the form data
        return response()->json([
            'valid' => false,
            'message' => 'Invalid login credentials.',
        ]);
    }

    public function logout(){
        activity()
        ->causedBy(Vendor::where('id', Auth::guard('vendor')->user()->id)->get()->first())
        ->tap(function(Activity $activity) {
            $activity->subject_type = 'System';
            $activity->subject_id = '0';
            $activity->log_name = 'Vendor Logout';
        })
        ->log(Auth::guard('vendor')->user()->email.' logged out as a vendor');

        Auth::guard('vendor')->logout();
        return redirect(route('vendor.login'));
    }
}
