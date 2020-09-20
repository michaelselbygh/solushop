<?php

namespace App\Http\Controllers\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Activitylog\Contracts\Activity;

use Auth;

use App\Customer;
use App\DeletedMessage;
use App\Manager;
use App\Message;
use App\MessageFlag;
use App\Vendor;

class FlagsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:manager');
    }

    public function getFlagsCount(Request $request){
        switch ($request->type) {
            case 'Active':
                return response()->json([
                    'count' => count(MessageFlag::with('message')->get())
                ]);

                break;

            case 'Deleted':

                return response()->json([
                    'count' => count(DeletedMessage::get())
                ]);

                break;
            
            default:
                return response()->json([
                    'type' => $request->type
                ]);
                break;
        }
    }

    public function getFlagsRecords(Request $request){
        switch ($request->type) {
            case 'Active':

                $messages = MessageFlag::with('message')->get()->toArray();
                for ($i=0; $i < sizeof($messages); $i++) { 
                    if (substr($messages[$i]["message"]["message_sender"], 0, 1) == "C") {
                        //customer
                        $sender = Customer::where('id', $messages[$i]["message"]["message_sender"])->first()->toArray();
                        $messages[$i]["message"]["sender"] = $sender["first_name"]." ".$sender["last_name"];
                    }elseif(substr($messages[$i]["message"]["message_sender"], 0, 1) == "M"){
                        $messages[$i]["message"]["sender"] = "Solushop Management";
                    }else{
                        //vendor
                        $messages[$i]["message"]["sender"] = Vendor::where('id', $messages[$i]["message"]["message_sender"])->get('name');
                    }
                }

                return response()->json([
                    'count' => count(MessageFlag::with('message')->get()),
                    'records' => $messages
                ]);

                break;

            case 'Deleted':

                $messages = DeletedMessage::get()->toArray();
                for ($i=0; $i < sizeof($messages); $i++) { 
                    if (substr($messages[$i]["message_sender"], 0, 1) == "C") {
                        //customer
                        $sender = Customer::where('id', $messages[$i]["message_sender"])->first()->toArray();
                        $messages[$i]["sender"] = $sender["first_name"]." ".$sender["last_name"];
                    }elseif(substr($messages[$i]["message_sender"], 0, 1) == "M"){
                        $messages[$i]["sender"] = "Solushop Management";
                    }else{
                        //vendor
                        $messages[$i]["sender"] = Vendor::where('id', $messages[$i]["message_sender"])->get('name');
                    }
                }
            
                return response()->json([
                    'count' => count(DeletedMessage::get()),
                    'records' => $messages
                ]);

                break;

            default:
                # do nothing
                break;
        }

        
    }
    public function processFlag(Request $request){
        switch ($request->action) {
            case 'approve':
                /*--- Log Activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Flagged Message Approved';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." approved flagged message ".$request->id);
                
                
                /*--- Delete Flag ---*/
                MessageFlag::where('mf_mid', $request->id)->delete();

                $message = "Flag approved as safe";
                break;

            case 'reject':

                $message = Message::where('id', $request->id)->first();
                $deleted_message = new DeletedMessage;
                $deleted_message->message_sender = $message->message_sender;
                $deleted_message->message_content = $message->message_content;
                $deleted_message->message_conversation_id = $message->message_conversation_id;
                $deleted_message->message_timestamp = $message->message_timestamp;
                $deleted_message->message_read = $message->message_read;
                $deleted_message->save();
                
                /*--- Update Message Contents and Sender ---*/
                $message->message_content = "This message has been deleted by Management because it does not conform to the TnCs for communication on Solushop.";
                $message->save();

                /*--- Log Activity ---*/
                activity()
                ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
                ->tap(function(Activity $activity) {
                    $activity->subject_type = 'System';
                    $activity->subject_id = '0';
                    $activity->log_name = 'Flagged Message Deleted';
                })
                ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." deleted flagged message ".$request->message_id);
                
                
                /*--- Delete Flag ---*/
                MessageFlag::where('mf_mid', $request->id)->delete();


                $message = "Unsafe message deleted";
                break;
            
            default:
                # code...
                break;
        }

        $messages = MessageFlag::with('message')->get()->toArray();
        for ($i=0; $i < sizeof($messages); $i++) { 
            if (substr($messages[$i]["message"]["message_sender"], 0, 1) == "C") {
                //customer
                $sender = Customer::where('id', $messages[$i]["message"]["message_sender"])->first()->toArray();
                $messages[$i]["message"]["sender"] = $sender["first_name"]." ".$sender["last_name"];
            }elseif(substr($messages[$i]["message"]["message_sender"], 0, 1) == "M"){
                $messages[$i]["message"]["sender"] = "Solushop Management";
            }else{
                //vendor
                $messages[$i]["message"]["sender"] = Vendor::where('id', $messages[$i]["message"]["message_sender"])->get('name');
            }
        }

        return response()->json([
            'type' => 'success',
            'message' => $message,
            'count' => count(MessageFlag::with('message')->get()),
            'records' => $messages
        ]);
    
    }
}
