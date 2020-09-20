<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Activitylog\Contracts\Activity;

use Auth;
use App\SalesAssociate;

class SalesAssociateLoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest:sales-associate')->except('logout');
    }

    public function showLoginForm()
    {
        return view('portal.main.login')
        ->with('entity', 'Sales Associate');
    }

    public function login(Request $request)
    {
        //attempt to log user in
        if(Auth::guard('sales-associate')->attempt(['email' => $request->username, 'password' => $request->password], $request->remember)){
            
            //if successful, then redirect to intended location
            /*--- log activity ---*/
            activity()
            ->causedBy(SalesAssociate::where('id', Auth::guard('sales-associate')->user()->id)->get()->first())
            ->tap(function(Activity $activity) {
                $activity->subject_type = 'System';
                $activity->subject_id = '0';
                $activity->log_name = 'Sales-Associate Login';
            })
            ->log(Auth::guard('sales-associate')->user()->first_name.' logged in as a Sales Associate');
            
            return response()->json([
                'valid' => true,
                'message' => 'Welcome back, '.Auth::guard('sales-associate')->user()->first_name
            ]);
        }

        /*--- log activity ---*/
        activity()
        ->tap(function(Activity $activity) {
           $activity->causer_type = 'App\SalesAssociate';
           $activity->causer_id = '-';
           $activity->subject_type = 'System';
           $activity->subject_id = '0';
           $activity->log_name = 'Sales Associate Login Attempt';
        })
        ->log($request->username.' attempted to log in as a Sales Associate');
        
        //if unsuccessful then redirect back to login with the form data
        return response()->json([
            'valid' => false,
            'message' => 'Invalid login credentials.',
        ]);
    }

    public function logout(){
        activity()
        ->causedBy(SalesAssociate::where('id', Auth::guard('sales-associate')->user()->id)->get()->first())
        ->tap(function(Activity $activity) {
            $activity->subject_type = 'System';
            $activity->subject_id = '0';
            $activity->log_name = 'Sales Associate Logout';
        })
        ->log(Auth::guard('sales-associate')->user()->email.' logged out as a sales associate');
        
        Auth::guard('sales-associate')->logout();
        return redirect(route('sales-associate.login'));
    }
}
