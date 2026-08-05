import { markAttempt, queuedDrafts, removeDraft, saveDraft } from './operation-outbox.js';

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
const recoveryTemplate = () => document.querySelector('meta[name="operation-recovery-template"]')?.content || '';

const operationStatus = async (scope, key) => {
    const template = recoveryTemplate();
    if (!template) return null;
    const url = template.replace('__SCOPE__', encodeURIComponent(scope)).replace('__KEY__', encodeURIComponent(key));
    const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    if (response.status === 404) return null;
    if (!response.ok) throw new Error('Operation status could not be checked.');
    return response.json();
};

const replay = async (item) => {
    const existing = await operationStatus(item.scope, item.idempotencyKey);
    if (existing?.status === 'succeeded') {
        await removeDraft(item.id);
        return existing;
    }
    const response = await fetch(item.url, {
        method: item.method,
        headers: {
            ...item.headers,
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Idempotency-Key': item.idempotencyKey,
        },
        credentials: 'same-origin',
        body: typeof item.body === 'string' ? item.body : JSON.stringify(item.body),
    });
    if (response.ok) {
        await removeDraft(item.id);
        return response.json().catch(() => ({}));
    }
    await markAttempt(item, 'queued');
    throw new Error(`Queued operation returned HTTP ${response.status}.`);
};

export const flushOutbox = async () => {
    if (!navigator.onLine) return;
    for (const item of await queuedDrafts()) {
        try { await replay(item); } catch (error) { console.warn('Express Cloud outbox replay deferred.', error); }
    }
};

export const durableRequest = async ({ scope, url, body, method = 'POST', headers = {} }) => {
    const idempotencyKey = body.idempotency_key || crypto.randomUUID();
    const payload = { ...body, idempotency_key: idempotencyKey };
    const id = await saveDraft({ scope, url, method, headers, body: payload, idempotencyKey });
    try {
        const result = await replay({ id, scope, url, method, headers, body: payload, idempotencyKey, attempts: 0 });
        return { queued: false, result };
    } catch (error) {
        if (!navigator.onLine || error instanceof TypeError) {
            return { queued: true, idempotencyKey };
        }
        const status = await operationStatus(scope, idempotencyKey).catch(() => null);
        if (status?.status === 'succeeded') {
            await removeDraft(id);
            return { queued: false, result: status };
        }
        throw error;
    }
};

const setConnectivity = () => {
    document.documentElement.dataset.connectivity = navigator.onLine ? 'online' : 'offline';
    window.dispatchEvent(new CustomEvent('express-cloud:connectivity', { detail: { online: navigator.onLine } }));
};

if ('serviceWorker' in navigator && document.querySelector('meta[name="pwa-enabled"]')?.content === '1') {
    window.addEventListener('load', () => navigator.serviceWorker.register('/service-worker.js'));
}
window.addEventListener('online', () => { setConnectivity(); flushOutbox(); });
window.addEventListener('offline', setConnectivity);
document.addEventListener('DOMContentLoaded', () => { setConnectivity(); flushOutbox(); });
window.ExpressCloudResilience = { durableRequest, flushOutbox, operationStatus };
