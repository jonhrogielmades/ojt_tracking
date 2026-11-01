const DB_NAME = 'ojt-tracker-offline';
const DB_VERSION = 1;
const STORE_NAME = 'drafts';
const ACTIVE_STUDENT_STORAGE_KEY = 'ojt-tracker-active-student-key';

function createId() {
    if (globalThis.crypto?.randomUUID) {
        return globalThis.crypto.randomUUID();
    }

    if (!globalThis.crypto?.getRandomValues) {
        throw new Error('Secure UUID generation is unavailable.');
    }

    const bytes = new Uint8Array(16);
    globalThis.crypto.getRandomValues(bytes);
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = [...bytes].map((byte) => byte.toString(16).padStart(2, '0')).join('');

    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}

function readActiveStudentKey() {
    try {
        return localStorage.getItem(ACTIVE_STUDENT_STORAGE_KEY) || '';
    } catch {
        return '';
    }
}

function openDatabase() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = () => {
            const database = request.result;
            if (!database.objectStoreNames.contains(STORE_NAME)) {
                const store = database.createObjectStore(STORE_NAME, { keyPath: 'idempotencyKey' });
                store.createIndex('studentKey', 'studentKey', { unique: false });
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function saveDraft(draft) {
    const database = await openDatabase();

    return new Promise((resolve, reject) => {
        const transaction = database.transaction(STORE_NAME, 'readwrite');
        transaction.objectStore(STORE_NAME).put(draft);
        transaction.oncomplete = () => {
            database.close();
            resolve();
        };
        transaction.onerror = () => {
            database.close();
            reject(transaction.error);
        };
        transaction.onabort = () => {
            database.close();
            reject(transaction.error);
        };
    });
}

function formPayload(form) {
    return Object.fromEntries(new FormData(form).entries());
}

function localDate() {
    const now = new Date();
    const localTime = new Date(now.getTime() - (now.getTimezoneOffset() * 60_000));
    return localTime.toISOString().slice(0, 10);
}

function showStatus(message, type = 'success') {
    const status = document.querySelector('#offline-status');
    status.textContent = message;
    status.dataset.type = type;
    status.hidden = false;
    status.focus();
}

async function queueForm(form, studentKey) {
    const payload = formPayload(form);
    const idempotencyKey = createId();
    const timestamp = new Date().toISOString();
    payload.idempotency_key = idempotencyKey;

    await saveDraft({
        idempotencyKey,
        studentKey,
        kind: form.dataset.kind,
        syncUrl: form.dataset.syncUrl,
        payload,
        status: 'pending',
        lastError: null,
        createdAt: timestamp,
        updatedAt: timestamp,
    });

    form.reset();
    const dateInput = form.querySelector('input[type="date"]');
    if (dateInput) {
        dateInput.value = localDate();
    }

    showStatus('Draft saved on this device. Open OJT Tracker after reconnecting to sync it.');
}

function initializeOfflineDrafts() {
    const studentKey = readActiveStudentKey();
    const draftingShell = document.querySelector('#drafting-shell');
    const setupMessage = document.querySelector('#setup-message');
    const today = localDate();

    document.querySelectorAll('input[type="date"]').forEach((input) => {
        input.value = today;
        input.defaultValue = today;
    });

    document.querySelectorAll('[data-retry-connection]').forEach((button) => {
        button.addEventListener('click', () => window.location.reload());
    });

    if (!studentKey) {
        setupMessage.hidden = false;
        draftingShell.hidden = true;
        return;
    }

    setupMessage.hidden = true;
    draftingShell.hidden = false;

    document.querySelectorAll('[data-offline-draft-form]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!form.reportValidity()) {
                return;
            }

            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;

            try {
                await queueForm(form, studentKey);
            } catch {
                showStatus('This draft could not be saved locally. Keep this page open and try again.', 'error');
            } finally {
                submitButton.disabled = false;
            }
        });
    });
}

initializeOfflineDrafts();
