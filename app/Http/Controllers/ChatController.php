<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class ChatController extends Controller
{
    // Start or get existing conversation with someone
    public function startConversation(Request $request) {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $authId = Auth::id();
        $otherId = $request->user_id;

        // Prevent chatting with yourself
        if ($authId === $otherId) {
            return response()->json(['error' => 'Cannot start conversation with yourself'], 400);
        }

        // Check if conversation already exists
        $conversation = Conversation::where(function(Builder $q) use ($authId, $otherId) {
            $q->where('user_one_id', $authId)->where('user_two_id', $otherId);
        })->orWhere(function(Builder $q) use ($authId, $otherId) {
            $q->where('user_one_id', $otherId)->where('user_two_id', $authId);
        })->first();

        // Create if it doesn't exist
        if (!$conversation) {
            $conversation = Conversation::create([
                'user_one_id' => $authId,
                'user_two_id' => $otherId,
            ]);
        }

        return response()->json($conversation->load(['userOne', 'userTwo']));
    }

    // Get all conversations for logged in user
    public function getConversations() {
        $userId = Auth::id();

        $conversations = Conversation::where('user_one_id', $userId)
            ->orWhere('user_two_id', $userId)
            ->with(['userOne', 'userTwo', 'latestMessage'])
            ->orderBy('last_message_at', 'desc')
            ->get();

        return response()->json($conversations);
    }

    // Get all messages in a conversation
    public function getMessages($conversationId) {
        $userId = Auth::id();

        // Make sure user belongs to this conversation
        $conversation = Conversation::where('id', $conversationId)
            ->where(function(Builder $q) use ($userId) {
                $q->where('user_one_id', $userId)
                  ->orWhere('user_two_id', $userId);
            })->firstOrFail();

        $messages = Message::where('conversation_id', $conversationId)
            ->with(['sender', 'replyTo.sender'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    // Send a message
    public function sendMessage(Request $request, $conversationId) {
        $request->validate([
            'body'        => 'required|string|max:5000',
            'reply_to_id' => 'nullable|exists:messages,id',
        ]);

        $userId = Auth::id();

        // Make sure user belongs to this conversation
        $conversation = Conversation::where('id', $conversationId)
            ->where(function($q) use ($userId) {
                $q->where('user_one_id', $userId)
                  ->orWhere('user_two_id', $userId);
            })->firstOrFail();

        $message = Message::create([
            'conversation_id' => $conversationId,
            'sender_id'       => $userId,
            'body'            => $request->body,
            'reply_to_id'     => $request->reply_to_id,
        ]);

        // Update last message time on conversation
        $conversation->update(['last_message_at' => now()]);

        return response()->json($message->load(['sender', 'replyTo.sender']), 201);
    }
}