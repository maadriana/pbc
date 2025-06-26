<?php

namespace App\Http\Controllers;

use App\Models\PbcConversation;
use App\Models\PbcMessage;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\SendMessageRequest;
use App\Http\Requests\CreateConversationRequest;

class MessageController extends Controller
{
    protected $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    /**
     * Display the messages page
     */
    public function index()
    {
        // Check permission
        if (!auth()->user()->hasPermission('view_messages')) {
            abort(403, 'You do not have permission to access messages.');
        }

        return view('messages');
    }

    /**
     * Get user's conversations with pagination and filters
     */
    public function getConversations(Request $request): JsonResponse
    {
        try {
            // Check permission
            if (!auth()->user()->hasPermission('view_messages')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view messages'
                ], 403);
            }

            $filters = $request->validate([
                'search' => 'nullable|string|max:255',
                'status' => 'nullable|in:active,completed,archived',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1'
            ]);

            $conversations = $this->messageService->getUserConversations(
                auth()->id(),
                $filters
            );

            return response()->json([
                'success' => true,
                'data' => $conversations->items(),
                'meta' => [
                    'current_page' => $conversations->currentPage(),
                    'last_page' => $conversations->lastPage(),
                    'per_page' => $conversations->perPage(),
                    'total' => $conversations->total(),
                    'from' => $conversations->firstItem(),
                    'to' => $conversations->lastItem()
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            \Log::error('MessageController::getConversations failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch conversations',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get messages for a specific conversation
     */
    public function getMessages(Request $request, $conversationId): JsonResponse
    {
        try {
            // Check permission
            if (!auth()->user()->hasPermission('view_messages')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view messages'
                ], 403);
            }

            $request->validate([
                'page' => 'nullable|integer|min:1'
            ]);

            $messages = $this->messageService->getConversationMessages(
                $conversationId,
                auth()->id(),
                $request->get('page', 1)
            );

            return response()->json([
                'success' => true,
                'data' => $messages->items(),
                'meta' => [
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total(),
                    'from' => $messages->firstItem(),
                    'to' => $messages->lastItem()
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found or you do not have access to it'
            ], 404);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            \Log::error('MessageController::getMessages failed', [
                'user_id' => auth()->id(),
                'conversation_id' => $conversationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch messages',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Send a new message
     */
    public function sendMessage(SendMessageRequest $request): JsonResponse
    {
        try {
            $message = $this->messageService->sendMessage(
                $request->conversation_id,
                auth()->id(),
                $request->message,
                $request->file('attachments', []),
                $request->reply_to_id
            );

            // Clear cache
            Cache::forget("user_conversations_" . auth()->id());
            Cache::forget("user_unread_count_" . auth()->id());

            // Clear cache for all conversation participants
            $conversation = PbcConversation::with('participants')->find($request->conversation_id);
            if ($conversation) {
                foreach ($conversation->participants as $participant) {
                    if ($participant->id !== auth()->id()) {
                        Cache::forget("user_conversations_{$participant->id}");
                        Cache::forget("user_unread_count_{$participant->id}");
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => $message,
                'message' => 'Message sent successfully'
            ], 201);

        } catch (\Exception $e) {
            \Log::error('MessageController::sendMessage failed', [
                'user_id' => auth()->id(),
                'conversation_id' => $request->conversation_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getTraceAsString() : 'Failed to send message'
            ], 400);
        }
    }

    /**
     * Mark message as read
     */
    public function markAsRead(Request $request, $messageId): JsonResponse
    {
        try {
            $this->messageService->markMessageAsRead($messageId, auth()->id());

            // Clear unread count cache
            Cache::forget("user_unread_count_" . auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Message marked as read'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found or you do not have access to it'
            ], 404);

        } catch (\Exception $e) {
            \Log::error('MessageController::markAsRead failed', [
                'user_id' => auth()->id(),
                'message_id' => $messageId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark message as read',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Mark all messages in a conversation as read
     */
    public function markConversationAsRead($conversationId): JsonResponse
    {
        try {
            // Verify user has access to conversation
            $conversation = PbcConversation::forUser(auth()->id())->findOrFail($conversationId);

            // Mark all unread messages as read
            $conversation->markAsReadForUser(auth()->id());

            // Clear cache
            Cache::forget("user_unread_count_" . auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'All messages marked as read'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found or you do not have access to it'
            ], 404);

        } catch (\Exception $e) {
            \Log::error('MessageController::markConversationAsRead failed', [
                'user_id' => auth()->id(),
                'conversation_id' => $conversationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark messages as read',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Create a new conversation
     */
    public function createConversation(CreateConversationRequest $request): JsonResponse
    {
        try {
            $conversation = $this->messageService->createConversation(
                $request->client_id,
                $request->project_id,
                $request->participant_ids,
                auth()->id(),
                $request->title
            );

            // Clear cache for all participants
            foreach ($request->participant_ids as $userId) {
                Cache::forget("user_conversations_{$userId}");
            }

            return response()->json([
                'success' => true,
                'data' => $conversation,
                'message' => 'Conversation created successfully'
            ], 201);

        } catch (\Exception $e) {
            \Log::error('MessageController::createConversation failed', [
                'user_id' => auth()->id(),
                'client_id' => $request->client_id ?? null,
                'project_id' => $request->project_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error' => config('app.debug') ? $e->getTraceAsString() : 'Failed to create conversation'
            ], 400);
        }
    }

    /**
     * Get conversation details
     */
    public function getConversation($conversationId): JsonResponse
    {
        try {
            $conversation = PbcConversation::with([
                'client:id,name',
                'project:id,name',
                'participants:id,name,role',
                'creator:id,name',
                'lastMessage:id,message,created_at,sender_id',
                'lastMessage.sender:id,name'
            ])
            ->withUnreadCount(auth()->id())
            ->forUser(auth()->id())
            ->findOrFail($conversationId);

            return response()->json([
                'success' => true,
                'data' => $conversation
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found or access denied'
            ], 404);

        } catch (\Exception $e) {
            \Log::error('MessageController::getConversation failed', [
                'user_id' => auth()->id(),
                'conversation_id' => $conversationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch conversation details',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update conversation status
     */
    public function updateConversationStatus(Request $request, $conversationId): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'required|in:active,completed,archived'
            ]);

            $conversation = PbcConversation::forUser(auth()->id())->findOrFail($conversationId);

            // Check permission - only creator or those with manage_conversations permission
            if (!auth()->user()->hasPermission('manage_conversations') &&
                $conversation->created_by !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to update this conversation'
                ], 403);
            }

            $conversation->update(['status' => $request->status]);

            // Clear cache for all participants
            foreach ($conversation->participants as $participant) {
                Cache::forget("user_conversations_{$participant->id}");
            }

            return response()->json([
                'success' => true,
                'data' => $conversation->fresh(),
                'message' => 'Conversation status updated successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation not found or access denied'
            ], 404);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            \Log::error('MessageController::updateConversationStatus failed', [
                'user_id' => auth()->id(),
                'conversation_id' => $conversationId,
                'status' => $request->status ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update conversation status',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get user's unread message count
     */
  public function getUnreadCount(): JsonResponse
{
    try {
        $count = Cache::remember(
            "user_unread_count_" . auth()->id(),
            300, // 5 minutes
            function () {
                return PbcMessage::whereHas('conversation', function ($q) {
                    $q->whereHas('participants', function ($participantQuery) {
                        $participantQuery->where('pbc_conversation_participants.user_id', auth()->id())
                                       ->where('pbc_conversation_participants.is_active', true);
                    });
                })
                ->where('sender_id', '!=', auth()->id())
                ->where('is_read', false)
                ->count();
            }
        );

        return response()->json([
            'success' => true,
            'data' => ['unread_count' => $count]
        ]);

    } catch (\Exception $e) {
        \Log::error('MessageController::getUnreadCount failed', [
            'user_id' => auth()->id(),
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => true, // Don't fail the request
            'data' => ['unread_count' => 0] // Default to 0
        ]);
    }
}

    /**
     * Get conversation statistics
     */
    public function getConversationStats($conversationId): JsonResponse
    {
        try {
            $stats = $this->messageService->getConversationStats($conversationId, auth()->id());

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get conversation statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Search messages within conversation
     */
    public function searchMessages(Request $request, $conversationId): JsonResponse
    {
        try {
            $request->validate([
                'search' => 'required|string|min:2|max:255',
                'page' => 'nullable|integer|min:1'
            ]);

            $messages = $this->messageService->searchMessages(
                $conversationId,
                auth()->id(),
                $request->search,
                $request->get('page', 1)
            );

            return response()->json([
                'success' => true,
                'data' => $messages->items(),
                'meta' => [
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total()
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search messages',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get conversation attachments
     */
    public function getConversationAttachments(Request $request, $conversationId): JsonResponse
    {
        try {
            $request->validate([
                'page' => 'nullable|integer|min:1'
            ]);

            $attachments = $this->messageService->getConversationAttachments(
                $conversationId,
                auth()->id(),
                $request->get('page', 1)
            );

            return response()->json([
                'success' => true,
                'data' => $attachments->items(),
                'meta' => [
                    'current_page' => $attachments->currentPage(),
                    'last_page' => $attachments->lastPage(),
                    'per_page' => $attachments->perPage(),
                    'total' => $attachments->total()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get conversation attachments',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Download message attachment
     */
    public function downloadAttachment($conversationId, $messageId, $attachmentId): JsonResponse
    {
        try {
            // Verify user has access to conversation
            $conversation = PbcConversation::forUser(auth()->id())->findOrFail($conversationId);

            // Get the message and verify it belongs to this conversation
            $message = PbcMessage::where('conversation_id', $conversationId)
                                 ->findOrFail($messageId);

            // Find the attachment
            $attachments = $message->attachments ?? [];
            $attachment = collect($attachments)->firstWhere('id', $attachmentId);

            if (!$attachment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attachment not found'
                ], 404);
            }

            // Check if file exists
            if (!Storage::disk('public')->exists($attachment['path'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found on server'
                ], 404);
            }

            return Storage::disk('public')->download(
                $attachment['path'],
                $attachment['name']
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Message or conversation not found'
            ], 404);

        } catch (\Exception $e) {
            \Log::error('MessageController::downloadAttachment failed', [
                'user_id' => auth()->id(),
                'conversation_id' => $conversationId,
                'message_id' => $messageId,
                'attachment_id' => $attachmentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to download attachment',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Add participant to conversation
     */
    public function addParticipant(Request $request, $conversationId): JsonResponse
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'role' => 'nullable|in:participant,moderator,observer'
            ]);

            $conversation = PbcConversation::forUser(auth()->id())->findOrFail($conversationId);

            // Check permission
            if (!auth()->user()->hasPermission('manage_conversations') &&
                $conversation->created_by !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to add participants'
                ], 403);
            }

            // Check if user is already a participant
            if ($conversation->participants()->where('user_id', $request->user_id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is already a participant in this conversation'
                ], 422);
            }

            $conversation->addParticipant($request->user_id, $request->get('role', 'participant'));

            // Clear cache
            Cache::forget("user_conversations_{$request->user_id}");

            return response()->json([
                'success' => true,
                'message' => 'Participant added successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add participant',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Remove participant from conversation
     */
    public function removeParticipant(Request $request, $conversationId, $userId): JsonResponse
    {
        try {
            $conversation = PbcConversation::forUser(auth()->id())->findOrFail($conversationId);

            // Check permission
            if (!auth()->user()->hasPermission('manage_conversations') &&
                $conversation->created_by !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to remove participants'
                ], 403);
            }

            // Cannot remove the creator
            if ($conversation->created_by == $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove conversation creator'
                ], 422);
            }

            $conversation->removeParticipant($userId);

            // Clear cache
            Cache::forget("user_conversations_{$userId}");

            return response()->json([
                'success' => true,
                'message' => 'Participant removed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove participant',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get available users for conversation
     */
    public function getAvailableUsers(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'search' => 'nullable|string|max:255',
                'exclude_conversation' => 'nullable|exists:pbc_conversations,id'
            ]);

            $query = User::select('id', 'name', 'email', 'role')
                         ->where('is_active', true)
                         ->where('id', '!=', auth()->id());

            // Exclude users already in conversation
            if ($request->exclude_conversation) {
                $conversation = PbcConversation::find($request->exclude_conversation);
                if ($conversation) {
                    $existingParticipants = $conversation->participants()->pluck('user_id')->toArray();
                    $query->whereNotIn('id', $existingParticipants);
                }
            }

            // Search filter
            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                      ->orWhere('email', 'like', "%{$request->search}%");
                });
            }

            $users = $query->orderBy('name')->limit(50)->get();

            return response()->json([
                'success' => true,
                'data' => $users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get available users',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
