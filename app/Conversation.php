<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    public function messages()
    {
        return $this->hasMany('App\Message', 'message_conversation_id', 'id' )->orderBy('id', 'desc');
    }

    public function last_message()
    {
        return $this->hasOne('App\Message', 'message_conversation_id', 'id' )->orderBy('id', 'desc');
    }

}
