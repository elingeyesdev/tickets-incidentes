<!-- DIRECT CHAT PRIMARY -->
<div class="card card-primary card-outline direct-chat direct-chat-primary" id="ticket-chat-card">
    <div class="card-header">
        <h3 class="card-title">Direct Chat</h3>

        <div class="card-tools">
            <span title="0 New Messages" class="badge bg-primary" id="chat-msg-count">0</span>
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <!-- /.card-header -->
    <div class="card-body">
        <!-- Conversations are loaded here -->
        <div class="direct-chat-messages" style="height: 550px;" id="chat-messages-list">
            <!-- Messages will be injected here via jQuery -->
            <div class="text-center text-muted py-5">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p class="mt-2">Cargando conversación...</p>
            </div>
        </div>
        <!--/.direct-chat-messages-->
    </div>
    <!-- /.card-body -->
    <div class="card-footer">
        <form id="chat-form" action="#" method="post">
            <!-- Attachments preview section -->
            <div id="chat-attachments-preview" style="margin-bottom: 10px; display: flex; gap: 8px; flex-wrap: wrap;">
                <!-- Previews will be injected here -->
            </div>
            <!-- End attachments preview -->
            
            <div class="input-group">
                <span class="input-group-prepend" style="display: flex; flex-direction: column; justify-content: flex-start; align-items: stretch;">
                    <button class="btn btn-light border" type="button" id="btn-attach-file" data-toggle="tooltip" title="Adjuntar Archivo" style="border-color: #ced4da; background-color: #e9ecef; color: #495057; flex-shrink: 0; height: 38px; padding: 0 12px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-paperclip"></i>
                    </button>
                    <input type="file" id="chat-file-input" multiple style="display: none;" accept=".pdf,.txt,.doc,.docx,.xls,.xlsx,.csv,.jpg,.jpeg,.png,.gif">
                </span>
                <textarea name="message" id="chat-message-input" placeholder="Escribe un mensaje..." class="form-control" rows="1" style="resize: none; height: 38px; line-height: 24px; min-height: 38px; max-height: 150px; overflow-y: auto;"></textarea>
                <span class="input-group-append" style="display: flex; flex-direction: column; justify-content: flex-start; align-items: stretch;">
                    <button type="submit" class="btn btn-primary" id="btn-send-message" style="white-space: nowrap; flex-shrink: 0; height: 38px; padding: 0 20px; display: flex; align-items: center; justify-content: center;">Enviar</button>
                </span>
            </div>
        </form>
    </div>
    <!-- /.card-footer-->
</div>
<!--/.direct-chat -->

<script>
(function() {
    console.log('[Ticket Chat] Script loaded - waiting for jQuery...');
    console.log('[Ticket Chat] Version: Optimistic UI with Event-Driven Architecture');

    function initTicketChat() {
        console.log('[Ticket Chat] jQuery available - Initializing');

        // ==============================================================
        // CONFIGURATION & STATE
        // ==============================================================
        const $chatCard = $('#ticket-chat-card');
        const $msgList = $('#chat-messages-list');
        const $form = $('#chat-form');
        const $input = $('#chat-message-input'); // Updated selector for textarea
        const $fileInput = $('#chat-file-input');
        const $previewContainer = $('#chat-attachments-preview');
        const $btnAttach = $('#btn-attach-file');
        const $msgCount = $('#chat-msg-count');

        let currentTicketCode = null;
        let currentTicketStatus = null; // Track ticket status for validations
        let selectedFiles = [];
        let editingMessageId = null; // Track if we're editing a message
        let editingMessageContent = null; // Original content backup

        // 🔴 GLOBAL SPAM PROTECTION: Track all operations in progress
        // Format: "operation:id" (e.g., "send:newMsg", "update:123", "delete:456", "deleteAtt:789")
        const operationsInProgress = new Set();

        // Helper function to check if operation is allowed
        function canPerformOperation(operationType, operationId) {
            const key = `${operationType}:${operationId}`;
            if (operationsInProgress.has(key)) {
                console.log(`[Chat] Operation blocked (already in progress): ${key}`);
                return false;
            }
            operationsInProgress.add(key);
            return true;
        }

        // Helper function to mark operation as complete
        function completeOperation(operationType, operationId) {
            const key = `${operationType}:${operationId}`;
            operationsInProgress.delete(key);
            console.log(`[Chat] Operation completed: ${key}`);
        }

        // ==============================================================
        // INITIALIZATION
        // ==============================================================
        
        // Validation Configuration
        if (typeof $.fn.validate !== 'undefined') {
            $form.validate({
                rules: {
                    message: {
                        // 🔴 CUSTOM: Only required if no files attached
                        // If files exist, empty message is allowed (will auto-fill with "Archivo adjunto.")
                        required: function(element) {
                            return selectedFiles.length === 0; // Required only if NO files
                        },
                        maxlength: 5000
                    }
                },
                messages: {
                    message: {
                        required: "Debes escribir un mensaje o adjuntar un archivo.",
                        maxlength: "El mensaje no puede exceder 5000 caracteres."
                    }
                },
                errorElement: 'span',
                errorClass: 'invalid-feedback',
                errorPlacement: function(error, element) {
                    error.addClass('invalid-feedback');
                    element.closest('.input-group').append(error);
                },
                highlight: function(element, errorClass, validClass) {
                    $(element).addClass('is-invalid');
                },
                unhighlight: function(element, errorClass, validClass) {
                    $(element).removeClass('is-invalid');
                },
                // 🔴 FIX: Disable automatic validation during typing/blur
                // Only validate on form submission to avoid unwanted errors while editing
                onkeyup: false,
                onfocusout: false,
                onsubmit: true,
                submitHandler: function(form) {
                    let content = $input.val().trim();

                    // If we're editing, call updateMessage instead of sending new message
                    if (editingMessageId) {
                        if (!content) {
                            $(document).Toasts('create', {
                                class: 'bg-warning',
                                title: 'Advertencia',
                                body: 'El mensaje no puede estar vacío.',
                                autohide: true,
                                delay: 2000
                            });
                            return false;
                        }
                        updateMessage(editingMessageId, content);
                    } else {
                        // 🔴 FIX #2: If no message but has attachments, auto-add "Archivo adjunto."
                        if (!content && selectedFiles.length > 0) {
                            console.log('[Chat] No message but has attachments - auto-adding "Archivo adjunto."');
                            content = 'Archivo adjunto.';
                        }

                        // Send new message via AJAX (no spam protection here, handled inside sendMessage)
                        sendMessage(content);
                    }

                    return false; // Prevent actual form submission
                }
            });
        }

        // ==============================================================
        // TEXTAREA HANDLERS: Validation Cleanup + Auto-grow (Combined)
        // ==============================================================

        $input.on('input', function() {
            // 🔴 FIX #1: Auto-clean validation errors when user starts typing
            if ($input.hasClass('is-invalid')) {
                const validator = $form.validate();
                validator.element($input); // Re-validate the element immediately
                console.log('[Chat] Validation error cleared on input');
            }

            // 🔴 FIX: AUTO-GROW TEXTAREA (AdminLTE v3 Pattern)
            const minHeight = 38; // Mínimo 38px (1 línea)
            const maxHeight = 150; // Máximo 150px

            // Reset height to auto to get scrollHeight
            $(this).css('height', 'auto');

            // Get the scrollHeight
            let newHeight = this.scrollHeight;

            // Apply min and max constraints
            newHeight = Math.max(newHeight, minHeight); // No less than 38px
            newHeight = Math.min(newHeight, maxHeight); // No more than 150px

            $(this).css('height', newHeight + 'px');

            console.log(`[Chat] Textarea height: ${newHeight}px | Validation cleared: ${$input.hasClass('is-invalid') ? 'NO' : 'YES'}`);
        });

        // ==============================================================
        // EVENTS
        // ==============================================================

        // 1. Load Chat (Triggered externally)
        $(document).on('tickets:view-details', function(e, ticketId) {
            // We need the ticket CODE for API calls, but ID is passed.
            // We'll wait for the main detail view to fetch the ticket and trigger a 'chat:ready' event
            // OR we can fetch it ourselves if we only have ID.
            // Better approach: The main detail view fetches the ticket object. 
            // Let's modify the main view to trigger 'tickets:data-loaded' with the full object.
            // For now, let's assume we can get the code from the DOM or wait for a specific event.
            // TEMPORARY: Fetch ticket again to get code (or rely on main view to set a global/DOM attr)
            // Let's use a custom event that passes the full ticket object.
        });

        // Better: Listen for a specific event passing the full ticket
        $(document).on('tickets:details-loaded', function(e, ticket) {
            currentTicketCode = ticket.ticket_code;
            currentTicketStatus = ticket.status; // Store status for validations
            cancelEdit(); // Reset editing state when switching tickets
            loadMessages();
        });



        // 2. Attach File Click
        $btnAttach.on('click', function() {
            $fileInput.click();
        });

        // 3. File Selected
        $fileInput.on('change', function() {
            const files = Array.from(this.files);
            
            // Validate Size (10MB) and Count (Max 5)
            const maxBytes = 10 * 1024 * 1024;
            if (selectedFiles.length + files.length > 5) {
                $(document).Toasts('create', {
                    class: 'bg-danger',
                    title: 'Error',
                    body: 'Máximo 5 archivos permitidos.'
                });
                return;
            }

            files.forEach(file => {
                if (file.size > maxBytes) {
                    $(document).Toasts('create', {
                        class: 'bg-danger',
                        title: 'Error',
                        body: `El archivo ${file.name} excede el límite de 10MB.`
                    });
                    return;
                }
                // Check duplicates
                if (!selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                    selectedFiles.push(file);
                }
            });

            renderFilePreviews();
            // Reset input to allow selecting same file again if needed
            $(this).val('');
        });

        // 4. Arrow Up to Edit Last Message
        $input.on('keydown', function(e) {
            // Escape to Cancel Edit
            if (e.key === 'Escape' && editingMessageId) {
                e.preventDefault();
                cancelEdit();
                return;
            }

            // Enter to Send (Shift+Enter for new line)
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                $form.submit();
                return;
            }

            // Arrow Up to Edit Last Message (only if input is empty and NOT editing)
            if (e.key === 'ArrowUp' && $(this).val().trim() === '' && !editingMessageId) {
                e.preventDefault();
                editLastOwnMessage();
            }
        });

        // 5. Message Actions (Edit/Delete) - Delegated Events
        $msgList.on('click', '.btn-edit-message', function() {
            const msgId = $(this).data('msg-id');
            const msgContent = $(this).data('msg-content');
            startEditMessage(msgId, msgContent);
        });

        $msgList.on('click', '.btn-delete-message', function() {
            const msgId = $(this).data('msg-id');
            confirmDeleteMessage(msgId);
        });

        $msgList.on('click', '.btn-delete-attachment', function() {
            const attId = $(this).data('att-id');
            const attName = $(this).data('att-name');
            const msgId = $(this).data('msg-id');
            const msgContent = $(this).data('msg-content');
            confirmDeleteAttachment(attId, attName, msgId, msgContent);
        });

        // 6. Confirm Edit Button
        $(document).on('click', '#btn-confirm-edit', function(e) {
            e.preventDefault();

            // Si estamos editando un mensaje, actualizar ese mensaje
            if (editingMessageId) {
                const content = $input.val();
                if (!content.trim()) {
                    $(document).Toasts('create', {
                        class: 'bg-warning',
                        title: 'Advertencia',
                        body: 'El mensaje no puede estar vacío.',
                        autohide: true,
                        delay: 2000
                    });
                    return;
                }
                updateMessage(editingMessageId, content);
            } else {
                // Si es un mensaje nuevo, enviar normalmente
                $form.submit();
            }
        });

        // 7. Cancel Edit Button
        $(document).on('click', '#btn-cancel-edit', function(e) {
            e.preventDefault();
            cancelEdit();
        });

        // 8. Form Submit Handler
        // NOTE: Form submission is now handled by jQuery Validation plugin's submitHandler
        // This ensures validation only happens on submit, not during editing/typing
        // The submitHandler in the validate() config above handles routing to either
        // updateMessage() for edits or sendMessage() for new messages

        // ==============================================================
        // CORE FUNCTIONS
        // ==============================================================

        async function loadMessages() {
            $msgList.html(`
                <div class="text-center text-muted py-5">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">Cargando mensajes...</p>
                </div>
            `);

            try {
                const token = window.tokenManager.getAccessToken();
                
                // Manual JWT Parse (Stateless Safe)
                let currentUserId = null;
                try {
                    const base64Url = token.split('.')[1];
                    const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
                    const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
                        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
                    }).join(''));
                    const payload = JSON.parse(jsonPayload);
                    currentUserId = payload.sub; // Standard JWT Subject
                } catch (e) {
                    console.error('[Ticket Chat] Error parsing token:', e);
                }

                const response = await $.ajax({
                    url: `/api/tickets/${currentTicketCode}/responses`,
                    method: 'GET',
                    headers: { 'Authorization': `Bearer ${token}` }
                });

                renderMessages(response.data, currentUserId);

            } catch (error) {
                console.error('[Ticket Chat] Error loading messages:', error);
                $msgList.html(`
                    <div class="text-center text-danger py-5">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                        <p class="mt-2">Error al cargar la conversación.</p>
                    </div>
                `);
            }
        }

        function renderMessages(messages, currentUserId) {
            $msgList.empty();
            $msgCount.text(messages.length);

            if (messages.length === 0) {
                $msgList.html('<p class="text-center text-muted py-5">No hay mensajes aún. ¡Sé el primero en escribir!</p>');
                return;
            }

            // Use renderSingleMessage for each message
            messages.forEach(msg => {
                const html = renderSingleMessage(msg, currentUserId, false);
                $msgList.append(html);
            });

            // Show actions on hover for messages
            $('.direct-chat-msg').hover(
                function() {
                    $(this).find('.message-actions').css('opacity', '1');
                },
                function() {
                    $(this).find('.message-actions').css('opacity', '0');
                }
            );

            // Show delete button on hover for attachments (delegated)
            $msgList.on('mouseenter', '.attachment-card', function() {
                $(this).find('.btn-delete-attachment').css('opacity', '1');
            });

            $msgList.on('mouseleave', '.attachment-card', function() {
                $(this).find('.btn-delete-attachment').css('opacity', '0');
            });

            // Highlight delete button on hover
            $msgList.on('mouseenter', '.btn-delete-attachment', function() {
                $(this).css({
                    'border-color': '#dc3545',
                    'color': '#dc3545',
                    'background-color': 'rgba(220, 53, 69, 0.1)'
                });
            });

            $msgList.on('mouseleave', '.btn-delete-attachment', function() {
                $(this).css({
                    'border-color': 'rgba(220, 53, 69, 0.4)',
                    'color': 'rgba(220, 53, 69, 0.6)',
                    'background-color': 'transparent'
                });
            });

            // Scroll to bottom
            $msgList.scrollTop($msgList[0].scrollHeight);
        }

        async function sendMessage(content) {
            // 🔴 OPTIMISTIC UI: Generate temporary ID FIRST for spam protection
            const tempId = `temp-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;

            // 🔴 SPAM PROTECTION: Check if send operation for THIS specific message is in progress
            // Use unique tempId instead of fixed 'newMsg' to allow message queuing
            if (!canPerformOperation('send', tempId)) {
                console.log(`[Chat] Send blocked - message ${tempId} is already being sent`);
                return;
            }

            const currentUserInfo = getCurrentUserInfo();

            // Create temporary message object (minimal, will be replaced with real data from API)
            const tempMessage = {
                id: tempId,
                content: content,
                created_at: new Date().toISOString(),
                author_id: currentUserInfo.id,
                user_id: currentUserInfo.id,
                author_type: 'user',
                attachments: []
            };

            // Add message to DOM with pending state (opacity 40%)
            addMessageToDOM(tempMessage, [], true);

            // Backup content in case of error
            const contentBackup = content;
            const filesBackup = [...selectedFiles];

            // Clear form immediately for better UX (allows sending next message)
            $input.val('');
            $input.css('height', '38px');
            selectedFiles = [];
            renderFilePreviews();

            try {
                const token = window.tokenManager.getAccessToken();

                // 1. Create Response
                const responseData = await $.ajax({
                    url: `/api/tickets/${currentTicketCode}/responses`,
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${token}` },
                    data: { content: content }
                });

                const responseId = responseData.data.id;
                const uploadedAttachments = [];

                // 2. Upload Attachments (if any)
                if (filesBackup.length > 0) {
                    for (const file of filesBackup) {
                        const formData = new FormData();
                        formData.append('file', file);

                        try {
                            const attResponse = await $.ajax({
                                url: `/api/tickets/${currentTicketCode}/responses/${responseId}/attachments`,
                                method: 'POST',
                                headers: { 'Authorization': `Bearer ${token}` },
                                data: formData,
                                processData: false,
                                contentType: false
                            });

                            // Capture uploaded attachment data
                            if (attResponse.data) {
                                uploadedAttachments.push(attResponse.data);
                            }
                        } catch (uploadError) {
                            console.error('Error uploading file:', file.name, uploadError);
                            $(document).Toasts('create', {
                                class: 'bg-warning',
                                title: 'Advertencia',
                                body: `No se pudo subir el archivo: ${file.name}`
                            });
                        }
                    }
                }

                // 🔴 OPTIMISTIC UI: Replace temp message with real message
                $(`.direct-chat-msg[data-msg-id="${tempId}"]`).remove();

                // Add real message with attachments (no pending state)
                // Note: Counter already incremented when temp message was added, don't increment again
                const currentUserId = getCurrentUserId();
                responseData.data.attachments = uploadedAttachments;

                const html = renderSingleMessage(responseData.data, currentUserId, false);
                $msgList.append(html);

                // Bind hover events for new message
                const $newMsg = $(`.direct-chat-msg[data-msg-id="${responseData.data.id}"]`);
                $newMsg.hover(
                    function() {
                        $(this).find('.message-actions').css('opacity', '1');
                    },
                    function() {
                        $(this).find('.message-actions').css('opacity', '0');
                    }
                );

                // Scroll to bottom
                $msgList.scrollTop($msgList[0].scrollHeight);

                // 🔴 Emit Event for other components (Payload Response Strategy)
                // Let the event handlers update counters - NO local counter updates here
                $(document).trigger('tickets:message-sent', {
                    message: responseData.data,
                    attachments: uploadedAttachments
                });

                $(document).Toasts('create', {
                    class: 'bg-success',
                    title: 'Éxito',
                    body: 'Mensaje enviado correctamente.',
                    autohide: true,
                    delay: 2000
                });

            } catch (error) {
                console.error('[Ticket Chat] Error sending message:', error);

                // 🔴 OPTIMISTIC UI ROLLBACK: Remove pending message and restore form
                removeMessageFromDOM(tempId);

                // Restore content and files to form
                $input.val(contentBackup);
                selectedFiles = filesBackup;
                renderFilePreviews();
                $input.trigger('input'); // Trigger auto-grow

                $(document).Toasts('create', {
                    class: 'bg-danger',
                    title: 'Error',
                    body: error.responseJSON?.message || 'Error al enviar el mensaje.',
                    autohide: true,
                    delay: 3000
                });
            } finally {
                // 🔴 SPAM PROTECTION: Mark operation as complete
                completeOperation('send', tempId);
            }
        }

        function renderFilePreviews() {
            $previewContainer.empty();
            selectedFiles.forEach((file, index) => {
                // Icon based on type
                let iconColor = '#6c757d';
                let iconClass = 'fa-file';
                if (file.type.includes('pdf')) { iconColor = '#dc3545'; iconClass = 'fa-file-pdf'; }
                else if (file.type.includes('image')) { iconColor = '#007bff'; iconClass = 'fa-file-image'; }
                else if (file.type.includes('sheet') || file.name.endsWith('xls') || file.name.endsWith('xlsx')) { iconColor = '#28a745'; iconClass = 'fa-file-excel'; }

                const html = `
                    <div style="display: flex; align-items: center; padding: 6px 10px; background-color: #f8f9fa; border: 1px solid #ced4da; border-radius: 4px; font-size: 0.85rem;">
                        <i class="fas ${iconClass} mr-2" style="color: ${iconColor};"></i>
                        <span style="color: #444; flex: 1;">${file.name}</span>
                        <button type="button" class="btn-remove-file" data-index="${index}" style="background-color: #e9ecef; border: 1px solid #ced4da; width: 18px; height: 18px; cursor: pointer; color: #495057; margin-left: 5px; transition: all 0.2s ease; border-radius: 50%; display: flex; align-items: center; justify-content: center; padding: 0;" title="Remove">
                            <i class="fas fa-times" style="font-size: 0.65rem;"></i>
                        </button>
                    </div>
                `;
                $previewContainer.append(html);
            });

            // Bind Remove Events
            $('.btn-remove-file').on('click', function() {
                const index = $(this).data('index');
                selectedFiles.splice(index, 1);
                renderFilePreviews();
            });
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('es-ES', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
        }

        function formatBytes(bytes, decimals = 2) {
            if (!+bytes) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
        }

        function isWithin30Minutes(dateString) {
            const createdAt = new Date(dateString);
            const now = new Date();
            const diffMs = now - createdAt;
            const diffMins = diffMs / (1000 * 60);
            return diffMins <= 30;
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        // ==============================================================
        // OPTIMISTIC UI HELPERS
        // ==============================================================

        /**
         * Get current user ID from JWT token
         * @returns {string|null} User ID
         */
        function getCurrentUserId() {
            try {
                const token = window.tokenManager.getAccessToken();
                const base64Url = token.split('.')[1];
                const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
                const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
                    return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
                }).join(''));
                const payload = JSON.parse(jsonPayload);
                return payload.sub;
            } catch (e) {
                console.error('[Chat] Error parsing token:', e);
                return null;
            }
        }

        /**
         * Get current user info from JWT token
         * @returns {Object} User info {id, name, email}
         */
        function getCurrentUserInfo() {
            try {
                const token = window.tokenManager.getAccessToken();
                const base64Url = token.split('.')[1];
                const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
                const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
                    return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
                }).join(''));
                const payload = JSON.parse(jsonPayload);

                return {
                    id: payload.sub,
                    email: payload.email || ''
                };
            } catch (e) {
                console.error('[Chat] Error parsing token:', e);
                return { id: null, email: '' };
            }
        }

        /**
         * Render a single message HTML
         * @param {Object} msg - Message object
         * @param {string} currentUserId - Current user ID
         * @param {boolean} isPending - Whether message is pending API confirmation
         * @returns {string} HTML string
         */
        function renderSingleMessage(msg, currentUserId, isPending = false) {
            const authorId = msg.author_id || msg.user_id;
            const isMe = String(authorId) === String(currentUserId);
            const alignClass = isMe ? 'right' : '';
            const nameFloat = isMe ? 'float-right' : 'float-left';
            const timeFloat = isMe ? 'float-left' : 'float-right';
            const bgClass = isMe ? 'background-color: #007bff; color: #fff;' : 'background-color: #d2d6de; color: #444;';

            // Apply pending opacity
            const opacityStyle = isPending ? 'opacity: 0.4; transition: opacity 0.3s ease;' : '';

            // 🔴 OPTIMISTIC UI: Show "Enviando..." for pending messages
            let displayName;
            let authorName = 'Usuario';

            if (isPending) {
                displayName = 'Enviando...';
                authorName = 'Enviando';
            } else {
                // Safe Author Access - Try multiple sources
                // Priority 1: author object with name
                if (msg.author && msg.author.name) {
                    authorName = msg.author.name;
                }
                // Priority 2: uploaded_by_name (from API)
                else if (msg.uploaded_by_name) {
                    authorName = msg.uploaded_by_name;
                }

                // Get role label in uppercase
                const roleLabel = msg.author_type ? msg.author_type.toUpperCase() : 'USER';

                // 🔴 FIX: Add "(tú)" suffix for current user messages
                const tuSuffix = isMe ? ' (tú)' : '';
                displayName = `<strong>${roleLabel}</strong> ${authorName}${tuSuffix}`;
            }

            // Avatar (UI Avatars)
            const avatarUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(authorName)}&size=128&background=${isMe ? '007bff' : '6c757d'}&color=fff&bold=true`;

            // Check if user can edit/delete this message
            const canModify = isMe && isWithin30Minutes(msg.created_at) && currentTicketStatus !== 'closed';

            // Message Actions Dropdown (Only for own messages, not for pending)
            let actionsHtml = '';
            if (canModify && !isPending) {
                actionsHtml = `
                    <div class="message-actions" style="position: absolute; top: 50%; right: 12px; transform: translateY(-50%); opacity: 0; transition: opacity 0.2s ease; z-index: 1000;">
                        <div class="dropdown">
                            <button class="btn btn-link p-0 text-white" type="button" data-toggle="dropdown" aria-expanded="false" style="font-size: 1.1rem; line-height: 1; padding: 4px 8px;">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" style="min-width: 140px; font-size: 0.9rem;">
                                <a class="dropdown-item btn-edit-message" href="#" data-msg-id="${msg.id}" data-msg-content="${escapeHtml(msg.content)}">
                                    <i class="fas fa-edit mr-2"></i> Editar
                                </a>
                                <a class="dropdown-item text-danger btn-delete-message" href="#" data-msg-id="${msg.id}">
                                    <i class="fas fa-trash mr-2"></i> Eliminar
                                </a>
                            </div>
                        </div>
                    </div>
                `;
            }

            let attachmentsHtml = '';
            if (msg.attachments && msg.attachments.length > 0) {
                msg.attachments.forEach(att => {
                    // Determine icon
                    let iconClass = 'fa-file-pdf';
                    if (att.file_type) {
                        if (att.file_type.includes('image')) iconClass = 'fa-file-image';
                        else if (att.file_type.includes('word') || att.file_type.includes('document')) iconClass = 'fa-file-word';
                        else if (att.file_type.includes('sheet') || att.file_type.includes('excel')) iconClass = 'fa-file-excel';
                    }

                    // Check if user can delete this attachment
                    const canDeleteAtt = isMe && isWithin30Minutes(att.created_at) && currentTicketStatus !== 'closed';

                    const marginStyle = isMe ? 'margin: 8px 50px 0 0' : 'margin: 8px 0 0 50px';
                    const bgColor = isMe ? 'rgba(0,123,255,0.1)' : '#f8f9fa';
                    const borderColor = isMe ? '#007bff' : '#d2d6de';
                    const linkColor = isMe ? 'class="text-primary"' : 'style="color: #444;"';
                    const buttonBorderColor = isMe ? 'rgba(0, 123, 255, 0.4)' : 'rgba(68, 68, 68, 0.4)';
                    const buttonColor = isMe ? 'rgba(0, 123, 255, 0.6)' : 'rgba(68, 68, 68, 0.6)';
                    const buttonHoverBorder = isMe ? 'rgba(0, 123, 255, 1)' : 'rgba(68, 68, 68, 1)';
                    const buttonHoverColor = isMe ? 'rgba(0, 123, 255, 1)' : 'rgba(68, 68, 68, 1)';

                    // Delete button for own attachments (not for pending)
                    let deleteButtonHtml = '';
                    if (canDeleteAtt && !isPending) {
                        deleteButtonHtml = `
                            <button type="button" class="btn-delete-attachment" data-att-id="${att.id}" data-att-name="${att.file_name}" data-msg-id="${msg.id}" data-msg-content="${escapeHtml(msg.content)}"
                                style="position: absolute; top: 50%; right: 60px; transform: translateY(-50%); width: 35px; height: 35px; border: 2px solid rgba(220, 53, 69, 0.4); background: transparent; cursor: pointer; border-radius: 2px; display: flex; align-items: center; justify-content: center; color: rgba(220, 53, 69, 0.6); padding: 0; transition: all 0.2s ease; opacity: 0;"
                                title="Eliminar adjunto">
                                <i class="fas fa-times" style="font-size: 0.9rem;"></i>
                            </button>
                        `;
                    }

                    attachmentsHtml += `
                        <div class="attachment-card" data-att-id="${att.id}" style="${marginStyle}; padding: 8px; background-color: ${bgColor}; border-radius: 4px; border-left: 3px solid ${borderColor}; position: relative;">
                            <div style="margin-bottom: 10px;">
                                <a href="${att.file_url}" target="_blank" style="text-decoration: none; font-size: 0.9rem;" ${linkColor}>
                                    <i class="fas ${iconClass} mr-2"></i>
                                    <strong>${att.file_name}</strong>
                                </a>
                            </div>
                            <div style="font-size: 0.85rem; color: #999; margin-bottom: 8px;">
                                <span>Tamaño: ${formatBytes(att.file_size_bytes)}</span>
                                <span class="mx-2">•</span>
                                <span>Tipo: ${att.file_type || 'application/octet-stream'}</span>
                            </div>
                            ${deleteButtonHtml}
                            <button type="button" onclick="window.open('${att.file_url}', '_blank')" style="position: absolute; top: 50%; right: 20px; transform: translateY(-50%); width: 35px; height: 35px; border: 2px solid ${buttonBorderColor}; background: transparent; cursor: pointer; border-radius: 2px; display: flex; align-items: center; justify-content: center; color: ${buttonColor}; padding: 0; transition: all 0.2s ease;" onmouseover="this.style.borderColor='${buttonHoverBorder}'; this.style.color='${buttonHoverColor}';" onmouseout="this.style.borderColor='${buttonBorderColor}'; this.style.color='${buttonColor}';">
                                <i class="fas fa-download" style="font-size: 0.9rem;"></i>
                            </button>
                        </div>
                    `;
                });
            }

            const html = `
                <div class="direct-chat-msg ${alignClass}" data-msg-id="${msg.id}" style="${opacityStyle}">
                    <div class="direct-chat-infos clearfix">
                        <span class="direct-chat-name ${nameFloat}">${displayName}</span>
                        <span class="direct-chat-timestamp ${timeFloat}">${formatDate(msg.created_at)}</span>
                    </div>
                    <img class="direct-chat-img" src="${avatarUrl}" alt="${authorName}">
                    <div class="direct-chat-text" style="${bgClass}; position: relative; padding-right: 85px;">
                        ${actionsHtml}
                        <span class="message-content">${msg.content}</span>
                    </div>
                    ${attachmentsHtml}
                </div>
            `;
            return html;
        }

        /**
         * Add a message to the DOM (optimistic UI)
         * @param {Object} message - Message object
         * @param {Array} attachments - Array of attachment objects
         * @param {boolean} isPending - Whether message is pending
         */
        function addMessageToDOM(message, attachments = [], isPending = false) {
            const currentUserId = getCurrentUserId();

            // 🔴 FIX: Remove "Sé el primero en escribir!" message if it exists
            const $emptyMessage = $msgList.find('p.text-center.text-muted');
            if ($emptyMessage.length > 0) {
                $emptyMessage.remove();
            }

            // Add attachments to message object
            message.attachments = attachments;

            const html = renderSingleMessage(message, currentUserId, isPending);
            $msgList.append(html);

            // Update message count
            const currentCount = parseInt($msgCount.text()) || 0;
            $msgCount.text(currentCount + 1);

            // Bind hover events for new message
            const $newMsg = $(`.direct-chat-msg[data-msg-id="${message.id}"]`);
            $newMsg.hover(
                function() {
                    $(this).find('.message-actions').css('opacity', '1');
                },
                function() {
                    $(this).find('.message-actions').css('opacity', '0');
                }
            );

            // Scroll to bottom
            $msgList.scrollTop($msgList[0].scrollHeight);
        }

        /**
         * Update message content in DOM
         * @param {string} msgId - Message ID
         * @param {string} newContent - New content
         */
        function updateMessageContentInDOM(msgId, newContent) {
            const $msg = $(`.direct-chat-msg[data-msg-id="${msgId}"]`);
            if ($msg.length === 0) return;

            $msg.find('.message-content').text(newContent);
            $msg.find('.btn-edit-message').attr('data-msg-content', newContent);
        }

        /**
         * Set message pending state (opacity)
         * @param {string} msgId - Message ID
         * @param {boolean} isPending - Whether pending
         */
        function setMessagePendingState(msgId, isPending) {
            const $msg = $(`.direct-chat-msg[data-msg-id="${msgId}"]`);
            if ($msg.length === 0) return;

            $msg.css('opacity', isPending ? '0.4' : '1');
        }

        /**
         * Remove message from DOM with fadeOut
         * @param {string} msgId - Message ID
         */
        function removeMessageFromDOM(msgId) {
            const $msg = $(`.direct-chat-msg[data-msg-id="${msgId}"]`);
            if ($msg.length === 0) return;

            $msg.fadeOut(300, function() {
                $(this).remove();

                // Update message count
                const currentCount = parseInt($msgCount.text()) || 0;
                if (currentCount > 0) $msgCount.text(currentCount - 1);
            });
        }

        // ==============================================================
        // MESSAGE EDITING FUNCTIONS
        // ==============================================================

        function startEditMessage(msgId, msgContent) {
            console.log('[Chat] Starting edit for message:', msgId);

            // Store editing state
            editingMessageId = msgId;
            editingMessageContent = $input.val(); // Backup current input (in case user was typing)

            // Load message content into input
            $input.val(msgContent);

            // Trigger input event to auto-expand textarea
            $input.trigger('input');
            $input.focus();

            // Hide original send button and show edit action buttons
            const $sendBtn = $('#btn-send-message');
            const $container = $sendBtn.closest('.input-group-append');
            $sendBtn.hide();

            // Change container to row flex for buttons to be side by side
            $container.css('flex-direction', 'row');

            // Add edit action buttons if they don't exist
            if ($('#btn-confirm-edit').length === 0) {
                const editButtons = `
                    <button type="button" class="btn btn-sm btn-success" id="btn-confirm-edit" title="Guardar cambios (Enter)" style="flex-shrink: 0; height: 38px; width: 38px; padding: 0; display: flex; align-items: center; justify-content: center; margin: 0;">
                        <i class="fas fa-check"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" id="btn-cancel-edit" title="Cancelar (Esc)" style="flex-shrink: 0; height: 38px; width: 38px; padding: 0; display: flex; align-items: center; justify-content: center; margin: 0; margin-left: -1px;">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                $sendBtn.after(editButtons);
            }

            // Show editing indicator
            const $editingIndicator = $('<div class="alert alert-info mb-2 py-1 px-2" id="editing-indicator" style="font-size: 0.85rem;"><i class="fas fa-edit mr-1"></i> Editando mensaje...</div>');
            $('#chat-attachments-preview').before($editingIndicator);
        }

        function cancelEdit() {
            console.log('[Chat] Canceling edit');

            // Restore original state
            if (editingMessageContent !== null) {
                $input.val(editingMessageContent);
            } else {
                $input.val('');
            }

            // Reset textarea height
            $input.css('height', '38px');

            // Reset editing state
            editingMessageId = null;
            editingMessageContent = null;

            // Reset UI - Show original send button, hide edit buttons
            const $sendBtn = $('#btn-send-message');
            const $container = $sendBtn.closest('.input-group-append');
            $sendBtn.show();
            $container.css('flex-direction', 'column'); // Reset to column layout
            $('#btn-confirm-edit').remove();
            $('#btn-cancel-edit').remove();
            $('#editing-indicator').remove();

            // Clear validation errors from jQuery Validation plugin
            $input.removeClass('is-invalid');
            $input.closest('.input-group').find('.invalid-feedback').remove();
        }

        async function updateMessage(msgId, newContent) {
            // 🔴 SPAM PROTECTION: Check if update operation for this message is already in progress
            if (!canPerformOperation('update', msgId)) {
                console.log(`[Chat] Update blocked - message ${msgId} is already being updated`);
                return;
            }

            const $confirmBtn = $('#btn-confirm-edit');
            const $cancelBtn = $('#btn-cancel-edit');

            // Store original button content for rollback
            const originalBtnContent = $confirmBtn.html();

            // 🔴 OPTIMISTIC UI: Get original message content for rollback
            const $msg = $(`.direct-chat-msg[data-msg-id="${msgId}"]`);
            const originalMessageContent = $msg.find('.message-content').text();

            // Disable both buttons and show loading state
            $confirmBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
            $cancelBtn.prop('disabled', true);

            // 🔴 OPTIMISTIC UI: Update content in DOM immediately with pending state
            updateMessageContentInDOM(msgId, newContent);
            setMessagePendingState(msgId, true);

            try {
                const token = window.tokenManager.getAccessToken();

                await $.ajax({
                    url: `/api/tickets/${currentTicketCode}/responses/${msgId}`,
                    method: 'PATCH',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    data: JSON.stringify({ content: newContent })
                });

                // 🔴 OPTIMISTIC UI: Success - remove pending state
                setMessagePendingState(msgId, false);

                $(document).Toasts('create', {
                    class: 'bg-success',
                    title: 'Éxito',
                    body: 'Mensaje actualizado correctamente.',
                    autohide: true,
                    delay: 2000
                });

                // Reset UI (exit edit mode)
                cancelEdit();

            } catch (error) {
                console.error('[Chat] Error updating message:', error);

                // 🔴 OPTIMISTIC UI ROLLBACK: Revert to original content
                updateMessageContentInDOM(msgId, originalMessageContent);
                setMessagePendingState(msgId, false);

                $(document).Toasts('create', {
                    class: 'bg-danger',
                    title: 'Error',
                    body: error.responseJSON?.message || 'Error al actualizar el mensaje.',
                    autohide: true,
                    delay: 3000
                });

                // Restore button state on error (keep edit mode active)
                $confirmBtn.prop('disabled', false).html(originalBtnContent);
                $cancelBtn.prop('disabled', false);
            } finally {
                // 🔴 SPAM PROTECTION: Mark operation as complete
                completeOperation('update', msgId);
            }
        }

        async function confirmDeleteMessage(msgId) {
            if (!confirm('¿Estás seguro de que deseas eliminar este mensaje? Esta acción no se puede deshacer.')) {
                return;
            }

            // 🔴 SPAM PROTECTION: Check if delete operation for this message is already in progress
            if (!canPerformOperation('delete', msgId)) {
                console.log(`[Chat] Delete blocked - message ${msgId} is already being deleted`);
                return;
            }

            try {
                const token = window.tokenManager.getAccessToken();

                await $.ajax({
                    url: `/api/tickets/${currentTicketCode}/responses/${msgId}`,
                    method: 'DELETE',
                    headers: { 'Authorization': `Bearer ${token}` }
                });

                // Success
                $(document).Toasts('create', {
                    class: 'bg-success',
                    title: 'Éxito',
                    body: 'Mensaje eliminado correctamente.',
                    autohide: true,
                    delay: 2000
                });

                // 🔴 PROFESSIONAL: Remove message from DOM immediately (no API call needed)
                $(`.direct-chat-msg[data-msg-id="${msgId}"]`).fadeOut(300, function() {
                    $(this).remove();
                });

                // 🔴 PROFESSIONAL: Emit event for other components to update counters
                $(document).trigger('tickets:message-deleted', {
                    messageId: msgId
                });

            } catch (error) {
                console.error('[Chat] Error deleting message:', error);
                $(document).Toasts('create', {
                    class: 'bg-danger',
                    title: 'Error',
                    body: error.responseJSON?.message || 'Error al eliminar el mensaje.',
                    autohide: true,
                    delay: 3000
                });
            } finally {
                // 🔴 SPAM PROTECTION: Mark operation as complete
                completeOperation('delete', msgId);
            }
        }

        async function confirmDeleteAttachment(attId, attName, msgId, msgContent) {
            if (!confirm(`¿Estás seguro de que deseas eliminar el archivo "${attName}"? Esta acción no se puede deshacer.`)) {
                return;
            }

            // 🔴 SPAM PROTECTION: Check if delete operation for this attachment is already in progress
            if (!canPerformOperation('deleteAtt', attId)) {
                console.log(`[Chat] Delete attachment blocked - attachment ${attId} is already being deleted`);
                return;
            }

            try {
                const token = window.tokenManager.getAccessToken();

                // 1. Delete the attachment
                await $.ajax({
                    url: `/api/tickets/${currentTicketCode}/attachments/${attId}`,
                    method: 'DELETE',
                    headers: { 'Authorization': `Bearer ${token}` }
                });

                // 🔴 ALWAYS emit attachment deleted event (for attachment list panel)
                $(document).trigger('tickets:attachment-deleted', {
                    attachmentId: attId,
                    messageId: msgId
                });
                console.log(`[Chat] Event dispatched: tickets:attachment-deleted (attId: ${attId})`);

                // 2. 🔴 FIX: If message content is "Archivo adjunto.", also delete the message (silent operation)
                if (msgContent === 'Archivo adjunto.') {
                    console.log(`[Chat] Message content is "Archivo adjunto." - deleting message ${msgId} in background`);

                    try {
                        await $.ajax({
                            url: `/api/tickets/${currentTicketCode}/responses/${msgId}`,
                            method: 'DELETE',
                            headers: { 'Authorization': `Bearer ${token}` }
                        });

                        // 🔴 PROFESSIONAL: Remove entire message from DOM (includes all attachments)
                        $(`.direct-chat-msg[data-msg-id="${msgId}"]`).fadeOut(300, function() {
                            $(this).remove();
                            console.log(`[Chat] Message ${msgId} removed from DOM (contained "Archivo adjunto.")`);
                        });

                        // 🔴 PROFESSIONAL: Emit event for message deletion
                        $(document).trigger('tickets:message-deleted', {
                            messageId: msgId
                        });
                        console.log(`[Chat] Event dispatched: tickets:message-deleted (msgId: ${msgId})`);
                    } catch (deleteError) {
                        console.error('[Chat] Error deleting message:', deleteError);
                        // Silent fail - message deletion is background operation
                    }
                } else {
                    // 🔴 PROFESSIONAL: Only remove the attachment from DOM (not the message)
                    $(`.attachment-card[data-att-id="${attId}"]`).fadeOut(300, function() {
                        $(this).remove();
                        console.log(`[Chat] Attachment ${attId} removed from DOM`);
                    });
                }

                // Show same toast regardless of background operations
                $(document).Toasts('create', {
                    class: 'bg-success',
                    title: 'Éxito',
                    body: 'Adjunto eliminado correctamente.',
                    autohide: true,
                    delay: 2000
                });

            } catch (error) {
                console.error('[Chat] Error deleting attachment:', error);
                $(document).Toasts('create', {
                    class: 'bg-danger',
                    title: 'Error',
                    body: error.responseJSON?.message || 'Error al eliminar el adjunto.',
                    autohide: true,
                    delay: 3000
                });
            } finally {
                // 🔴 SPAM PROTECTION: Mark operation as complete
                completeOperation('deleteAtt', attId);
            }
        }

        function editLastOwnMessage() {
            // Get all messages
            const $messages = $('.direct-chat-msg[data-msg-id]');

            // Find the last message that has edit button (own message + within 30 mins + not closed)
            for (let i = $messages.length - 1; i >= 0; i--) {
                const $msg = $messages.eq(i);
                const $editBtn = $msg.find('.btn-edit-message');

                if ($editBtn.length > 0) {
                    const msgId = $editBtn.data('msg-id');
                    const msgContent = $editBtn.data('msg-content');
                    startEditMessage(msgId, msgContent);
                    return;
                }
            }
        }
    }

    // Wait for jQuery
    if (typeof jQuery !== 'undefined') {
        $(document).ready(initTicketChat);
    } else {
        var checkJQuery = setInterval(function() {
            if (typeof jQuery !== 'undefined') {
                clearInterval(checkJQuery);
                $(document).ready(initTicketChat);
            }
        }, 100);
        setTimeout(function() {
            clearInterval(checkJQuery);
        }, 10000);
    }
})();
</script>
