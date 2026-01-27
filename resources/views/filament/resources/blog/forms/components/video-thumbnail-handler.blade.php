<div id="video-thumbnail-handler" style="display: none;"></div>

<script>
    (function () {
        // We use a Map to track processed file IDs to avoid regenerating, 
        // but we will sync the final output array based on CURRENT FilePond files.
        const processedIds = new Set();
        let thumbnailsData = []; // Array of { id, filename, thumbnail }

        function updateHiddenField() {
            // Filter thumbnailsData to only include items that are currently in FilePond
            // (This logic is actually handled in the sync loop, but we ensure cleanliness here)
            const jsonData = JSON.stringify(thumbnailsData);

            // Update Livewire state
            if (window.Livewire) {
                try {
                    const hiddenInput = document.getElementById('form.video_thumbnails_store');
                    if (hiddenInput) {
                        const formElement = hiddenInput.closest('[wire\\:id]');
                        if (formElement) {
                            const wireId = formElement.getAttribute('wire:id');
                            const component = window.Livewire.find(wireId);
                            if (component) {
                                component.set('data.video_thumbnails_store', jsonData);
                            }
                        }
                    }
                } catch (e) {
                    console.error('Failed to update Livewire state:', e);
                }
            }

            // DOM fallback
            const hiddenInput = document.getElementById('form.video_thumbnails_store');
            if (hiddenInput) {
                hiddenInput.value = jsonData;
                hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }

        async function generateThumbnail(fileObject, id) {
            try {
                // Ensure we handle actual File objects (new uploads)
                if (!(fileObject instanceof File)) {
                    return; // Skip server-side files or mocks
                }

                const video = document.createElement('video');
                // Use ObjectURL for specific file slice or full file
                video.src = URL.createObjectURL(fileObject);
                video.currentTime = 1; // Capture at 1s
                video.muted = true;
                video.playsInline = true;

                await new Promise((resolve, reject) => {
                    video.onseeked = resolve;
                    video.onerror = (e) => {
                        // Try seeking to 0 if 1 fails (short video)
                        if (video.currentTime > 0) {
                            video.currentTime = 0;
                        } else {
                            resolve(); // Resolve anyway to avoid hanging
                        }
                    };
                    // Timeout safety
                    setTimeout(resolve, 2000);
                });

                if (video.videoWidth === 0) {
                    URL.revokeObjectURL(video.src);
                    return;
                }

                const MAX_WIDTH = 150;
                let width = video.videoWidth;
                let height = video.videoHeight;

                if (width > MAX_WIDTH) {
                    const ratio = MAX_WIDTH / width;
                    width = MAX_WIDTH;
                    height = height * ratio;
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                canvas.getContext('2d').drawImage(video, 0, 0, width, height);
                const base64 = canvas.toDataURL('image/jpeg', 0.7);

                URL.revokeObjectURL(video.src);

                // Add to store
                thumbnailsData.push({
                    id: id,
                    filename: fileObject.name,
                    thumbnail: base64
                });

                updateHiddenField();

            } catch (e) {
                console.error('Thumbnail gen error:', e);
            }
        }

        function scanFilePond() {
            if (typeof FilePond === 'undefined') return;

            const roots = document.querySelectorAll('.filepond--root');
            let foundFiles = [];

            // 1. Collect all current valid video files across all instances
            for (const root of roots) {
                const pond = FilePond.find(root);
                if (!pond) continue;

                const items = pond.getFiles();
                for (const item of items) {
                    if (!item.file) continue;

                    // Check if it's a video
                    // Note: 'type' might be empty sometimes, check extension fallback
                    let isVideo = item.file.type && item.file.type.startsWith('video/');
                    if (!isVideo && item.file.name) {
                        const ext = item.file.name.split('.').pop().toLowerCase();
                        if (['mp4', 'mov', 'avi', 'webm', 'mkv'].includes(ext)) {
                            isVideo = true;
                        }
                    }

                    if (isVideo) {
                        const id = item.serverId || item.id;
                        foundFiles.push({
                            id: id,
                            file: item.file
                        });
                    }
                }
            }

            // 2. Sync thumbnailsData: Remove items NOT in foundFiles
            const currentIds = foundFiles.map(f => f.id);
            const initialCount = thumbnailsData.length;
            thumbnailsData = thumbnailsData.filter(t => currentIds.includes(t.id));

            // Remove from processedIds if no longer present (so we can re-process if added back)
            for (const processedId of processedIds) {
                if (!currentIds.includes(processedId)) {
                    processedIds.delete(processedId);
                }
            }

            if (thumbnailsData.length !== initialCount) {
                updateHiddenField(); // Update if items removed
            }

            // 3. Process new files
            for (const item of foundFiles) {
                // If not processed yet, generate
                if (!processedIds.has(item.id)) {
                    processedIds.add(item.id);
                    // Only generate for actual Files (uploads), not blobs/mocks usually
                    generateThumbnail(item.file, item.id);
                }
            }
        }

        setInterval(scanFilePond, 1500);

    })();
</script>