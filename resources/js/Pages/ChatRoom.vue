<template>
    <Head title="Dashboard" />

    <BreezeAuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Chat room: {{ room }}
            </h2>
            <div>
                Shareable link: <code class="bg-yellow-300 rounded-lg px-2 py-0.5 select-all">{{ link }}</code>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl flex items-start mx-auto sm:px-6 lg:px-8 space-x-2">
                <div class="w-1/4 bg-white shadow-sm sm:rounded-lg p-3 text-sm">
                    <div
                        v-for="user in users"
                        :key="user.id"
                        :class="{
                            'font-bold': $page.props.auth.user.id === user.id,
                        }"
                    >
                        {{ user.name }}
                    </div>
                </div>
                <div class="flex flex-col space-y-3 flex-1 h-96 overflow-y-auto">
                    <div
                        class="bg-white shadow-sm sm:rounded-lg p-3 w-full"
                        v-for="(line, i) in lines"
                        :key="i"
                    >
                        <div :class="{
                            'text-red-500': line.type === 'error',
                            'italic text-gray-600': line.type === 'system',
                        }">
                            <div
                                v-if="line.type === 'message'"
                                class="font-bold"
                            >
                                {{ line.user.name }}
                            </div>
                            <div>
                                {{ line.message }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="bottom-auto p-6 bg-white w-full">
            <breeze-input
                v-model="message"
                type="text"
                class="mt-1 block max-w-7xl w-full mx-auto"
                placeholder="Type your message here and press ENTER..."
                @keyup.enter="sendMessage"
            />
        </div>
    </BreezeAuthenticatedLayout>
</template>

<script>
import BreezeAuthenticatedLayout from '@/Layouts/Authenticated.vue'
import BreezeInput from '@/Components/Input.vue'
import { Head } from '@inertiajs/inertia-vue3';

export default {
    components: {
        BreezeAuthenticatedLayout,
        BreezeInput,
        Head,
    },

    props: [
        'room',
        'link',
    ],

    data() {
        return {
            lines: [],
            users: [],
            message: '',
            lastMessageId: 0,
            pollingInterval: null,
            aiEnabled: false,
            aiStatus: null,
        };
    },

    mounted() {
        // Initialize with current user
        this.users = [this.$page.props.auth.user];
        this.systemMessage('You have joined the channel.');
        
        // Load existing room history once
        this.fetchNewMessages();

        // Check AI status
        this.checkAIStatus();
        
        // Prefer WebSockets via Soketi; will fallback to polling on error
        this.setupWebSocket();
    },

    methods: {
        systemMessage(message) {
            this.lines.push({ message, type: 'system' });
        },

        errorMessage(message) {
            this.lines.push({ message, type: 'error' });
        },

        userMessage(message, user) {
            this.lines.push({ message, user, type: 'message' });
        },

        sendMessage() {
            let message = this.message;
            this.message = '';

            if (this.isAIMessage(message)) {
                this.sendAIMessage(message);
            } else {
                // Prefer real-time broadcast via Soketi
                axios.post(this.route('send.message'), { room: this.room, message })
                    .then(() => {
                        // Also persist to cache for late joiners/polling fallback
                        axios.post(this.route('messages.store'), { room: this.room, message }).catch(() => {});
                    })
                    .catch(error => {
                        this.errorMessage('Failed to send message: ' + error.message);
                    });
            }
        },

        isAIMessage(message) {
            // Check if message starts with @ai or contains AI commands
            const aiCommands = ['@ai', '/ai', 'ai help', 'ai status', 'ai clear'];
            return aiCommands.some(cmd => message.toLowerCase().includes(cmd));
        },

        sendAIMessage(message) {
            // Show the user's prompt immediately
            this.userMessage(message, this.$page.props.auth.user);
            // Show immediate feedback while waiting
            const thinkingIndex = this.lines.length;
            this.lines.push({ message: '🤖 AI is thinking...', type: 'system' });

            axios.post(this.route('ai.chat'), {
                room: this.room,
                message: message
            }).then((response) => {
                if (response.data && response.data.success) {
                    const provider = response.data.provider || 'AI';
                    const aiText = response.data.ai_response || response.data.reply || '';
                    // Replace the thinking line with the AI answer
                    this.$set ? this.$set(this.lines, thinkingIndex, { message: `🤖 ${provider}: ${aiText}`, type: 'system' })
                              : (this.lines[thinkingIndex] = { message: `🤖 ${provider}: ${aiText}`, type: 'system' });
                } else {
                    const errText = (response.data && (response.data.exception || response.data.error)) || 'AI service unavailable';
                    this.$set ? this.$set(this.lines, thinkingIndex, { message: '❌ ' + errText, type: 'error' })
                              : (this.lines[thinkingIndex] = { message: '❌ ' + errText, type: 'error' });
                }
            }).catch(error => {
                const errText = (error.response && error.response.data && (error.response.data.exception || error.response.data.error)) || error.message;
                this.$set ? this.$set(this.lines, thinkingIndex, { message: '❌ Failed to contact AI: ' + errText, type: 'error' })
                          : (this.lines[thinkingIndex] = { message: '❌ Failed to contact AI: ' + errText, type: 'error' });
            });
        },

        checkAIStatus() {
            axios.get(this.route('ai.status')).then((response) => {
                this.aiStatus = response.data;
                this.aiEnabled = response.data.available;
                
                if (this.aiEnabled) {
                    this.systemMessage(`🤖 AI Assistant (${response.data.provider}) is online! Type @ai help for commands.`);
                } else {
                    this.systemMessage('🤖 AI Assistant is currently offline.');
                }
            }).catch(error => {
                this.systemMessage('🤖 AI Assistant status unknown.');
            });
        },

        setupWebSocket() {
            // Set up Laravel Echo with Soketi
            if (window.Echo) {
                window.Echo
                    .join(`room.${this.room}`)
                    .here(users => {
                        this.users = users;
                        this.systemMessage('You have joined the channel.');
                    })
                    .joining(user => {
                        this.users.push(user);
                        this.systemMessage(`${user.name} joined the channel.`);
                    })
                    .leaving(user => {
                        this.users.splice(this.users.indexOf(user), 1);
                        this.systemMessage(`${user.name} left the channel.`);
                    })
                    .error((error) => {
                        this.errorMessage(`WebSocket error: ${JSON.stringify(error)}`);
                    })
                    .listen('.room.message', ({ message, user }) => {
                        this.userMessage(message, user);
                    })
                    .listen('.ai.message', ({ message, user }) => {
                        const label = user && user.name ? user.name : 'AI';
                        const aiLine = `🤖 ${label}: ${message}`;

                        // If already displayed (from optimistic UI), skip
                        const exists = this.lines.some(line => line.type === 'system' && line.message === aiLine);
                        if (exists) { return; }

                        // If a thinking line is present, replace the most recent one
                        for (let i = this.lines.length - 1; i >= 0; i--) {
                            const line = this.lines[i];
                            if (line && line.type === 'system' && typeof line.message === 'string' && line.message.includes('AI is thinking')) {
                                this.$set ? this.$set(this.lines, i, { message: aiLine, type: 'system' })
                                          : (this.lines[i] = { message: aiLine, type: 'system' });
                                return;
                            }
                        }

                        // Otherwise append the AI line
                        this.systemMessage(aiLine);
                    });
            } else {
                this.errorMessage('WebSocket connection not available. Falling back to polling.');
                this.startPolling();
            }
        },

        startPolling() {
            this.pollingInterval = setInterval(() => {
                this.fetchNewMessages();
            }, 2000); // Poll every 2 seconds
        },

        stopPolling() {
            if (this.pollingInterval) {
                clearInterval(this.pollingInterval);
                this.pollingInterval = null;
            }
        },

        fetchNewMessages() {
            axios.get(this.route('messages.index', { room: this.room }))
                .then(response => {
                    const messages = response.data.messages || [];

                    messages.forEach(msg => {
                        if (msg.user && msg.user.is_ai) {
                            const aiLine = `🤖 ${msg.user.name}: ${msg.message}`;
                            const exists = this.lines.some(line => line.type === 'system' && line.message === aiLine);
                            if (!exists) {
                                this.systemMessage(aiLine);
                            }
                        } else if (msg.user) {
                            const exists = this.lines.some(line => line.type === 'message' && line.message === msg.message && line.user && line.user.id === msg.user.id);
                            if (!exists) {
                                this.userMessage(msg.message, msg.user);
                            }
                        }
                    });
                })
                .catch(error => {
                    // Silently fail for polling
                });
        },
    },

    beforeUnmount() {
        this.stopPolling();
    }
}
</script>
