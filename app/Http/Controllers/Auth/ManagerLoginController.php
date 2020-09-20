<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Activitylog\Contracts\Activity; 
use Auth;

use App\Manager;

class ManagerLoginController extends Controller
{
    protected $redirectTo = '/portal/manager';

    public function __construct()
    {
        $this->middleware('guest:manager')->except('logout');
    }

    public function showLoginForm()
    {
        return view('portal.main.login')
                ->with('entity', 'Manager');
    }

    public function login(Request $request)
    {
        //attempt to log user in
        if(Auth::guard('manager')->attempt(['email' => $request->username, 'password' => $request->password], $request->remember)){
            
            //if successful, then redirect to intended location
            /*--- log activity ---*/
            activity()
            ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
            ->tap(function(Activity $activity) {
                $activity->subject_type = 'System';
                $activity->subject_id = '0';
                $activity->log_name = 'Manager Login';
            })
            ->log(Auth::guard('manager')->user()->email.' logged in as a manager');
            
            return response()->json([
                'valid' => true,
                'message' => 'Welcome back, '.Auth::guard('manager')->user()->first_name
            ]);
        }

        /*--- log activity ---*/
        activity()
        ->tap(function(Activity $activity) {
           $activity->causer_type = 'App\Manager';
           $activity->causer_id = '-';
           $activity->subject_type = 'System';
           $activity->subject_id = '0';
           $activity->log_name = 'Manager Login Attempt';
        })
        ->log($request->username.' attempted to log in as a manager');
        
        //if unsuccessful then redirect back to login with the form data
        return response()->json([
            'valid' => false,
            'message' => 'Invalid login credentials.',
        ]);
    }

    public function logout(){
        /*--- log activity ---*/
        activity()
        ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
        ->tap(function(Activity $activity) {
            $activity->subject_type = 'System';
            $activity->subject_id = '0';
            $activity->log_name = 'Manager Logout';
        })
        ->log(Auth::guard('manager')->user()->email.' logged out as a manager');

        Auth::guard('manager')->logout();
        
        return redirect(route('manager.login'));
    }
}
