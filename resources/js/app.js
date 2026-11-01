const DB_NAME = 'ojt-tracker-offline';
const DB_VERSION = 1;
const STORE_NAME = 'drafts';
const SYNC_TAG = 'ojt-draft-sync';
const ACTIVE_STUDENT_STORAGE_KEY = 'ojt-tracker-active-student-key';
const NAVIGATION_TOAST_STORAGE_KEY = 'ojt-tracker-navigation-toast';
const ACCESSIBILITY_PREFERENCES_KEY = 'ojt-tracker-accessibility-preferences';

const body = document.body;
const isAuthenticated = body?.dataset.authenticated === 'true';
const studentKey = body?.dataset.studentKey || '';
let deferredInstallPrompt = null;
let toastTimer = null;
let logoutForm = null;

function accessibilityPreferences() {
    try {
        return JSON.parse(localStorage.getItem(ACCESSIBILITY_PREFERENCES_KEY) || '{}');
    } catch {
        return {};
    }
}

function applyAccessibilityPreferences(preferences) {
    document.documentElement.classList.toggle('large-text', preferences.largeText === true);
    document.documentElement.classList.toggle('high-contrast', preferences.highContrast === true);
}

function initializeAccessibilityPreferences() {
    const preferences = accessibilityPreferences();
    applyAccessibilityPreferences(preferences);

    const controls = {
        largeText: document.querySelectorAll('[data-accessibility-large-text]'),
        highContrast: document.querySelectorAll('[data-accessibility-high-contrast]'),
    };

    Object.entries(controls).forEach(([preference, inputs]) => {
        inputs.forEach((input) => {
            input.checked = preferences[preference] === true;
            input.addEventListener('change', () => {
                const updatedPreferences = {
                    ...accessibilityPreferences(),
                    [preference]: input.checked,
                };

                try {
                    localStorage.setItem(ACCESSIBILITY_PREFERENCES_KEY, JSON.stringify(updatedPreferences));
                } catch {
                    // Preferences still apply for the current page when storage is unavailable.
                }

                applyAccessibilityPreferences(updatedPreferences);
                controls[preference].forEach((matchingInput) => {
                    matchingInput.checked = input.checked;
                });
            });
        });
    });
}

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

function rememberActiveStudentKey() {
    try {
        localStorage.setItem(ACTIVE_STUDENT_STORAGE_KEY, studentKey);
    } catch {
        // Offline drafting remains available only when local storage is permitted.
    }
}

function forgetActiveStudentKey() {
    try {
        localStorage.removeItem(ACTIVE_STUDENT_STORAGE_KEY);
    } catch {
        // The server logout still proceeds when local storage is unavailable.
    }
}

function storeNavigationToast(message) {
    try {
        sessionStorage.setItem(NAVIGATION_TOAST_STORAGE_KEY, message);
    } catch {
        // Navigation still succeeds when session storage is unavailable.
    }
}

function consumeNavigationToast() {
    let message = '';

    try {
        message = sessionStorage.getItem(NAVIGATION_TOAST_STORAGE_KEY) || '';
        sessionStorage.removeItem(NAVIGATION_TOAST_STORAGE_KEY);
    } catch {
        return;
    }

    if (message) {
        showToast(message);
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

async function withStore(mode, callback) {
    const database = await openDatabase();

    return new Promise((resolve, reject) => {
        const transaction = database.transaction(STORE_NAME, mode);
        const store = transaction.objectStore(STORE_NAME);
        let result;

        try {
            result = callback(store);
        } catch (error) {
            database.close();
            reject(error);
            return;
        }

        transaction.oncomplete = () => {
            database.close();
            resolve(result);
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

async function saveDraft(draft) {
    await withStore('readwrite', (store) => store.put(draft));
}

async function removeDraft(idempotencyKey) {
    await withStore('readwrite', (store) => store.delete(idempotencyKey));
}

async function draftsForCurrentStudent() {
    if (!studentKey) {
        return [];
    }

    const database = await openDatabase();

    return new Promise((resolve, reject) => {
        const transaction = database.transaction(STORE_NAME, 'readonly');
        const index = transaction.objectStore(STORE_NAME).index('studentKey');
        const request = index.getAll(IDBKeyRange.only(studentKey));

        request.onsuccess = () => {
            database.close();
            resolve(request.result.sort((first, second) => first.createdAt.localeCompare(second.createdAt)));
        };
        request.onerror = () => {
            database.close();
            reject(request.error);
        };
    });
}

async function clearCurrentStudentDrafts() {
    const drafts = await draftsForCurrentStudent();
    await withStore('readwrite', (store) => {
        drafts.forEach((draft) => store.delete(draft.idempotencyKey));
    });
}

function showToast(message) {
    const toast = document.querySelector('#app-toast');
    if (!toast) {
        return;
    }

    toast.textContent = message;
    toast.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        toast.hidden = true;
    }, 5000);
}

function updateConnectionStatus() {
    const status = document.querySelector('#connection-status');
    if (!status) {
        return;
    }

    const dot = status.querySelector('span:first-child');
    const label = status.querySelector('span:last-child');
    const online = navigator.onLine;
    status.classList.remove('bg-slate-100', 'text-slate-600', 'bg-emerald-50', 'text-emerald-700', 'bg-amber-50', 'text-amber-800');
    status.classList.add(...(online ? ['bg-emerald-50', 'text-emerald-700'] : ['bg-amber-50', 'text-amber-800']));
    dot.className = `h-2 w-2 rounded-full ${online ? 'bg-emerald-500' : 'bg-amber-500'}`;
    label.textContent = online ? 'Online' : 'Offline';
}

function formPayload(form) {
    const payload = {};
    const data = new FormData(form);

    for (const [key, value] of data.entries()) {
        if (key === '_token' || key === '_method' || value instanceof File) {
            continue;
        }

        if (key.endsWith('[]')) {
            const normalizedKey = key.slice(0, -2);
            payload[normalizedKey] ??= [];
            payload[normalizedKey].push(value);
        } else {
            payload[key] = value;
        }
    }

    return payload;
}

function preparedFormPayload(form) {
    const payload = formPayload(form);
    const idempotencyInput = form.querySelector('input[name="idempotency_key"]');
    const idempotencyKey = payload.idempotency_key || idempotencyInput?.value || createId();
    payload.idempotency_key = idempotencyKey;

    if (idempotencyInput) {
        idempotencyInput.value = idempotencyKey;
    }

    return payload;
}

function formHasSelectedFile(form) {
    return [...form.querySelectorAll('input[type="file"]')].some((input) => input.files?.length > 0);
}

async function requestBackgroundSync() {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    try {
        const registration = await navigator.serviceWorker.getRegistration();
        if (registration && 'sync' in registration) {
            await registration.sync.register(SYNC_TAG);
        }
    } catch {
        // Normal online-event and launch retries remain available.
    }
}

async function queueOfflinePayload(form, payload) {
    const idempotencyInput = form.querySelector('input[name="idempotency_key"]');
    const idempotencyKey = payload.idempotency_key || createId();
    payload.idempotency_key = idempotencyKey;
    const timestamp = new Date().toISOString();

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
    if (idempotencyInput) {
        idempotencyInput.value = createId();
    }

    await refreshDraftInterface();
    void requestBackgroundSync();
    showToast('Saved locally — Pending sync. It will retry when your connection returns.');
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

async function parseResponse(response) {
    try {
        return await response.json();
    } catch {
        return {};
    }
}

function clearFormStatus(form) {
    const status = form.querySelector('[data-form-status]');
    if (status) {
        status.hidden = true;
        status.replaceChildren();
    }

    form.querySelectorAll('.input-error').forEach((field) => {
        field.classList.remove('input-error');
        field.removeAttribute('aria-invalid');
    });
}

function showFormResponse(form, result = {}, fallback = 'The entry could not be saved. Please try again.') {
    const status = form.querySelector('[data-form-status]');
    if (!status) {
        showToast(result.message || fallback);
        return;
    }

    const validationMessages = Object.values(result.errors || {}).flat().filter(Boolean);
    const messages = [...new Set([result.message || fallback, ...validationMessages])];
    const message = document.createElement('p');
    message.textContent = messages.join(' ');
    status.replaceChildren(message);

    if (result.resolution_url) {
        const link = document.createElement('a');
        link.href = result.resolution_url;
        link.className = 'mt-2 block font-bold underline';
        link.textContent = 'Open the saved DTR entry to resolve this conflict →';
        status.append(link);
    }

    Object.keys(result.errors || {}).forEach((name) => {
        const field = form.elements.namedItem(name);
        if (field instanceof HTMLElement) {
            field.classList.add('input-error');
            field.setAttribute('aria-invalid', 'true');
        }
    });

    status.hidden = false;
    status.focus();
}

function setFormBusy(form, busy) {
    const button = form.querySelector('button[type="submit"]');
    if (!button) {
        return;
    }

    button.dataset.idleLabel ||= button.textContent;
    button.disabled = busy;
    button.textContent = busy ? 'Saving…' : button.dataset.idleLabel;
}

function navigateAfterSuccess(form, message) {
    storeNavigationToast(message);
    window.location.assign(form.dataset.successUrl);
}

function responseMatchesSuccessUrl(response, successUrl) {
    if (!response.redirected || !successUrl) {
        return false;
    }

    const responseUrl = new URL(response.url, window.location.href);
    const expectedUrl = new URL(successUrl, window.location.href);

    return responseUrl.origin === expectedUrl.origin && responseUrl.pathname === expectedUrl.pathname;
}

async function submitTextForm(form, payload) {
    let response;

    try {
        response = await fetch(form.dataset.syncUrl, {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        });
    } catch {
        await queueOfflinePayload(form, payload);
        return;
    }

    const result = await parseResponse(response);
    if (response.ok && result.status === 'synced') {
        navigateAfterSuccess(form, result.message || 'Entry saved and synced.');
        return;
    }

    if (response.status === 409 && result.conflict_type === 'idempotency_key') {
        const idempotencyInput = form.querySelector('input[name="idempotency_key"]');
        if (idempotencyInput) {
            idempotencyInput.value = createId();
        }
    }

    showFormResponse(
        form,
        result,
        response.ok
            ? 'The server response could not be confirmed. Your form was not queued; check your connection and try again.'
            : `The server could not save this entry (${response.status}). Please review the form and try again.`,
    );
}

async function submitAttachmentForm(form) {
    let response;

    try {
        response = await fetch(form.action, {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: new FormData(form),
        });
    } catch {
        showFormResponse(
            form,
            {},
            'The connection failed before the attachment could be confirmed. Your form and selected file are still here—check your connection and retry.',
        );
        return;
    }

    if (responseMatchesSuccessUrl(response, form.dataset.successUrl)) {
        navigateAfterSuccess(form, 'Activity log and attachment saved.');
        return;
    }

    const result = await parseResponse(response);
    if (response.ok && result.status === 'synced') {
        navigateAfterSuccess(form, result.message || 'Activity log and attachment saved.');
        return;
    }

    if (response.status === 409 && result.conflict_type === 'idempotency_key') {
        const idempotencyInput = form.querySelector('input[name="idempotency_key"]');
        if (idempotencyInput) {
            idempotencyInput.value = createId();
        }
    }

    showFormResponse(
        form,
        result,
        response.ok
            ? 'The server response could not be confirmed. Your form and selected file are still here—check your connection and retry.'
            : `The server could not save this activity (${response.status}). Your form and selected file are still here.`,
    );
}

async function syncDraft(draft) {
    draft.status = 'syncing';
    draft.lastError = null;
    draft.resolutionUrl = null;
    draft.updatedAt = new Date().toISOString();
    await saveDraft(draft);
    await refreshDraftInterface();

    try {
        const response = await fetch(draft.syncUrl, {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(draft.payload),
        });
        const result = await parseResponse(response);

        if (response.ok && result.status === 'synced') {
            await removeDraft(draft.idempotencyKey);
            return { synced: true };
        }

        if (response.ok) {
            throw new Error('The sync endpoint returned an unexpected response.');
        }

        if (response.status === 409 || response.status === 422) {
            draft.status = 'conflict';
            draft.lastError = result.message || Object.values(result.errors || {}).flat()[0] || 'This draft conflicts with server data. Review it before retrying.';
            draft.resolutionUrl = result.resolution_url || null;
            draft.updatedAt = new Date().toISOString();
            await saveDraft(draft);
            return { synced: false, stop: false };
        }

        draft.status = 'pending';
        draft.resolutionUrl = null;
        draft.lastError = response.status === 401 || response.status === 419
            ? 'Your session expired. Sign in again before syncing this draft.'
            : result.message || `Sync failed (${response.status}). Try again.`;
        draft.updatedAt = new Date().toISOString();
        await saveDraft(draft);

        return { synced: false, stop: response.status === 401 || response.status === 419 };
    } catch {
        draft.status = 'pending';
        draft.resolutionUrl = null;
        draft.lastError = 'Connection lost during sync. The draft is still safely stored on this device.';
        draft.updatedAt = new Date().toISOString();
        await saveDraft(draft);
        return { synced: false, stop: true };
    }
}

async function syncAllDrafts({ includeConflicts = false, announce = false } = {}) {
    if (!navigator.onLine || !studentKey) {
        if (announce) {
            showToast('You are offline. Drafts will remain pending.');
        }
        return false;
    }

    const drafts = await draftsForCurrentStudent();
    const candidates = drafts.filter((draft) => draft.status !== 'conflict' || includeConflicts);
    let syncedCount = 0;

    for (const draft of candidates) {
        const result = await syncDraft(draft);
        if (result.synced) {
            syncedCount += 1;
        }
        if (result.stop) {
            break;
        }
    }

    await refreshDraftInterface();
    const remaining = (await draftsForCurrentStudent()).length;
    if (announce) {
        showToast(remaining === 0 ? 'All drafts synced.' : `${syncedCount} synced. ${remaining} still need attention.`);
    } else if (syncedCount > 0) {
        showToast(`${syncedCount} pending ${syncedCount === 1 ? 'draft has' : 'drafts have'} synced successfully.`);
    }

    return remaining === 0;
}

function draftTitle(draft) {
    const date = draft.payload.work_date || draft.payload.activity_date || 'Undated';
    return `${draft.kind === 'dtr' ? 'DTR' : 'Activity'} · ${date}`;
}

function draftCard(draft) {
    const card = document.createElement('article');
    card.className = 'rounded-2xl border border-slate-200 p-4';
    const top = document.createElement('div');
    top.className = 'flex items-start justify-between gap-3';
    const copy = document.createElement('div');
    const title = document.createElement('p');
    title.className = 'text-sm font-bold text-slate-950';
    title.textContent = draftTitle(draft);
    const time = document.createElement('p');
    time.className = 'mt-1 text-xs text-slate-500';
    time.textContent = `Saved locally ${new Date(draft.createdAt).toLocaleString()}`;
    copy.append(title, time);
    const badge = document.createElement('span');
    badge.className = `rounded-full px-2 py-1 text-xs font-bold ${draft.status === 'conflict' ? 'bg-rose-100 text-rose-700' : draft.status === 'syncing' ? 'bg-sky-100 text-sky-700' : 'bg-amber-100 text-amber-800'}`;
    badge.textContent = draft.status === 'conflict' ? 'Needs review' : draft.status === 'syncing' ? 'Syncing' : 'Pending sync';
    top.append(copy, badge);
    card.append(top);

    if (draft.lastError) {
        const error = document.createElement('p');
        error.className = 'mt-3 rounded-xl bg-rose-50 px-3 py-2 text-xs leading-5 text-rose-700';
        error.textContent = draft.lastError;
        card.append(error);
    }

    if (draft.resolutionUrl) {
        const resolutionLink = document.createElement('a');
        resolutionLink.href = draft.resolutionUrl;
        resolutionLink.className = 'mt-3 inline-flex text-xs font-bold text-teal-700 hover:text-teal-800';
        resolutionLink.textContent = 'Edit the saved DTR to resolve this conflict →';
        card.append(resolutionLink);
    }

    const actions = document.createElement('div');
    actions.className = 'mt-3 flex gap-2';
    const retry = document.createElement('button');
    retry.type = 'button';
    retry.className = 'btn-secondary min-h-9 flex-1 px-3 py-1.5 text-xs';
    retry.textContent = 'Retry';
    retry.disabled = draft.status === 'syncing';
    retry.addEventListener('click', async () => {
        const result = await syncDraft(draft);
        await refreshDraftInterface();
        showToast(result.synced ? 'Draft synced successfully.' : 'Draft is still pending. Review the message for details.');
    });
    const discard = document.createElement('button');
    discard.type = 'button';
    discard.className = 'btn-ghost min-h-9 px-3 py-1.5 text-xs text-rose-600';
    discard.textContent = 'Discard';
    discard.addEventListener('click', async () => {
        if (window.confirm('Discard this local draft? This cannot be undone.')) {
            await removeDraft(draft.idempotencyKey);
            await refreshDraftInterface();
            showToast('Local draft discarded.');
        }
    });
    actions.append(retry, discard);
    card.append(actions);

    return card;
}

async function refreshDraftInterface() {
    if (!isAuthenticated || !studentKey) {
        return;
    }

    const drafts = await draftsForCurrentStudent();
    const count = document.querySelector('#pending-count');
    const list = document.querySelector('#draft-list');
    if (count) {
        count.textContent = String(drafts.length);
    }
    if (!list) {
        return;
    }

    list.replaceChildren();
    if (drafts.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'rounded-2xl bg-emerald-50 px-4 py-5 text-center text-sm font-semibold text-emerald-800';
        empty.textContent = 'No pending drafts. Everything is synced.';
        list.append(empty);
        return;
    }

    drafts.forEach((draft) => list.append(draftCard(draft)));
}

function toggleDraftPanel(open) {
    const panel = document.querySelector('[data-draft-panel]');
    const backdrop = document.querySelector('[data-draft-panel-backdrop]');
    if (!panel || !backdrop) {
        return;
    }
    panel.hidden = !open;
    backdrop.hidden = !open;
}

function submitLogout() {
    if (!logoutForm) {
        return;
    }
    forgetActiveStudentKey();
    logoutForm.dataset.allowSubmit = 'true';
    logoutForm.requestSubmit();
}

function initializeOfflineForms() {
    document.querySelectorAll('[data-offline-form]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            clearFormStatus(form);

            if (!form.reportValidity()) {
                return;
            }

            setFormBusy(form, true);

            try {
                if (formHasSelectedFile(form)) {
                    if (!navigator.onLine) {
                        showFormResponse(
                            form,
                            {},
                            'Attachments require an internet connection. Your form and selected file are still here—reconnect and retry, or remove the file to save a text-only draft.',
                        );
                        return;
                    }

                    await submitAttachmentForm(form);
                    return;
                }

                const payload = preparedFormPayload(form);
                if (!navigator.onLine) {
                    await queueOfflinePayload(form, payload);
                    return;
                }

                await submitTextForm(form, payload);
            } catch {
                showFormResponse(
                    form,
                    {},
                    'The entry could not be saved to the server or this device. Keep this form open and try again.',
                );
            } finally {
                setFormBusy(form, false);
            }
        });
    });
}

function initializeLogoutProtection() {
    document.querySelectorAll('[data-logout-form]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            if (form.dataset.allowSubmit === 'true') {
                return;
            }

            event.preventDefault();

            let drafts;
            try {
                drafts = await draftsForCurrentStudent();
            } catch {
                showToast('Pending drafts could not be checked. Please try again before logging out.');
                return;
            }

            if (drafts.length === 0) {
                forgetActiveStudentKey();
                form.dataset.allowSubmit = 'true';
                form.requestSubmit();
                return;
            }

            logoutForm = form;
            const modal = document.querySelector('#logout-modal');
            modal.hidden = false;
            modal.classList.add('grid');
        });
    });

    document.querySelector('[data-logout-cancel]')?.addEventListener('click', () => {
        const modal = document.querySelector('#logout-modal');
        modal.hidden = true;
        modal.classList.remove('grid');
        logoutForm = null;
    });
    document.querySelector('[data-logout-delete]')?.addEventListener('click', async () => {
        await clearCurrentStudentDrafts();
        submitLogout();
    });
    document.querySelector('[data-logout-sync]')?.addEventListener('click', async () => {
        const finished = await syncAllDrafts({ includeConflicts: true, announce: true });
        if (finished) {
            submitLogout();
        }
    });
}

function initializeInstallPrompt() {
    const installButtons = document.querySelectorAll('[data-install-button]');
    if (window.matchMedia('(display-mode: standalone)').matches) {
        return;
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredInstallPrompt = event;
        installButtons.forEach((button) => {
            button.hidden = false;
        });
    });

    installButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            if (!deferredInstallPrompt) {
                return;
            }
            deferredInstallPrompt.prompt();
            await deferredInstallPrompt.userChoice;
            deferredInstallPrompt = null;
            installButtons.forEach((item) => {
                item.hidden = true;
            });
        });
    });
}

function initializeInterface() {
    document.querySelector('[data-draft-panel-open]')?.addEventListener('click', () => toggleDraftPanel(true));
    document.querySelector('[data-draft-panel-close]')?.addEventListener('click', () => toggleDraftPanel(false));
    document.querySelector('[data-draft-panel-backdrop]')?.addEventListener('click', () => toggleDraftPanel(false));
    document.querySelector('[data-sync-now]')?.addEventListener('click', () => syncAllDrafts({ announce: true }));
    document.querySelector('[data-retry-conflicts]')?.addEventListener('click', () => syncAllDrafts({ includeConflicts: true, announce: true }));

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
}

async function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) {
        return;
    }
    try {
        await navigator.serviceWorker.register('/sw.js', { scope: '/' });
    } catch {
        // The web experience remains usable when service workers are unavailable.
    }
}

window.addEventListener('online', async () => {
    updateConnectionStatus();
    await syncAllDrafts();
});
window.addEventListener('offline', updateConnectionStatus);
navigator.serviceWorker?.addEventListener('message', (event) => {
    if (event.data?.type === 'SYNC_OFFLINE_DRAFTS') {
        syncAllDrafts();
    }
});

initializeInstallPrompt();
initializeInterface();
initializeAccessibilityPreferences();
updateConnectionStatus();
registerServiceWorker();
consumeNavigationToast();

if (isAuthenticated && studentKey) {
    rememberActiveStudentKey();
    initializeOfflineForms();
    initializeLogoutProtection();
    refreshDraftInterface().then(() => {
        if (navigator.onLine) {
            syncAllDrafts();
        }
    });
}
