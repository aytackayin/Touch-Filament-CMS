document.addEventListener('DOMContentLoaded', async () => {
    const titleInput = document.getElementById('title');
    const categorySelect = document.getElementById('category');
    const noteInput = document.getElementById('note');
    const btnSave = document.getElementById('btnSave');
    const statusDiv = document.getElementById('status');

    const settingsPanel = document.getElementById('settingsPanel');
    const toggleSettings = document.getElementById('toggleSettings');
    const siteUrlInput = document.getElementById('siteUrl');
    const apiKeyInput = document.getElementById('apiKey');
    const saveSettingsBtn = document.getElementById('saveSettings');

    let youtubeData = { id: '', description: '' };

    // 1. Load Settings
    const data = await chrome.storage.local.get(['siteUrl', 'apiKey']);
    if (data.siteUrl) siteUrlInput.value = data.siteUrl;
    if (data.apiKey) apiKeyInput.value = data.apiKey;

    // 2. Hide/Show Settings
    toggleSettings.addEventListener('click', () => {
        settingsPanel.style.display = settingsPanel.style.display === 'block' ? 'none' : 'block';
    });

    saveSettingsBtn.addEventListener('click', async () => {
        let url = siteUrlInput.value.replace(/\/$/, ""); // Remove trailing slash
        await chrome.storage.local.set({ siteUrl: url, apiKey: apiKeyInput.value });
        alert('Ayarlar kaydedildi!');
        fetchCategories(); // Refresh categories
        settingsPanel.style.display = 'none';
    });

    // 3. Detect YouTube Info & Fetch Description
    chrome.tabs.query({ active: true, currentWindow: true }, async (tabs) => {
        const tab = tabs[0];
        if (tab.url && tab.url.includes("youtube.com/watch?v=")) {
            const urlObj = new URL(tab.url);
            youtubeData.id = urlObj.searchParams.get("v");
            titleInput.value = tab.title.replace(" - YouTube", "");

            // Fetch description from page DOM
            try {
                const results = await chrome.scripting.executeScript({
                    target: { tabId: tab.id },
                    func: () => {
                        // YouTube description selectors vary, trying common ones
                        const descEl = document.querySelector('#description-inline-expander .yt-core-attributed-string') ||
                            document.querySelector('yt-attributed-string#description-text') ||
                            document.querySelector('meta[name="description"]');

                        if (descEl) {
                            return descEl.innerText || descEl.getAttribute('content') || "";
                        }
                        return "";
                    }
                });

                if (results && results[0] && results[0].result) {
                    youtubeData.description = results[0].result;
                }
            } catch (e) {
                console.error("Description fetch error:", e);
            }
        } else {
            showStatus("Lütfen bir YouTube video sayfasında olun.", "error");
            btnSave.disabled = true;
        }
    });

    // 4. Fetch Categories from API
    async function fetchCategories() {
        const { siteUrl, apiKey } = await chrome.storage.local.get(['siteUrl', 'apiKey']);
        if (!siteUrl || !apiKey) {
            categorySelect.innerHTML = '<option>Önce ayarları yapın!</option>';
            return;
        }

        try {
            const response = await fetch(`${siteUrl}/api/youtube/categories`, {
                headers: { 'X-API-KEY': apiKey, 'Accept': 'application/json' }
            });
            const categories = await response.json();

            categorySelect.innerHTML = categories.map(c => `<option value="${c.id}">${c.title}</option>`).join('');
        } catch (err) {
            categorySelect.innerHTML = '<option>Bağlantı hatası!</option>';
        }
    }

    fetchCategories();

    // 5. Send to CMS
    btnSave.addEventListener('click', async () => {
        const { siteUrl, apiKey } = await chrome.storage.local.get(['siteUrl', 'apiKey']);

        if (!categorySelect.value) return showStatus("Lütfen kategori seçin.", "error");

        btnSave.disabled = true;
        showStatus("Kaydediliyor...", "success");

        try {
            const response = await fetch(`${siteUrl}/api/youtube/store`, {
                method: 'POST',
                headers: {
                    'X-API-KEY': apiKey,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    title: titleInput.value,
                    video_id: youtubeData.id,
                    description: youtubeData.description || "Açıklama bulunamadı.",
                    category_id: categorySelect.value,
                    note: noteInput.value
                })
            });

            const result = await response.json();
            if (response.ok) {
                showStatus("Başarıyla kaydedildi!", "success");
                setTimeout(() => window.close(), 1500);
            } else {
                showStatus(result.message || "Bir hata oluştu.", "error");
                btnSave.disabled = false;
            }
        } catch (err) {
            showStatus("Sunucuya ulaşılamadı.", "error");
            btnSave.disabled = false;
        }
    });

    function showStatus(msg, type) {
        statusDiv.textContent = msg;
        statusDiv.className = `status ${type}`;
        statusDiv.style.display = 'block';
    }
});
