// Salon de chat : les nouveaux messages arrivent par interrogation périodique
// (pas de serveur WebSocket à maintenir sur IIS).
const POLL_VISIBLE_MS = 5000;
const URL_PATTERN = /(https?:\/\/[^\s<>"]+)/g;

window.chatRoom = function chatRoom(config) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    const request = async (url, method = 'GET', payload = null) => {
        const response = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: payload ? JSON.stringify(payload) : null,
        });
        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            throw new Error(data.message || 'La requête a échoué. Réessayez.');
        }

        return response.json();
    };

    return {
        messages: config.messages,
        since: config.now,
        body: '',
        sending: false,
        editingId: null,
        editBody: '',
        error: '',
        polling: false,

        init() {
            this.$nextTick(() => this.scrollToBottom());
            setInterval(() => this.poll(), POLL_VISIBLE_MS);
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    this.poll();
                }
            });
        },

        lastId() {
            return this.messages.reduce((max, message) => Math.max(max, message.id), 0);
        },

        upsert(message) {
            const index = this.messages.findIndex((existing) => existing.id === message.id);
            if (index === -1) {
                this.messages.push(message);
            } else {
                this.messages.splice(index, 1, message);
            }
        },

        nearBottom() {
            const list = this.$refs.list;

            return !list || list.scrollHeight - list.scrollTop - list.clientHeight < 120;
        },

        scrollToBottom() {
            const list = this.$refs.list;
            if (list) {
                list.scrollTop = list.scrollHeight;
            }
        },

        async poll() {
            if (this.polling || document.hidden) {
                return;
            }
            this.polling = true;
            try {
                const params = new URLSearchParams({ after: this.lastId(), since: this.since });
                const data = await request(`${config.pollUrl}?${params}`);
                const stick = this.nearBottom();
                data.changes.forEach((message) => this.upsert(message));
                data.messages.forEach((message) => this.upsert(message));
                this.since = data.now;
                if (stick && data.messages.length) {
                    this.$nextTick(() => this.scrollToBottom());
                }
            } catch (e) {
                // Réseau momentanément indisponible : nouvel essai au prochain cycle.
            } finally {
                this.polling = false;
            }
        },

        async send() {
            const body = this.body.trim();
            if (!body || this.sending) {
                return;
            }
            this.sending = true;
            this.error = '';
            try {
                this.upsert(await request(config.postUrl, 'POST', { body }));
                this.body = '';
                this.$nextTick(() => this.scrollToBottom());
            } catch (e) {
                this.error = e.message;
            } finally {
                this.sending = false;
            }
        },

        onKeydown(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                this.send();
            }
        },

        startEdit(message) {
            this.editingId = message.id;
            this.editBody = message.body;
        },

        async saveEdit(message) {
            const body = this.editBody.trim();
            if (!body) {
                return;
            }
            try {
                this.upsert(await request(config.messageUrl.replace('__ID__', message.id), 'PUT', { body }));
                this.editingId = null;
            } catch (e) {
                this.error = e.message;
            }
        },

        async remove(message) {
            if (!window.confirm('Supprimer ce message ?')) {
                return;
            }
            try {
                this.upsert(await request(config.messageUrl.replace('__ID__', message.id), 'DELETE'));
            } catch (e) {
                this.error = e.message;
            }
        },

        showDate(index) {
            return index === 0 || this.messages[index - 1].date !== this.messages[index].date;
        },

        segments(body) {
            return body.split(URL_PATTERN).filter(Boolean).map((text) => ({ text, url: /^https?:\/\//.test(text) }));
        },
    };
};
