@extends('layouts.app')

@section('title', 'Messages')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-comments mr-2"></i>Messages
                    </h3>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#createConversationModal">
                        <i class="fas fa-plus mr-1"></i>New Conversation
                    </button>
                </div>
                <div class="card-body">
                    @if(\Schema::hasTable('pbc_conversations'))
                        @php
                            try {
                                $conversations = \App\Models\PbcConversation::with(['client', 'project', 'lastMessage', 'participants'])
                                    ->forUser(auth()->id())
                                    ->orderBy('last_message_at', 'desc')
                                    ->limit(10)
                                    ->get();
                            } catch (\Exception $e) {
                                $conversations = collect();
                                $error = $e->getMessage();
                            }
                        @endphp

                        @if(isset($error))
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>Database Error:</strong> {{ $error }}
                                <hr>
                                <small>This usually means the message system tables need to be set up. Please run the migrations first.</small>
                            </div>
                        @elseif($conversations->count() > 0)
                            <div class="list-group">
                                @foreach($conversations as $conversation)
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">
                                            {{ $conversation->title ?: ($conversation->client->name ?? 'Unknown Client') . ' - ' . ucfirst($conversation->project->engagement_type ?? 'Unknown') . ' ' . ($conversation->project->engagement_period ? $conversation->project->engagement_period->format('Y') : '') }}
                                        </h5>
                                        <small>{{ $conversation->last_message_at ? $conversation->last_message_at->diffForHumans() : 'No messages yet' }}</small>
                                    </div>
                                    <p class="mb-1">
                                        @if($conversation->lastMessage)
                                            {{ Str::limit($conversation->lastMessage->message, 100) }}
                                        @else
                                            <em>No messages yet</em>
                                        @endif
                                    </p>
                                    <small>
                                        <strong>Project Type:</strong> {{ ucfirst($conversation->project->engagement_type ?? 'Unknown') }}
                                        <strong>Period:</strong> {{ $conversation->project->engagement_period ? $conversation->project->engagement_period->format('M Y') : 'Not set' }}
                                        <br>
                                        <strong>Participants:</strong>
                                        {{ $conversation->participants->pluck('name')->join(', ') }}
                                        <span class="badge badge-{{ $conversation->status === 'active' ? 'success' : 'secondary' }} ml-2">
                                            {{ ucfirst($conversation->status) }}
                                        </span>
                                    </small>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                                <h4>No conversations yet</h4>
                                <p class="text-muted">Start a new conversation with your team members</p>
                                <button class="btn btn-primary" data-toggle="modal" data-target="#createConversationModal">
                                    <i class="fas fa-plus mr-1"></i>Start First Conversation
                                </button>
                            </div>
                        @endif
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Message system not set up yet.</strong> Please run the database migrations first.
                            <hr>
                            <small>Run: <code>php artisan migrate</code> and <code>php artisan db:seed --class=MessageSeeder</code></small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Create Conversation Modal --}}
<div class="modal fade" id="createConversationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Conversation</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ url('/api/v1/messages/conversations') }}" method="POST" id="createConversationForm">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="client_id">Client *</label>
                        <select name="client_id" id="client_id" class="form-control" required>
                            <option value="">Select Client</option>
                            @if(\Schema::hasTable('clients'))
                                @foreach(\App\Models\Client::orderBy('name')->get() as $client)
                                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="project_id">Project *</label>
                        <select name="project_id" id="project_id" class="form-control" required>
                            <option value="">Select Project</option>
                            @if(\Schema::hasTable('projects'))
                                @php
                                    try {
                                        $projects = \App\Models\Project::with('client')->get();
                                    } catch (\Exception $e) {
                                        $projects = collect();
                                    }
                                @endphp
                                @foreach($projects as $project)
                                    @php
                                        $projectDisplay = ucfirst($project->engagement_type) . ' - ' . ($project->engagement_period ? $project->engagement_period->format('M Y') : 'No Date');
                                        $clientName = $project->client->name ?? 'Unknown Client';
                                    @endphp
                                    <option value="{{ $project->id }}" data-client="{{ $project->client_id }}">
                                        {{ $projectDisplay }} ({{ $clientName }})
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="participants">Participants *</label>
                        <select name="participant_ids[]" id="participants" class="form-control" multiple required>
                            @foreach(\App\Models\User::where('is_active', true)->where('id', '!=', auth()->id())->orderBy('name')->get() as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ ucfirst(str_replace('_', ' ', $user->role)) }})</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple participants</small>
                    </div>

                    <div class="form-group">
                        <label for="title">Custom Title (Optional)</label>
                        <input type="text" name="title" id="title" class="form-control" placeholder="Leave empty to auto-generate">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Conversation</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Filter projects by selected client
$('#client_id').change(function() {
    var clientId = $(this).val();
    $('#project_id option').each(function() {
        var projectClientId = $(this).data('client');
        if (clientId === '' || projectClientId == clientId || projectClientId === undefined) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
    $('#project_id').val('');
});

// Handle form submission
$('#createConversationForm').submit(function(e) {
    e.preventDefault();

    var formData = $(this).serialize();

    $.ajax({
        url: $(this).attr('action'),
        method: 'POST',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        },
        success: function(response) {
            if (response.success) {
                alert('Conversation created successfully!');
                $('#createConversationModal').modal('hide');
                location.reload(); // Refresh to show new conversation
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr) {
            var errors = xhr.responseJSON?.errors || {};
            var errorMessage = 'Error creating conversation:\n';
            for (var field in errors) {
                errorMessage += '- ' + errors[field].join('\n- ') + '\n';
            }
            alert(errorMessage);
        }
    });
});
</script>
@endsection
