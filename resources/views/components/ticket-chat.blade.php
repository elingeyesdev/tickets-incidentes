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
                <span class="input-group-prepend">
                    <button class="btn btn-light border" type="button" id="btn-attach-file" data-toggle="tooltip" title="Adjuntar Archivo" style="border-color: #ced4da; background-color: #e9ecef; color: #495057;">
                        <i class="fas fa-paperclip"></i>
                    </button>
                    <input type="file" id="chat-file-input" multiple style="display: none;" accept=".pdf,.txt,.doc,.docx,.xls,.xlsx,.csv,.jpg,.jpeg,.png,.gif">
                </span>
                <textarea name="message" id="chat-message-input" placeholder="Escribe un mensaje..." class="form-control" rows="1" style="resize: none; height: 38px; line-height: 24px;"></textarea>
                <span class="input-group-append">
                    <button type="submit" class="btn btn-primary" id="btn-send-message">Enviar</button>
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
    console.log('[Ticket Chat] Version: Stateless Fix Applied'); // VERIFICATION LOG

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
        let currentTicketId = null;
        let selectedFiles = [];
        let currentUser = null; // Will be set from token or API

        // ==============================================================
        // INITIALIZATION
        // ==============================================================
        
        // Validation Configuration
        if (typeof $.fn.validate !== 'undefined') {
            $form.validate({
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
                }
            });
        }

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
            currentTicketId = ticket.id;
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

        // 4. Send Message
            // Handle Enter to Send (Shift+Enter for new line)
            $input.on('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    $form.submit();
                }
            });

            $form.on('submit', async function(e) {
            e.preventDefault();
            if (!$form.valid()) return;

            const content = $input.val();
            if (!content.trim() && selectedFiles.length === 0) return;

            await sendMessage(content);
        });

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

            messages.forEach(msg => {
                const authorId = msg.author_id || msg.user_id;
                const isMe = String(authorId) === String(currentUserId);
                const alignClass = isMe ? 'right' : '';
                const nameFloat = isMe ? 'float-right' : 'float-left';
                const timeFloat = isMe ? 'float-left' : 'float-right';
                const bgClass = isMe ? 'background-color: #007bff; color: #fff;' : 'background-color: #d2d6de; color: #444;';

                // Safe Author Access
                let authorName = 'Desconocido';

                if (msg.author && msg.author.name) {
                    authorName = msg.author.name;
                } else if (msg.uploaded_by_name) {
                    authorName = msg.uploaded_by_name;
                } else {
                    // TODO: IMPLEMENTAR CUANDO EXISTA
                }

                // Get role label in uppercase
                const roleLabel = msg.author_type ? msg.author_type.toUpperCase() : 'UNKNOWN';
                const displayName = `<strong>${roleLabel}</strong> ${authorName}`;

                // Avatar (UI Avatars)
                const avatarUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(authorName)}&size=128&background=${isMe ? '007bff' : '6c757d'}&color=fff&bold=true`;

                let attachmentsHtml = '';
                if (msg.attachments && msg.attachments.length > 0) {
                    msg.attachments.forEach(att => {
                        // Determine icon (simple, like mock)
                        let iconClass = 'fa-file-pdf'; // Mock uses fa-file-pdf for all
                        if (att.file_type) {
                            if (att.file_type.includes('image')) iconClass = 'fa-file-image';
                            else if (att.file_type.includes('word') || att.file_type.includes('document')) iconClass = 'fa-file-word';
                            else if (att.file_type.includes('sheet') || att.file_type.includes('excel')) iconClass = 'fa-file-excel';
                        }

                        // EXACT MOCK DESIGN - Different styles for user vs agent
                        const marginStyle = isMe ? 'margin: 8px 50px 0 0' : 'margin: 8px 0 0 50px';
                        const bgColor = isMe ? 'rgba(0,123,255,0.1)' : '#f8f9fa';
                        const borderColor = isMe ? '#007bff' : '#d2d6de';
                        const linkColor = isMe ? 'class="text-primary"' : 'style="color: #444;"';
                        const buttonBorderColor = isMe ? 'rgba(0, 123, 255, 0.4)' : 'rgba(68, 68, 68, 0.4)';
                        const buttonColor = isMe ? 'rgba(0, 123, 255, 0.6)' : 'rgba(68, 68, 68, 0.6)';
                        const buttonHoverBorder = isMe ? 'rgba(0, 123, 255, 1)' : 'rgba(68, 68, 68, 1)';
                        const buttonHoverColor = isMe ? 'rgba(0, 123, 255, 1)' : 'rgba(68, 68, 68, 1)';

                        attachmentsHtml += `
                            <div style="${marginStyle}; padding: 8px; background-color: ${bgColor}; border-radius: 4px; border-left: 3px solid ${borderColor}; position: relative;">
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
                                <button type="button" onclick="window.open('${att.file_url}', '_blank')" style="position: absolute; top: 50%; right: 20px; transform: translateY(-50%); width: 35px; height: 35px; border: 2px solid ${buttonBorderColor}; background: transparent; cursor: pointer; border-radius: 2px; display: flex; align-items: center; justify-content: center; color: ${buttonColor}; padding: 0; transition: all 0.2s ease;" onmouseover="this.style.borderColor='${buttonHoverBorder}'; this.style.color='${buttonHoverColor}';" onmouseout="this.style.borderColor='${buttonBorderColor}'; this.style.color='${buttonColor}';">
                                    <i class="fas fa-download" style="font-size: 0.9rem;"></i>
                                </button>
                            </div>
                        `;
                    });
                }

                const html = `
                    <div class="direct-chat-msg ${alignClass}">
                        <div class="direct-chat-infos clearfix">
                            <span class="direct-chat-name ${nameFloat}">${displayName}</span>
                            <span class="direct-chat-timestamp ${timeFloat}">${formatDate(msg.created_at)}</span>
                        </div>
                        <img class="direct-chat-img" src="${avatarUrl}" alt="${authorName}">
                        <div class="direct-chat-text" style="${bgClass}">
                            ${msg.content}
                        </div>
                        ${attachmentsHtml}
                    </div>
                `;
                $msgList.append(html);
            });
            
            // Scroll to bottom
            $msgList.scrollTop($msgList[0].scrollHeight);
        }

        async function sendMessage(content) {
            const $btn = $('#btn-send-message');
            const originalBtnText = $btn.text();
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

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
                if (selectedFiles.length > 0) {
                    for (const file of selectedFiles) {
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

                // 3. Cleanup & Reload
                $input.val('');
                selectedFiles = [];
                renderFilePreviews();
                loadMessages(); // Reload chat to show new message
                
                // 4. Emit Event for other components (Payload Response Strategy)
                $(document).trigger('tickets:message-sent', {
                    message: responseData.data,
                    attachments: uploadedAttachments
                });

                $(document).Toasts('create', {
                    class: 'bg-success',
                    title: 'Éxito',
                    body: 'Mensaje enviado correctamente.',
                    autohide: true,
                    delay: 3000
                });

            } catch (error) {
                console.error('[Ticket Chat] Error sending message:', error);
                $(document).Toasts('create', {
                    class: 'bg-danger',
                    title: 'Error',
                    body: error.responseJSON?.message || 'Error al enviar el mensaje.',
                    autohide: true,
                    delay: 3000
                });
            } finally {
                $btn.prop('disabled', false).text(originalBtnText);
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
