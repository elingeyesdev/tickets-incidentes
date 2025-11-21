<!-- Card: Timeline de Respuestas -->
<div class="card" x-show="responses.length > 0" x-cloak>
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-comments mr-2"></i>Conversación (<span x-text="responses.length">0</span> respuestas)
        </h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="timeline">
            <template x-for="(response, index) in responses" :key="response.id">
                <div>
                    <!-- Time Label (agrupación por día) -->
                    <template x-if="index === 0 || !isSameDay(responses[index-1].created_at, response.created_at)">
                        <div class="time-label">
                            <span :class="{
                                'bg-primary': response.author_type === 'user',
                                'bg-success': response.author_type === 'agent'
                            }" x-text="formatDateLabel(response.created_at)"></span>
                        </div>
                    </template>

                    <!-- Timeline Item -->
                    <div>
                        <i :class="{
                            'fas fa-user bg-blue': response.author_type === 'user',
                            'fas fa-user-tie bg-green': response.author_type === 'agent'
                        }"></i>

                        <div class="timeline-item">
                            <span class="time">
                                <i class="fas fa-clock"></i>
                                <span x-text="formatTime(response.created_at)"></span>
                            </span>

                            <h3 class="timeline-header">
                                <a href="#" x-text="response.author?.name || 'N/A'">N/A</a>
                                <template x-if="response.author_type === 'user'">
                                    <span class="badge badge-primary badge-sm ml-2">USER</span>
                                </template>
                                <template x-if="response.author_type === 'agent'">
                                    <span class="badge badge-success badge-sm ml-2">AGENT</span>
                                </template>
                                respondió
                            </h3>

                            <div class="timeline-body" x-html="response.content.replace(/\n/g, '<br>')"></div>

                            <!-- Attachments de esta respuesta -->
                            <template x-if="response.attachments && response.attachments.length > 0">
                                <div class="timeline-footer">
                                    <span class="badge badge-primary">
                                        <i class="fas fa-paperclip mr-1"></i>
                                        <span x-text="response.attachments.length"></span> adjunto(s)
                                    </span>
                                    <template x-for="att in response.attachments" :key="att.id">
                                        <a href="#"
                                           class="btn btn-sm btn-default ml-2"
                                           @click.prevent="downloadAttachment(att.id)">
                                            <i class="fas fa-download mr-1"></i>
                                            <span x-text="att.file_name"></span>
                                        </a>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Timeline End -->
            <div>
                <i class="fas fa-clock bg-gray"></i>
            </div>
        </div>
    </div>
</div>