<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
        $translations = is_array($translations ?? null) ? $translations : [];
        $sharedPageTitle = $translations['shared_page_title'] ?? 'Shared - eXtplorer';
    ?>
    <title><?= esc($sharedPageTitle) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('favicon.svg') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/remixicon.css') ?>">
    <?php
        $translations = is_array($translations ?? null) ? $translations : [];
        $locale = is_string($locale ?? null) && $locale !== '' ? $locale : 'en';
        $st = static function (string $key, string $fallback = '') use ($translations): string {
            $value = $translations[$key] ?? null;
            if (is_string($value) && $value !== '') {
                return $value;
            }
            return $fallback !== '' ? $fallback : $key;
        };

        $shareTitle = $share['subject'] ?? (
            $is_file
                ? ($filename ?? basename($share['path'] ?? $st('shared_item', 'Shared Item')))
                : basename($share['path'] ?? $st('shared_content', 'Shared Content'))
        );
        if (($share['source'] ?? '') === 'transfer' && empty($share['subject'])) {
            $shareTitle = $st('shared_file_transfer', 'File Transfer');
        }
        $shareMode = $share['mode'] ?? 'read';
        $shareModeLabels = [
            'read' => $st('shared_mode_read', 'Read'),
            'upload' => $st('shared_mode_upload', 'Upload'),
            'write' => $st('shared_mode_write', 'Write'),
        ];
        $shareModeLabel = $shareModeLabels[$shareMode] ?? ucfirst($shareMode);
        $shareExpiresAt = $share['expires_at'] ?? null;
        $shareCreatedBy = $share['created_by'] ?? null;
        $shareSender = $share['sender_email'] ?? null;
    ?>
    <style <?= csp_style_nonce() ?>>
        body, html { height: 100%; background-color: #f8f9fa; }
        .shared-container { max-width: 1100px; margin: 0 auto; height: 100%; display: flex; flex-direction: column; background: white; box-shadow: 0 0 20px rgba(0,0,0,0.05); }
        .shared-header { padding: 1rem 1.25rem; border-bottom: 1px solid #eee; display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; }
        .shared-brand { display: flex; align-items: center; gap: 0.75rem; }
        .shared-title { margin: 0; font-weight: 600; }
        .shared-subtitle { margin: 0.1rem 0 0 0; }
        .shared-meta { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 0.5rem; }
        .shared-meta .badge { font-weight: 500; }
        .shared-meta-pill { background: var(--bs-tertiary-bg); color: var(--bs-body-color); border: 1px solid var(--bs-border-color); }
        .shared-actions { display: flex; flex-direction: column; align-items: flex-end; gap: 0.4rem; }
        .shared-actions .btn { white-space: nowrap; }
        .file-list { flex: 1; overflow-y: auto; padding: 1rem; }
        .shared-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.25rem 0.25rem 0.75rem 0.25rem; border-bottom: 1px solid #f0f0f0; margin-bottom: 0.75rem; }
        .shared-toolbar-left { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
        .shared-toolbar-right { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
        .select-auto-width { width: auto; }
        .shared-breadcrumbs { display: flex; flex-wrap: wrap; align-items: center; gap: 0.25rem; padding: 0.25rem 0.25rem 0.5rem 0.25rem; }
        .shared-breadcrumbs .btn { padding: 0; }
        .file-item { display: flex; align-items: center; padding: 0.75rem; border-bottom: 1px solid #f0f0f0; cursor: pointer; }
        .file-item:hover { background-color: #f8f9fa; }
        .file-item-name { flex: 1; min-width: 0; }
        .file-name { flex: 1; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .file-submeta { font-size: 0.8rem; color: #9aa0a6; margin-top: 0.1rem; }
        .file-icon { font-size: 1.5rem; margin-right: 1rem; color: #6c757d; }
        .file-meta { font-size: 0.85rem; color: #adb5bd; }
        .file-actions { display: flex; align-items: center; gap: 0.25rem; margin-left: 0.5rem; }
        .file-actions .btn { opacity: 0.85; }
        .file-actions .btn:hover { opacity: 1; }
        .preview-box { display: flex; align-items: center; justify-content: center; height: 100%; flex-direction: column; }
        .preview-icon { font-size: 5rem; color: #dee2e6; }
        .max-h-80vh { max-height: 80vh; }
        .preview-modal-body { min-height: 400px; background: #000; }
        .preview-pdf-frame { height: 70vh; border: none; }
        .shared-empty { text-align: center; color: #6c757d; padding: 3rem 1rem; }
        .shared-empty i { font-size: 2.5rem; color: #c2c7cc; display: block; margin-bottom: 0.5rem; }

        @media (max-width: 767.98px) {
            .shared-container { max-width: 100%; min-height: 100vh; box-shadow: none; }
            .shared-header { flex-direction: column; align-items: stretch; }
            .shared-actions { align-items: stretch; }
        }
    </style>
</head>
<body>
    <div id="app" class="shared-container">
        <div class="shared-header">
            <div>
                <div class="shared-brand">
                    <img src="<?= base_url('logo-dark.svg') ?>" height="32" alt="eXtplorer logo">
                    <div>
                        <h5 class="shared-title"><?= esc($shareTitle) ?></h5>
                        <p class="shared-subtitle text-muted small"><?= esc($st('shared_via', 'Shared via eXtplorer')) ?></p>
                    </div>
                </div>
                <div class="shared-meta">
                    <span class="badge shared-meta-pill">
                        <i class="ri-shield-check-line me-1"></i><?= esc($shareModeLabel) ?>
                    </span>
                    <?php if ($shareExpiresAt): ?>
                        <span class="badge shared-meta-pill">
                            <i class="ri-timer-line me-1"></i><?= esc($st('shared_expires', 'Expires')) ?> <?= esc(date('Y-m-d', (int)$shareExpiresAt)) ?>
                        </span>
                    <?php else: ?>
                        <span class="badge shared-meta-pill">
                            <i class="ri-infinity-line me-1"></i><?= esc($st('shared_no_expiry', 'No expiry')) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($shareSender): ?>
                        <span class="badge shared-meta-pill">
                            <i class="ri-mail-line me-1"></i><?= esc($shareSender) ?>
                        </span>
                    <?php elseif ($shareCreatedBy): ?>
                        <span class="badge shared-meta-pill">
                            <i class="ri-user-3-line me-1"></i><?= esc($shareCreatedBy) ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!$is_file): ?>
                        <span v-if="!loading" class="badge shared-meta-pill">
                            <i class="ri-folders-line me-1"></i>{{ summaryText }}
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="shared-actions">
                <?php if ($is_file): ?>
                <a href="<?= site_url('s/' . $hash . '/download') ?>" class="btn btn-primary btn-sm">
                    <i class="ri-download-line me-1"></i><?= esc($st('shared_download', 'Download')) ?>
                </a>
                <?php elseif (isset($share['mode']) && $share['mode'] === 'upload'): ?>
                    <span class="badge bg-warning text-dark"><?= esc($st('shared_upload_only', 'Upload Only')) ?></span>
                <?php else: ?>
                    <a href="<?= site_url('s/' . $hash . '/download') ?>" class="btn btn-primary btn-sm">
                        <i class="ri-download-2-line me-1"></i><?= esc($st('shared_download_all', 'Download All')) ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="file-list">
            <?php if ($is_file): ?>
                <div class="preview-box">
                    <i class="ri-file-text-line preview-icon"></i>
                    <h4 class="mt-3"><?= esc($filename) ?></h4>
                    <p class="text-muted"><?= number_format($size / 1024, 2) ?> KB</p>
                    <a href="<?= site_url('s/' . $hash . '/download') ?>" class="btn btn-primary mt-3"><?= esc($st('shared_download_file', 'Download File')) ?></a>
                </div>
            <?php else: ?>
                <div v-if="loading" class="text-center mt-5"><div class="spinner-border text-primary"></div></div>
                <div v-else>
                    <div class="shared-breadcrumbs">
                        <template v-for="(crumb, idx) in breadcrumbs" :key="crumb.path || 'root'">
                            <button type="button" class="btn btn-link btn-sm text-decoration-none" @click="navigateTo(crumb.path)">
                                <i v-if="idx === 0" class="ri-home-5-line me-1"></i>{{ crumb.label }}
                            </button>
                            <span v-if="idx < breadcrumbs.length - 1" class="text-muted small">/</span>
                        </template>
                    </div>

                    <div class="shared-toolbar">
                        <div class="shared-toolbar-left">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                                <input
                                    v-model.trim="searchQuery"
                                    type="search"
                                    class="form-control"
                                    :placeholder="t('shared_search_placeholder', 'Search files')"
                                    :aria-label="t('shared_search_placeholder', 'Search files')"
                                >
                            </div>
                        </div>
                        <div class="shared-toolbar-right">
                            <label class="small text-muted">{{ t('shared_sort_label', 'Sort') }}</label>
                            <select v-model="sortKey" class="form-select form-select-sm select-auto-width" :aria-label="t('shared_sort_aria', 'Sort files')">
                                <option value="type_name">{{ t('shared_sort_type_name', 'Type + Name') }}</option>
                                <option value="name">{{ t('shared_sort_name', 'Name') }}</option>
                                <option value="mtime_desc">{{ t('shared_sort_newest', 'Newest') }}</option>
                                <option value="mtime_asc">{{ t('shared_sort_oldest', 'Oldest') }}</option>
                                <option value="size_desc">{{ t('shared_sort_largest', 'Largest') }}</option>
                                <option value="size_asc">{{ t('shared_sort_smallest', 'Smallest') }}</option>
                            </select>
                            <span class="small text-muted">{{ t('shared_items_count', '{count} items', { count: filteredFiles.length }) }}</span>
                        </div>
                    </div>

                    <div v-for="file in filteredFiles" :key="file.name" class="file-item" @click="open(file)">
                        <i :class="getIcon(file)" class="file-icon"></i>
                        <div class="file-item-name">
                            <div class="file-name">{{ file.name }}</div>
                            <div class="file-submeta">{{ file.type === 'dir' ? t('shared_folder', 'Folder') : fileExtLabel(file.name) }}</div>
                        </div>
                        <div class="file-meta me-3">{{ formatSize(file.size) }}</div>
                        <div class="file-meta" :title="formatDate(file.mtime)">{{ formatRelativeDate(file.mtime) }}</div>
                        <div class="file-actions">
                            <button
                                v-if="file.type !== 'dir' && isPreviewable(file)"
                                type="button"
                                class="btn btn-link btn-sm p-0"
                                @click.stop="previewItem(file)"
                                :title="t('shared_preview', 'Preview')"
                                :aria-label="t('shared_preview_file', 'Preview file')"
                            >
                                <i class="ri-eye-line"></i>
                            </button>
                            <button
                                v-if="file.type !== 'dir'"
                                type="button"
                                class="btn btn-link btn-sm p-0"
                                @click.stop="downloadItem(file)"
                                :title="t('shared_download', 'Download')"
                                :aria-label="t('shared_download_file', 'Download file')"
                            >
                                <i class="ri-download-line"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div v-if="filteredFiles.length === 0" class="shared-empty">
                        <i class="ri-folder-open-line"></i>
                        <p class="mb-1">{{ t('shared_empty_no_match', 'No files match your view.') }}</p>
                        <p class="small text-muted mb-0" v-if="files.length > 0">{{ t('shared_empty_try_clear', 'Try clearing the search or changing the sort.') }}</p>
                        <p class="small text-muted mb-0" v-else>{{ t('shared_empty_folder_empty', 'This folder is empty.') }}</p>
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
        const { createApp, ref, reactive, computed, onMounted } = Vue;
        const hash = "<?= $hash ?>";
        const baseUrl = "<?= base_url() ?>";
        const isFile = <?= $is_file ? 'true' : 'false' ?>;
        const share = <?= json_encode($share, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        const locale = "<?= esc($locale) ?>";
        const translations = <?= json_encode($translations, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

        const t = (key, fallback = '', params = null) => {
            let value = translations[key];
            if (typeof value !== 'string' || value.length === 0) {
                value = fallback || key;
            }
            if (params && typeof params === 'object') {
                for (const [paramKey, paramValue] of Object.entries(params)) {
                    value = value.replaceAll(`{${paramKey}}`, String(paramValue));
                }
            }
            return value;
        };

        createApp({
            setup() {
                const files = ref([]);
                const loading = ref(false);
                const currentPath = ref('');
                const searchQuery = ref('');
                const sortKey = ref('type_name');
                const previewState = reactive({ src: '', type: '', filename: '' });
                let previewModal = null;

                const loadPath = async (path = '') => {
                    loading.value = true;
                    try {
                        const res = await fetch(baseUrl + 's/' + hash + '/ls?path=' + encodeURIComponent(path))
                            .then(r => r.json());
                        
                        files.value = Array.isArray(res.items) ? res.items : [];
                        currentPath.value = path;
                        searchQuery.value = '';
                    } catch(e) { console.error(e); }
                    finally { loading.value = false; }
                };

                const previewableExts = new Set(['jpg','jpeg','png','gif','webp','svg','mp4','webm','ogv','mp3','wav','ogg','pdf']);
                const getExt = (name) => (name.split('.').pop() || '').toLowerCase();

                const isPreviewable = (file) => {
                    if (!file || file.type === 'dir') return false;
                    return previewableExts.has(getExt(file.name || ''));
                };

                const buildPath = (file) => currentPath.value ? `${currentPath.value}/${file.name}` : file.name;

                const downloadItem = (file) => {
                    if (!file || file.type === 'dir') return;
                    const path = buildPath(file);
                    window.location.href = baseUrl + 's/' + hash + '/download?path=' + encodeURIComponent(path);
                };

                const previewItem = (file) => {
                    open(file);
                };

                const open = (file) => {
                    if (file.type === 'dir') {
                        const newPath = buildPath(file);
                        loadPath(newPath);
                    } else {
                        const ext = getExt(file.name || '');
                        const imgExts = ['jpg','jpeg','png','gif','webp','svg'];
                        const vidExts = ['mp4','webm','ogv'];
                        const audExts = ['mp3','wav','ogg'];
                        
                        if (imgExts.includes(ext) || vidExts.includes(ext) || audExts.includes(ext) || ext === 'pdf') {
                            previewState.filename = file.name;
                            const path = buildPath(file);
                            previewState.src = baseUrl + 's/' + hash + '/download?path=' + encodeURIComponent(path) + '&inline=1';
                            
                            if (imgExts.includes(ext)) previewState.type = 'image';
                            else if (vidExts.includes(ext)) previewState.type = 'video';
                            else if (audExts.includes(ext)) previewState.type = 'audio';
                            else if (ext === 'pdf') previewState.type = 'pdf';
                            
                            previewModal.show();
                        } else {
                            // Download file
                            downloadItem(file);
                        }
                    }
                };

                const goUp = () => {
                    if (!currentPath.value) return;
                    const parts = currentPath.value.split('/');
                    parts.pop();
                    loadPath(parts.join('/'));
                };

                const navigateTo = (path) => {
                    loadPath(path || '');
                };

                const breadcrumbs = computed(() => {
                    if (!currentPath.value) {
                        return [{ label: t('shared_root', 'Root'), path: '' }];
                    }
                    const parts = currentPath.value.split('/').filter(Boolean);
                    const crumbs = [{ label: t('shared_root', 'Root'), path: '' }];
                    let acc = '';
                    for (const part of parts) {
                        acc = acc ? `${acc}/${part}` : part;
                        crumbs.push({ label: part, path: acc });
                    }
                    return crumbs;
                });

                const filteredFiles = computed(() => {
                    const q = searchQuery.value.trim().toLowerCase();
                    const base = q
                        ? files.value.filter((f) => (f.name || '').toLowerCase().includes(q))
                        : files.value.slice();

                    const dirFirst = (a, b) => {
                        if (a.type !== b.type) return a.type === 'dir' ? -1 : 1;
                        return 0;
                    };

                    const byName = (a, b) => (a.name || '').localeCompare(b.name || '');
                    const byMtime = (a, b) => (Number(a.mtime) || 0) - (Number(b.mtime) || 0);
                    const bySize = (a, b) => (Number(a.size) || 0) - (Number(b.size) || 0);

                    base.sort((a, b) => {
                        const df = dirFirst(a, b);
                        if (df !== 0 && sortKey.value !== 'name') return df;

                        switch (sortKey.value) {
                            case 'name':
                                return byName(a, b);
                            case 'mtime_asc':
                                return byMtime(a, b) || byName(a, b);
                            case 'mtime_desc':
                                return -byMtime(a, b) || byName(a, b);
                            case 'size_asc':
                                return bySize(a, b) || byName(a, b);
                            case 'size_desc':
                                return -bySize(a, b) || byName(a, b);
                            case 'type_name':
                            default:
                                return df || byName(a, b);
                        }
                    });

                    return base;
                });

                const summaryText = computed(() => {
                    if (isFile) return '';
                    const count = filteredFiles.value.length;
                    const size = filteredFiles.value.reduce((acc, f) => acc + (f.type === 'dir' ? 0 : (Number(f.size) || 0)), 0);
                    const countLabel = count === 1
                        ? t('shared_item_singular', 'item')
                        : t('shared_item_plural', 'items');
                    return `${count} ${countLabel} · ${formatSize(size)}`;
                });

                const fileExtLabel = (name) => {
                    const ext = (name.split('.').pop() || '').toUpperCase();
                    if (!ext) return t('shared_file', 'File');
                    return t('shared_file_ext', '{ext} file', { ext });
                };

                const formatRelativeDate = (t) => {
                    const ts = Number(t) || 0;
                    if (!ts) return t('shared_none', '—');
                    const diffSec = Math.max(0, Math.floor(Date.now() / 1000) - ts);
                    const day = 86400;
                    const hour = 3600;
                    if (diffSec < 60) {
                        return t('shared_just_now', 'just now');
                    }
                    if (diffSec < hour) {
                        const mins = Math.max(1, Math.floor(diffSec / 60));
                        return t('shared_minutes_ago', '{count}m ago', { count: mins });
                    }
                    if (diffSec < day) {
                        const hours = Math.floor(diffSec / hour);
                        return t('shared_hours_ago', '{count}h ago', { count: hours });
                    }
                    const days = Math.floor(diffSec / day);
                    if (days < 30) return t('shared_days_ago', '{count}d ago', { count: days });
                    return formatDate(ts);
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
                    if (b === 0 || !b) return t('shared_none', '-');
                    const k = 1024, sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
                    const i = Math.floor(Math.log(b) / Math.log(k));
                    return parseFloat((b / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                };
                const formatDate = (t) => new Date(t * 1000).toLocaleDateString();

                onMounted(() => {
                    loadPath();
                    previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
                });

                return {
                    share,
                    locale,
                    t,
                    files,
                    filteredFiles,
                    loading,
                    currentPath,
                    searchQuery,
                    sortKey,
                    breadcrumbs,
                    summaryText,
                    open,
                    goUp,
                    navigateTo,
                    getIcon,
                    isPreviewable,
                    downloadItem,
                    previewItem,
                    formatSize,
                    formatDate,
                    formatRelativeDate,
                    fileExtLabel,
                    previewState
                };
            }
        }).mount('#app');
    </script>
    <?php endif; ?>
</body>
</html>
