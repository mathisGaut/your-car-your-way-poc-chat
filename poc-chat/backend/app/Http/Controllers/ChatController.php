<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function users(): JsonResponse
    {
        return response()->json(
            User::query()->orderBy('id')->get(['id', 'name'])
        );
    }

    public function show(Conversation $conversation): JsonResponse
    {
        $conversation->load('participants:id,name');

        return response()->json($conversation);
    }

    public function messages(Conversation $conversation): JsonResponse
    {
        $messages = $conversation->messages()
            ->with('sender:id,name')
            ->orderBy('sent_at')
            ->get();

        return response()->json($messages);
    }

    public function storeMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $data = $request->validate([
            'sender_id' => ['required', 'integer', 'exists:users,id'],
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $message = $conversation->messages()->create([
            'sender_id' => $data['sender_id'],
            'content' => $data['content'],
            'sent_at' => now(),
        ]);

        $message->load('sender:id,name');

        broadcast(new MessageSent($message));

        return response()->json($message, 201);
    }
}
