const TransferModal = {
    render: ((Vue) => {
const _Vue = Vue
const { createElementVNode: _createElementVNode, createCommentVNode: _createCommentVNode, createTextVNode: _createTextVNode } = _Vue

const _hoisted_1 = {
  class: "modal fade",
  id: "transferModal",
  tabindex: "-1"
}
const _hoisted_2 = { class: "modal-dialog modal-lg" }
const _hoisted_3 = { class: "modal-content" }
const _hoisted_4 = { class: "modal-header" }
const _hoisted_5 = { class: "modal-title" }
const _hoisted_6 = ["aria-label"]
const _hoisted_7 = { class: "modal-body" }
const _hoisted_8 = { class: "nav nav-tabs mb-3" }
const _hoisted_9 = { class: "nav-item" }
const _hoisted_10 = ["onClick"]
const _hoisted_11 = { class: "nav-item" }
const _hoisted_12 = ["onClick"]
const _hoisted_13 = { key: 0 }
const _hoisted_14 = {
  key: 0,
  class: "text-center py-5"
}
const _hoisted_15 = { class: "text-muted" }
const _hoisted_16 = {
  key: 0,
  class: "alert alert-light border text-start small mx-auto mb-3",
  role: "alert"
}
const _hoisted_17 = { class: "d-flex flex-wrap gap-3 justify-content-between" }
const _hoisted_18 = { class: "input-group mb-3 w-75 mx-auto" }
const _hoisted_19 = ["value", "aria-label"]
const _hoisted_20 = ["onClick"]
const _hoisted_21 = { class: "d-flex flex-wrap justify-content-center gap-2" }
const _hoisted_22 = ["onClick"]
const _hoisted_23 = ["onClick"]
const _hoisted_24 = ["onClick"]
const _hoisted_25 = ["onClick"]
const _hoisted_26 = { key: 1 }
const _hoisted_27 = {
  key: 0,
  class: "alert alert-info d-flex align-items-center justify-content-between mb-3"
}
const _hoisted_28 = { class: "d-flex gap-2" }
const _hoisted_29 = ["onClick"]
const _hoisted_30 = ["onClick"]
const _hoisted_31 = { class: "row" }
const _hoisted_32 = { class: "col-md-6 border-end" }
const _hoisted_33 = ["onDragover", "onDragleave", "onDrop"]
const _hoisted_34 = { key: 0 }
const _hoisted_35 = { class: "mb-2" }
const _hoisted_36 = { class: "btn btn-sm btn-outline-primary position-relative overflow-hidden" }
const _hoisted_37 = ["onChange", "aria-label"]
const _hoisted_38 = { class: "mt-2 text-muted small" }
const _hoisted_39 = { class: "btn btn-sm btn-link position-relative overflow-hidden text-decoration-none" }
const _hoisted_40 = ["onChange", "aria-label"]
const _hoisted_41 = {
  key: 1,
  class: "text-start w-100"
}
const _hoisted_42 = { class: "d-flex justify-content-between align-items-center mb-2" }
const _hoisted_43 = { class: "fw-bold" }
const _hoisted_44 = { class: "badge bg-secondary" }
const _hoisted_45 = { class: "list-group list-group-flush overflow-auto transfer-files-list" }
const _hoisted_46 = ["title"]
const _hoisted_47 = {
  key: 0,
  class: "badge bg-info-subtle text-info-emphasis me-1",
  title: "From Server"
}
const _hoisted_48 = ["onClick", "aria-label"]
const _hoisted_49 = ["onClick"]
const _hoisted_50 = { class: "col-md-6" }
const _hoisted_51 = { class: "mb-3" }
const _hoisted_52 = {
  class: "form-label small fw-bold",
  for: "transferRecipientInput"
}
const _hoisted_53 = { class: "border rounded p-2 bg-light-subtle" }
const _hoisted_54 = {
  key: 0,
  class: "d-flex flex-wrap gap-1 mb-2"
}
const _hoisted_55 = { class: "me-1" }
const _hoisted_56 = ["onClick", "aria-label"]
const _hoisted_57 = { class: "input-group input-group-sm" }
const _hoisted_58 = ["onUpdate:modelValue", "placeholder", "onKeydown", "onBlur"]
const _hoisted_59 = ["onClick"]
const _hoisted_60 = {
  key: 0,
  class: "text-danger small mt-1"
}
const _hoisted_61 = {
  key: 1,
  class: "text-muted small mt-1"
}
const _hoisted_62 = { class: "mb-3" }
const _hoisted_63 = {
  class: "form-label small fw-bold",
  for: "transferSubjectInput"
}
const _hoisted_64 = ["onUpdate:modelValue", "placeholder"]
const _hoisted_65 = { class: "mb-3" }
const _hoisted_66 = {
  class: "form-label small fw-bold",
  for: "transferMessageInput"
}
const _hoisted_67 = ["onUpdate:modelValue", "placeholder"]
const _hoisted_68 = { class: "row g-2 mb-3" }
const _hoisted_69 = { class: "col-6" }
const _hoisted_70 = {
  class: "form-label small fw-bold",
  for: "transferExpiresSelect"
}
const _hoisted_71 = ["onUpdate:modelValue"]
const _hoisted_72 = { value: "1" }
const _hoisted_73 = { value: "3" }
const _hoisted_74 = { value: "7" }
const _hoisted_75 = { value: "14" }
const _hoisted_76 = { value: "30" }
const _hoisted_77 = { class: "col-6" }
const _hoisted_78 = { class: "form-check form-check-sm" }
const _hoisted_79 = ["onUpdate:modelValue"]
const _hoisted_80 = {
  class: "form-check-label",
  for: "notifyDown"
}
const _hoisted_81 = ["onClick", "disabled"]
const _hoisted_82 = { key: 0 }
const _hoisted_83 = { key: 1 }
const _hoisted_84 = { key: 1 }
const _hoisted_85 = { class: "d-flex flex-wrap gap-2 mb-3" }
const _hoisted_86 = ["onClick"]
const _hoisted_87 = ["onClick"]
const _hoisted_88 = ["onClick"]
const _hoisted_89 = ["onClick"]
const _hoisted_90 = {
  key: 0,
  class: "text-center p-3"
}
const _hoisted_91 = {
  key: 1,
  class: "text-center text-muted p-5"
}
const _hoisted_92 = { class: "mt-2" }
const _hoisted_93 = {
  key: 2,
  class: "table-responsive"
}
const _hoisted_94 = { class: "table table-hover table-sm small align-middle" }
const _hoisted_95 = { class: "table-light" }
const _hoisted_96 = { class: "text-end" }
const _hoisted_97 = { class: "fw-bold" }
const _hoisted_98 = ["title"]
const _hoisted_99 = {
  key: 0,
  class: "text-muted small"
}
const _hoisted_100 = { class: "text-end" }
const _hoisted_101 = { class: "small text-muted mb-1" }
const _hoisted_102 = ["href"]
const _hoisted_103 = ["onClick", "title", "aria-label"]
const _hoisted_104 = ["onClick", "title", "aria-label"]

return function render(_ctx, _cache) {
  with (_ctx) {
    const { createElementVNode: _createElementVNode, toDisplayString: _toDisplayString, createTextVNode: _createTextVNode, withModifiers: _withModifiers, normalizeClass: _normalizeClass, createCommentVNode: _createCommentVNode, openBlock: _openBlock, createElementBlock: _createElementBlock, renderList: _renderList, Fragment: _Fragment, vModelText: _vModelText, withKeys: _withKeys, withDirectives: _withDirectives, vModelSelect: _vModelSelect, vModelCheckbox: _vModelCheckbox } = _Vue

    return (_openBlock(), _createElementBlock("div", _hoisted_1, [
      _createElementVNode("div", _hoisted_2, [
        _createElementVNode("div", _hoisted_3, [
          _createElementVNode("div", _hoisted_4, [
            _createElementVNode("h5", _hoisted_5, [
              _cache[0] || (_cache[0] = _createElementVNode("i", { class: "ri-send-plane-fill me-2" }, null, -1 /* CACHED */)),
              _createTextVNode(_toDisplayString(t('transfer_title', 'Send Files')), 1 /* TEXT */)
            ]),
            _createElementVNode("button", {
              type: "button",
              class: "btn-close",
              "data-bs-dismiss": "modal",
              "aria-label": t('close', 'Close')
            }, null, 8 /* PROPS */, _hoisted_6)
          ]),
          _createElementVNode("div", _hoisted_7, [
            _createElementVNode("ul", _hoisted_8, [
              _createElementVNode("li", _hoisted_9, [
                _createElementVNode("a", {
                  class: _normalizeClass(["nav-link", {active: tab === 'new'}]),
                  href: "#",
                  onClick: _withModifiers($event => (tab = 'new'), ["prevent"])
                }, _toDisplayString(t('transfer_new', 'New Transfer')), 11 /* TEXT, CLASS, PROPS */, _hoisted_10)
              ]),
              _createElementVNode("li", _hoisted_11, [
                _createElementVNode("a", {
                  class: _normalizeClass(["nav-link", {active: tab === 'history'}]),
                  href: "#",
                  onClick: _withModifiers(loadHistory, ["prevent"])
                }, _toDisplayString(t('transfer_history', 'History')), 11 /* TEXT, CLASS, PROPS */, _hoisted_12)
              ])
            ]),
            _createCommentVNode(" New Transfer Tab "),
            (tab === 'new')
              ? (_openBlock(), _createElementBlock("div", _hoisted_13, [
                  successLink
                    ? (_openBlock(), _createElementBlock("div", _hoisted_14, [
                        _cache[4] || (_cache[4] = _createElementVNode("div", { class: "mb-4 text-success display-1" }, [
                          _createElementVNode("i", { class: "ri-checkbox-circle-line" })
                        ], -1 /* CACHED */)),
                        _createElementVNode("h3", null, _toDisplayString(t('transfer_sent', 'Transfer Sent!')), 1 /* TEXT */),
                        _createElementVNode("p", _hoisted_15, _toDisplayString(t('transfer_sent_desc', 'Your files have been uploaded and the link is ready.')), 1 /* TEXT */),
                        lastTransfer
                          ? (_openBlock(), _createElementBlock("div", _hoisted_16, [
                              _createElementVNode("div", _hoisted_17, [
                                _createElementVNode("div", null, [
                                  _createElementVNode("strong", null, _toDisplayString(t('transfer_files', 'Files:')), 1 /* TEXT */),
                                  _createTextVNode(" " + _toDisplayString(lastTransfer.fileCount), 1 /* TEXT */)
                                ]),
                                _createElementVNode("div", null, [
                                  _createElementVNode("strong", null, _toDisplayString(t('transfer_total_size', 'Total size:')), 1 /* TEXT */),
                                  _createTextVNode(" " + _toDisplayString(formatSize(lastTransfer.totalSize)), 1 /* TEXT */)
                                ]),
                                _createElementVNode("div", null, [
                                  _createElementVNode("strong", null, _toDisplayString(t('transfer_recipients', 'Recipients:')), 1 /* TEXT */),
                                  _createTextVNode(" " + _toDisplayString(lastTransfer.recipients.length), 1 /* TEXT */)
                                ]),
                                _createElementVNode("div", null, [
                                  _createElementVNode("strong", null, _toDisplayString(t('transfer_expires', 'Expires:')), 1 /* TEXT */),
                                  _createTextVNode(" " + _toDisplayString(formatDate(lastTransfer.expiresAt)), 1 /* TEXT */)
                                ])
                              ])
                            ]))
                          : _createCommentVNode("v-if", true),
                        _createElementVNode("div", _hoisted_18, [
                          _createElementVNode("input", {
                            type: "text",
                            class: "form-control",
                            value: successLink,
                            readonly: "",
                            id: "transferLink",
                            "aria-label": t('transfer_copy_link', 'Transfer link')
                          }, null, 8 /* PROPS */, _hoisted_19),
                          _createElementVNode("button", {
                            class: "btn btn-outline-primary",
                            onClick: copyLink
                          }, _toDisplayString(t('transfer_copy', 'Copy')), 9 /* TEXT, PROPS */, _hoisted_20)
                        ]),
                        _createElementVNode("div", _hoisted_21, [
                          _createElementVNode("button", {
                            class: "btn btn-primary",
                            onClick: copyLink
                          }, [
                            _cache[1] || (_cache[1] = _createElementVNode("i", { class: "ri-file-copy-line me-1" }, null, -1 /* CACHED */)),
                            _createTextVNode(_toDisplayString(t('transfer_copy_link', 'Copy Link')), 1 /* TEXT */)
                          ], 8 /* PROPS */, _hoisted_22),
                          _createElementVNode("button", {
                            class: "btn btn-outline-primary",
                            onClick: openLink
                          }, [
                            _cache[2] || (_cache[2] = _createElementVNode("i", { class: "ri-external-link-line me-1" }, null, -1 /* CACHED */)),
                            _createTextVNode(_toDisplayString(t('transfer_open_link', 'Open Link')), 1 /* TEXT */)
                          ], 8 /* PROPS */, _hoisted_23),
                          _createElementVNode("button", {
                            class: "btn btn-outline-secondary",
                            onClick: goHistory
                          }, [
                            _cache[3] || (_cache[3] = _createElementVNode("i", { class: "ri-history-line me-1" }, null, -1 /* CACHED */)),
                            _createTextVNode(_toDisplayString(t('transfer_view_history', 'View History')), 1 /* TEXT */)
                          ], 8 /* PROPS */, _hoisted_24),
                          _createElementVNode("button", {
                            class: "btn btn-light border",
                            onClick: resetForm
                          }, _toDisplayString(t('transfer_send_another', 'Send Another')), 9 /* TEXT, PROPS */, _hoisted_25)
                        ])
                      ]))
                    : (_openBlock(), _createElementBlock("div", _hoisted_26, [
                        resumeState
                          ? (_openBlock(), _createElementBlock("div", _hoisted_27, [
                              _createElementVNode("div", null, [
                                _cache[5] || (_cache[5] = _createElementVNode("i", { class: "ri-history-line me-2" }, null, -1 /* CACHED */)),
                                _createTextVNode(" " + _toDisplayString(t('transfer_resume_found_prefix', 'Unfinished transfer found')) + " (" + _toDisplayString(resumeState.fileNames.length) + " " + _toDisplayString(t('transfer_files_lower', 'files')) + "). ", 1 /* TEXT */)
                              ]),
                              _createElementVNode("div", _hoisted_28, [
                                _createElementVNode("button", {
                                  class: "btn btn-sm btn-outline-danger",
                                  onClick: resetForm
                                }, _toDisplayString(t('transfer_discard', 'Discard')), 9 /* TEXT, PROPS */, _hoisted_29),
                                _createElementVNode("button", {
                                  class: "btn btn-sm btn-primary",
                                  onClick: resumeUpload
                                }, _toDisplayString(t('transfer_resume', 'Resume')), 9 /* TEXT, PROPS */, _hoisted_30)
                              ])
                            ]))
                          : _createCommentVNode("v-if", true),
                        _createElementVNode("div", _hoisted_31, [
                          _createCommentVNode(" Left: File Drop "),
                          _createElementVNode("div", _hoisted_32, [
                            _createElementVNode("div", {
                              class: _normalizeClass(["dropzone p-4 mb-3 border border-2 border-dashed rounded text-center bg-light transfer-dropzone", {'bg-primary-subtle border-primary': isDragOver}]),
                              onDragover: _withModifiers($event => (isDragOver = true), ["prevent"]),
                              onDragleave: $event => (isDragOver = false),
                              onDrop: _withModifiers(handleDrop, ["prevent"])
                            }, [
                              (allFiles.length === 0)
                                ? (_openBlock(), _createElementBlock("div", _hoisted_34, [
                                    _cache[6] || (_cache[6] = _createElementVNode("i", { class: "ri-upload-cloud-2-line display-4 text-muted mb-2" }, null, -1 /* CACHED */)),
                                    _createElementVNode("p", _hoisted_35, _toDisplayString(t('transfer_drop_hint', 'Drag & Drop files or folders here')), 1 /* TEXT */),
                                    _createElementVNode("button", _hoisted_36, [
                                      _createTextVNode(_toDisplayString(t('transfer_browse_files', 'Browse Files')) + " ", 1 /* TEXT */),
                                      _createElementVNode("input", {
                                        id: "transferFileInput",
                                        name: "transfer_files",
                                        type: "file",
                                        multiple: "",
                                        class: "position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer",
                                        onChange: handleFileSelect,
                                        "aria-label": t('transfer_browse_files', 'Browse Files')
                                      }, null, 40 /* PROPS, NEED_HYDRATION */, _hoisted_37)
                                    ]),
                                    _createElementVNode("div", _hoisted_38, _toDisplayString(t('transfer_or', 'or')), 1 /* TEXT */),
                                    _createElementVNode("button", _hoisted_39, [
                                      _createTextVNode(_toDisplayString(t('transfer_browse_folder', 'Browse Folder')) + " ", 1 /* TEXT */),
                                      _createElementVNode("input", {
                                        id: "transferFolderInput",
                                        name: "transfer_folder_files",
                                        type: "file",
                                        multiple: "",
                                        webkitdirectory: "",
                                        class: "position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer",
                                        onChange: handleFileSelect,
                                        "aria-label": t('transfer_browse_folder', 'Browse Folder')
                                      }, null, 40 /* PROPS, NEED_HYDRATION */, _hoisted_40)
                                    ])
                                  ]))
                                : (_openBlock(), _createElementBlock("div", _hoisted_41, [
                                    _createElementVNode("div", _hoisted_42, [
                                      _createElementVNode("span", _hoisted_43, _toDisplayString(allFiles.length) + " " + _toDisplayString(t('transfer_files_badge', 'Files')), 1 /* TEXT */),
                                      _createElementVNode("span", _hoisted_44, _toDisplayString(formatSize(totalSize)), 1 /* TEXT */)
                                    ]),
                                    _createElementVNode("div", _hoisted_45, [
                                      (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(allFiles, (f, idx) => {
                                        return (_openBlock(), _createElementBlock("div", {
                                          key: idx,
                                          class: "list-group-item d-flex justify-content-between align-items-center p-2 small"
                                        }, [
                                          _createElementVNode("div", {
                                            class: "text-truncate me-2",
                                            title: f.name
                                          }, [
                                            _createElementVNode("i", {
                                              class: _normalizeClass([getIcon(f.name), "me-1"])
                                            }, null, 2 /* CLASS */),
                                            (f.path)
                                              ? (_openBlock(), _createElementBlock("span", _hoisted_47, "Internal"))
                                              : _createCommentVNode("v-if", true),
                                            _createTextVNode(" " + _toDisplayString(f.name), 1 /* TEXT */)
                                          ], 8 /* PROPS */, _hoisted_46),
                                          _createElementVNode("button", {
                                            type: "button",
                                            class: "btn btn-link btn-sm p-0 text-danger",
                                            onClick: $event => (removeFile(idx)),
                                            "aria-label": 'Remove file: ' + f.name
                                          }, [...(_cache[7] || (_cache[7] = [
                                            _createElementVNode("i", {
                                              class: "ri-close-line",
                                              "aria-hidden": "true"
                                            }, null, -1 /* CACHED */)
                                          ]))], 8 /* PROPS */, _hoisted_48)
                                        ]))
                                      }), 128 /* KEYED_FRAGMENT */))
                                    ]),
                                    _createElementVNode("button", {
                                      class: "btn btn-sm btn-outline-danger w-100 mt-2",
                                      onClick: resetForm
                                    }, _toDisplayString(t('transfer_clear_all', 'Clear All')), 9 /* TEXT, PROPS */, _hoisted_49)
                                  ]))
                            ], 42 /* CLASS, PROPS, NEED_HYDRATION */, _hoisted_33)
                          ]),
                          _createCommentVNode(" Right: Form "),
                          _createElementVNode("div", _hoisted_50, [
                            _createElementVNode("div", _hoisted_51, [
                              _createElementVNode("label", _hoisted_52, _toDisplayString(t('transfer_send_to', 'Send To (Email)')), 1 /* TEXT */),
                              _createElementVNode("div", _hoisted_53, [
                                (recipients.length)
                                  ? (_openBlock(), _createElementBlock("div", _hoisted_54, [
                                      (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(recipients, (email) => {
                                        return (_openBlock(), _createElementBlock("span", {
                                          key: email,
                                          class: "badge text-bg-primary d-inline-flex align-items-center"
                                        }, [
                                          _createElementVNode("span", _hoisted_55, _toDisplayString(email), 1 /* TEXT */),
                                          _createElementVNode("button", {
                                            type: "button",
                                            class: "btn btn-sm btn-link text-white text-decoration-none p-0",
                                            onClick: $event => (removeRecipient(email)),
                                            "aria-label": t('transfer_remove_recipient', 'Remove recipient')
                                          }, [...(_cache[8] || (_cache[8] = [
                                            _createElementVNode("i", { class: "ri-close-line" }, null, -1 /* CACHED */)
                                          ]))], 8 /* PROPS */, _hoisted_56)
                                        ]))
                                      }), 128 /* KEYED_FRAGMENT */))
                                    ]))
                                  : _createCommentVNode("v-if", true),
                                _createElementVNode("div", _hoisted_57, [
                                  _cache[9] || (_cache[9] = _createElementVNode("span", { class: "input-group-text bg-white" }, [
                                    _createElementVNode("i", { class: "ri-at-line" })
                                  ], -1 /* CACHED */)),
                                  _withDirectives(_createElementVNode("input", {
                                    type: "email",
                                    id: "transferRecipientInput",
                                    class: "form-control",
                                    "onUpdate:modelValue": $event => ((recipientInput) = $event),
                                    placeholder: t('transfer_recipient_placeholder', 'Type email and press Enter'),
                                    onKeydown: [
                                      _withKeys(_withModifiers(addRecipientFromInput, ["prevent"]), ["enter"]),
                                      handleRecipientKeydown
                                    ],
                                    onBlur: addRecipientFromInput,
                                    list: "transferRecipientHints"
                                  }, null, 40 /* PROPS, NEED_HYDRATION */, _hoisted_58), [
                                    [
                                      _vModelText,
                                      recipientInput,
                                      void 0,
                                      { trim: true }
                                    ]
                                  ]),
                                  _createElementVNode("button", {
                                    class: "btn btn-outline-primary",
                                    type: "button",
                                    onClick: addRecipientFromInput
                                  }, _toDisplayString(t('transfer_add_recipient', 'Add')), 9 /* TEXT, PROPS */, _hoisted_59)
                                ])
                              ]),
                              _cache[10] || (_cache[10] = _createElementVNode("datalist", { id: "transferRecipientHints" }, [
                                _createElementVNode("option", { value: "user@example.com" })
                              ], -1 /* CACHED */)),
                              recipientError
                                ? (_openBlock(), _createElementBlock("div", _hoisted_60, _toDisplayString(recipientError), 1 /* TEXT */))
                                : (_openBlock(), _createElementBlock("div", _hoisted_61, _toDisplayString(t('transfer_recipient_hint', 'Press Enter or click Add to create recipient chips.')), 1 /* TEXT */))
                            ]),
                            _createElementVNode("div", _hoisted_62, [
                              _createElementVNode("label", _hoisted_63, _toDisplayString(t('transfer_subject', 'Subject')), 1 /* TEXT */),
                              _withDirectives(_createElementVNode("input", {
                                id: "transferSubjectInput",
                                type: "text",
                                class: "form-control form-control-sm",
                                "onUpdate:modelValue": $event => ((form.subject) = $event),
                                placeholder: t('transfer_subject_placeholder', 'Files for you')
                              }, null, 8 /* PROPS */, _hoisted_64), [
                                [_vModelText, form.subject]
                              ])
                            ]),
                            _createElementVNode("div", _hoisted_65, [
                              _createElementVNode("label", _hoisted_66, _toDisplayString(t('transfer_message', 'Message')), 1 /* TEXT */),
                              _withDirectives(_createElementVNode("textarea", {
                                id: "transferMessageInput",
                                class: "form-control form-control-sm",
                                rows: "3",
                                "onUpdate:modelValue": $event => ((form.message) = $event),
                                placeholder: t('transfer_message_placeholder', 'Here are the files...')
                              }, null, 8 /* PROPS */, _hoisted_67), [
                                [_vModelText, form.message]
                              ])
                            ]),
                            _createElementVNode("div", _hoisted_68, [
                              _createElementVNode("div", _hoisted_69, [
                                _createElementVNode("label", _hoisted_70, _toDisplayString(t('transfer_expires_in', 'Expires In')), 1 /* TEXT */),
                                _withDirectives(_createElementVNode("select", {
                                  id: "transferExpiresSelect",
                                  class: "form-select form-select-sm",
                                  "onUpdate:modelValue": $event => ((form.expiresIn) = $event)
                                }, [
                                  _createElementVNode("option", _hoisted_72, _toDisplayString(t('transfer_expires_1', '1 Day')), 1 /* TEXT */),
                                  _createElementVNode("option", _hoisted_73, _toDisplayString(t('transfer_expires_3', '3 Days')), 1 /* TEXT */),
                                  _createElementVNode("option", _hoisted_74, _toDisplayString(t('transfer_expires_7', '7 Days')), 1 /* TEXT */),
                                  _createElementVNode("option", _hoisted_75, _toDisplayString(t('transfer_expires_14', '14 Days')), 1 /* TEXT */),
                                  _createElementVNode("option", _hoisted_76, _toDisplayString(t('transfer_expires_30', '30 Days')), 1 /* TEXT */)
                                ], 8 /* PROPS */, _hoisted_71), [
                                  [_vModelSelect, form.expiresIn]
                                ])
                              ]),
                              _createElementVNode("div", _hoisted_77, [
                                _cache[11] || (_cache[11] = _createElementVNode("label", { class: "form-label small fw-bold" }, " ", -1 /* CACHED */)),
                                _createElementVNode("div", _hoisted_78, [
                                  _withDirectives(_createElementVNode("input", {
                                    class: "form-check-input",
                                    type: "checkbox",
                                    id: "notifyDown",
                                    "onUpdate:modelValue": $event => ((form.notifyDownload) = $event)
                                  }, null, 8 /* PROPS */, _hoisted_79), [
                                    [_vModelCheckbox, form.notifyDownload]
                                  ]),
                                  _createElementVNode("label", _hoisted_80, _toDisplayString(t('transfer_notify_download', 'Notify on Download')), 1 /* TEXT */)
                                ])
                              ])
                            ]),
                            _createElementVNode("button", {
                              class: "btn btn-primary w-100",
                              onClick: $event => (sendTransfer()),
                              disabled: isUploading || allFiles.length === 0
                            }, [
                              isUploading
                                ? (_openBlock(), _createElementBlock("span", _hoisted_82, [
                                    _cache[12] || (_cache[12] = _createElementVNode("span", { class: "spinner-border spinner-border-sm me-1" }, null, -1 /* CACHED */)),
                                    _createTextVNode(" " + _toDisplayString(t('transfer_sending', 'Sending...')) + " " + _toDisplayString(uploadProgress) + "% ", 1 /* TEXT */)
                                  ]))
                                : (_openBlock(), _createElementBlock("span", _hoisted_83, _toDisplayString(t('transfer_button', 'Transfer')), 1 /* TEXT */))
                            ], 8 /* PROPS */, _hoisted_81)
                          ])
                        ])
                      ]))
                ]))
              : _createCommentVNode("v-if", true),
            _createCommentVNode(" History Tab "),
            (tab === 'history')
              ? (_openBlock(), _createElementBlock("div", _hoisted_84, [
                  _createElementVNode("div", _hoisted_85, [
                    _createElementVNode("button", {
                      class: _normalizeClass(["btn btn-sm", historyFilter === 'all' ? 'btn-primary' : 'btn-outline-primary']),
                      onClick: $event => (setHistoryFilter('all'))
                    }, _toDisplayString(t('transfer_filter_all', 'All')), 11 /* TEXT, CLASS, PROPS */, _hoisted_86),
                    _createElementVNode("button", {
                      class: _normalizeClass(["btn btn-sm", historyFilter === 'active' ? 'btn-primary' : 'btn-outline-primary']),
                      onClick: $event => (setHistoryFilter('active'))
                    }, _toDisplayString(t('transfer_filter_active', 'Active')), 11 /* TEXT, CLASS, PROPS */, _hoisted_87),
                    _createElementVNode("button", {
                      class: _normalizeClass(["btn btn-sm", historyFilter === 'downloaded' ? 'btn-primary' : 'btn-outline-primary']),
                      onClick: $event => (setHistoryFilter('downloaded'))
                    }, _toDisplayString(t('transfer_filter_downloaded', 'Downloaded')), 11 /* TEXT, CLASS, PROPS */, _hoisted_88),
                    _createElementVNode("button", {
                      class: _normalizeClass(["btn btn-sm", historyFilter === 'expired' ? 'btn-primary' : 'btn-outline-primary']),
                      onClick: $event => (setHistoryFilter('expired'))
                    }, _toDisplayString(t('transfer_filter_expired', 'Expired')), 11 /* TEXT, CLASS, PROPS */, _hoisted_89)
                  ]),
                  loadingHistory
                    ? (_openBlock(), _createElementBlock("div", _hoisted_90, [...(_cache[13] || (_cache[13] = [
                        _createElementVNode("div", { class: "spinner-border text-primary" }, null, -1 /* CACHED */)
                      ]))]))
                    : (filteredHistory.length === 0)
                      ? (_openBlock(), _createElementBlock("div", _hoisted_91, [
                          _cache[14] || (_cache[14] = _createElementVNode("i", { class: "ri-history-line display-4 opacity-50" }, null, -1 /* CACHED */)),
                          _createElementVNode("p", _hoisted_92, _toDisplayString(t('transfer_no_history', 'No transfers sent yet.')), 1 /* TEXT */)
                        ]))
                      : (_openBlock(), _createElementBlock("div", _hoisted_93, [
                          _createElementVNode("table", _hoisted_94, [
                            _createElementVNode("thead", _hoisted_95, [
                              _createElementVNode("tr", null, [
                                _createElementVNode("th", null, _toDisplayString(t('transfer_col_status', 'Status')), 1 /* TEXT */),
                                _createElementVNode("th", null, _toDisplayString(t('transfer_col_subject', 'Subject')), 1 /* TEXT */),
                                _createElementVNode("th", null, _toDisplayString(t('transfer_col_to', 'To')), 1 /* TEXT */),
                                _createElementVNode("th", null, _toDisplayString(t('transfer_col_date', 'Date')), 1 /* TEXT */),
                                _createElementVNode("th", null, _toDisplayString(t('transfer_col_size', 'Size')), 1 /* TEXT */),
                                _createElementVNode("th", null, _toDisplayString(t('transfer_col_downloads', 'Downloads')), 1 /* TEXT */),
                                _createElementVNode("th", null, _toDisplayString(t('transfer_col_expires', 'Expires')), 1 /* TEXT */),
                                _createElementVNode("th", _hoisted_96, _toDisplayString(t('admin_actions', 'Actions')), 1 /* TEXT */)
                              ])
                            ]),
                            _createElementVNode("tbody", null, [
                              (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(filteredHistory, (item) => {
                                return (_openBlock(), _createElementBlock("tr", { key: item.hash }, [
                                  _createElementVNode("td", null, [
                                    _createElementVNode("span", {
                                      class: _normalizeClass(["badge", statusBadgeClass(item.status)])
                                    }, _toDisplayString(statusLabel(item.status)), 3 /* TEXT, CLASS */)
                                  ]),
                                  _createElementVNode("td", _hoisted_97, _toDisplayString(item.subject || t('transfer_no_subject', '(No Subject)')), 1 /* TEXT */),
                                  _createElementVNode("td", {
                                    class: "text-truncate transfer-recipient",
                                    title: item.recipients?.join(', ')
                                  }, _toDisplayString(item.recipients ? item.recipients[0] + (item.recipients.length > 1 ? ' +' + (item.recipients.length-1) : '') : '-'), 9 /* TEXT, PROPS */, _hoisted_98),
                                  _createElementVNode("td", null, _toDisplayString(formatDate(item.created_at)), 1 /* TEXT */),
                                  _createElementVNode("td", null, _toDisplayString(formatSize(item.total_size)), 1 /* TEXT */),
                                  _createElementVNode("td", null, [
                                    _createElementVNode("span", {
                                      class: _normalizeClass(["badge", item.downloads > 0 ? 'bg-success' : 'bg-secondary'])
                                    }, _toDisplayString(item.downloads), 3 /* TEXT, CLASS */)
                                  ]),
                                  _createElementVNode("td", null, [
                                    _createElementVNode("span", {
                                      class: _normalizeClass({'text-danger': item.is_expired})
                                    }, _toDisplayString(item.is_expired ? t('transfer_filter_expired', 'Expired') : formatDate(item.expires_at)), 3 /* TEXT, CLASS */),
                                    (item.expires_in && !item.is_expired)
                                      ? (_openBlock(), _createElementBlock("div", _hoisted_99, _toDisplayString(expiryCountdown(item.expires_in)), 1 /* TEXT */))
                                      : _createCommentVNode("v-if", true)
                                  ]),
                                  _createElementVNode("td", _hoisted_100, [
                                    _createElementVNode("div", _hoisted_101, [
                                      _createElementVNode("a", {
                                        href: transferLink(item.hash),
                                        target: "_blank",
                                        rel: "noopener"
                                      }, _toDisplayString(transferLink(item.hash)), 9 /* TEXT, PROPS */, _hoisted_102)
                                    ]),
                                    _createElementVNode("button", {
                                      class: "btn btn-link p-0 me-2",
                                      onClick: $event => (copyItemLink(item.hash)),
                                      title: t('transfer_copy_link', 'Copy Link'),
                                      "aria-label": t('transfer_copy_link', 'Copy Link')
                                    }, [...(_cache[15] || (_cache[15] = [
                                      _createElementVNode("i", {
                                        class: "ri-links-line",
                                        "aria-hidden": "true"
                                      }, null, -1 /* CACHED */)
                                    ]))], 8 /* PROPS */, _hoisted_103),
                                    _createElementVNode("button", {
                                      class: "btn btn-link p-0 text-danger",
                                      onClick: $event => (deleteItem(item.hash)),
                                      title: t('delete', 'Delete'),
                                      "aria-label": t('delete', 'Delete')
                                    }, [...(_cache[16] || (_cache[16] = [
                                      _createElementVNode("i", {
                                        class: "ri-delete-bin-line",
                                        "aria-hidden": "true"
                                      }, null, -1 /* CACHED */)
                                    ]))], 8 /* PROPS */, _hoisted_104)
                                  ])
                                ]))
                              }), 128 /* KEYED_FRAGMENT */))
                            ])
                          ])
                        ]))
                ]))
              : _createCommentVNode("v-if", true)
          ])
        ])
      ])
    ]))
  }
}
})(Vue),
    data() {
        return {
            tab: 'new',
            files: [], // Browser File objects
            internalFiles: [], // Internal file objects {name, path, size}
            recipients: [],
            recipientInput: '',
            recipientError: '',
            form: {
                subject: '',
                message: '',
                expiresIn: 7,
                notifyDownload: true
            },
            isDragOver: false,
            isUploading: false,
            uploadProgress: 0,
            successLink: null,
            lastTransfer: null,

            resumeState: null,

            history: [],
            loadingHistory: false,
            historyFilter: 'all',
            modal: null
        };
    },
    computed: {
        allFiles() {
            return [...this.internalFiles, ...this.files];
        },
        totalSize() {
            return this.allFiles.reduce((acc, f) => acc + f.size, 0);
        },
        filteredHistory() {
            if (this.historyFilter === 'all') return this.history;
            return this.history.filter(item => item.status === this.historyFilter);
        }
    },
    mounted() {
        this.modal = new bootstrap.Modal(document.getElementById('transferModal'));
        document.getElementById('transferModal').addEventListener('show.bs.modal', this.checkResume);
    },
    methods: {
        t(key, fallback = '', params = {}) {
            const value = i18n.t(key, params);
            if (value === key) {
                if (!fallback) return key;
                let str = fallback;
                Object.keys(params).forEach((k) => {
                    str = str.replace(`{${k}}`, params[k]);
                });
                return str;
            }
            return value;
        },
        checkResume() {
            const saved = localStorage.getItem('extplorer_transfer_resume');
            if (saved) {
                try {
                    const state = JSON.parse(saved);
                    // Basic expiry check (24h)
                    if (Date.now() - state.timestamp < 86400000) {
                        this.resumeState = state;
                    } else {
                        localStorage.removeItem('extplorer_transfer_resume');
                    }
                } catch(e) { localStorage.removeItem('extplorer_transfer_resume'); }
            }
        },
        open() {
            this.modal.show();
            if (!this.allFiles.length && !this.successLink) {
                 this.tab = 'new';
            }
        },
        openWithFiles(files) {
            this.resetForm();
            // Filter for files only
            this.internalFiles = files.filter(f => f.type !== 'dir').map(f => ({
                name: f.name,
                path: f.path,
                size: f.size,
                type: 'internal'
            }));
            this.tab = 'new';
            this.modal.show();
        },
        resetForm() {
            this.files = [];
            this.internalFiles = [];
            this.recipients = [];
            this.recipientInput = '';
            this.recipientError = '';
            this.form = { subject: '', message: '', expiresIn: 7, notifyDownload: true };
            this.successLink = null;
            this.lastTransfer = null;
            this.uploadProgress = 0;
            this.isUploading = false;
            this.resumeState = null;
            localStorage.removeItem('extplorer_transfer_resume');
        },
        async resumeUpload() {
            if (!this.resumeState) return;

            const { sessionId, recipients, form, fileNames } = this.resumeState;

            // Restore Form
            this.recipients = recipients || [];
            this.recipientInput = '';
            this.recipientError = '';
            this.form = form;
            this.resumeState.isResuming = true;

            // Ask user to re-select files (security restriction: can't restore File objects)
            const { value: fileList } = await Swal.fire({
                title: this.t('transfer_resume_title', 'Resume Upload'),
                text: this.t('transfer_resume_text_prefix', 'Please re-select the following files to resume: ') + fileNames.join(', '),
                input: 'file',
                inputAttributes: { multiple: 'multiple' },
                showCancelButton: true
            });

            if (fileList && fileList.length > 0) {
                // Verify files match
                const selectedNames = Array.from(fileList).map(f => f.name).sort();
                const savedNames = fileNames.sort();

                // Simple name check
                if (JSON.stringify(selectedNames) !== JSON.stringify(savedNames)) {
                    return Swal.fire(this.t('error', 'Error'), this.t('transfer_resume_mismatch', 'Selected files do not match the pending upload.'), 'error');
                }

                this.files = Array.from(fileList);
                this.sendTransfer(sessionId); // Pass existing session ID
            }
        },
        handleDrop(e) {
            this.isDragOver = false;
            const items = e.dataTransfer.items;
            if (items) {
                for (let i=0; i < items.length; i++) {
                    const item = items[i];
                    if (item.kind === 'file') {
                        const entry = item.webkitGetAsEntry();
                        if (entry.isFile) entry.file(f => this.files.push(f));
                    }
                }
                if (this.files.length === 0) [...e.dataTransfer.files].forEach(f => this.files.push(f));
            }
        },
        handleFileSelect(e) {
            [...e.target.files].forEach(f => this.files.push(f));
        },
        removeFile(idx) {
            // idx is index in allFiles (internalFiles + files)
            if (idx < this.internalFiles.length) {
                this.internalFiles.splice(idx, 1);
            } else {
                this.files.splice(idx - this.internalFiles.length, 1);
            }
        },
        isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },
        normalizeEmail(email) {
            return (email || '').trim().toLowerCase();
        },
        addRecipient(email) {
            const normalized = this.normalizeEmail(email);
            if (!normalized) return false;
            if (!this.isValidEmail(normalized)) {
                this.recipientError = this.t('transfer_invalid_email', 'Please enter a valid email address.');
                return false;
            }
            if (this.recipients.includes(normalized)) {
                this.recipientError = this.t('transfer_recipient_exists', 'Recipient already added.');
                return false;
            }
            this.recipients.push(normalized);
            this.recipientError = '';
            return true;
        },
        addRecipientFromInput() {
            const raw = this.recipientInput;
            if (!raw) return;
            const parts = raw.split(/[,\s;]+/).filter(Boolean);
            let addedAny = false;
            for (const part of parts) {
                const added = this.addRecipient(part);
                addedAny = addedAny || added;
            }
            if (addedAny) this.recipientInput = '';
        },
        removeRecipient(email) {
            this.recipients = this.recipients.filter(e => e !== email);
            if (!this.recipients.length) this.recipientError = '';
        },
        handleRecipientKeydown(e) {
            if (e.key === ',' || e.key === ';') {
                e.preventDefault();
                this.addRecipientFromInput();
            }
        },
        async sendTransfer(existingSessionId = null) {
            // Try to commit any pending input before validation.
            this.addRecipientFromInput();

            const validRecipients = this.recipients.filter(e => this.isValidEmail(e));
            if (validRecipients.length === 0) {
                const message = this.t('transfer_require_recipient', 'Please enter at least one valid recipient email.');
                if (window.Swal) {
                    await Swal.fire(this.t('error', 'Error'), message, 'error');
                } else {
                    alert(message);
                }
                return;
            }

            this.isUploading = true;
            this.resumeState = null; // Hide resume UI
            this.uploadProgress = 0;

            const sessionId = existingSessionId || (Date.now().toString(36) + Math.random().toString(36).substr(2));

            // Save state for resume (only for browser files)
            if (this.files.length > 0) {
                localStorage.setItem('extplorer_transfer_resume', JSON.stringify({
                    sessionId,
                    recipients: validRecipients,
                    form: this.form,
                    fileNames: this.files.map(f => f.name),
                    timestamp: Date.now()
                }));
            }

            try {
                // 1. Stage Internal Files
                if (this.internalFiles.length > 0) {
                     await Api.post('transfer/stage', {
                         sessionId,
                         paths: this.internalFiles.map(f => f.path)
                     });
                     // Assume partial progress for staging
                     this.uploadProgress = 10;
                }

                // 2. Upload Files
                const totalBytes = this.totalSize;
                let uploadedBytes = 0; // Local counter for progress

                // Adjust total bytes calculation to include internal files if we want weighted progress
                // For simplicity, we just base progress on the "uploading" part of browser files,
                // but if we have internal files, they are "instant", so we should count them as done.
                const internalSize = this.internalFiles.reduce((acc, f) => acc + f.size, 0);
                uploadedBytes += internalSize;

                for (let file of this.files) {
                    const CHUNK_SIZE = 1024 * 1024; // 1MB
                    const totalChunks = Math.ceil(file.size / CHUNK_SIZE);

                    // Check status from server to skip chunks
                    let startChunk = 0;
                    let startOffset = 0;
                    if (existingSessionId) {
                        try {
                            const status = await Api.get(`transfer/status?sessionId=${sessionId}&fileName=${encodeURIComponent(file.name)}`);
                            if (status.status === 'complete') {
                                uploadedBytes += file.size;
                                continue; // Skip file
                            }
                            if (status.status === 'partial') {
                                const uploaded = Math.min(status.uploaded || 0, file.size);
                                startChunk = Math.floor(uploaded / CHUNK_SIZE);
                                startOffset = uploaded % CHUNK_SIZE;
                                uploadedBytes += uploaded;
                            }
                        } catch(e) { console.warn("Status check failed", e); }
                    }

                    for (let i = startChunk; i < totalChunks; i++) {
                        let startByte = i * CHUNK_SIZE;
                        if (i === startChunk && startOffset > 0) {
                            startByte += startOffset;
                        }
                        const endByte = Math.min((i + 1) * CHUNK_SIZE, file.size);
                        const chunk = file.slice(startByte, endByte);
                        if (chunk.size === 0) continue;
                        const fd = new FormData();
                        fd.append('file', chunk);
                        fd.append('sessionId', sessionId);
                        fd.append('fileName', file.name);
                        fd.append('chunkIndex', i);
                        fd.append('totalChunks', totalChunks);
                        fd.append('fileOffset', startByte);
                        fd.append('fileSize', file.size);

                        await this.uploadChunk(fd);

                        uploadedBytes += chunk.size;
                        // Avoid >100% due to chunk overhead/estimates
                        this.uploadProgress = Math.min(95, Math.round((uploadedBytes / totalBytes) * 95));
                    }
                }

                this.uploadProgress = 98;

                // 3. Finalize Send
                const res = await Api.post('transfer/send', {
                    sessionId,
                    recipients: validRecipients,
                    subject: this.form.subject,
                    message: this.form.message,
                    expiresIn: this.form.expiresIn,
                    notifyDownload: this.form.notifyDownload
                });

                this.uploadProgress = 100;
                this.successLink = res.link;
                this.lastTransfer = {
                    fileCount: this.allFiles.length,
                    totalSize: this.totalSize,
                    recipients: [...validRecipients],
                    expiresAt: (Number(this.form.expiresIn) || 0) > 0
                        ? Math.floor(Date.now() / 1000) + (Number(this.form.expiresIn) * 86400)
                        : null
                };
                this.isUploading = false;
                localStorage.removeItem('extplorer_transfer_resume'); // Clear resume state

                const failures = Array.isArray(res.email_failures) ? res.email_failures : [];
                if (failures.length > 0) {
                    const msg = this.t(
                        'transfer_email_failures_prefix',
                        'The transfer link was created, but some emails failed: '
                    ) + failures.join(', ');
                    if (window.Swal) {
                        Swal.fire(this.t('warning', 'Warning'), msg, 'warning');
                    } else {
                        alert(msg);
                    }
                }

            } catch (e) {
                const message = this.t('transfer_failed_prefix', 'Transfer failed: ') + e.message;
                if (window.Swal) {
                    await Swal.fire(this.t('error', 'Error'), message, 'error');
                } else {
                    alert(message);
                }
                this.isUploading = false;
            }
        },
        async uploadChunk(formData) {
             const headers = { 'X-Requested-With': 'XMLHttpRequest' };
             if (window.csrfHash) headers['X-CSRF-TOKEN'] = window.csrfHash;

             // Retry logic
             let retries = 3;
             while (retries > 0) {
                 try {
                     const res = await fetch(window.baseUrl + 'api/transfer/upload', { method: 'POST', headers, body: formData });
                     if (!res.ok) throw new Error(await Api.readErrorMessage(res, this.t('transfer_upload_failed', 'Upload failed')));
                     return;
                 } catch(e) {
                     retries--;
                     if (retries === 0) throw e;
                     await new Promise(r => setTimeout(r, 1000));
                 }
             }
        },
        async copyText(text) {
            if (!text) return false;

            try {
                if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                    await navigator.clipboard.writeText(text);
                    return true;
                }
            } catch (e) {
                console.warn('Clipboard API failed, falling back to execCommand.', e);
            }

            try {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.setAttribute('readonly', 'readonly');
                ta.style.position = 'fixed';
                ta.style.top = '-1000px';
                ta.style.left = '-1000px';
                document.body.appendChild(ta);
                ta.focus();
                ta.select();
                const ok = document.execCommand('copy');
                document.body.removeChild(ta);
                return !!ok;
            } catch (e) {
                console.error('Fallback clipboard copy failed.', e);
                return false;
            }
        },
        async copyLink() {
            const el = document.getElementById('transferLink');
            if (!el) return;
            const value = el.value || this.successLink || '';
            const ok = await this.copyText(value);
            if (!ok) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire(this.t('error', 'Error'), this.t('copy_failed', 'Copy failed.'), 'error');
                } else {
                    alert(this.t('copy_failed', 'Copy failed.'));
                }
            }
        },
        openLink() {
            if (!this.successLink) return;
            window.open(this.successLink, '_blank', 'noopener');
        },
        goHistory() {
            this.loadHistory();
        },
        setHistoryFilter(filter) {
            this.historyFilter = filter;
        },
        statusBadgeClass(status) {
            if (status === 'downloaded') return 'bg-success';
            if (status === 'expired') return 'bg-danger';
            return 'bg-primary';
        },
        statusLabel(status) {
            if (status === 'downloaded') return this.t('transfer_filter_downloaded', 'Downloaded');
            if (status === 'expired') return this.t('transfer_filter_expired', 'Expired');
            return this.t('transfer_filter_active', 'Active');
        },
        transferLink(hash) {
            return window.baseUrl + 's/' + hash;
        },
        async copyItemLink(hash) {
            const link = this.transferLink(hash);
            const ok = await this.copyText(link);
            if (!ok) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire(this.t('error', 'Error'), this.t('copy_failed', 'Copy failed.'), 'error');
                } else {
                    alert(this.t('copy_failed', 'Copy failed.'));
                }
            }
        },
        async loadHistory() {
            this.tab = 'history';
            this.loadingHistory = true;
            try {
                this.history = await Api.get('transfer/history');
                this.historyFilter = 'all';
            } catch (e) { console.error(e); }
            finally { this.loadingHistory = false; }
        },
        async deleteItem(hash) {
            if (!confirm(this.t('confirm_title', 'Are you sure?'))) return;
            try {
                await Api.delete('transfer/' + hash);
                this.loadHistory();
            } catch (e) { alert(e.message); }
        },

        // Helpers
        formatSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        formatDate(ts) {
            if (!ts) return this.t('never', 'Never');
            return new Date(ts * 1000).toLocaleDateString();
        },
        isExpired(ts) {
            if (!ts) return false;
            return Date.now() / 1000 > ts;
        },
        expiryCountdown(seconds) {
            const days = Math.ceil(seconds / 86400);
            if (days <= 1) return this.t('transfer_expires_within_day', 'expires within 1 day');
            if (days < 7) return this.t('transfer_expires_in_days', 'expires in {days} days', { days });
            const weeks = Math.ceil(days / 7);
            if (weeks === 1) return this.t('transfer_expires_in_week', 'expires in 1 week');
            return this.t('transfer_expires_in_weeks', 'expires in {weeks} weeks', { weeks });
        },
        getIcon(name) { return 'ri-file-line'; }
    }
};
