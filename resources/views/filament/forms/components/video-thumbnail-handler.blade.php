<div id="video-thumbnail-handler" style="display: none;"></div>

<script>
    (function () {
        const processedList = [];
        let thumbnailsData = [];

        function isProcessed(id) {
            return processedList.includes(id);
        }

        function markProcessed(id) {
            processedList.push(id);
        }

        function isVideoFile(filename) {
            const videoExtensions = ['.mp4', '.webm', '.ogg', '.mov', '.avi', '.mkv'];
            return videoExtensions.some(ext => filename.toLowerCase().endsWith(ext));
        }

        function updateHiddenField() {
            const jsonData = JSON.stringify(thumbnailsData);

            // Update Livewire state directly - find the form component
            if (window.Livewire) {
                try {
                    // Find the component that contains our hidden input
                    const hiddenInput = document.getElementById('form.video_thumbnails_store');
                    if (hiddenInput) {
                        const formElement = hiddenInput.closest('[wire\\:id]');
                        if (formElement) {
                            const wireId = formElement.getAttribute('wire:id');
                            const component = window.Livewire.find(wireId);
                            if (component) {
                                component.set('data.video_thumbnails_store', jsonData);
                                console.log('Updated Livewire state with', thumbnailsData.length, 'thumbnails');
                            }
                        }
                    }
                } catch (e) {
                    console.error('Failed to update Livewire state:', e);
                }
            }

            // Also update DOM as fallback
            const hiddenInput = document.getElementById('form.video_thumbnails_store');
            if (hiddenInput) {
                hiddenInput.value = jsonData;
                hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }

        async function generateThumbnail(fileObject, id) {
            try {
                const video = document.createElement('video');
                video.src = URL.createObjectURL(fileObject);
                video.currentTime = 1;
                video.muted = true;

                await new Promise(resolve => {
                    video.onseeked = resolve;
                    video.onerror = resolve;
                    setTimeout(resolve, 3000);
                });

                if (video.videoWidth === 0) {
                    console.warn('Video has no dimensions, skipping thumbnail');
                    return;
                }

                // Calculate resized dimensions (max width 640px)
                const MAX_WIDTH = 640;
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
                const base64 = canvas.toDataURL('image/jpeg', 0.6); // Lower quality to 0.6

                // Check if this file is already in our local array to prevent duplicates
                if (!thumbnailsData.some(t => t.filename === fileObject.name)) {
                    // Add new thumbnail to our local array
                    thumbnailsData.push({
                        id: id,
                        filename: fileObject.name,
                        thumbnail: base64
                    });

                    // Update the hidden field
                    updateHiddenField();

                    console.log('Thumbnail generated for:', fileObject.name);
                } else {
                    console.log('Thumbnail already exists for:', fileObject.name);
                }

                URL.revokeObjectURL(video.src);
            } catch (e) {
                console.error('Thumbnail generation failed:', e);
            }
        }

        function scanFilePond() {
            if (typeof FilePond === 'undefined') return;

            const roots = document.querySelectorAll('.filepond--root');
            for (const root of roots) {
                const pond = FilePond.find(root);
                if (!pond) continue;

                const files = pond.getFiles();
                for (const item of files) {
                    if (!item.file || !item.file.type.startsWith('video/')) continue;

                    const id = item.serverId || item.id;
                    if (!id || isProcessed(id)) continue;

                    console.log('Processing video:', item.file.name);
                    markProcessed(id);
                    generateThumbnail(item.file, id);
                }
            }
        }

        // Initialize
        console.log('Video Thumbnail Handler Initialized');

        // Scan periodically for FilePond uploads
        setInterval(scanFilePond, 2000);

        // Before form submit, ensure hidden field is updated
        document.addEventListener('submit', function (e) {
            if (thumbnailsData.length > 0) {
                updateHiddenField();
                console.log('Form submitting with', thumbnailsData.length, 'video thumbnails');
            }
        }, true);
    })();
</script>