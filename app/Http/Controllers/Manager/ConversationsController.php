<?php

namespace App\Http\Controllers\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Spatie\Activitylog\Contracts\Activity;


use Auth;

use Mail;
use App\Mail\Alert;

use App\ActivityLog;
use App\Conversation;
use App\Customer;
use App\DeletedMessage;
use App\Manager;
use App\Message;
use App\Vendor;

class ConversationsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:manager');
    }

    public function getConversationsCount(Request $request){
        return response()->json([
            'count' => count(Message::get())
        ]);
    }

    public function getConversationsRecords(Request $request){
        $conversations = Conversation::with('last_message')->get();
        for ($i=0; $i < sizeof($conversations); $i++) { 
            $conversation_key = explode("|", $conversations[$i]["conv_key"]);

            $conversations[$i]["customer"] = Customer::where([
                ['id', "=", trim($conversation_key[0])]
            ])->first();
            
            $conversations[$i]["vendor"] = Vendor::where('id', trim($conversation_key[1]))->first();
        }

        return response()->json([
            'count' => count(Message::get()),
            'records' => $conversations
        ]);
    }

    public function getConversationCount(Request $request){
        return response()->json([
            'count' => count(Message::where('message_conversation_id', $request->id)->get()),
        ]);
    }

    public function getConversationRecords(Request $request){

        /* Get conversation details */
        $conversation["record"] = Conversation::where('id', $request->id)->first();
        $conversation["participant_ids"] = explode("|", $conversation["record"]["conv_key"]);
        $conversation["customer"] = Customer::where('id', $conversation["participant_ids"][0])->first();
        $conversation["vendor"] = Vendor::where('id', $conversation["participant_ids"][1])->first();

        /* Get conversation messages */
        $conversation["messages"] = Message::where('message_conversation_id', $request->id)->get();

        return response()->json([
            'count' => count(Message::where('message_conversation_id', $request->id)->get()),
            'records' => $conversation
        ]);
        
    }

    public function sendMessage(Request $request){
        $validator = Validator::make($request->all(), [
            'message' => 'required'
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

        /* Get conversation details */
        $conversation["record"] = Conversation::where('id', $request->id)->first();
        $conversation["participant_ids"] = explode("|", $conversation["record"]["conv_key"]);
        $conversation["customer"] = Customer::where('id', $conversation["participant_ids"][0])->first();
        $conversation["vendor"] = Vendor::where('id', $conversation["participant_ids"][1])->first();

        $message = new Message;
        $message->message_sender = "MGMT";
        $message->message_content = $request->message;
        $message->message_conversation_id = $request->id;
        $message->message_timestamp = date("Y-m-d H:i:s");
        $message->message_read = "Init|";
        $message->save();

        //send email
        //customer
        $data = array(
            'subject' => 'New Message from Solushop Management',
            'name' => $conversation["customer"]->first_name,
            'message' => "You have a new message from Solushop Management on Solushop<br>Solushop Management - \"".$request->message."\"<br><br>View here: <a href='https://www.solushop.com.gh/my-account/messages/".$conversation["vendor"]->username."'>Conversation with ".$conversation["vendor"]->name."</a>"
        );

        Mail::to($conversation["customer"]->email, $conversation["customer"]->first_name)
            ->queue(new Alert($data));

        //vendor
        $data = array(
            'subject' => 'New Message from Solushop Management',
            'name' => $conversation["vendor"]->name,
            'message' => "You have a new message from Solushop Management on Solushop<br>Solushop Management - \"".$request->message."\"<br><br>View here: <a href='https://www.solushop.com.gh/portal/vendor/".$request->id."'>Conversation with ".$conversation["customer"]->first_name."</a>"
        );

        Mail::to($conversation["vendor"]->email, $conversation["vendor"]->name)
            ->queue(new Alert($data));

        /*--- log activity ---*/
        activity()
        ->causedBy(Manager::where('id', Auth::guard('manager')->user()->id)->get()->first())
        ->tap(function(Activity $activity) {
            $activity->subject_type = 'System';
            $activity->subject_id = '0';
            $activity->log_name = 'Management Message Sent';
        })
        ->log(Auth::guard('manager')->user()->first_name." ".Auth::guard('manager')->user()->last_name." sent a message [".$request->message."] in conversation with ID ".$request->id);

        return response()->json([
            "type" => "success",
            "message" => "Message sent",
            'count' => count(Message::where('message_conversation_id', $request->id)->get()),
            'messages' => Message::where('message_conversation_id', $request->id)->get()
        ]);

    }
}
