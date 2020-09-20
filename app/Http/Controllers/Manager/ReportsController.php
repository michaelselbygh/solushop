<?php

namespace App\Http\Controllers\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\SMS;
use App\ActivityLog;

use Auth;

class ReportsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:manager');
    }

    
    public function getSMSCount(Request $request){
        return response()->json([
            'count' => count(SMS::get())
        ]);
    }

    public function getSMSRecords(Request $request){
        return response()->json([
            'count' => count(SMS::get()),
            'records' => SMS::with('state')->get()
        ]);
    }

    public function getActivityCount(Request $request){
        return response()->json([
            'count' => count(ActivityLog::get())
        ]);
    }

    public function getActivityRecords(Request $request){
        return response()->json([
            'count' => count(ActivityLog::get()),
            'records' => ActivityLog::get()
        ]);
    }
}
