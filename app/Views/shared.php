<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared - eXtplorer</title>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('favicon.svg') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/remixicon.css') ?>">
    <style <?= csp_style_nonce() ?>>
        body, html { height: 100%; background-color: #f8f9fa; }
        .shared-container { max-width: 1000px; margin: 0 auto; height: 100%; display: flex; flex-direction: column; background: white; box-shadow: 0 0 20px rgba(0,0,0,0.05); }
        .shared-header { padding: 1rem; border-bottom: 1px solid #eee; display: flex; align-items: center; justify-content: space-between; }
        .file-list { flex: 1; overflow-y: auto; padding: 1rem; }
        .file-item { display: flex; align-items: center; padding: 0.75rem; border-bottom: 1px solid #f0f0f0; cursor: pointer; }
        .file-item:hover { background-color: #f8f9fa; }
        .file-icon { font-size: 1.5rem; margin-right: 1rem; color: #6c757d; }
        .file-name { flex: 1; font-weight: 500; }
        .file-meta { font-size: 0.85rem; color: #adb5bd; }
        .preview-box { display: flex; align-items: center; justify-content: center; height: 100%; flex-direction: column; }
        .preview-icon { font-size: 5rem; color: #dee2e6; }
        .max-h-80vh { max-height: 80vh; }
        .preview-modal-body { min-height: 400px; background: #000; }
        .preview-pdf-frame { height: 70vh; border: none; }
    </style>
</head>
<body>
    <div id="app" class="shared-container">
        <div class="shared-header">
            <div class="d-flex align-items-center">
                <img src="<?= base_url('logo-dark.svg') ?>" height="32" class="me-3">
                <h5 class="mb-0 text-muted">Shared Content</h5>
            </div>
            <div>
                <?php if ($is_file): ?>
                <a href="<?= site_url('s/' . $hash . '/download') ?>" class="btn btn-primary btn-sm">
                    <i class="ri-download-line me-1"></i> Download
                </a>
                <?php elseif (isset($share['mode']) && $share['mode'] === 'upload'): ?>
                    <span class="badge bg-warning text-dark">Dropzone (Upload Only)</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="file-list">
            <?php if ($is_file): ?>
                <div class="preview-box">
                    <i class="ri-file-text-line preview-icon"></i>
                    <h4 class="mt-3"><?= esc($filename) ?></h4>
                    <p class="text-muted"><?= number_format($size / 1024, 2) ?> KB</p>
                    <a href="<?= site_url('s/' . $hash . '/download') ?>" class="btn btn-primary mt-3">Download File</a>
                </div>
            <?php else: ?>
                <div v-if="loading" class="text-center mt-5"><div class="spinner-border text-primary"></div></div>
                <div v-else>
                    <div v-if="currentPath" class="mb-3 px-2">
                        <button class="btn btn-link btn-sm p-0 text-decoration-none" @click="goUp">
                            <i class="ri-arrow-up-line"></i> Go Up
                        </button>
                        <span class="ms-2 text-muted small">/ {{ currentPath }}</span>
                    </div>

                    <div v-for="file in files" :key="file.name" class="file-item" @click="open(file)">
                        <i :class="getIcon(file)" class="file-icon"></i>
                        <div class="file-name">{{ file.name }}</div>
                        <div class="file-meta me-3">{{ formatSize(file.size) }}</div>
                        <div class="file-meta">{{ formatDate(file.mtime) }}</div>
                    </div>
                    
                    <div v-if="files.length === 0" class="text-center text-muted mt-5">
                        <p>Empty directory</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Preview Modal -->
        <div class="modal fade" id="previewModal" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content bg-dark border-0 shadow-lg">
                    <div class="modal-header border-0 py-2">
                        <h6 class="modal-title text-white">{{ previewState.filename }}</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-0 text-center position-relative d-flex align-items-center justify-content-center preview-modal-body">
                        <img v-if="previewState.type === 'image'" :src="previewState.src" class="img-fluid rounded max-h-80vh">
                        <video v-if="previewState.type === 'video'" :src="previewState.src" controls autoplay class="w-100 max-h-80vh"></video>
                        <div v-if="previewState.type === 'audio'" class="p-5 w-100">
                            <i class="ri-music-2-line fs-1 text-white-50 d-block mb-3"></i>
                            <audio :src="previewState.src" controls autoplay class="w-100"></audio>
                        </div>
                        <iframe v-if="previewState.type === 'pdf'" :src="previewState.src" class="w-100 preview-pdf-frame"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$is_file): ?>
    <script src="<?= base_url('assets/js/vue.global.js') ?>"></script>
    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script <?= csp_script_nonce() ?>>
        const { createApp, ref, reactive, onMounted } = Vue;
        const hash = "<?= $hash ?>";
        const baseUrl = "<?= base_url() ?>";

        createApp({
            setup() {
                const files = ref([]);
                const loading = ref(false);
                const currentPath = ref('');
                const previewState = reactive({ src: '', type: '', filename: '' });
                let previewModal = null;

                const loadPath = async (path = '') => {
                    loading.value = true;
                    try {
                        const res = await fetch(baseUrl + 's/' + hash + '/ls?path=' + encodeURIComponent(path))
                            .then(r => r.json());
                        
                        files.value = res.items.sort((a,b) => {
                             if (a.type !== b.type) return a.type === 'dir' ? -1 : 1;
                             return a.name.localeCompare(b.name);
                        });
                        currentPath.value = path;
                    } catch(e) { console.error(e); }
                    finally { loading.value = false; }
                };

                const open = (file) => {
                    if (file.type === 'dir') {
                        const newPath = currentPath.value ? currentPath.value + '/' + file.name : file.name;
                        loadPath(newPath);
                    } else {
                        const ext = file.name.split('.').pop().toLowerCase();
                        const imgExts = ['jpg','jpeg','png','gif','webp','svg'];
                        const vidExts = ['mp4','webm','ogv'];
                        const audExts = ['mp3','wav','ogg'];
                        
                        if (imgExts.includes(ext) || vidExts.includes(ext) || audExts.includes(ext) || ext === 'pdf') {
                            previewState.filename = file.name;
                            const path = currentPath.value ? currentPath.value + '/' + file.name : file.name;
                            previewState.src = baseUrl + 's/' + hash + '/download?path=' + encodeURIComponent(path) + '&inline=1';
                            
                            if (imgExts.includes(ext)) previewState.type = 'image';
                            else if (vidExts.includes(ext)) previewState.type = 'video';
                            else if (audExts.includes(ext)) previewState.type = 'audio';
                            else if (ext === 'pdf') previewState.type = 'pdf';
                            
                            previewModal.show();
                        } else {
                            // Download file
                            const path = currentPath.value ? currentPath.value + '/' + file.name : file.name;
                            window.location.href = baseUrl + 's/' + hash + '/download?path=' + encodeURIComponent(path);
                        }
                    }
                };

                const goUp = () => {
                    if (!currentPath.value) return;
                    const parts = currentPath.value.split('/');
                    parts.pop();
                    loadPath(parts.join('/'));
                };

                const getIcon = (f) => {
                    if (f.type === 'dir') return 'ri-folder-fill text-warning';
                    const ext = f.name.split('.').pop().toLowerCase();
                    if (['jpg','jpeg','png','gif','svg','webp'].includes(ext)) return 'ri-image-fill text-success';
                    if (['mp4','webm','ogv'].includes(ext)) return 'ri-movie-fill text-info';
                    if (['pdf'].includes(ext)) return 'ri-file-pdf-line text-danger';
                    return 'ri-file-line text-secondary';
                };
                
                const formatSize = (b) => {
                    if (b === 0 || !b) return '-';
                    const k = 1024, sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
                    const i = Math.floor(Math.log(b) / Math.log(k));
                    return parseFloat((b / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                };
                const formatDate = (t) => new Date(t*1000).toLocaleDateString();

                onMounted(() => {
                    loadPath();
                    previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
                });

                return { files, loading, currentPath, open, goUp, getIcon, formatSize, formatDate, previewState };
            }
        }).mount('#app');
    </script>
    <?php endif; ?>
</body>
</html>
