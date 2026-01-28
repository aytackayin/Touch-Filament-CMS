
// Queue management in background
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === 'saveBlog') {
        processQueueItem(request.data);
        sendResponse({ status: 'queued' });
    }
});

async function processQueueItem(data) {
    // 1. Durumu 'pending' olarak kaydet
    await updateQueueStatus(data.video_id, data.title, 'pending');

    const { siteUrl, apiKey } = await chrome.storage.local.get(['siteUrl', 'apiKey']);

    if (!siteUrl || !apiKey) {
        await updateQueueStatus(data.video_id, data.title, 'error', 'Ayarlar eksik!');
        return;
    }

    try {
        const response = await fetch(`${siteUrl}/api/youtube/store`, {
            method: 'POST',
            headers: {
                'X-API-KEY': apiKey,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (response.ok) {
            await updateQueueStatus(data.video_id, data.title, 'success', 'Tamamlandı');
            // Başarılı olduktan 5 saniye sonra listeden kaldır
            setTimeout(() => removeQueueItem(data.video_id), 5000);
        } else {
            await updateQueueStatus(data.video_id, data.title, 'error', result.message || 'Hata oluştu');
        }
    } catch (err) {
        await updateQueueStatus(data.video_id, data.title, 'error', 'Sunucu hatası');
    }
}

async function updateQueueStatus(videoId, title, status, message = '') {
    const { taskQueue } = await chrome.storage.local.get(['taskQueue']) || { taskQueue: {} };
    const queue = taskQueue || {};

    queue[videoId] = {
        title: title,
        status: status,
        message: message,
        timestamp: Date.now()
    };

    await chrome.storage.local.set({ taskQueue: queue });
}

async function removeQueueItem(videoId) {
    const { taskQueue } = await chrome.storage.local.get(['taskQueue']);
    if (taskQueue && taskQueue[videoId]) {
        delete taskQueue[videoId];
        await chrome.storage.local.set({ taskQueue });
    }
}
