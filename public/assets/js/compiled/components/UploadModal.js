const UploadModal = {
    render: ((Vue) => {
const _Vue = Vue
const { createElementVNode: _createElementVNode, createCommentVNode: _createCommentVNode, createTextVNode: _createTextVNode } = _Vue

const _hoisted_1 = {
  class: "modal fade",
  id: "uploadModal",
  tabindex: "-1",
  "data-bs-backdrop": "static"
}
const _hoisted_2 = { class: "modal-dialog modal-lg modal-dialog-centered" }
const _hoisted_3 = { class: "modal-content" }
const _hoisted_4 = { class: "modal-header" }
const _hoisted_5 = { class: "modal-title" }
const _hoisted_6 = ["aria-label"]
const _hoisted_7 = { class: "modal-body" }
const _hoisted_8 = ["onDragover", "onDragleave", "onDrop"]
const _hoisted_9 = ["onChange", "aria-label"]
const _hoisted_10 = ["onChange", "aria-label"]
const _hoisted_11 = { class: "fw-semibold" }
const _hoisted_12 = { class: "text-muted small mt-1" }
const _hoisted_13 = { class: "d-flex flex-wrap justify-content-center gap-2 mt-3" }
const _hoisted_14 = ["onClick", "disabled"]
const _hoisted_15 = ["onClick", "disabled"]
const _hoisted_16 = {
  key: 0,
  class: "alert alert-info small d-flex align-items-center"
}
const _hoisted_17 = { class: "row g-3 mb-3" }
const _hoisted_18 = { class: "col-md-7" }
const _hoisted_19 = {
  class: "form-label small fw-bold",
  for: "uploadConflictSelect"
}
const _hoisted_20 = ["onUpdate:modelValue", "disabled"]
const _hoisted_21 = { value: "replace" }
const _hoisted_22 = { value: "skip" }
const _hoisted_23 = { value: "keep_both" }
const _hoisted_24 = { class: "col-md-5" }
const _hoisted_25 = { class: "small fw-bold mb-2" }
const _hoisted_26 = ["aria-label"]
const _hoisted_27 = { class: "small text-muted mt-1" }
const _hoisted_28 = {
  key: 1,
  class: "text-center small text-muted my-2"
}
const _hoisted_29 = {
  key: 2,
  class: "alert alert-warning small d-flex align-items-center"
}
const _hoisted_30 = {
  key: 3,
  class: "text-center text-muted py-4 border rounded bg-light-subtle"
}
const _hoisted_31 = {
  key: 4,
  class: "list-group list-group-flush mb-3 upload-list"
}
const _hoisted_32 = { class: "d-flex align-items-start justify-content-between gap-3" }
const _hoisted_33 = { class: "d-flex align-items-start text-truncate" }
const _hoisted_34 = { class: "min-w-0" }
const _hoisted_35 = ["title"]
const _hoisted_36 = ["title"]
const _hoisted_37 = { class: "small text-muted" }
const _hoisted_38 = { class: "d-flex align-items-center gap-2 flex-shrink-0" }
const _hoisted_39 = ["onClick"]
const _hoisted_40 = ["onClick", "disabled"]
const _hoisted_41 = ["onClick", "aria-label"]
const _hoisted_42 = ["aria-label"]
const _hoisted_43 = {
  key: 0,
  class: "text-danger small mt-1"
}
const _hoisted_44 = {
  key: 1,
  class: "text-muted small mt-1"
}
const _hoisted_45 = { class: "modal-footer" }
const _hoisted_46 = ["onClick", "disabled"]
const _hoisted_47 = {
  type: "button",
  class: "btn btn-secondary",
  "data-bs-dismiss": "modal",
  "data-testid": "upload-close"
}
const _hoisted_48 = ["onClick", "disabled"]
const _hoisted_49 = {
  key: 0,
  class: "spinner-border spinner-border-sm me-1"
}

return function render(_ctx, _cache) {
  with (_ctx) {
    const { createElementVNode: _createElementVNode, toDisplayString: _toDisplayString, createTextVNode: _createTextVNode, withModifiers: _withModifiers, normalizeClass: _normalizeClass, openBlock: _openBlock, createElementBlock: _createElementBlock, createCommentVNode: _createCommentVNode, vModelSelect: _vModelSelect, withDirectives: _withDirectives, renderList: _renderList, Fragment: _Fragment } = _Vue

    return (_openBlock(), _createElementBlock("div", _hoisted_1, [
      _createElementVNode("div", _hoisted_2, [
        _createElementVNode("div", _hoisted_3, [
          _createElementVNode("div", _hoisted_4, [
            _createElementVNode("h5", _hoisted_5, [
              _cache[0] || (_cache[0] = _createElementVNode("i", { class: "ri-upload-cloud-2-line me-2" }, null, -1 /* CACHED */)),
              _createTextVNode(" " + _toDisplayString(t('upload_files', 'Upload Files')), 1 /* TEXT */)
            ]),
            _createElementVNode("button", {
              type: "button",
              class: "btn-close",
              "data-bs-dismiss": "modal",
              "aria-label": t('close', 'Close')
            }, null, 8 /* PROPS */, _hoisted_6)
          ]),
          _createElementVNode("div", _hoisted_7, [
            _createElementVNode("div", {
              class: _normalizeClass(["upload-dropzone p-4 border border-2 border-dashed rounded text-center mb-3 bg-light", {'border-primary bg-primary-subtle': dragOver}]),
              onDragover: _withModifiers($event => (dragOver = true), ["prevent"]),
              onDragleave: _withModifiers($event => (dragOver = false), ["prevent"]),
              onDrop: _withModifiers(handleDrop, ["prevent"])
            }, [
              _createElementVNode("input", {
                id: "uploadFileInput",
                name: "upload_files",
                type: "file",
                ref: "fileInput",
                multiple: "",
                hidden: "",
                onChange: handleFileSelect,
                "aria-label": t('upload_browse_files', 'Browse files')
              }, null, 40 /* PROPS, NEED_HYDRATION */, _hoisted_9),
              _createElementVNode("input", {
                id: "uploadFolderInput",
                name: "upload_folder_files",
                type: "file",
                ref: "folderInput",
                multiple: "",
                webkitdirectory: "",
                hidden: "",
                onChange: handleFileSelect,
                "aria-label": t('upload_browse_folder', 'Browse folder')
              }, null, 40 /* PROPS, NEED_HYDRATION */, _hoisted_10),
              _cache[3] || (_cache[3] = _createElementVNode("i", { class: "ri-upload-2-line fs-1 text-muted" }, null, -1 /* CACHED */)),
              _createElementVNode("div", _hoisted_11, _toDisplayString(dragOver ? t('upload_drop_active', 'Drop files to queue them') : t('drag_drop_desc', 'Drag files here or click to browse')), 1 /* TEXT */),
              _createElementVNode("div", _hoisted_12, _toDisplayString(t('upload_folder_hint', 'Folder uploads keep their folder structure when supported by the browser.')), 1 /* TEXT */),
              _createElementVNode("div", _hoisted_13, [
                _createElementVNode("button", {
                  type: "button",
                  class: "btn btn-sm btn-outline-primary",
                  onClick: triggerPicker,
                  disabled: !canUploadHere
                }, [
                  _cache[1] || (_cache[1] = _createElementVNode("i", {
                    class: "ri-file-add-line me-1",
                    "aria-hidden": "true"
                  }, null, -1 /* CACHED */)),
                  _createTextVNode(_toDisplayString(t('upload_browse_files', 'Browse files')), 1 /* TEXT */)
                ], 8 /* PROPS */, _hoisted_14),
                _createElementVNode("button", {
                  type: "button",
                  class: "btn btn-sm btn-outline-secondary",
                  onClick: triggerFolderPicker,
                  disabled: !canUploadHere
                }, [
                  _cache[2] || (_cache[2] = _createElementVNode("i", {
                    class: "ri-folder-upload-line me-1",
                    "aria-hidden": "true"
                  }, null, -1 /* CACHED */)),
                  _createTextVNode(_toDisplayString(t('upload_browse_folder', 'Browse folder')), 1 /* TEXT */)
                ], 8 /* PROPS */, _hoisted_15)
              ])
            ], 42 /* CLASS, PROPS, NEED_HYDRATION */, _hoisted_8),
            (!canUploadHere)
              ? (_openBlock(), _createElementBlock("div", _hoisted_16, [
                  _cache[4] || (_cache[4] = _createElementVNode("i", {
                    class: "ri-folder-open-line fs-5 me-2",
                    "aria-hidden": "true"
                  }, null, -1 /* CACHED */)),
                  _createElementVNode("div", null, _toDisplayString(t('upload_select_target_folder', 'Open a writable folder before uploading files.')), 1 /* TEXT */)
                ]))
              : _createCommentVNode("v-if", true),
            _createElementVNode("div", _hoisted_17, [
              _createElementVNode("div", _hoisted_18, [
                _createElementVNode("label", _hoisted_19, _toDisplayString(t('upload_conflict_label', 'If a file already exists')), 1 /* TEXT */),
                _withDirectives(_createElementVNode("select", {
                  id: "uploadConflictSelect",
                  class: "form-select form-select-sm",
                  "onUpdate:modelValue": $event => ((conflictMode) = $event),
                  disabled: uploading
                }, [
                  _createElementVNode("option", _hoisted_21, _toDisplayString(t('upload_conflict_replace', 'Replace existing file')), 1 /* TEXT */),
                  _createElementVNode("option", _hoisted_22, _toDisplayString(t('upload_conflict_skip', 'Skip the new file')), 1 /* TEXT */),
                  _createElementVNode("option", _hoisted_23, _toDisplayString(t('upload_conflict_keep_both', 'Keep both files')), 1 /* TEXT */)
                ], 8 /* PROPS */, _hoisted_20), [
                  [_vModelSelect, conflictMode]
                ])
              ]),
              _createElementVNode("div", _hoisted_24, [
                _createElementVNode("div", _hoisted_25, _toDisplayString(t('upload_total_progress', 'Total progress')), 1 /* TEXT */),
                _createElementVNode("div", {
                  class: "progress progress-compact",
                  "aria-label": t('upload_total_progress', 'Total progress')
                }, [
                  _createElementVNode("div", {
                    class: _normalizeClass(["progress-bar", progressClass(totalProgress)])
                  }, null, 2 /* CLASS */)
                ], 8 /* PROPS */, _hoisted_26),
                _createElementVNode("div", _hoisted_27, _toDisplayString(completedCount) + " / " + _toDisplayString(files.length) + " " + _toDisplayString(t('upload_files_queued', 'files')) + " · " + _toDisplayString(totalProgress) + "%", 1 /* TEXT */)
              ])
            ]),
            configLoading
              ? (_openBlock(), _createElementBlock("div", _hoisted_28, [
                  _cache[5] || (_cache[5] = _createElementVNode("div", { class: "spinner-border spinner-border-sm me-1" }, null, -1 /* CACHED */)),
                  _createTextVNode(" " + _toDisplayString(t('upload_checking_permissions', 'Checking permissions...')), 1 /* TEXT */)
                ]))
              : _createCommentVNode("v-if", true),
            hasBlockedFiles
              ? (_openBlock(), _createElementBlock("div", _hoisted_29, [
                  _cache[6] || (_cache[6] = _createElementVNode("i", {
                    class: "ri-alert-line fs-5 me-2",
                    "aria-hidden": "true"
                  }, null, -1 /* CACHED */)),
                  _createElementVNode("div", null, _toDisplayString(t('blocked_files_msg', 'Some files have blocked extensions and will be skipped.')), 1 /* TEXT */)
                ]))
              : _createCommentVNode("v-if", true),
            (files.length === 0)
              ? (_openBlock(), _createElementBlock("div", _hoisted_30, [
                  _cache[7] || (_cache[7] = _createElementVNode("i", {
                    class: "ri-inbox-line fs-2 d-block mb-2",
                    "aria-hidden": "true"
                  }, null, -1 /* CACHED */)),
                  _createTextVNode(" " + _toDisplayString(t('upload_empty_queue', 'No files queued yet.')), 1 /* TEXT */)
                ]))
              : (_openBlock(), _createElementBlock("div", _hoisted_31, [
                  (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(files, (item) => {
                    return (_openBlock(), _createElementBlock("div", {
                      key: item.id,
                      class: "list-group-item px-0 py-3"
                    }, [
                      _createElementVNode("div", _hoisted_32, [
                        _createElementVNode("div", _hoisted_33, [
                          _createElementVNode("i", {
                            class: _normalizeClass([getFileIcon(item.file.name), "me-2 text-secondary fs-5"]),
                            "aria-hidden": "true"
                          }, null, 2 /* CLASS */),
                          _createElementVNode("div", _hoisted_34, [
                            _createElementVNode("div", {
                              class: "text-truncate fw-semibold",
                              title: item.displayPath
                            }, _toDisplayString(item.file.name), 9 /* TEXT, PROPS */, _hoisted_35),
                            (item.relativePath)
                              ? (_openBlock(), _createElementBlock("div", {
                                  key: 0,
                                  class: "small text-muted text-truncate",
                                  title: item.relativePath
                                }, _toDisplayString(item.relativePath), 9 /* TEXT, PROPS */, _hoisted_36))
                              : _createCommentVNode("v-if", true),
                            _createElementVNode("div", _hoisted_37, _toDisplayString(formatSize(item.file.size)), 1 /* TEXT */)
                          ])
                        ]),
                        _createElementVNode("div", _hoisted_38, [
                          _createElementVNode("span", {
                            class: _normalizeClass(["badge", statusBadgeClass(item)])
                          }, _toDisplayString(statusLabel(item)), 3 /* TEXT, CLASS */),
                          (item.status === 'uploading')
                            ? (_openBlock(), _createElementBlock("button", {
                                key: 0,
                                type: "button",
                                class: "btn btn-sm btn-outline-danger",
                                onClick: $event => (cancelFile(item))
                              }, [
                                _cache[8] || (_cache[8] = _createElementVNode("i", {
                                  class: "ri-stop-circle-line me-1",
                                  "aria-hidden": "true"
                                }, null, -1 /* CACHED */)),
                                _createTextVNode(_toDisplayString(t('cancel', 'Cancel')), 1 /* TEXT */)
                              ], 8 /* PROPS */, _hoisted_39))
                            : _createCommentVNode("v-if", true),
                          (item.status === 'error' || item.status === 'canceled')
                            ? (_openBlock(), _createElementBlock("button", {
                                key: 1,
                                type: "button",
                                class: "btn btn-sm btn-outline-primary",
                                onClick: $event => (retryFile(item)),
                                disabled: uploading
                              }, [
                                _cache[9] || (_cache[9] = _createElementVNode("i", {
                                  class: "ri-restart-line me-1",
                                  "aria-hidden": "true"
                                }, null, -1 /* CACHED */)),
                                _createTextVNode(_toDisplayString(t('upload_retry', 'Retry')), 1 /* TEXT */)
                              ], 8 /* PROPS */, _hoisted_40))
                            : _createCommentVNode("v-if", true),
                          (item.status !== 'uploading')
                            ? (_openBlock(), _createElementBlock("button", {
                                key: 2,
                                type: "button",
                                class: "btn btn-link btn-sm text-secondary",
                                onClick: $event => (removeFile(item)),
                                "aria-label": t('upload_remove_file', 'Remove file') + ': ' + item.file.name
                              }, [...(_cache[10] || (_cache[10] = [
                                _createElementVNode("i", {
                                  class: "ri-close-line",
                                  "aria-hidden": "true"
                                }, null, -1 /* CACHED */)
                              ]))], 8 /* PROPS */, _hoisted_41))
                            : _createCommentVNode("v-if", true)
                        ])
                      ]),
                      _createElementVNode("div", {
                        class: "progress progress-compact mt-2",
                        "aria-label": t('upload_file_progress', 'File progress') + ': ' + item.file.name
                      }, [
                        _createElementVNode("div", {
                          class: _normalizeClass(["progress-bar", progressClass(item.progress)])
                        }, null, 2 /* CLASS */)
                      ], 8 /* PROPS */, _hoisted_42),
                      (item.errorMessage)
                        ? (_openBlock(), _createElementBlock("div", _hoisted_43, _toDisplayString(item.errorMessage), 1 /* TEXT */))
                        : (item.resultPath && item.resultPath !== item.relativePath)
                          ? (_openBlock(), _createElementBlock("div", _hoisted_44, _toDisplayString(t('upload_saved_as', 'Saved as {path}', { path: item.resultPath })), 1 /* TEXT */))
                          : _createCommentVNode("v-if", true)
                    ]))
                  }), 128 /* KEYED_FRAGMENT */))
                ]))
          ]),
          _createElementVNode("div", _hoisted_45, [
            _createElementVNode("button", {
              type: "button",
              class: "btn btn-outline-secondary",
              onClick: clearFinished,
              disabled: uploading || completedCount === 0
            }, _toDisplayString(t('upload_clear_finished', 'Clear finished')), 9 /* TEXT, PROPS */, _hoisted_46),
            _createElementVNode("button", _hoisted_47, _toDisplayString(t('close', 'Close')), 1 /* TEXT */),
            _createElementVNode("button", {
              type: "button",
              class: "btn btn-primary",
              onClick: startUpload,
              disabled: !canUploadHere || uploading || uploadableCount === 0,
              "data-testid": "upload-submit"
            }, [
              uploading
                ? (_openBlock(), _createElementBlock("span", _hoisted_49))
                : _createCommentVNode("v-if", true),
              _createTextVNode(" " + _toDisplayString(uploading ? t('uploading', 'Uploading...') : t('upload', 'Upload')) + " (" + _toDisplayString(uploadableCount) + ") ", 1 /* TEXT */)
            ], 8 /* PROPS */, _hoisted_48)
          ])
        ])
      ])
    ]))
  }
}
})(Vue),
    setup() {
        const { ref, reactive, computed } = Vue;
        const t = (key, fallback = '', params = {}) => {
            const value = i18n.t(key, params);
            return value === key ? (fallback || key) : value;
        };
        const dragOver = ref(false);
        const files = ref([]);
        const fileInput = ref(null);
        const folderInput = ref(null);
        const uploading = ref(false);
        const configLoading = ref(false);
        const conflictMode = ref('replace');
        const config = reactive({ allowed: [], blocked: [], system: [] });
        let modalInstance = null;
        let nextId = 1;

        const loadConfig = async () => {
            configLoading.value = true;
            try {
                const res = await Api.get('profile/details');
                config.allowed = res.allowed_extensions ? res.allowed_extensions.split(',').map(s => s.trim().toLowerCase()) : [];
                config.blocked = res.blocked_extensions ? res.blocked_extensions.split(',').map(s => s.trim().toLowerCase()) : [];
                config.system = res.system_blocklist || [];
            } catch(e) { console.error(e); }
            finally { configLoading.value = false; }
        };

        const isAllowed = (name) => {
            const ext = name.includes('.') ? name.split('.').pop().toLowerCase() : '';
            if (config.allowed.length > 0) {
                return config.allowed.includes(ext) ? true : t('upload_not_allowed', 'Not in allowed list');
            }
            if (config.blocked.includes(ext)) return t('upload_blocked_user', 'In blocked list');
            if (config.system.includes(ext)) return t('upload_blocked_system', 'System blocked extension');
            return true;
        };

        const addFiles = (fileList) => {
            for (const file of Array.from(fileList)) {
                const relativePath = file.webkitRelativePath || '';
                const check = isAllowed(file.name);
                files.value.push({
                    id: nextId++,
                    file,
                    relativePath,
                    displayPath: relativePath || file.name,
                    valid: check === true,
                    error: check === true ? null : check,
                    status: check === true ? 'pending' : 'blocked',
                    progress: 0,
                    uploadedBytes: 0,
                    errorMessage: check === true ? '' : check,
                    resultPath: '',
                    controller: null,
                    xhr: null,
                    uploadSessionId: null
                });
            }
        };

        const handleDrop = (e) => {
            dragOver.value = false;
            if (e.dataTransfer.files.length) addFiles(e.dataTransfer.files);
        };

        const handleFileSelect = (e) => {
            if (e.target.files.length) addFiles(e.target.files);
            e.target.value = '';
        };

        const triggerPicker = () => fileInput.value?.click();
        const triggerFolderPicker = () => folderInput.value?.click();
        const removeFile = (item) => {
            if (item.status === 'uploading') return;
            files.value = files.value.filter((f) => f.id !== item.id);
        };
        const cancelFile = (item) => {
            if (item.xhr) item.xhr.abort();
            if (item.controller) item.controller.abort();
            if (item.uploadSessionId) {
                Api.delete(`upload/session/${encodeURIComponent(item.uploadSessionId)}`).catch(() => {});
                item.uploadSessionId = null;
            }
            item.status = 'canceled';
            item.errorMessage = t('upload_canceled', 'Canceled');
        };
        const retryFile = (item) => {
            item.status = item.valid ? 'pending' : 'blocked';
            item.progress = 0;
            item.uploadedBytes = 0;
            item.errorMessage = item.valid ? '' : item.error;
            item.resultPath = '';
        };
        const clearFinished = () => {
            files.value = files.value.filter((item) => !['done', 'skipped'].includes(item.status));
        };

        const progressClass = (value) => {
            const clamped = Math.max(0, Math.min(100, Math.round(value / 5) * 5));
            return `progress-w-${clamped}`;
        };

        const uploadableCount = computed(() => files.value.filter(f => f.valid && ['pending', 'error', 'canceled'].includes(f.status)).length);
        const completedCount = computed(() => files.value.filter(f => ['done', 'skipped'].includes(f.status)).length);
        const hasBlockedFiles = computed(() => files.value.some(f => f.status === 'blocked'));
        const canUploadHere = computed(() => Boolean(store.cwd && store.cwd !== '/'));
        const totalBytes = computed(() => files.value.filter(f => f.valid).reduce((sum, item) => sum + item.file.size, 0));
        const totalUploadedBytes = computed(() => files.value.filter(f => f.valid).reduce((sum, item) => {
            if (['done', 'skipped'].includes(item.status)) return sum + item.file.size;
            return sum + Math.round(item.file.size * (item.progress / 100));
        }, 0));
        const totalProgress = computed(() => totalBytes.value > 0 ? Math.round((totalUploadedBytes.value / totalBytes.value) * 100) : 0);

        const updateCsrfFromResponse = (res) => {
            if (Api.refreshCsrfToken) Api.refreshCsrfToken(res);
        };

        const uploadSingle = (item, path) => new Promise((resolve, reject) => {
            const fd = new FormData();
            fd.append('file', item.file);
            fd.append('path', path);
            fd.append('relativePath', item.relativePath);
            fd.append('conflict', conflictMode.value);

            const xhr = new XMLHttpRequest();
            item.xhr = xhr;
            xhr.open('POST', window.baseUrl + 'api/upload');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            if (window.csrfHash) xhr.setRequestHeader('X-CSRF-TOKEN', window.csrfHash);
            xhr.upload.onprogress = (event) => {
                if (!event.lengthComputable) return;
                item.progress = Math.min(99, Math.round((event.loaded / event.total) * 100));
            };
            xhr.onload = async () => {
                item.xhr = null;
                const headerToken = xhr.getResponseHeader('X-CSRF-HASH');
                if (headerToken) window.csrfHash = headerToken;
                let payload = {};
                try {
                    payload = xhr.responseText ? JSON.parse(xhr.responseText) : {};
                } catch (e) {
                    payload = { message: xhr.responseText };
                }
                if (xhr.status < 200 || xhr.status >= 300) {
                    if (Api.isSessionExpiredPayload && Api.isSessionExpiredPayload(payload, xhr.status)) {
                        Api.handleSessionExpired(payload);
                        return;
                    }
                    reject(new Error(Api.errorFromPayload(payload, 'Upload failed')));
                    return;
                }
                resolve(payload);
            };
            xhr.onerror = () => {
                item.xhr = null;
                reject(new Error(Api.genericNetworkError()));
            };
            xhr.onabort = () => {
                item.xhr = null;
                reject(new DOMException('Upload canceled', 'AbortError'));
            };
            xhr.send(fd);
        });

        const uploadChunked = async (item, path) => {
            const CHUNK_SIZE = 1024 * 1024;
            const total = Math.ceil(item.file.size / CHUNK_SIZE);
            item.controller = new AbortController();

            const session = await Api.post('upload/session', {
                filename: item.file.name,
                path,
                relativePath: item.relativePath,
                totalSize: item.file.size,
                chunkSize: CHUNK_SIZE,
                totalChunks: total,
                conflict: conflictMode.value
            });
            item.uploadSessionId = session.id;
            if (!item.uploadSessionId) {
                throw new Error('Upload session could not be created.');
            }

            for (let i = 0; i < total; i++) {
                const chunk = item.file.slice(i * CHUNK_SIZE, (i + 1) * CHUNK_SIZE);
                const fd = new FormData();
                fd.append('file', chunk);

                const headers = { 'X-Requested-With': 'XMLHttpRequest' };
                if (window.csrfHash) headers['X-CSRF-TOKEN'] = window.csrfHash;
                const res = await fetch(window.baseUrl + 'api/upload/session/' + encodeURIComponent(item.uploadSessionId) + '/chunk/' + i, {
                    method: 'PUT',
                    headers,
                    body: fd,
                    signal: item.controller.signal
                });
                updateCsrfFromResponse(res);
                const payload = await Api.parseResponseBody(res);
                if (!res.ok) {
                    if (Api.isSessionExpiredPayload && Api.isSessionExpiredPayload(payload, res.status)) {
                        return Api.blockForSessionExpired(payload);
                    }
                    throw new Error(Api.errorFromPayload(payload, 'Upload chunk failed'));
                }

                item.progress = Math.round(((i + 1) / total) * 100);
            }

            const result = await Api.post(`upload/session/${encodeURIComponent(item.uploadSessionId)}/complete`);
            item.uploadSessionId = null;
            return result || {};
        };

        const startUpload = async () => {
            if (!canUploadHere.value) {
                Swal.fire(i18n.t('upload_files'), t('upload_select_target_folder', 'Open a writable folder before uploading files.'), 'info');
                return;
            }
            if (uploading.value || uploadableCount.value === 0) return;
            uploading.value = true;
            const path = store.cwd;
            const pending = files.value.filter(f => f.valid && ['pending', 'error', 'canceled'].includes(f.status));

            for (const item of pending) {
                item.status = 'uploading';
                item.progress = 0;
                item.errorMessage = '';
                item.resultPath = '';
                try {
                    const payload = item.file.size <= 1024 * 1024
                        ? await uploadSingle(item, path)
                        : await uploadChunked(item, path);
                    item.progress = 100;
                    item.resultPath = payload.path || item.relativePath || item.file.name;
                    item.status = payload.status === 'skipped' ? 'skipped' : 'done';
                } catch(e) {
                    item.status = e.name === 'AbortError' ? 'canceled' : 'error';
                    item.errorMessage = item.status === 'canceled' ? t('upload_canceled', 'Canceled') : e.message;
                } finally {
                    item.controller = null;
                    item.xhr = null;
                    item.uploadSessionId = null;
                }
            }

            uploading.value = false;
            if (typeof store.reload === 'function') {
                await store.reload();
            } else {
                await store.loadPath(store.cwd);
                store.refreshTree();
            }
            if (files.value.length > 0 && files.value.every(f => ['done', 'skipped', 'blocked'].includes(f.status))) {
                Swal.fire(i18n.t('uploaded'), '', 'success');
            }
        };

        const open = () => {
            files.value = [];
            loadConfig();
            if (!modalInstance) modalInstance = new bootstrap.Modal(document.getElementById('uploadModal'));
            modalInstance.show();
        };

        const getFileIcon = (name) => {
            const ext = name.split('.').pop().toLowerCase();
            if (['jpg','jpeg','png','gif','webp'].includes(ext)) return 'ri-image-line';
            if (['pdf'].includes(ext)) return 'ri-file-pdf-line';
            if (['zip','tar','gz','rar','7z'].includes(ext)) return 'ri-file-zip-line';
            return 'ri-file-line';
        };

        const formatSize = (bytes) => {
            if (!bytes) return '0 B';
            const k = 1024;
            const sizes = ['B','KB','MB','GB'];
            const index = Math.min(sizes.length - 1, Math.floor(Math.log(bytes) / Math.log(k)));
            return `${parseFloat((bytes / Math.pow(k, index)).toFixed(2))} ${sizes[index]}`;
        };

        const statusLabel = (item) => {
            const labels = {
                pending: t('ready', 'Ready'),
                blocked: t('blocked', 'Blocked'),
                uploading: t('uploading', 'Uploading...'),
                done: t('upload_done', 'Done'),
                skipped: t('upload_skipped', 'Skipped'),
                error: t('error', 'Error'),
                canceled: t('upload_canceled', 'Canceled')
            };
            return labels[item.status] || item.status;
        };

        const statusBadgeClass = (item) => {
            if (item.status === 'done') return 'bg-success-subtle text-success border border-success-subtle';
            if (item.status === 'skipped') return 'bg-secondary-subtle text-secondary border border-secondary-subtle';
            if (item.status === 'uploading') return 'bg-primary-subtle text-primary border border-primary-subtle';
            if (item.status === 'error' || item.status === 'blocked') return 'bg-danger-subtle text-danger border border-danger-subtle';
            if (item.status === 'canceled') return 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
            return 'bg-success-subtle text-success border border-success-subtle';
        };

        return {
            dragOver, files, fileInput, folderInput, uploading, configLoading, hasBlockedFiles, canUploadHere, uploadableCount,
            completedCount, totalProgress, conflictMode, handleDrop, handleFileSelect, triggerPicker,
            triggerFolderPicker, removeFile, cancelFile, retryFile, clearFinished, startUpload, open,
            getFileIcon, formatSize, progressClass, statusLabel, statusBadgeClass, t
        };
    }
};
