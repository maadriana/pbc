<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\PbcConversation;
use App\Models\PbcMessage;
use App\Models\User;
use App\Models\Client;
use App\Models\Project;

class MessageController extends BaseController
{
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
     * Get conversations for the current user
     */
    public function getConversations(): JsonResponse
    {
        try {
            $userId = auth()->id();

            $conversations = PbcConversation::with(['client', 'project', 'lastMessage.sender', 'participants'])
                ->forUser($userId)
                ->withUnreadCount($userId)
                ->orderBy('last_message_at', 'desc')
                ->get();

            $transformedData = $conversations->map(function ($conversation) {
                return [
                    'id' => $conversation->id,
                    'title' => $conversation->title,
                    'client' => $conversation->client ? [
                        'id' => $conversation->client->id,
                        'name' => $conversation->client->name
                    ] : null,
                    'project' => $conversation->project ? [
                        'id' => $conversation->project->id,
                        'name' => $conversation->project->name,
                        'engagement_type' => $conversation->project->engagement_type ?? 'Project'
                    ] : null,
                    'last_message' => $conversation->lastMessage ? [
                        'message' => $conversation->lastMessage->message,
                        'sender_id' => $conversation->lastMessage->sender_id
                    ] : null,
                    'last_message_at' => $conversation->last_message_at ? $conversation->last_message_at->toISOString() : null,
                    'unread_count' => $conversation->unread_count ?? 0,
                    'status' => $conversation->status,
                    'participants' => $conversation->participants->map(function ($participant) {
                        return [
                            'id' => $participant->id,
                            'name' => $participant->name
                        ];
                    }),
                    'created_at' => $conversation->created_at->toISOString()
                ];
            });

            return $this->success($transformedData, 'Conversations retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to get conversations: ' . $e->getMessage());
            return $this->error('Failed to retrieve conversations', $e->getMessage(), 500);
        }
    }

    /**
     * Get available users for conversations
     */
    public function getAvailableUsers(): JsonResponse
    {
        try {
            $users = User::where('is_active', true)
                         ->where('id', '!=', auth()->id())
                         ->select('id', 'name', 'role', 'email')
                         ->orderBy('name')
                         ->get();

            return $this->success($users, 'Users retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to get users: ' . $e->getMessage());
            return $this->error('Failed to retrieve users', $e->getMessage(), 500);
        }
    }

    /**
     * Get conversation details
     */
    public function getConversation($id): JsonResponse
    {
        try {
            $conversation = PbcConversation::with(['client', 'project', 'participants'])
                                          ->forUser(auth()->id())
                                          ->findOrFail($id);

            $data = [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'client' => $conversation->client ? [
                    'id' => $conversation->client->id,
                    'name' => $conversation->client->name
                ] : null,
                'project' => $conversation->project ? [
                    'id' => $conversation->project->id,
                    'name' => $conversation->project->name,
                    'engagement_type' => $conversation->project->engagement_type ?? 'Project'
                ] : null,
                'status' => $conversation->status,
                'participants' => $conversation->participants->map(function ($participant) {
                    return [
                        'id' => $participant->id,
                        'name' => $participant->name
                    ];
                }),
                'created_at' => $conversation->created_at->toISOString()
            ];

            return $this->success($data, 'Conversation retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to get conversation: ' . $e->getMessage());
            return $this->error('Failed to retrieve conversation', $e->getMessage(), 500);
        }
    }

    /**
     * Get messages for a conversation
     */
    public function getMessages($conversationId): JsonResponse
    {
        try {
            // Verify user has access to conversation
            $conversation = PbcConversation::forUser(auth()->id())->findOrFail($conversationId);

            $messages = PbcMessage::with(['sender:id,name,role', 'replyTo:id,message,sender_id', 'replyTo.sender:id,name'])
                ->forConversation($conversationId)
                ->orderBy('created_at', 'asc')
                ->get();

            $transformedData = $messages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'conversation_id' => $message->conversation_id,
                    'sender_id' => $message->sender_id,
                    'sender' => $message->sender ? [
                        'id' => $message->sender->id,
                        'name' => $message->sender->name
                    ] : ['id' => null, 'name' => 'System'],
                    'message' => $message->message,
                    'attachments' => $message->attachments ?? [],
                    'is_read' => $message->is_read,
                    'created_at' => $message->created_at->toISOString()
                ];
            });

            // Mark messages as read for this user
            PbcMessage::forConversation($conversationId)
                ->where('sender_id', '!=', auth()->id())
                ->where('is_read', false)
                ->update(['is_read' => true, 'read_at' => now()]);

            return $this->success($transformedData, 'Messages retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to get messages: ' . $e->getMessage());
            return $this->error('Failed to retrieve messages', $e->getMessage(), 500);
        }
    }

    /**
     * Send a message
     */
    public function sendMessage(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'conversation_id' => 'required|integer|exists:pbc_conversations,id',
                'message' => 'nullable|string|max:5000',
                'attachments.*' => 'file|max:10240'
            ]);

            // Verify user has access to conversation
            $conversation = PbcConversation::forUser(auth()->id())->findOrFail($request->conversation_id);

            if (!auth()->user()->hasPermission('send_messages')) {
                return $this->forbidden('You do not have permission to send messages');
            }

            // Process attachments
            $attachmentData = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('conversations/' . $request->conversation_id, $filename, 'public');

                    $attachmentData[] = [
                        'name' => $file->getClientOriginalName(),
                        'filename' => $filename,
                        'path' => $path,
                        'size' => $file->getSize(),
                        'type' => $file->getClientOriginalExtension()
                    ];
                }
            }

            // Create message
            $message = PbcMessage::create([
                'conversation_id' => $request->conversation_id,
                'sender_id' => auth()->id(),
                'message' => $request->message,
                'attachments' => $attachmentData,
                'message_type' => empty($attachmentData) ? 'text' : 'file',
                'is_read' => false
            ]);

            // Update conversation last message time
            $conversation->update(['last_message_at' => now()]);

            $message->load('sender:id,name,role');

            $transformedMessage = [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'sender_id' => $message->sender_id,
                'sender' => [
                    'id' => $message->sender->id,
                    'name' => $message->sender->name
                ],
                'message' => $message->message,
                'attachments' => $message->attachments ?? [],
                'is_read' => $message->is_read,
                'created_at' => $message->created_at->toISOString()
            ];

            Log::info('Message sent', [
                'user_id' => auth()->id(),
                'conversation_id' => $request->conversation_id,
                'message_id' => $message->id
            ]);

            return $this->success($transformedMessage, 'Message sent successfully');

        } catch (\Exception $e) {
            Log::error('Failed to send message: ' . $e->getMessage());
            return $this->error('Failed to send message', $e->getMessage(), 500);
        }
    }

    /**
     * Create a new conversation
     */
    public function createConversation(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'client_id' => 'required|integer|exists:clients,id',
                'project_id' => 'required|integer|exists:projects,id',
                'participant_ids' => 'required|array|min:1',
                'participant_ids.*' => 'integer|exists:users,id|distinct',
                'title' => 'nullable|string|max:255'
            ]);

            if (!auth()->user()->hasPermission('send_messages')) {
                return $this->forbidden('You do not have permission to create conversations');
            }

            DB::beginTransaction();
            try {
                // Get client and project for title generation
                $client = Client::findOrFail($request->client_id);
                $project = Project::findOrFail($request->project_id);

                $title = $request->title ?: "{$client->name} - {$project->name}";

                // Create conversation
                $conversation = PbcConversation::create([
                    'client_id' => $request->client_id,
                    'project_id' => $request->project_id,
                    'title' => $title,
                    'created_by' => auth()->id(),
                    'status' => 'active',
                    'last_message_at' => now()
                ]);

                // Add participants (include creator)
                $participantIds = array_unique(array_merge($request->participant_ids, [auth()->id()]));
                $participantData = [];

                foreach ($participantIds as $userId) {
                    $participantData[$userId] = [
                        'joined_at' => now(),
                        'is_active' => true,
                        'role' => $userId === auth()->id() ? 'moderator' : 'participant'
                    ];
                }

                $conversation->participants()->attach($participantData);

                // Create system message
                PbcMessage::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => null,
                    'message' => "Conversation created by " . auth()->user()->name,
                    'message_type' => 'system',
                    'is_read' => false
                ]);

                DB::commit();

                $conversation->load(['client', 'project', 'participants']);

                $transformedConversation = [
                    'id' => $conversation->id,
                    'title' => $conversation->title,
                    'client_id' => $conversation->client_id,
                    'project_id' => $conversation->project_id,
                    'status' => $conversation->status,
                    'created_by' => auth()->id(),
                    'created_at' => $conversation->created_at->toISOString()
                ];

                Log::info('Conversation created', [
                    'user_id' => auth()->id(),
                    'conversation_id' => $conversation->id,
                    'participants' => $participantIds
                ]);

                return $this->success($transformedConversation, 'Conversation created successfully');

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Failed to create conversation: ' . $e->getMessage());
            return $this->error('Failed to create conversation', $e->getMessage(), 500);
        }
    }

    /**
     * Mark conversation as read
     */
    public function markConversationAsRead($conversationId): JsonResponse
    {
        try {
            // Verify user has access to conversation
            $conversation = PbcConversation::forUser(auth()->id())->findOrFail($conversationId);

            // Mark all messages in conversation as read for this user
            PbcMessage::forConversation($conversationId)
                ->where('sender_id', '!=', auth()->id())
                ->where('is_read', false)
                ->update(['is_read' => true, 'read_at' => now()]);

            Log::info('Conversation marked as read', [
                'user_id' => auth()->id(),
                'conversation_id' => $conversationId
            ]);

            return $this->success(null, 'Conversation marked as read');

        } catch (\Exception $e) {
            Log::error('Failed to mark conversation as read: ' . $e->getMessage());
            return $this->error('Failed to mark conversation as read', $e->getMessage(), 500);
        }
    }

    /**
     * Get clients (for dropdown)
     */
    public function getClients(): JsonResponse
    {
        try {
            $clients = Client::where('is_active', true)
                            ->select('id', 'name')
                            ->orderBy('name')
                            ->get();

            return $this->success($clients, 'Clients retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to get clients: ' . $e->getMessage());
            return $this->error('Failed to retrieve clients', $e->getMessage(), 500);
        }
    }

    /**
     * Get projects (for dropdown)
     */
    public function getProjects(): JsonResponse
    {
        try {
            $projects = Project::with('client:id,name')
                              ->select('id', 'name', 'client_id', 'engagement_type', 'engagement_period')
                              ->orderBy('name')
                              ->get();

            $transformedProjects = $projects->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'client_id' => $project->client_id,
                    'engagement_type' => $project->engagement_type ?? 'Project',
                    'engagement_period' => $project->engagement_period
                ];
            });

            return $this->success($transformedProjects, 'Projects retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to get projects: ' . $e->getMessage());
            return $this->error('Failed to retrieve projects', $e->getMessage(), 500);
        }
    }

    /**
     * Get unread message count
     */
    public function getUnreadCount(): JsonResponse
    {
        try {
            $userId = auth()->id();

            // Count unread messages where user is participant but not sender
            $unreadCount = DB::table('pbc_messages as m')
                ->join('pbc_conversations as c', 'm.conversation_id', '=', 'c.id')
                ->join('pbc_conversation_participants as cp', function($join) use ($userId) {
                    $join->on('cp.conversation_id', '=', 'c.id')
                         ->where('cp.user_id', '=', $userId)
                         ->where('cp.is_active', '=', true);
                })
                ->where('m.sender_id', '!=', $userId)
                ->where('m.is_read', false)
                ->whereNull('m.deleted_at')
                ->count();

            return $this->success(['unread_count' => $unreadCount], 'Unread count retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to get unread count: ' . $e->getMessage());
            return $this->error('Failed to retrieve unread count', $e->getMessage(), 500);
        }
    }

    /**
     * Mark specific message as read
     */
    public function markAsRead($messageId): JsonResponse
    {
        try {
            $message = PbcMessage::findOrFail($messageId);

            // Verify user has access to this message's conversation
            $conversation = PbcConversation::forUser(auth()->id())->findOrFail($message->conversation_id);

            if ($message->sender_id !== auth()->id() && !$message->is_read) {
                $message->update(['is_read' => true, 'read_at' => now()]);
            }

            return $this->success(null, 'Message marked as read');

        } catch (\Exception $e) {
            Log::error('Failed to mark message as read: ' . $e->getMessage());
            return $this->error('Failed to mark message as read', $e->getMessage(), 500);
        }
    }

    /**
     * Get conversation statistics
     */
    public function getConversationStats($conversationId): JsonResponse
    {
        try {
            // Verify user has access to conversation
            $conversation = PbcConversation::forUser(auth()->id())->findOrFail($conversationId);

            $stats = [
                'total_messages' => $conversation->messages()->count(),
                'total_attachments' => $conversation->messages()->whereNotNull('attachments')->count(),
                'participants_count' => $conversation->activeParticipants()->count(),
                'unread_count' => $conversation->getUnreadCountForUser(auth()->id()),
                'created_at' => $conversation->created_at,
                'last_activity' => $conversation->last_message_at
            ];

            return $this->success($stats, 'Conversation statistics retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to get conversation stats: ' . $e->getMessage());
            return $this->error('Failed to retrieve conversation statistics', $e->getMessage(), 500);
        }
    }

    /**
     * Search messages within conversation
     */
    public function searchMessages($conversationId, Request $request): JsonResponse
    {
        try {
            // Verify user has access to conversation
            $conversation = PbcConversation::forUser(auth()->id())->findOrFail($conversationId);

            $searchTerm = $request->get('q', '');
            $page = $request->get('page', 1);

            $messages = PbcMessage::with(['sender:id,name,role'])
                ->forConversation($conversationId)
                ->where('message', 'like', "%{$searchTerm}%")
                ->orderBy('created_at', 'desc')
                ->paginate(20, ['*'], 'page', $page);

            return $this->paginated($messages, 'Messages found successfully');

        } catch (\Exception $e) {
            Log::error('Failed to search messages: ' . $e->getMessage());
            return $this->error('Failed to search messages', $e->getMessage(), 500);
        }
    }

    /**
     * Get conversation attachments
     */
    public function getConversationAttachments($conversationId, Request $request): JsonResponse
    {
        try {
            // Verify user has access to conversation
            $conversation = PbcConversation::forUser(auth()->id())->findOrFail($conversationId);

            $page = $request->get('page', 1);

            $messages = PbcMessage::with(['sender:id,name,role'])
                ->forConversation($conversationId)
                ->whereNotNull('attachments')
                ->orderBy('created_at', 'desc')
                ->paginate(20, ['*'], 'page', $page);

            return $this->paginated($messages, 'Attachments retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to get attachments: ' . $e->getMessage());
            return $this->error('Failed to retrieve attachments', $e->getMessage(), 500);
        }
    }
}
