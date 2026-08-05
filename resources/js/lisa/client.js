const escapeText = (value) => String(value ?? '');

export const createLisaClient = ({ endpoint, csrf, messagesElement }) => ({
    draft: '',
    sending: false,
    typing: false,
    messages: [],
    async send() {
        const content = this.draft.trim();
        if (!content || this.sending) return;
        this.messages.push({ id: crypto.randomUUID(), role: 'user', content: escapeText(content) });
        this.draft = '';
        this.sending = true;
        this.typing = true;
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                credentials: 'same-origin',
                body: JSON.stringify({ message: content }),
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Lisa could not respond.');
            this.messages.push({
                id: crypto.randomUUID(),
                role: 'assistant',
                content: escapeText(payload.reply),
                snapshotId: payload.snapshot_id,
                evidenceHash: payload.evidence_hash,
            });
        } catch (error) {
            this.messages.push({ id: crypto.randomUUID(), role: 'assistant', content: escapeText(error.message) });
        } finally {
            this.typing = false;
            this.sending = false;
            this.$nextTick(() => {
                const element = this.$refs.messages || messagesElement;
                if (element) element.scrollTop = element.scrollHeight;
            });
        }
    },
});

document.addEventListener('alpine:init', () => {
    window.Alpine.data('lisaChat', (endpoint, csrf) => createLisaClient({ endpoint, csrf }));
});
