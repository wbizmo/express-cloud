const DATABASE = 'express-cloud-outbox';
const VERSION = 1;
const STORE = 'requests';

const openDatabase = () => new Promise((resolve, reject) => {
    const request = indexedDB.open(DATABASE, VERSION);
    request.onupgradeneeded = () => {
        const database = request.result;
        if (!database.objectStoreNames.contains(STORE)) {
            const store = database.createObjectStore(STORE, { keyPath: 'id' });
            store.createIndex('status', 'status');
            store.createIndex('createdAt', 'createdAt');
        }
    };
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
});

const transaction = async (mode, callback) => {
    const database = await openDatabase();
    return new Promise((resolve, reject) => {
        const tx = database.transaction(STORE, mode);
        const store = tx.objectStore(STORE);
        const result = callback(store);
        tx.oncomplete = () => resolve(result?.result ?? result);
        tx.onerror = () => reject(tx.error);
        tx.onabort = () => reject(tx.error);
    });
};

export const saveDraft = async ({ scope, url, method = 'POST', headers = {}, body, idempotencyKey }) => {
    const id = `${scope}:${idempotencyKey}`;
    await transaction('readwrite', (store) => store.put({
        id,
        scope,
        url,
        method,
        headers,
        body,
        idempotencyKey,
        status: 'queued',
        createdAt: new Date().toISOString(),
        attempts: 0,
    }));
    return id;
};

export const removeDraft = async (id) => transaction('readwrite', (store) => store.delete(id));

export const queuedDrafts = async () => {
    const database = await openDatabase();
    return new Promise((resolve, reject) => {
        const tx = database.transaction(STORE, 'readonly');
        const request = tx.objectStore(STORE).getAll();
        request.onsuccess = () => resolve(request.result.filter((item) => item.status === 'queued'));
        request.onerror = () => reject(request.error);
    });
};

export const markAttempt = async (item, status = 'queued') => transaction('readwrite', (store) => store.put({
    ...item,
    status,
    attempts: Number(item.attempts || 0) + 1,
    lastAttemptAt: new Date().toISOString(),
}));
