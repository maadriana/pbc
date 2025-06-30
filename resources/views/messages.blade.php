@extends('layouts.app')

@section('title', 'Messages')

@section('page-title', 'Messages')
@section('page-subtitle', 'Communication Center')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm" style="height: calc(100vh - 250px);">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-comments text-primary mr-2"></i>Messages
                </h5>
                <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createConversationModal">
                    <i class="fas fa-plus mr-1"></i>New Conversation
                </button>
            </div>

            <div class="card-body p-0" style="height: 100%;">
                <div class="row no-gutters h-100">
                    <!-- Conversations Sidebar -->
                    <div class="col-md-4 border-right" style="background: #f8f9fa;">
                        <!-- Search Box -->
                        <div class="p-3 border-bottom">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white border-right-0">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                </div>
                                <input type="text" class="form-control border-left-0" placeholder="Search conversations..." id="conversationSearch">
                            </div>
                        </div>

                        <!-- Conversations List -->
                        <div id="conversationsList" style="height: calc(100% - 80px); overflow-y: auto;">
                            <div class="text-center p-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-2 text-muted">Loading conversations...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Chat Area -->
                    <div class="col-md-8 d-flex flex-column">
                        <!-- Welcome State -->
                        <div id="welcomeState" class="d-flex align-items-center justify-content-center h-100 flex-column">
                            <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">Welcome to Messages</h4>
                            <p class="text-muted">Select a conversation to start messaging</p>
                        </div>

                        <!-- Chat Container -->
                        <div id="chatContainer" class="d-flex flex-column h-100" style="display: none !important;">
                            <!-- Chat Header -->
                            <div class="chat-header bg-white border-bottom p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 id="chatTitle" class="mb-0 font-weight-bold"></h6>
                                        <small id="chatSubtitle" class="text-muted"></small>
                                    </div>
                                    <div class="btn-group">
                                        <button class="btn btn-outline-secondary btn-sm" onclick="markAllAsRead()" title="Mark all as read">
                                            <i class="fas fa-check-double"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary btn-sm" onclick="showConversationInfo()" title="Conversation info">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Messages List -->
                            <div class="flex-grow-1 p-3" id="messagesList" style="overflow-y: auto; background: #f8f9fa;">
                                <div class="text-center p-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Loading messages...</p>
                                </div>
                            </div>

                            <!-- Message Input -->
                            <div class="bg-white border-top p-3">
                                <form id="messageForm" enctype="multipart/form-data">
                                    <div class="input-group">
                                        <input type="file" id="attachmentInput" multiple style="display: none" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
                                        <div class="input-group-prepend">
                                            <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('attachmentInput').click()" title="Attach files">
                                                <i class="fas fa-paperclip"></i>
                                            </button>
                                        </div>
                                        <input type="text" class="form-control" id="messageInput" placeholder="Type your message..." maxlength="5000">
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div id="attachmentPreview" class="mt-2"></div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Conversation Modal -->
<div class="modal fade" id="createConversationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle text-primary mr-2"></i>Create New Conversation
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="createConversationForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="clientSelect">
                            <i class="fas fa-building text-muted mr-1"></i>Client *
                        </label>
                        <select id="clientSelect" class="form-control" required>
                            <option value="">Select Client</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="projectSelect">
                            <i class="fas fa-project-diagram text-muted mr-1"></i>Project *
                        </label>
                        <select id="projectSelect" class="form-control" required>
                            <option value="">Select Project</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="participantsSelect">
                            <i class="fas fa-users text-muted mr-1"></i>Participants *
                        </label>
                        <select id="participantsSelect" class="form-control" multiple required style="height: 120px;">
                        </select>
                        <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple participants</small>
                    </div>
                    <div class="form-group">
                        <label for="conversationTitle">
                            <i class="fas fa-tag text-muted mr-1"></i>Custom Title (Optional)
                        </label>
                        <input type="text" id="conversationTitle" class="form-control" placeholder="Leave empty to auto-generate">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        <i class="fas fa-plus mr-1"></i>Create Conversation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Alert Container -->
<div id="alertContainer" style="position: fixed; top: 20px; right: 20px; z-index: 1060; max-width: 300px;"></div>

<style>
.conversation-item {
    padding: 15px;
    border-bottom: 1px solid #e9ecef;
    cursor: pointer;
    transition: all 0.2s ease;
    background: white;
    margin: 2px;
    border-radius: 8px;
}

.conversation-item:hover {
    background: #e3f2fd;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.conversation-item.active {
    background: #2196f3;
    color: white;
    box-shadow: 0 4px 8px rgba(33,150,243,0.3);
}

.conversation-item.active .text-muted {
    color: rgba(255,255,255,0.8) !important;
}

.message {
    margin-bottom: 15px;
    display: flex;
}

.message.own {
    justify-content: flex-end;
}

.message-bubble {
    max-width: 70%;
    padding: 12px 16px;
    border-radius: 18px;
    position: relative;
    word-wrap: break-word;
}

.message.own .message-bubble {
    background: #2196f3;
    color: white;
}

.message:not(.own) .message-bubble {
    background: white;
    border: 1px solid #e9ecef;
    color: #333;
}

.message-meta {
    font-size: 0.75rem;
    margin-top: 5px;
    opacity: 0.7;
}

.unread-badge {
    background: #f44336;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: bold;
}

.attachment-item {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: #2196f3;
    text-decoration: none;
    font-size: 0.875rem;
    margin-top: 5px;
    padding: 4px 8px;
    background: rgba(33,150,243,0.1);
    border-radius: 12px;
    border: 1px solid rgba(33,150,243,0.2);
}

.attachment-item:hover {
    text-decoration: none;
    background: rgba(33,150,243,0.2);
    color: #1976d2;
}

.message.own .attachment-item {
    background: rgba(255,255,255,0.2);
    color: white;
    border-color: rgba(255,255,255,0.3);
}

.message.own .attachment-item:hover {
    background: rgba(255,255,255,0.3);
    color: white;
}

.conversation-preview {
    font-size: 0.875rem;
    color: #6c757d;
    margin-top: 4px;
    line-height: 1.3;
}

#messagesList::-webkit-scrollbar,
#conversationsList::-webkit-scrollbar {
    width: 6px;
}

#messagesList::-webkit-scrollbar-track,
#conversationsList::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

#messagesList::-webkit-scrollbar-thumb,
#conversationsList::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

#messagesList::-webkit-scrollbar-thumb:hover,
#conversationsList::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

.empty-conversations {
    text-align: center;
    padding: 2rem;
    color: #6c757d;
}

.empty-messages {
    text-align: center;
    padding: 2rem;
    color: #6c757d;
}

@media (max-width: 768px) {
    .col-md-4 {
        display: none;
    }

    .col-md-8 {
        flex: 0 0 100%;
        max-width: 100%;
    }

    .show-conversations .col-md-4 {
        display: block;
        flex: 0 0 100%;
        max-width: 100%;
    }

    .show-conversations .col-md-8 {
        display: none;
    }
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Global variables
let currentConversationId = null;
let conversations = [];
let users = [];
let clients = [];
let projects = [];
const currentUserId = {{ auth()->id() }};

// CSRF token setup
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Initialize the application
$(document).ready(function() {
    console.log('Messages app initializing...');
    loadInitialData();
    setupEventListeners();
    startAutoRefresh();
});

// Load initial data
async function loadInitialData() {
    try {
        console.log('Loading initial data...');
        await Promise.all([
            loadConversations(),
            loadUsers(),
            loadClients(),
            loadProjects()
        ]);
        populateCreateConversationForm();
        console.log('Initial data loaded successfully');
    } catch (error) {
        console.error('Error loading initial data:', error);
        showAlert('Failed to load initial data: ' + error.message, 'danger');
    }
}

// Load conversations
async function loadConversations() {
    try {
        console.log('Loading conversations...');
        const response = await $.get('/api/v1/messages/conversations');
        console.log('Conversations response:', response);

        if (response.success) {
            conversations = response.data;
            renderConversations();
        } else {
            throw new Error(response.message || 'Failed to load conversations');
        }
    } catch (error) {
        console.error('Error loading conversations:', error);
        $('#conversationsList').html(`
            <div class="empty-conversations">
                <i class="fas fa-exclamation-triangle text-warning fa-2x mb-2"></i>
                <h6>Failed to load conversations</h6>
                <button class="btn btn-sm btn-primary mt-2" onclick="loadConversations()">
                    <i class="fas fa-redo mr-1"></i>Retry
                </button>
            </div>
        `);
    }
}

// Load users
async function loadUsers() {
    try {
        const response = await $.get('/api/v1/messages/available-users');
        if (response.success) {
            users = response.data;
            console.log('Users loaded:', users.length);
        }
    } catch (error) {
        console.error('Error loading users:', error);
    }
}

// Load clients
async function loadClients() {
    try {
        const response = await $.get('/api/v1/clients');
        if (response.success) {
            clients = response.data.data || response.data;
            console.log('Clients loaded:', clients.length);
        }
    } catch (error) {
        console.error('Error loading clients:', error);
    }
}

// Load projects
async function loadProjects() {
    try {
        const response = await $.get('/api/v1/projects');
        if (response.success) {
            projects = response.data.data || response.data;
            console.log('Projects loaded:', projects.length);
        }
    } catch (error) {
        console.error('Error loading projects:', error);
    }
}

// Render conversations
function renderConversations() {
    const container = $('#conversationsList');

    if (conversations.length === 0) {
        container.html(`
            <div class="empty-conversations">
                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                <h6>No conversations yet</h6>
                <p class="small text-muted">Start a new conversation with your team</p>
                <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createConversationModal">
                    <i class="fas fa-plus mr-1"></i>Start Conversation
                </button>
            </div>
        `);
        return;
    }

    const html = conversations.map(conv => {
        const lastMessage = conv.last_message ?
            (conv.last_message.message || 'File attachment') :
            'No messages yet';
        const timeAgo = conv.last_message_at ?
            formatTimeAgo(conv.last_message_at) : '';
        const unreadBadge = conv.unread_count > 0 ?
            `<span class="unread-badge">${conv.unread_count}</span>` : '';

        return `
            <div class="conversation-item" onclick="selectConversation(${conv.id})" data-id="${conv.id}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="d-block">${conv.title || (conv.client?.name + ' - ' + conv.project?.name)}</strong>
                            ${unreadBadge}
                        </div>
                        <div class="conversation-preview">${truncate(lastMessage, 60)}</div>
                        <small class="text-muted">
                            <i class="fas fa-building mr-1"></i>${conv.client?.name} •
                            <i class="fas fa-project-diagram mr-1"></i>${conv.project?.engagement_type || 'Project'}
                        </small>
                    </div>
                    <small class="text-muted ml-2">${timeAgo}</small>
                </div>
            </div>
        `;
    }).join('');

    container.html(html);
}

// Select conversation
async function selectConversation(conversationId) {
    console.log('Selecting conversation:', conversationId);
    currentConversationId = conversationId;

    // Update UI
    $('.conversation-item').removeClass('active');
    $(`.conversation-item[data-id="${conversationId}"]`).addClass('active');

    // Show chat container, hide welcome
    $('#welcomeState').hide();
    $('#chatContainer').show();

    // Load conversation details and messages
    await loadConversationDetails(conversationId);
    await loadMessages(conversationId);

    // Mark as read
    markConversationAsRead(conversationId);
}

// Load conversation details
async function loadConversationDetails(conversationId) {
    try {
        const response = await $.get(`/api/v1/messages/conversations/${conversationId}`);
        if (response.success) {
            const conv = response.data;
            $('#chatTitle').text(conv.title || `${conv.client?.name} - ${conv.project?.name}`);
            $('#chatSubtitle').text(`${conv.participants?.length || 0} participants • ${conv.status}`);
        }
    } catch (error) {
        console.error('Error loading conversation details:', error);
    }
}

// Load messages
async function loadMessages(conversationId) {
    try {
        $('#messagesList').html(`
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">Loading messages...</p>
            </div>
        `);

        const response = await $.get(`/api/v1/messages/conversations/${conversationId}/messages`);
        if (response.success) {
            renderMessages(response.data);
            scrollToBottom();
        }
    } catch (error) {
        console.error('Error loading messages:', error);
        $('#messagesList').html(`
            <div class="empty-messages">
                <i class="fas fa-exclamation-circle text-danger fa-2x mb-2"></i>
                <h6>Failed to load messages</h6>
                <button class="btn btn-sm btn-primary mt-2" onclick="loadMessages(${conversationId})">
                    <i class="fas fa-redo mr-1"></i>Retry
                </button>
            </div>
        `);
    }
}

// Render messages
function renderMessages(messages) {
    const container = $('#messagesList');

    if (messages.length === 0) {
        container.html(`
            <div class="empty-messages">
                <i class="fas fa-comment fa-2x text-muted mb-3"></i>
                <h6>No messages yet</h6>
                <p class="text-muted">Start the conversation!</p>
            </div>
        `);
        return;
    }

    const html = messages.map(msg => {
        const isOwn = msg.sender_id == currentUserId;
        const senderName = msg.sender?.name || 'System';
        const timeAgo = formatTimeAgo(msg.created_at);

        let attachmentsHtml = '';
        if (msg.attachments && msg.attachments.length > 0) {
            attachmentsHtml = msg.attachments.map(att => `
                <div class="mt-2">
                    <a href="#" class="attachment-item" onclick="downloadAttachment(${currentConversationId}, ${msg.id}, '${att.id}')">
                        <i class="fas fa-paperclip"></i>
                        ${att.name}
                    </a>
                </div>
            `).join('');
        }

        return `
            <div class="message ${isOwn ? 'own' : ''}">
                <div class="message-bubble">
                    ${msg.message ? `<div>${escapeHtml(msg.message)}</div>` : ''}
                    ${attachmentsHtml}
                    <div class="message-meta">
                        ${isOwn ? '' : senderName + ' • '}${timeAgo}
                    </div>
                </div>
            </div>
        `;
    }).join('');

    container.html(html);
}

// Send message
async function sendMessage(messageText, attachments = []) {
    if (!currentConversationId) return;
    if (!messageText.trim() && attachments.length === 0) return;

    try {
        const formData = new FormData();
        formData.append('conversation_id', currentConversationId);
        if (messageText.trim()) {
            formData.append('message', messageText.trim());
        }

        attachments.forEach((file, index) => {
            formData.append(`attachments[${index}]`, file);
        });

        // Disable form
        $('#messageInput').prop('disabled', true);
        $('button[type="submit"]').prop('disabled', true);

        const response = await $.ajax({
            url: '/api/v1/messages/send',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false
        });

        if (response.success) {
            // Reload messages and conversations
            await loadMessages(currentConversationId);
            await loadConversations();

            // Clear form
            $('#messageInput').val('').prop('disabled', false);
            $('#attachmentInput').val('');
            $('#attachmentPreview').empty();
            $('button[type="submit"]').prop('disabled', false);

            showAlert('Message sent successfully', 'success', 2000);
        }
    } catch (error) {
        console.error('Error sending message:', error);
        showAlert('Failed to send message', 'danger');

        // Re-enable form
        $('#messageInput').prop('disabled', false);
        $('button[type="submit"]').prop('disabled', false);
    }
}

// Mark conversation as read
async function markConversationAsRead(conversationId) {
    try {
        await $.ajax({
            url: `/api/v1/messages/conversations/${conversationId}/read-all`,
            method: 'PUT'
        });
        await loadConversations();
    } catch (error) {
        console.error('Error marking conversation as read:', error);
    }
}

// Mark all as read
async function markAllAsRead() {
    if (!currentConversationId) return;
    await markConversationAsRead(currentConversationId);
    showAlert('All messages marked as read', 'success', 2000);
}

// Setup event listeners
function setupEventListeners() {
    // Message form
    $('#messageForm').on('submit', function(e) {
        e.preventDefault();
        const messageText = $('#messageInput').val();
        const attachments = Array.from(document.getElementById('attachmentInput').files);
        sendMessage(messageText, attachments);
    });

    // Enter to send
    $('#messageInput').on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            $('#messageForm').submit();
        }
    });

    // Attachment preview
    $('#attachmentInput').on('change', function() {
        const files = Array.from(this.files);
        const preview = $('#attachmentPreview');

        if (files.length > 0) {
            const html = files.map(file => `
                <span class="badge badge-secondary mr-1">
                    <i class="fas fa-paperclip"></i> ${file.name}
                </span>
            `).join('');
            preview.html(`<div class="mt-2">Attachments: ${html}</div>`);
        } else {
            preview.empty();
        }
    });

    // Search conversations
    $('#conversationSearch').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.conversation-item').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(searchTerm));
        });
    });

    // Create conversation form
    $('#createConversationForm').on('submit', async function(e) {
        e.preventDefault();

        const formData = {
            client_id: $('#clientSelect').val(),
            project_id: $('#projectSelect').val(),
            participant_ids: $('#participantsSelect').val(),
            title: $('#conversationTitle').val()
        };

        const submitBtn = $(this).find('button[type="submit"]');
        const spinner = submitBtn.find('.spinner-border');
        spinner.removeClass('d-none');
        submitBtn.prop('disabled', true);

        try {
            const response = await $.post('/api/v1/messages/conversations', formData);
            if (response.success) {
                $('#createConversationModal').modal('hide');
                await loadConversations();
                selectConversation(response.data.id);
                showAlert('Conversation created successfully!', 'success');
                this.reset();
            }
        } catch (error) {
            console.error('Error creating conversation:', error);
            const errorMsg = error.responseJSON?.message || 'Failed to create conversation';
            showAlert(errorMsg, 'danger');
        } finally {
            spinner.addClass('d-none');
            submitBtn.prop('disabled', false);
        }
    });

    // Client selection
    $('#clientSelect').on('change', function() {
        const clientId = $(this).val();
        filterProjects(clientId);
    });

    // Modal reset
    $('#createConversationModal').on('hidden.bs.modal', function() {
        $('#createConversationForm')[0].reset();
        filterProjects('');
    });
}

// Populate create form
function populateCreateConversationForm() {
    // Clients
    const clientOptions = clients.map(client =>
        `<option value="${client.id}">${client.name}</option>`
    ).join('');
    $('#clientSelect').html('<option value="">Select Client</option>' + clientOptions);

    // Projects
    const projectOptions = projects.map(project => {
        const projectName = `${project.engagement_type || 'Project'} - ${project.engagement_period || 'No Date'}`;
        const clientName = clients.find(c => c.id === project.client_id)?.name || 'Unknown Client';
        return `<option value="${project.id}" data-client="${project.client_id}">${projectName} (${clientName})</option>`;
    }).join('');
    $('#projectSelect').html('<option value="">Select Project</option>' + projectOptions);

    // Users
    const userOptions = users.map(user =>
        `<option value="${user.id}">${user.name} (${user.role.replace('_', ' ')})</option>`
    ).join('');
    $('#participantsSelect').html(userOptions);
}

// Filter projects by client
function filterProjects(clientId) {
    $('#projectSelect option').each(function() {
        const projectClientId = $(this).data('client');
        if (!clientId || projectClientId == clientId || !projectClientId) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
    $('#projectSelect').val('');
}

// Show conversation info
function showConversationInfo() {
    if (!currentConversationId) return;

    const conv = conversations.find(c => c.id === currentConversationId);
    if (conv) {
        const participants = conv.participants?.map(p => p.name).join(', ') || 'None';
        const info = `
            <strong>Title:</strong> ${conv.title || 'Auto-generated'}<br>
            <strong>Client:</strong> ${conv.client?.name}<br>
            <strong>Project:</strong> ${conv.project?.name}<br>
            <strong>Status:</strong> ${conv.status}<br>
            <strong>Participants:</strong> ${participants}<br>
            <strong>Created:</strong> ${formatDate(conv.created_at)}
        `;
