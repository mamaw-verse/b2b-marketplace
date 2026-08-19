<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\ConversationThread;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class MessageController extends Controller
{
    /**
     * List messages in a thread (cursor paginated, newest last).
     */
    public function index(Request $request, ConversationThread $thread): JsonResponse
    {
        Gate::authorize('create', [Message::class, $thread]);

        $messages = $thread->messages()
            ->with('sender')
            ->orderBy('created_at')
            ->cursorPaginate($request->integer('limit', 50));

        $data = $messages->map(fn (Message $m) => [
            'id' => $m->id,
            'thread_id' => $m->thread_id,
            'sender' => [
                'id' => $m->sender_id,
                'name' => $m->sender->first_name.' '.$m->sender->last_name,
            ],
            'body' => $m->body,
            'attachments' => $m->attachments->map(fn ($a) => [
                'media_id' => $a->id,
                'original_filename' => $a->original_filename,
            ]),
            'created_at' => $m->created_at->toIso8601String(),
        ]);

        return response()->json([
            'data' => $data,
            'meta' => [
                'next_cursor' => $messages->nextCursor()?->encode(),
                'prev_cursor' => $messages->previousCursor()?->encode(),
                'per_page' => $messages->perPage(),
            ],
        ]);
    }

    /**
     * Send a message in a thread.
     */
    public function store(Request $request, ConversationThread $thread): JsonResponse
    {
        Gate::authorize('create', [Message::class, $thread]);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'attachment_media_ids' => ['nullable', 'array', 'max:5'],
            'attachment_media_ids.*' => ['integer', 'exists:media,id'],
        ]);

        $message = $thread->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        if (! empty($validated['attachment_media_ids'])) {
            $message->attachments()->attach($validated['attachment_media_ids']);
        }

        // Touch conversation updated_at for ordering
        $thread->conversation->touch();

        // Update RFQ activity if thread is RFQ-linked
        if ($thread->rfq_id) {
            $thread->rfq->touchActivity();

            // Also touch rfq_recipients last_activity_at
            $thread->rfq->recipients()->update(['last_activity_at' => now()]);
        }

        // Broadcast the message
        MessageSent::dispatch($message->load('sender'));

        return response()->json([
            'data' => [
                'id' => $message->id,
                'thread_id' => $message->thread_id,
                'sender' => [
                    'id' => $message->sender_id,
                    'name' => $message->sender->first_name.' '.$message->sender->last_name,
                ],
                'body' => $message->body,
                'attachments' => $message->attachments->map(fn ($a) => [
                    'media_id' => $a->id,
                    'original_filename' => $a->original_filename,
                ]),
                'created_at' => $message->created_at->toIso8601String(),
            ],
        ], 201);
    }
}
