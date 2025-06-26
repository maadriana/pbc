<?php

namespace App\Services;

use App\Models\PbcConversation;
use App\Models\PbcMessage;
use App\Models\User;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class MessageService
{
    /**
     * Get user's conversations with filters and caching
     */
    public function getUserConversations($userId, array $filters = [])
    {
        $cacheKey = "user_conversations_{$userId}_" . md5(json_encode($filters));

        return Cache::remember($cacheKey, 300, function () use ($userId, $filters) {
            $query = PbcConversation::with([
                'client:id,name',
                'project:id,name',
                'lastMessage:id,conversation_id,message,created_at,sender_id,message_type',
                'lastMessage.sender:id,name,role',
                'participants:id,name,role'
            ])
            ->withUnreadCount($userId)
            ->forUser($userId)
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->whereHas('client', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('project', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('messages', function ($q) use ($search) {
                        $q->where('message', 'like', "%{$search}%");
                    });
                });
            })
            ->when($filters['status'] ?? null, function ($q, $status) {
                $q->where('status', $status);
            })
            ->orderBy('last_message_at', 'desc');

            return $query->paginate($filters['per_page'] ?? 15);
        });
    }

    /**
     * Get conversation messages with read status update
     */
    public function getConversationMessages($conversationId, $userId, $page = 1)
    {
        // Verify user has access to conversation
        $conversation = PbcConversation::forUser($userId)->findOrFail($conversationId);

        $messages = PbcMessage::with([
            'sender:id,name,role',
            'replyTo:id,message,sender_id',
            'replyTo.sender:id,name'
        ])
        ->forConversation($conversationId)
        ->orderBy('created_at', 'asc')
        ->paginate(50, ['*'], 'page', $page);

        // Mark messages as read for this user (async)
        $this->markConversationAsReadAsync($conversationId, $userId);

        return $messages;
    }

    /**
     * Send a new message with attachments
     */
    public function sendMessage($conversationId, $senderId, $message = null, array $attachments = [], $replyToId = null)
    {
        // Verify user has access and permission
        $conversation = PbcConversation::forUser($senderId)->findOrFail($conversationId);
        $user = User::findOrFail($senderId);

        // Check permissions
        if (!$user->hasPermission('send_messages')) {
            throw new \Exception('You do not have permission to send messages');
        }

        DB::beginTransaction();
        try {
            // Process attachments
            $attachmentData = [];
            foreach ($attachments as $file) {
                if ($file instanceof UploadedFile) {
                    $attachmentData[] = $this->storeAttachment($file, $conversationId);
                }
            }

            // Determine message type
            $messageType = 'text';
            if (!empty($attachmentData)) {
                $messageType = empty($message) ? 'file' : 'text';
            }

            // Create message
            $messageData = [
                'conversation_id' => $conversationId,
                'sender_id' => $senderId,
                'message' => $message,
                'attachments' => $attachmentData,
                'message_type' => $messageType,
                'reply_to_id' => $replyToId,
                'is_read' => false
            ];

            $newMessage = PbcMessage::create($messageData);

            // Update conversation last message time
            $conversation->update(['last_message_at' => now()]);

            // Clear relevant cache
            $this->clearUserCache($senderId);
            $this->clearConversationParticipantsCache($conversationId);

            DB::commit();

            return $newMessage->load([
                'sender:id,name,role',
                'replyTo:id,message,sender_id',
                'replyTo.sender:id,name'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Create a new conversation
     */
    public function createConversation($clientId, $projectId, array $participantIds, $creatorId, $title = null)
{
    // Validate relationships
    $client = Client::findOrFail($clientId);
    $project = Project::where('client_id', $clientId)->findOrFail($projectId);

        // Ensure creator is included in participants
        if (!in_array($creatorId, $participantIds)) {
            $participantIds[] = $creatorId;
        }

        DB::beginTransaction();
        try {
        // Generate title if not provided - UPDATED
        if (!$title) {
            $title = "{$client->name} - " . ucfirst($project->engagement_type) . " {$project->engagement_period->format('Y')}";
        }


            $conversation = PbcConversation::create([
                'client_id' => $clientId,
                'project_id' => $projectId,
                'title' => $title,
                'created_by' => $creatorId,
                'status' => 'active',
                'last_message_at' => now()
            ]);

            // Add participants
            $participantData = [];
            foreach ($participantIds as $userId) {
                $user = User::findOrFail($userId);
                $participantData[$userId] = [
                    'joined_at' => now(),
                    'is_active' => true,
                    'role' => $userId === $creatorId ? 'moderator' : 'participant'
                ];
            }

            $conversation->participants()->attach($participantData);

            // Send system message
            $this->sendSystemMessage(
                $conversation->id,
                "Conversation created by " . User::find($creatorId)->name
            );

            // Clear cache for all participants
            foreach ($participantIds as $userId) {
                $this->clearUserCache($userId);
            }

            DB::commit();

            return $conversation->load([
                'client:id,name',
                'project:id,name',
                'participants:id,name,role',
                'creator:id,name'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Store file attachment
     */
    private function storeAttachment(UploadedFile $file, $conversationId)
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs("conversations/{$conversationId}/attachments", $filename, 'public');

        return [
            'id' => Str::uuid(),
            'name' => $file->getClientOriginalName(),
            'filename' => $filename,
            'path' => $path,
            'url' => Storage::url($path),
            'size' => $file->getSize(),
            'type' => $file->getClientOriginalExtension(),
            'mime_type' => $file->getClientMimeType(),
            'uploaded_at' => now()->toISOString()
        ];
    }

    /**
     * Mark conversation messages as read
     */
    private function markConversationAsReadAsync($conversationId, $userId)
    {
        // Use job queue for better performance
        dispatch(function () use ($conversationId, $userId) {
            PbcMessage::forConversation($conversationId)
                ->where('sender_id', '!=', $userId)
                ->where('is_read', false)
                ->update(['is_read' => true, 'read_at' => now()]);

            // Clear unread count cache
            Cache::forget("user_unread_count_{$userId}");
        })->afterResponse();
    }

    /**
     * Mark specific message as read
     */
    public function markMessageAsRead($messageId, $userId)
    {
        $message = PbcMessage::findOrFail($messageId);

        // Verify user has access to this message's conversation
        $conversation = PbcConversation::forUser($userId)->findOrFail($message->conversation_id);

        if ($message->sender_id !== $userId && !$message->is_read) {
            $message->update(['is_read' => true, 'read_at' => now()]);
            Cache::forget("user_unread_count_{$userId}");
        }
    }

    /**
     * Send system message
     */
    private function sendSystemMessage($conversationId, $message)
    {
        return PbcMessage::create([
            'conversation_id' => $conversationId,
            'sender_id' => null, // System message
            'message' => $message,
            'message_type' => 'system',
            'is_read' => false
        ]);
    }

    /**
     * Clear user-related cache
     */
    private function clearUserCache($userId)
    {
        Cache::forget("user_conversations_{$userId}");
        Cache::forget("user_unread_count_{$userId}");

        // Clear paginated cache variations
        for ($page = 1; $page <= 5; $page++) {
            Cache::forget("user_conversations_{$userId}_page_{$page}");
        }
    }

    /**
     * Clear cache for all conversation participants
     */
    private function clearConversationParticipantsCache($conversationId)
    {
        $conversation = PbcConversation::with('participants')->find($conversationId);
        if ($conversation) {
            foreach ($conversation->participants as $participant) {
                $this->clearUserCache($participant->id);
            }
        }
    }
}
