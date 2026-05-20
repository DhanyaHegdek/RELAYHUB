<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'reply_to_id',
        'read_at',
    ];

    // Message belongs to a conversation
    public function conversation() {
        return $this->belongsTo(Conversation::class);
    }

    // Message belongs to a sender (user)
    public function sender() {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // Message can reply to another message
    public function replyTo() {
        return $this->belongsTo(Message::class, 'reply_to_id');
    }

    // Message can have many replies
    public function replies() {
        return $this->hasMany(Message::class, 'reply_to_id');
    }
}