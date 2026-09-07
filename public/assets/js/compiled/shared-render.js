const sharedAppTemplateRender = ((Vue) => {
const _Vue = Vue
const { createElementVNode: _createElementVNode, createCommentVNode: _createCommentVNode, createTextVNode: _createTextVNode } = _Vue

const _hoisted_1 = { class: "shared-header" }
const _hoisted_2 = { class: "shared-brand" }
const _hoisted_3 = ["src"]
const _hoisted_4 = { class: "shared-title" }
const _hoisted_5 = { class: "shared-subtitle text-muted small" }
const _hoisted_6 = { class: "shared-meta" }
const _hoisted_7 = { class: "badge shared-meta-pill" }
const _hoisted_8 = {
  key: 0,
  class: "badge shared-meta-pill"
}
const _hoisted_9 = {
  key: 1,
  class: "badge shared-meta-pill"
}
const _hoisted_10 = {
  key: 2,
  class: "badge shared-meta-pill"
}
const _hoisted_11 = {
  key: 3,
  class: "badge shared-meta-pill"
}
const _hoisted_12 = {
  key: 4,
  class: "badge shared-meta-pill"
}
const _hoisted_13 = { class: "shared-actions" }
const _hoisted_14 = ["href"]
const _hoisted_15 = {
  key: 1,
  class: "badge bg-warning text-dark"
}
const _hoisted_16 = ["href"]
const _hoisted_17 = { class: "file-list" }
const _hoisted_18 = {
  key: 0,
  class: "preview-box"
}
const _hoisted_19 = { class: "mt-3" }
const _hoisted_20 = { class: "text-muted" }
const _hoisted_21 = ["href"]
const _hoisted_22 = {
  key: 0,
  class: "text-center mt-5"
}
const _hoisted_23 = { key: 1 }
const _hoisted_24 = {
  key: 0,
  class: "shared-upload-panel",
  role: "status",
  "aria-live": "polite"
}
const _hoisted_25 = { class: "shared-upload-panel-title" }
const _hoisted_26 = { class: "small" }
const _hoisted_27 = { class: "shared-upload-panel-note" }
const _hoisted_28 = ["onDragenter", "onDragover", "onDragleave", "onDrop"]
const _hoisted_29 = { class: "shared-dropzone-title" }
const _hoisted_30 = { class: "shared-dropzone-subtitle" }
const _hoisted_31 = { class: "shared-upload-actions" }
const _hoisted_32 = ["onClick"]
const _hoisted_33 = {
  key: 0,
  type: "button",
  class: "btn btn-outline-secondary btn-sm",
  disabled: ""
}
const _hoisted_34 = { class: "shared-upload-limits" }
const _hoisted_35 = { class: "badge shared-upload-limit-pill" }
const _hoisted_36 = {
  key: 0,
  class: "badge shared-upload-limit-pill"
}
const _hoisted_37 = {
  key: 1,
  class: "badge shared-upload-limit-pill"
}
const _hoisted_38 = {
  key: 2,
  class: "badge shared-upload-limit-pill"
}
const _hoisted_39 = ["onChange"]
const _hoisted_40 = {
  key: 0,
  class: "alert alert-success small mt-2 mb-0 py-2"
}
const _hoisted_41 = {
  key: 1,
  class: "alert alert-danger small mt-2 mb-0 py-2"
}
const _hoisted_42 = {
  key: 2,
  class: "shared-upload-queue"
}
const _hoisted_43 = { class: "d-flex justify-content-between align-items-center mb-2" }
const _hoisted_44 = { class: "small text-muted" }
const _hoisted_45 = ["onClick"]
const _hoisted_46 = { class: "d-flex justify-content-between gap-3" }
const _hoisted_47 = { class: "flex-grow-1" }
const _hoisted_48 = { class: "shared-upload-queue-name" }
const _hoisted_49 = { class: "shared-upload-queue-meta" }
const _hoisted_50 = { class: "shared-upload-queue-status" }
const _hoisted_51 = { class: "shared-upload-queue-progress" }
const _hoisted_52 = ["aria-label"]
const _hoisted_53 = {
  key: 0,
  class: "small text-danger mt-1"
}
const _hoisted_54 = { class: "shared-breadcrumbs" }
const _hoisted_55 = ["onClick"]
const _hoisted_56 = {
  key: 0,
  class: "ri-home-5-line me-1"
}
const _hoisted_57 = {
  key: 0,
  class: "text-muted small"
}
const _hoisted_58 = { class: "shared-toolbar" }
const _hoisted_59 = { class: "shared-toolbar-left" }
const _hoisted_60 = { class: "input-group input-group-sm" }
const _hoisted_61 = ["onUpdate:modelValue", "placeholder", "aria-label"]
const _hoisted_62 = { class: "shared-toolbar-right" }
const _hoisted_63 = { class: "small text-muted" }
const _hoisted_64 = ["onUpdate:modelValue", "aria-label"]
const _hoisted_65 = { value: "type_name" }
const _hoisted_66 = { value: "name" }
const _hoisted_67 = { value: "mtime_desc" }
const _hoisted_68 = { value: "mtime_asc" }
const _hoisted_69 = { value: "size_desc" }
const _hoisted_70 = { value: "size_asc" }
const _hoisted_71 = { class: "small text-muted" }
const _hoisted_72 = ["onClick"]
const _hoisted_73 = { class: "file-item-name" }
const _hoisted_74 = { class: "file-name" }
const _hoisted_75 = { class: "file-submeta" }
const _hoisted_76 = { class: "file-meta me-3" }
const _hoisted_77 = ["title"]
const _hoisted_78 = { class: "file-actions" }
const _hoisted_79 = ["onClick", "title", "aria-label"]
const _hoisted_80 = ["onClick", "title", "aria-label"]
const _hoisted_81 = {
  key: 1,
  class: "shared-empty"
}
const _hoisted_82 = { class: "mb-1" }
const _hoisted_83 = {
  key: 0,
  class: "small text-muted mb-0"
}
const _hoisted_84 = {
  key: 1,
  class: "small text-muted mb-0"
}
const _hoisted_85 = {
  class: "modal fade",
  id: "previewModal",
  tabindex: "-1"
}
const _hoisted_86 = { class: "modal-dialog modal-xl modal-dialog-centered" }
const _hoisted_87 = { class: "modal-content bg-dark border-0 shadow-lg" }
const _hoisted_88 = { class: "modal-header border-0 py-2" }
const _hoisted_89 = { class: "modal-title text-white" }
const _hoisted_90 = { class: "modal-body p-0 text-center position-relative d-flex align-items-center justify-content-center preview-modal-body" }
const _hoisted_91 = ["src"]
const _hoisted_92 = ["src"]
const _hoisted_93 = {
  key: 2,
  class: "p-5 w-100"
}
const _hoisted_94 = ["src"]
const _hoisted_95 = ["src"]

return function render(_ctx, _cache) {
  with (_ctx) {
    const { createElementVNode: _createElementVNode, toDisplayString: _toDisplayString, createTextVNode: _createTextVNode, openBlock: _openBlock, createElementBlock: _createElementBlock, createCommentVNode: _createCommentVNode, Fragment: _Fragment, withModifiers: _withModifiers, normalizeClass: _normalizeClass, renderList: _renderList, vModelText: _vModelText, withDirectives: _withDirectives, vModelSelect: _vModelSelect } = _Vue

    return (_openBlock(), _createElementBlock(_Fragment, null, [
      _createElementVNode("div", _hoisted_1, [
        _createElementVNode("div", null, [
          _createElementVNode("div", _hoisted_2, [
            _createElementVNode("img", {
              src: baseUrl + 'logo-dark.svg',
              height: "32",
              alt: "eXtplorer logo"
            }, null, 8 /* PROPS */, _hoisted_3),
            _createElementVNode("div", null, [
              _createElementVNode("h5", _hoisted_4, _toDisplayString(shareTitle), 1 /* TEXT */),
              _createElementVNode("p", _hoisted_5, _toDisplayString(t('shared_via', 'Shared via eXtplorer')), 1 /* TEXT */)
            ])
          ]),
          _createElementVNode("div", _hoisted_6, [
            _createElementVNode("span", _hoisted_7, [
              _cache[0] || (_cache[0] = _createElementVNode("i", { class: "ri-shield-check-line me-1" }, null, -1 /* CACHED */)),
              _createTextVNode(_toDisplayString(shareModeLabel), 1 /* TEXT */)
            ]),
            shareExpiresAt
              ? (_openBlock(), _createElementBlock("span", _hoisted_8, [
                  _cache[1] || (_cache[1] = _createElementVNode("i", { class: "ri-timer-line me-1" }, null, -1 /* CACHED */)),
                  _createTextVNode(_toDisplayString(t('shared_expires', 'Expires')) + " " + _toDisplayString(shareExpiresDate), 1 /* TEXT */)
                ]))
              : (_openBlock(), _createElementBlock("span", _hoisted_9, [
                  _cache[2] || (_cache[2] = _createElementVNode("i", { class: "ri-infinity-line me-1" }, null, -1 /* CACHED */)),
                  _createTextVNode(_toDisplayString(t('shared_no_expiry', 'No expiry')), 1 /* TEXT */)
                ])),
            shareSender
              ? (_openBlock(), _createElementBlock("span", _hoisted_10, [
                  _cache[3] || (_cache[3] = _createElementVNode("i", { class: "ri-mail-line me-1" }, null, -1 /* CACHED */)),
                  _createTextVNode(_toDisplayString(shareSender), 1 /* TEXT */)
                ]))
              : shareCreatedBy
                ? (_openBlock(), _createElementBlock("span", _hoisted_11, [
                    _cache[4] || (_cache[4] = _createElementVNode("i", { class: "ri-user-3-line me-1" }, null, -1 /* CACHED */)),
                    _createTextVNode(_toDisplayString(shareCreatedBy), 1 /* TEXT */)
                  ]))
                : _createCommentVNode("v-if", true),
            (!isFile && !loading)
              ? (_openBlock(), _createElementBlock("span", _hoisted_12, [
                  _cache[5] || (_cache[5] = _createElementVNode("i", { class: "ri-folders-line me-1" }, null, -1 /* CACHED */)),
                  _createTextVNode(_toDisplayString(summaryText), 1 /* TEXT */)
                ]))
              : _createCommentVNode("v-if", true)
          ])
        ]),
        _createElementVNode("div", _hoisted_13, [
          isFile
            ? (_openBlock(), _createElementBlock("a", {
                key: 0,
                href: baseUrl + 's/' + hash + '/download',
                class: "btn btn-primary btn-sm"
              }, [
                _cache[6] || (_cache[6] = _createElementVNode("i", { class: "ri-download-line me-1" }, null, -1 /* CACHED */)),
                _createTextVNode(_toDisplayString(t('shared_download', 'Download')), 1 /* TEXT */)
              ], 8 /* PROPS */, _hoisted_14))
            : uploadMode
              ? (_openBlock(), _createElementBlock("span", _hoisted_15, _toDisplayString(t('shared_upload_only', 'Upload Only')), 1 /* TEXT */))
              : (_openBlock(), _createElementBlock("a", {
                  key: 2,
                  href: baseUrl + 's/' + hash + '/download',
                  class: "btn btn-primary btn-sm"
                }, [
                  _cache[7] || (_cache[7] = _createElementVNode("i", { class: "ri-download-2-line me-1" }, null, -1 /* CACHED */)),
                  _createTextVNode(_toDisplayString(t('shared_download_all', 'Download All')), 1 /* TEXT */)
                ], 8 /* PROPS */, _hoisted_16))
        ])
      ]),
      _createElementVNode("div", _hoisted_17, [
        isFile
          ? (_openBlock(), _createElementBlock("div", _hoisted_18, [
              _cache[8] || (_cache[8] = _createElementVNode("i", { class: "ri-file-text-line preview-icon" }, null, -1 /* CACHED */)),
              _createElementVNode("h4", _hoisted_19, _toDisplayString(filename), 1 /* TEXT */),
              _createElementVNode("p", _hoisted_20, _toDisplayString(formatSize(size)), 1 /* TEXT */),
              _createElementVNode("a", {
                href: baseUrl + 's/' + hash + '/download',
                class: "btn btn-primary mt-3"
              }, _toDisplayString(t('shared_download_file', 'Download File')), 9 /* TEXT, PROPS */, _hoisted_21)
            ]))
          : (_openBlock(), _createElementBlock(_Fragment, { key: 1 }, [
              loading
                ? (_openBlock(), _createElementBlock("div", _hoisted_22, [...(_cache[9] || (_cache[9] = [
                    _createElementVNode("div", { class: "spinner-border text-primary" }, null, -1 /* CACHED */)
                  ]))]))
                : (_openBlock(), _createElementBlock("div", _hoisted_23, [
                    uploadMode
                      ? (_openBlock(), _createElementBlock("div", _hoisted_24, [
                          _cache[11] || (_cache[11] = _createElementVNode("div", { class: "shared-upload-panel-icon" }, [
                            _createElementVNode("i", { class: "ri-upload-cloud-2-line" })
                          ], -1 /* CACHED */)),
                          _createElementVNode("div", null, [
                            _createElementVNode("div", _hoisted_25, _toDisplayString(t('shared_upload_panel_title', 'Upload-Only Share')), 1 /* TEXT */),
                            _createElementVNode("div", _hoisted_26, _toDisplayString(t('shared_upload_panel_desc', 'You can upload files to this share. Files already here may not be downloadable.')), 1 /* TEXT */),
                            _createElementVNode("div", _hoisted_27, _toDisplayString(t('shared_upload_panel_note', 'You can drag and drop files here.')), 1 /* TEXT */),
                            _createElementVNode("div", {
                              class: _normalizeClass(["shared-dropzone", { active: dropzoneActive }]),
                              onDragenter: _withModifiers(onDragEnter, ["prevent"]),
                              onDragover: _withModifiers(onDragOver, ["prevent"]),
                              onDragleave: _withModifiers(onDragLeave, ["prevent"]),
                              onDrop: _withModifiers(onDrop, ["prevent"])
                            }, [
                              _createElementVNode("div", _hoisted_29, _toDisplayString(t('shared_dropzone_title', 'Drop files to upload')), 1 /* TEXT */),
                              _createElementVNode("div", _hoisted_30, _toDisplayString(t('shared_dropzone_subtitle', 'or choose files from your device')), 1 /* TEXT */),
                              _createElementVNode("div", _hoisted_31, [
                                _createElementVNode("button", {
                                  type: "button",
                                  class: "btn btn-primary btn-sm",
                                  onClick: openFilePicker
                                }, [
                                  _cache[10] || (_cache[10] = _createElementVNode("i", { class: "ri-upload-2-line me-1" }, null, -1 /* CACHED */)),
                                  _createTextVNode(_toDisplayString(uploading ? t('shared_uploading', 'Uploading...') : t('shared_upload_button', 'Upload Files')), 1 /* TEXT */)
                                ], 8 /* PROPS */, _hoisted_32),
                                uploading
                                  ? (_openBlock(), _createElementBlock("button", _hoisted_33, _toDisplayString(overallProgress) + "% ", 1 /* TEXT */))
                                  : _createCommentVNode("v-if", true)
                              ]),
                              _createElementVNode("div", _hoisted_34, [
                                _createElementVNode("span", _hoisted_35, [
                                  (policyMaxFileMb > 0)
                                    ? (_openBlock(), _createElementBlock(_Fragment, { key: 0 }, [
                                        _createTextVNode(_toDisplayString(t('shared_upload_limit', 'Max file size: {max} MB', { max: policyMaxFileMb })), 1 /* TEXT */)
                                      ], 64 /* STABLE_FRAGMENT */))
                                    : (_openBlock(), _createElementBlock(_Fragment, { key: 1 }, [
                                        _createTextVNode(_toDisplayString(t('shared_upload_limit_unlimited', 'No file size limit configured')), 1 /* TEXT */)
                                      ], 64 /* STABLE_FRAGMENT */))
                                ]),
                                hasAllowedExtensions
                                  ? (_openBlock(), _createElementBlock("span", _hoisted_36, _toDisplayString(t('shared_allowed_types', 'Allowed types: {types}', { types: allowedExtensionsLabel })), 1 /* TEXT */))
                                  : _createCommentVNode("v-if", true),
                                hasQuotaLimit
                                  ? (_openBlock(), _createElementBlock("span", _hoisted_37, _toDisplayString(t('shared_quota_remaining', 'Remaining quota: {remaining}', { remaining: formatSize(quotaRemainingBytes) })), 1 /* TEXT */))
                                  : _createCommentVNode("v-if", true),
                                hasFileCountLimit
                                  ? (_openBlock(), _createElementBlock("span", _hoisted_38, _toDisplayString(t('shared_files_remaining', 'Remaining files: {count}', { count: filesRemaining })), 1 /* TEXT */))
                                  : _createCommentVNode("v-if", true)
                              ]),
                              _createElementVNode("input", {
                                ref: "fileInput",
                                type: "file",
                                class: "d-none",
                                multiple: "",
                                onChange: onFileInputChange
                              }, null, 40 /* PROPS, NEED_HYDRATION */, _hoisted_39)
                            ], 42 /* CLASS, PROPS, NEED_HYDRATION */, _hoisted_28),
                            uploadMessage
                              ? (_openBlock(), _createElementBlock("div", _hoisted_40, _toDisplayString(uploadMessage), 1 /* TEXT */))
                              : _createCommentVNode("v-if", true),
                            uploadError
                              ? (_openBlock(), _createElementBlock("div", _hoisted_41, _toDisplayString(uploadError), 1 /* TEXT */))
                              : _createCommentVNode("v-if", true),
                            (uploadQueue.length > 0)
                              ? (_openBlock(), _createElementBlock("div", _hoisted_42, [
                                  _createElementVNode("div", _hoisted_43, [
                                    _createElementVNode("div", _hoisted_44, _toDisplayString(queueSummaryText), 1 /* TEXT */),
                                    hasCompletedUploads
                                      ? (_openBlock(), _createElementBlock("button", {
                                          key: 0,
                                          type: "button",
                                          class: "btn btn-link btn-sm text-decoration-none p-0",
                                          onClick: clearCompleted
                                        }, _toDisplayString(t('shared_clear_completed', 'Clear completed')), 9 /* TEXT, PROPS */, _hoisted_45))
                                      : _createCommentVNode("v-if", true)
                                  ]),
                                  (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(uploadQueue, (item) => {
                                    return (_openBlock(), _createElementBlock("div", {
                                      key: item.id,
                                      class: "shared-upload-queue-item"
                                    }, [
                                      _createElementVNode("div", _hoisted_46, [
                                        _createElementVNode("div", _hoisted_47, [
                                          _createElementVNode("div", _hoisted_48, _toDisplayString(item.name), 1 /* TEXT */),
                                          _createElementVNode("div", _hoisted_49, _toDisplayString(formatSize(item.size)), 1 /* TEXT */)
                                        ]),
                                        _createElementVNode("div", _hoisted_50, [
                                          _createElementVNode("span", {
                                            class: _normalizeClass(["badge", queueStatusBadgeClass(item)])
                                          }, _toDisplayString(queueStatusLabel(item)), 3 /* TEXT, CLASS */)
                                        ])
                                      ]),
                                      _createElementVNode("div", _hoisted_51, [
                                        _createElementVNode("div", {
                                          class: "progress progress-compact",
                                          role: "progressbar",
                                          "aria-label": queueStatusLabel(item),
                                          "aria-valuemin": "0",
                                          "aria-valuemax": "100"
                                        }, [
                                          _createElementVNode("div", {
                                            class: _normalizeClass(["progress-bar", queueProgressBarClass(item)])
                                          }, null, 2 /* CLASS */)
                                        ], 8 /* PROPS */, _hoisted_52)
                                      ]),
                                      (item.error)
                                        ? (_openBlock(), _createElementBlock("div", _hoisted_53, _toDisplayString(item.error), 1 /* TEXT */))
                                        : _createCommentVNode("v-if", true)
                                    ]))
                                  }), 128 /* KEYED_FRAGMENT */))
                                ]))
                              : _createCommentVNode("v-if", true)
                          ])
                        ]))
                      : _createCommentVNode("v-if", true),
                    _createElementVNode("div", _hoisted_54, [
                      (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(breadcrumbs, (crumb, idx) => {
                        return (_openBlock(), _createElementBlock(_Fragment, { key: crumb.path || 'root' }, [
                          _createElementVNode("button", {
                            type: "button",
                            class: "btn btn-link btn-sm text-decoration-none",
                            onClick: $event => (navigateTo(crumb.path))
                          }, [
                            (idx === 0)
                              ? (_openBlock(), _createElementBlock("i", _hoisted_56))
                              : _createCommentVNode("v-if", true),
                            _createTextVNode(_toDisplayString(crumb.label), 1 /* TEXT */)
                          ], 8 /* PROPS */, _hoisted_55),
                          (idx < breadcrumbs.length - 1)
                            ? (_openBlock(), _createElementBlock("span", _hoisted_57, "/"))
                            : _createCommentVNode("v-if", true)
                        ], 64 /* STABLE_FRAGMENT */))
                      }), 128 /* KEYED_FRAGMENT */))
                    ]),
                    _createElementVNode("div", _hoisted_58, [
                      _createElementVNode("div", _hoisted_59, [
                        _createElementVNode("div", _hoisted_60, [
                          _cache[12] || (_cache[12] = _createElementVNode("span", { class: "input-group-text bg-white" }, [
                            _createElementVNode("i", { class: "ri-search-line" })
                          ], -1 /* CACHED */)),
                          _withDirectives(_createElementVNode("input", {
                            "onUpdate:modelValue": $event => ((searchQuery) = $event),
                            type: "search",
                            class: "form-control",
                            placeholder: t('shared_search_placeholder', 'Search files'),
                            "aria-label": t('shared_search_placeholder', 'Search files')
                          }, null, 8 /* PROPS */, _hoisted_61), [
                            [
                              _vModelText,
                              searchQuery,
                              void 0,
                              { trim: true }
                            ]
                          ])
                        ])
                      ]),
                      _createElementVNode("div", _hoisted_62, [
                        _createElementVNode("label", _hoisted_63, _toDisplayString(t('shared_sort_label', 'Sort')), 1 /* TEXT */),
                        _withDirectives(_createElementVNode("select", {
                          "onUpdate:modelValue": $event => ((sortKey) = $event),
                          class: "form-select form-select-sm select-auto-width",
                          "aria-label": t('shared_sort_aria', 'Sort files')
                        }, [
                          _createElementVNode("option", _hoisted_65, _toDisplayString(t('shared_sort_type_name', 'Type + Name')), 1 /* TEXT */),
                          _createElementVNode("option", _hoisted_66, _toDisplayString(t('shared_sort_name', 'Name')), 1 /* TEXT */),
                          _createElementVNode("option", _hoisted_67, _toDisplayString(t('shared_sort_newest', 'Newest')), 1 /* TEXT */),
                          _createElementVNode("option", _hoisted_68, _toDisplayString(t('shared_sort_oldest', 'Oldest')), 1 /* TEXT */),
                          _createElementVNode("option", _hoisted_69, _toDisplayString(t('shared_sort_largest', 'Largest')), 1 /* TEXT */),
                          _createElementVNode("option", _hoisted_70, _toDisplayString(t('shared_sort_smallest', 'Smallest')), 1 /* TEXT */)
                        ], 8 /* PROPS */, _hoisted_64), [
                          [_vModelSelect, sortKey]
                        ]),
                        _createElementVNode("span", _hoisted_71, _toDisplayString(t('shared_items_count', '{count} items', { count: filteredFiles.length })), 1 /* TEXT */)
                      ])
                    ]),
                    (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(filteredFiles, (file) => {
                      return (_openBlock(), _createElementBlock("div", {
                        key: file.name,
                        class: "file-item",
                        onClick: $event => (open(file))
                      }, [
                        _createElementVNode("i", {
                          class: _normalizeClass([getIcon(file), "file-icon"])
                        }, null, 2 /* CLASS */),
                        _createElementVNode("div", _hoisted_73, [
                          _createElementVNode("div", _hoisted_74, _toDisplayString(file.name), 1 /* TEXT */),
                          _createElementVNode("div", _hoisted_75, _toDisplayString(file.type === 'dir' ? t('shared_folder', 'Folder') : fileExtLabel(file.name)), 1 /* TEXT */)
                        ]),
                        _createElementVNode("div", _hoisted_76, _toDisplayString(formatSize(file.size)), 1 /* TEXT */),
                        _createElementVNode("div", {
                          class: "file-meta",
                          title: formatDate(file.mtime)
                        }, _toDisplayString(formatRelativeDate(file.mtime)), 9 /* TEXT, PROPS */, _hoisted_77),
                        _createElementVNode("div", _hoisted_78, [
                          (!uploadMode && file.type !== 'dir' && isPreviewable(file))
                            ? (_openBlock(), _createElementBlock("button", {
                                key: 0,
                                type: "button",
                                class: "btn btn-link btn-sm p-0",
                                onClick: _withModifiers($event => (previewItem(file)), ["stop"]),
                                title: t('shared_preview', 'Preview'),
                                "aria-label": t('shared_preview_file', 'Preview file')
                              }, [...(_cache[13] || (_cache[13] = [
                                _createElementVNode("i", { class: "ri-eye-line" }, null, -1 /* CACHED */)
                              ]))], 8 /* PROPS */, _hoisted_79))
                            : _createCommentVNode("v-if", true),
                          (!uploadMode && file.type !== 'dir')
                            ? (_openBlock(), _createElementBlock("button", {
                                key: 1,
                                type: "button",
                                class: "btn btn-link btn-sm p-0",
                                onClick: _withModifiers($event => (downloadItem(file)), ["stop"]),
                                title: t('shared_download', 'Download'),
                                "aria-label": t('shared_download_file', 'Download file')
                              }, [...(_cache[14] || (_cache[14] = [
                                _createElementVNode("i", { class: "ri-download-line" }, null, -1 /* CACHED */)
                              ]))], 8 /* PROPS */, _hoisted_80))
                            : _createCommentVNode("v-if", true)
                        ])
                      ], 8 /* PROPS */, _hoisted_72))
                    }), 128 /* KEYED_FRAGMENT */)),
                    (filteredFiles.length === 0)
                      ? (_openBlock(), _createElementBlock("div", _hoisted_81, [
                          _cache[15] || (_cache[15] = _createElementVNode("i", { class: "ri-folder-open-line" }, null, -1 /* CACHED */)),
                          _createElementVNode("p", _hoisted_82, _toDisplayString(t('shared_empty_no_match', 'No files match your view.')), 1 /* TEXT */),
                          (files.length > 0)
                            ? (_openBlock(), _createElementBlock("p", _hoisted_83, _toDisplayString(t('shared_empty_try_clear', 'Try clearing the search or changing the sort.')), 1 /* TEXT */))
                            : (_openBlock(), _createElementBlock("p", _hoisted_84, _toDisplayString(t('shared_empty_folder_empty', 'This folder is empty.')), 1 /* TEXT */))
                        ]))
                      : _createCommentVNode("v-if", true)
                  ]))
            ], 64 /* STABLE_FRAGMENT */))
      ]),
      _createCommentVNode(" Preview Modal "),
      _createElementVNode("div", _hoisted_85, [
        _createElementVNode("div", _hoisted_86, [
          _createElementVNode("div", _hoisted_87, [
            _createElementVNode("div", _hoisted_88, [
              _createElementVNode("h6", _hoisted_89, _toDisplayString(previewState.filename), 1 /* TEXT */),
              _cache[16] || (_cache[16] = _createElementVNode("button", {
                type: "button",
                class: "btn-close btn-close-white",
                "data-bs-dismiss": "modal"
              }, null, -1 /* CACHED */))
            ]),
            _createElementVNode("div", _hoisted_90, [
              (previewState.type === 'image')
                ? (_openBlock(), _createElementBlock("img", {
                    key: 0,
                    src: previewState.src,
                    class: "img-fluid rounded max-h-80vh"
                  }, null, 8 /* PROPS */, _hoisted_91))
                : _createCommentVNode("v-if", true),
              (previewState.type === 'video')
                ? (_openBlock(), _createElementBlock("video", {
                    key: 1,
                    src: previewState.src,
                    controls: "",
                    autoplay: "",
                    class: "w-100 max-h-80vh"
                  }, null, 8 /* PROPS */, _hoisted_92))
                : _createCommentVNode("v-if", true),
              (previewState.type === 'audio')
                ? (_openBlock(), _createElementBlock("div", _hoisted_93, [
                    _cache[17] || (_cache[17] = _createElementVNode("i", { class: "ri-music-2-line fs-1 text-white-50 d-block mb-3" }, null, -1 /* CACHED */)),
                    _createElementVNode("audio", {
                      src: previewState.src,
                      controls: "",
                      autoplay: "",
                      class: "w-100"
                    }, null, 8 /* PROPS */, _hoisted_94)
                  ]))
                : _createCommentVNode("v-if", true),
              (previewState.type === 'pdf')
                ? (_openBlock(), _createElementBlock("iframe", {
                    key: 3,
                    src: previewState.src,
                    class: "w-100 preview-pdf-frame"
                  }, null, 8 /* PROPS */, _hoisted_95))
                : _createCommentVNode("v-if", true)
            ])
          ])
        ])
      ])
    ], 64 /* STABLE_FRAGMENT */))
  }
}
})(Vue);
