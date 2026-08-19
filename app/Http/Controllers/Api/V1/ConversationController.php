<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\ConversationThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ConversationController extends Controller
{
    /**
     * List user's conversations (as buyer or shop owner).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = Conversation::forUser($user)
            ->with(['buyer', 'shop'])
            ->withCount('threads')
            ->orderByDesc('updated_at')
            ->cursorPaginate($request->integer('limit', 20));

        $data = $conversations->map(function (Conversation $c) use ($user) {
            // Determine the counterpart
            $isBuyer = $user->id === $c->buyer_user_id;

            return [
                'id' => $c->id,
                'counterpart' => $isBuyer
                    ? ['type' => 'shop', 'id' => $c->shop_id, 'name' => $c->shop->storefront_name]
                    : ['type' => 'buyer', 'id' => $c->buyer_user_id, 'name' => $c->buyer->first_name.' '.$c->buyer->last_name],
                'threads_count' => $c->threads_count,
                'created_at' => $c->created_at->toIso8601String(),
                'updated_at' => $c->updated_at->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'next_cursor' => $conversations->nextCursor()?->encode(),
                'prev_cursor' => $conversations->previousCursor()?->encode(),
                'per_page' => $conversations->perPage(),
            ],
        ]);
    }

    /**
     * List threads in a conversation.
     */
    public function threads(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);

        $threads = $conversation->threads()
            ->with('rfq:id,title,status')
            ->orderByDesc('updated_at')
            ->get();

        return response()->json([
            'data' => $threads->map(fn (ConversationThread $t) => [
                'id' => $t->id,
                'conversation_id' => $t->conversation_id,
                'title' => $t->title,
                'rfq_id' => $t->rfq_id,
                'rfq_title' => $t->rfq?->title,
                'rfq_status' => $t->rfq?->status,
                'is_default' => $t->is_default,
                'created_at' => $t->created_at->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Create a new thread in a conversation.
     */
    public function storeThread(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('createThread', $conversation);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $thread = $conversation->threads()->create([
            'title' => $validated['title'],
            'is_default' => false,
        ]);

        return response()->json([
            'data' => [
                'id' => $thread->id,
                'conversation_id' => $thread->conversation_id,
                'title' => $thread->title,
                'rfq_id' => null,
                'is_default' => false,
                'created_at' => $thread->created_at->toIso8601String(),
            ],
        ], 201);
    }
}
