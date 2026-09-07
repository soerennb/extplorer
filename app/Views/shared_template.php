    <div id="app" class="shared-container">
        <div class="shared-header">
            <div>
                <div class="shared-brand">
                    <img :src="baseUrl + 'logo-dark.svg'" height="32" alt="eXtplorer logo">
                    <div>
                        <h5 class="shared-title">{{ shareTitle }}</h5>
                        <p class="shared-subtitle text-muted small">{{ t('shared_via', 'Shared via eXtplorer') }}</p>
                    </div>
                </div>
                <div class="shared-meta">
                    <span class="badge shared-meta-pill">
                        <i class="ri-shield-check-line me-1"></i>{{ shareModeLabel }}
                    </span>
                    <span v-if="shareExpiresAt" class="badge shared-meta-pill">
                        <i class="ri-timer-line me-1"></i>{{ t('shared_expires', 'Expires') }} {{ shareExpiresDate }}
                    </span>
                    <span v-else class="badge shared-meta-pill">
                        <i class="ri-infinity-line me-1"></i>{{ t('shared_no_expiry', 'No expiry') }}
                    </span>
                    <span v-if="shareSender" class="badge shared-meta-pill">
                        <i class="ri-mail-line me-1"></i>{{ shareSender }}
                    </span>
                    <span v-else-if="shareCreatedBy" class="badge shared-meta-pill">
                        <i class="ri-user-3-line me-1"></i>{{ shareCreatedBy }}
                    </span>
                    <span v-if="!isFile && !loading" class="badge shared-meta-pill">
                        <i class="ri-folders-line me-1"></i>{{ summaryText }}
                    </span>
                </div>
            </div>
            <div class="shared-actions">
                <a v-if="isFile" :href="baseUrl + 's/' + hash + '/download'" class="btn btn-primary btn-sm">
                    <i class="ri-download-line me-1"></i>{{ t('shared_download', 'Download') }}
                </a>
                <span v-else-if="uploadMode" class="badge bg-warning text-dark">{{ t('shared_upload_only', 'Upload Only') }}</span>
                <a v-else :href="baseUrl + 's/' + hash + '/download'" class="btn btn-primary btn-sm">
                        <i class="ri-download-2-line me-1"></i>{{ t('shared_download_all', 'Download All') }}
                    </a>
            </div>
        </div>

        <div class="file-list">
            <div v-if="isFile" class="preview-box">
                    <i class="ri-file-text-line preview-icon"></i>
                    <h4 class="mt-3">{{ filename }}</h4>
                    <p class="text-muted">{{ formatSize(size) }}</p>
                    <a :href="baseUrl + 's/' + hash + '/download'" class="btn btn-primary mt-3">{{ t('shared_download_file', 'Download File') }}</a>
                </div>
            <template v-else>
                <div v-if="loading" class="text-center mt-5"><div class="spinner-border text-primary"></div></div>
                <div v-else>
                        <div v-if="uploadMode" class="shared-upload-panel" role="status" aria-live="polite">
                            <div class="shared-upload-panel-icon">
                                <i class="ri-upload-cloud-2-line"></i>
                            </div>
                            <div>
                            <div class="shared-upload-panel-title">{{ t('shared_upload_panel_title', 'Upload-Only Share') }}</div>
                            <div class="small">{{ t('shared_upload_panel_desc', 'You can upload files to this share. Files already here may not be downloadable.') }}</div>
                            <div class="shared-upload-panel-note">{{ t('shared_upload_panel_note', 'You can drag and drop files here.') }}</div>
                                <div
                                    class="shared-dropzone"
                                    :class="{ active: dropzoneActive }"
                                    @dragenter.prevent="onDragEnter"
                                    @dragover.prevent="onDragOver"
                                    @dragleave.prevent="onDragLeave"
                                    @drop.prevent="onDrop"
                                >
                                    <div class="shared-dropzone-title">{{ t('shared_dropzone_title', 'Drop files to upload') }}</div>
                                    <div class="shared-dropzone-subtitle">{{ t('shared_dropzone_subtitle', 'or choose files from your device') }}</div>
                                    <div class="shared-upload-actions">
                                        <button type="button" class="btn btn-primary btn-sm" @click="openFilePicker">
                                            <i class="ri-upload-2-line me-1"></i>{{ uploading ? t('shared_uploading', 'Uploading...') : t('shared_upload_button', 'Upload Files') }}
                                        </button>
                                        <button v-if="uploading" type="button" class="btn btn-outline-secondary btn-sm" disabled>
                                            {{ overallProgress }}%
                                        </button>
                                    </div>
                                    <div class="shared-upload-limits">
                                        <span class="badge shared-upload-limit-pill">
                                            <template v-if="policyMaxFileMb > 0">
                                                {{ t('shared_upload_limit', 'Max file size: {max} MB', { max: policyMaxFileMb }) }}
                                            </template>
                                            <template v-else>
                                                {{ t('shared_upload_limit_unlimited', 'No file size limit configured') }}
                                            </template>
                                        </span>
                                        <span v-if="hasAllowedExtensions" class="badge shared-upload-limit-pill">
                                            {{ t('shared_allowed_types', 'Allowed types: {types}', { types: allowedExtensionsLabel }) }}
                                        </span>
                                        <span v-if="hasQuotaLimit" class="badge shared-upload-limit-pill">
                                            {{ t('shared_quota_remaining', 'Remaining quota: {remaining}', { remaining: formatSize(quotaRemainingBytes) }) }}
                                        </span>
                                        <span v-if="hasFileCountLimit" class="badge shared-upload-limit-pill">
                                            {{ t('shared_files_remaining', 'Remaining files: {count}', { count: filesRemaining }) }}
                                        </span>
                                    </div>
                                    <input ref="fileInput" type="file" class="d-none" multiple @change="onFileInputChange">
                                </div>
                                <div v-if="uploadMessage" class="alert alert-success small mt-2 mb-0 py-2">
                                    {{ uploadMessage }}
                                </div>
                                <div v-if="uploadError" class="alert alert-danger small mt-2 mb-0 py-2">
                                    {{ uploadError }}
                                </div>
                                <div v-if="uploadQueue.length > 0" class="shared-upload-queue">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="small text-muted">{{ queueSummaryText }}</div>
                                        <button
                                            v-if="hasCompletedUploads"
                                            type="button"
                                            class="btn btn-link btn-sm text-decoration-none p-0"
                                            @click="clearCompleted"
                                        >
                                            {{ t('shared_clear_completed', 'Clear completed') }}
                                        </button>
                                    </div>
                                    <div v-for="item in uploadQueue" :key="item.id" class="shared-upload-queue-item">
                                        <div class="d-flex justify-content-between gap-3">
                                            <div class="flex-grow-1">
                                                <div class="shared-upload-queue-name">{{ item.name }}</div>
                                                <div class="shared-upload-queue-meta">{{ formatSize(item.size) }}</div>
                                            </div>
                                            <div class="shared-upload-queue-status">
                                                <span class="badge" :class="queueStatusBadgeClass(item)">{{ queueStatusLabel(item) }}</span>
                                            </div>
                                        </div>
                                        <div class="shared-upload-queue-progress">
                                            <div class="progress progress-compact" role="progressbar" :aria-label="queueStatusLabel(item)" aria-valuemin="0" aria-valuemax="100">
                                                <div class="progress-bar" :class="queueProgressBarClass(item)"></div>
                                            </div>
                                        </div>
                                        <div v-if="item.error" class="small text-danger mt-1">{{ item.error }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                                v-if="!uploadMode && file.type !== 'dir' && isPreviewable(file)"
                                type="button"
                                class="btn btn-link btn-sm p-0"
                                @click.stop="previewItem(file)"
                                :title="t('shared_preview', 'Preview')"
                                :aria-label="t('shared_preview_file', 'Preview file')"
                            >
                                <i class="ri-eye-line"></i>
                            </button>
                            <button
                                v-if="!uploadMode && file.type !== 'dir'"
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
            </template>
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
