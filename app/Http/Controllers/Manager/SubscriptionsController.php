<?php

namespace App\Http\Controllers\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Contracts\Activity;

use App\Manager;
use App\Product;
use App\Vendor;
use App\VendorSubscription;

use Auth;

use Mail;
use App\Mail\Alert;

class SubscriptionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:manager');
    }

    // public function getSubscriptionsCount(Request $request){
    //     return response()->json([
    //         'count' => VendorSubscription::sum("vs_days_left")
    //     ]);
    // }

    // public function getSubscriptionsRecords(Request $request){
    //     return response()->json([
    //         'count' => VendorSubscription::sum("vs_days_left"),
    //         'records' => DB::select("SELECT *, vendor_subscriptions.id as subscription_id, vendor_subscriptions.created_at as subscription_created_at, vendor_subscriptions.updated_at as subscription_updated_at FROM vendors, vendor_subscriptions, vs_packages WHERE vendors.id = vendor_subscriptions.vs_vendor_id AND vendor_subscriptions.vs_vsp_id = vs_packages.id")
    //     ]);
    // }

    // public function cancelSubscription(Request $request){
    //     //select details
    //     $subscription = DB::select(
    //         "SELECT *, vendor_subscriptions.id as subscription_id, vendor_subscriptions.created_at as subscription_created_at, vendor_subscriptions.updated_at as subscription_updated_at FROM vendors, vendor_subscriptions, vs_packages WHERE vendors.id = vendor_subscriptions.vs_vendor_id AND vendor_subscriptions.vs_vsp_id = vs_packages.id AND vendor_subscriptions.id = :vendor_subscription_id",
    //         ['vendor_subscription_id' => $request->id]
    //     );

    //     //delete all non live products of that vendor
    //     Product::
    //         where([
    //             ['product_state', "<>", 1],
    //             ['product_vid', "=", $subscription[0]->vs_vendor_id]
    //         ])
    //         ->update([
    //             'product_state' => 4
    //         ]);

    //     //deactivate live products of that vendor
    //     Product::
    //         where([
    //             ['product_state', "=", 1],
    //             ['product_vid', "=", $subscription[0]->vs_vendor_id]
    //         ])
    //         ->update([
    //             'product_state' => 5
    //         ]);

    //     //update days left to 0
    //     VendorSubscription::
    //         where([
    //             ['vs_vendor_id', "=", $subscription[0]->vs_vendor_id]
    //         ])
    //         ->update([
    //             'vs_days_left' => 0
    //         ]);

    //     /*--- Notify Vendor ---*/
    //     send_sms($subscription[0]->phone, "Dear ".$subscription[0]->name.", your subscription as a vendor with Solushop Ghana has been cancelled.");

    //     $data = array(
    //         'subject' => 'Subscription Cancelled - Solushop Ghana',
    //         'name' => $subscription[0]->name,
    //         'message' => "Your subscription as a vendor with Solushop Ghana has been cancelled."
    //     );

    //     Mail::to($subscription[0]->email, $subscription[0]->name)
    //         ->queue(new Alert($data));

    //     /*--- log activity ---*/
    //     activity()
    //     ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
    //     ->tap(function(Activity $activity) {
    //         $activity->subject_type = 'System';
    //         $activity->subject_id = '0';
    //         $activity->log_name = 'Vendor Subscription Cancellation';
    //     })
    //     ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." cancelled ".$subscription[0]->name."'s subscription.");

    //     return response()->json([
    //         "type" => "success",
    //         "message" => $subscription[0]->name."'s subscription cancelled.",
    //         'count' => VendorSubscription::sum("vs_days_left"),
    //         'records' => DB::select("SELECT *, vendor_subscriptions.id as subscription_id, vendor_subscriptions.created_at as subscription_created_at, vendor_subscriptions.updated_at as subscription_updated_at FROM vendors, vendor_subscriptions, vs_packages WHERE vendors.id = vendor_subscriptions.vs_vendor_id AND vendor_subscriptions.vs_vsp_id = vs_packages.id")
    //     ]);
    // }
}


