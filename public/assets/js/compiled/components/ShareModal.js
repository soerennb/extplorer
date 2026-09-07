const ShareModal = {
    render: ((Vue) => {
const _Vue = Vue
const { createElementVNode: _createElementVNode, createCommentVNode: _createCommentVNode, createTextVNode: _createTextVNode } = _Vue

const _hoisted_1 = {
  class: "modal fade",
  id: "shareModal",
  tabindex: "-1"
}
const _hoisted_2 = { class: "modal-dialog modal-dialog-centered" }
const _hoisted_3 = { class: "modal-content" }
const _hoisted_4 = { class: "modal-header" }
const _hoisted_5 = { class: "modal-title" }
const _hoisted_6 = ["aria-label"]
const _hoisted_7 = { class: "modal-body" }
const _hoisted_8 = {
  key: 0,
  class: "text-center"
}
const _hoisted_9 = { key: 1 }
const _hoisted_10 = {
  class: "alert alert-success border small mb-3",
  role: "status"
}
const _hoisted_11 = {
  class: "form-label small fw-bold",
  for: "shareUrlInput"
}
const _hoisted_12 = { class: "input-group mb-3" }
const _hoisted_13 = ["value", "aria-label"]
const _hoisted_14 = ["onClick", "title", "aria-label"]
const _hoisted_15 = ["onClick", "title", "aria-label"]
const _hoisted_16 = { class: "alert alert-light border small mb-3" }
const _hoisted_17 = { key: 0 }
const _hoisted_18 = { class: "text-muted" }
const _hoisted_19 = { key: 1 }
const _hoisted_20 = { class: "d-grid gap-2" }
const _hoisted_21 = ["onClick"]
const _hoisted_22 = ["onClick"]
const _hoisted_23 = ["disabled", "onClick"]
const _hoisted_24 = { key: 2 }
const _hoisted_25 = { class: "small text-muted" }
const _hoisted_26 = {
  key: 0,
  class: "d-grid mb-3"
}
const _hoisted_27 = ["onClick"]
const _hoisted_28 = {
  key: 1,
  class: "alert alert-light border small"
}
const _hoisted_29 = { key: 0 }
const _hoisted_30 = { key: 1 }
const _hoisted_31 = { key: 2 }
const _hoisted_32 = { class: "mb-3" }
const _hoisted_33 = {
  class: "form-label",
  for: "shareModeSelect"
}
const _hoisted_34 = ["onUpdate:modelValue"]
const _hoisted_35 = ["value"]
const _hoisted_36 = {
  key: 0,
  class: "form-text"
}
const _hoisted_37 = {
  key: 2,
  class: "alert alert-warning small"
}
const _hoisted_38 = { class: "fw-bold mb-1" }
const _hoisted_39 = { class: "mt-1" }
const _hoisted_40 = { key: 0 }
const _hoisted_41 = { key: 1 }
const _hoisted_42 = {
  key: 0,
  class: "mt-1"
}
const _hoisted_43 = { class: "mt-1" }
const _hoisted_44 = { key: 0 }
const _hoisted_45 = { key: 1 }
const _hoisted_46 = { class: "mt-1" }
const _hoisted_47 = { key: 0 }
const _hoisted_48 = { key: 1 }
const _hoisted_49 = { class: "mb-3" }
const _hoisted_50 = {
  class: "form-label",
  for: "sharePasswordInput"
}
const _hoisted_51 = ["onUpdate:modelValue", "placeholder"]
const _hoisted_52 = { class: "form-text" }
const _hoisted_53 = { class: "mb-3" }
const _hoisted_54 = {
  class: "form-label",
  for: "shareExpirySelect"
}
const _hoisted_55 = ["onUpdate:modelValue"]
const _hoisted_56 = ["value"]
const _hoisted_57 = ["value"]
const _hoisted_58 = {
  key: 0,
  class: "form-text"
}
const _hoisted_59 = {
  key: 1,
  class: "form-text"
}
const _hoisted_60 = ["disabled", "onClick"]

return function render(_ctx, _cache) {
  with (_ctx) {
    const { createElementVNode: _createElementVNode, toDisplayString: _toDisplayString, createTextVNode: _createTextVNode, openBlock: _openBlock, createElementBlock: _createElementBlock, createCommentVNode: _createCommentVNode, renderList: _renderList, Fragment: _Fragment, vModelSelect: _vModelSelect, withDirectives: _withDirectives, vModelText: _vModelText } = _Vue

    return (_openBlock(), _createElementBlock("div", _hoisted_1, [
      _createElementVNode("div", _hoisted_2, [
        _createElementVNode("div", _hoisted_3, [
          _createElementVNode("div", _hoisted_4, [
            _createElementVNode("h5", _hoisted_5, [
              _cache[0] || (_cache[0] = _createElementVNode("i", { class: "ri-share-line me-2" }, null, -1 /* CACHED */)),
              _createTextVNode(" " + _toDisplayString(t('share_title') || 'Share'), 1 /* TEXT */)
            ]),
            _createElementVNode("button", {
              type: "button",
              class: "btn-close",
              "data-bs-dismiss": "modal",
              "aria-label": t('close') || 'Close'
            }, null, 8 /* PROPS */, _hoisted_6)
          ]),
          _createElementVNode("div", _hoisted_7, [
            loading
              ? (_openBlock(), _createElementBlock("div", _hoisted_8, [...(_cache[1] || (_cache[1] = [
                  _createElementVNode("div", { class: "spinner-border" }, null, -1 /* CACHED */)
                ]))]))
              : currentShare
                ? (_openBlock(), _createElementBlock("div", _hoisted_9, [
                    _createElementVNode("div", _hoisted_10, [
                      _createElementVNode("strong", null, _toDisplayString(t('share_link_active', 'Public link active')), 1 /* TEXT */),
                      _createElementVNode("div", null, _toDisplayString(t('share_link_active_desc', 'Anyone with access to this link can use it according to the settings below.')), 1 /* TEXT */)
                    ]),
                    _createElementVNode("label", _hoisted_11, _toDisplayString(t('share_public_link', 'Public link')), 1 /* TEXT */),
                    _createElementVNode("div", _hoisted_12, [
                      _createElementVNode("input", {
                        type: "text",
                        class: "form-control",
                        value: shareUrl,
                        readonly: "",
                        id: "shareUrlInput",
                        "aria-label": t('share_public_link', 'Public link')
                      }, null, 8 /* PROPS */, _hoisted_13),
                      _createElementVNode("button", {
                        class: "btn btn-outline-primary",
                        onClick: copyLink,
                        title: t('copy') || 'Copy link',
                        "aria-label": t('copy') || 'Copy link'
                      }, [...(_cache[2] || (_cache[2] = [
                        _createElementVNode("i", {
                          class: "ri-file-copy-line",
                          "aria-hidden": "true"
                        }, null, -1 /* CACHED */)
                      ]))], 8 /* PROPS */, _hoisted_14),
                      _createElementVNode("button", {
                        class: "btn btn-outline-secondary",
                        onClick: openLink,
                        title: t('share_open_link', 'Open link'),
                        "aria-label": t('share_open_link', 'Open link')
                      }, [...(_cache[3] || (_cache[3] = [
                        _createElementVNode("i", {
                          class: "ri-external-link-line",
                          "aria-hidden": "true"
                        }, null, -1 /* CACHED */)
                      ]))], 8 /* PROPS */, _hoisted_15)
                    ]),
                    _createElementVNode("div", _hoisted_16, [
                      _createElementVNode("div", null, [
                        _createElementVNode("strong", null, _toDisplayString(t('share_created_at', 'Created')) + ":", 1 /* TEXT */),
                        _createTextVNode(" " + _toDisplayString(formatDate(currentShare.created_at)), 1 /* TEXT */)
                      ]),
                      _createElementVNode("div", null, [
                        _createElementVNode("strong", null, _toDisplayString(t('share_mode_label') || 'Mode') + ":", 1 /* TEXT */),
                        _createTextVNode(" " + _toDisplayString(modeLabel(currentShare.mode)), 1 /* TEXT */)
                      ]),
                      _createElementVNode("div", null, [
                        _createElementVNode("strong", null, _toDisplayString(t('expires')) + ":", 1 /* TEXT */),
                        _cache[4] || (_cache[4] = _createTextVNode("  ", -1 /* CACHED */)),
                        (currentShare.expires_at)
                          ? (_openBlock(), _createElementBlock("span", _hoisted_17, [
                              _createTextVNode(_toDisplayString(formatDate(currentShare.expires_at)) + " ", 1 /* TEXT */),
                              _createElementVNode("span", _hoisted_18, "(" + _toDisplayString(expiryHuman(currentShare.expires_at)) + ")", 1 /* TEXT */)
                            ]))
                          : (_openBlock(), _createElementBlock("span", _hoisted_19, _toDisplayString(t('share_no_expiry', 'No expiry')), 1 /* TEXT */))
                      ]),
                      _createElementVNode("div", null, [
                        _createElementVNode("strong", null, _toDisplayString(t('password') || 'Password') + ":", 1 /* TEXT */),
                        _cache[5] || (_cache[5] = _createTextVNode("  ", -1 /* CACHED */)),
                        _createElementVNode("span", null, _toDisplayString(currentShare.password_hash ? t('password_protected') : t('share_no_password', 'No password')), 1 /* TEXT */)
                      ]),
                      _createElementVNode("div", null, [
                        _createElementVNode("strong", null, _toDisplayString(t('downloads') || 'Downloads') + ":", 1 /* TEXT */),
                        _createTextVNode(" " + _toDisplayString(currentShare.downloads), 1 /* TEXT */)
                      ])
                    ]),
                    _createElementVNode("div", _hoisted_20, [
                      _createElementVNode("button", {
                        class: "btn btn-outline-primary",
                        onClick: copyLink
                      }, [
                        _cache[6] || (_cache[6] = _createElementVNode("i", {
                          class: "ri-file-copy-line me-1",
                          "aria-hidden": "true"
                        }, null, -1 /* CACHED */)),
                        _createTextVNode(_toDisplayString(t('copy')), 1 /* TEXT */)
                      ], 8 /* PROPS */, _hoisted_21),
                      _createElementVNode("button", {
                        class: "btn btn-outline-secondary",
                        onClick: openLink
                      }, [
                        _cache[7] || (_cache[7] = _createElementVNode("i", {
                          class: "ri-external-link-line me-1",
                          "aria-hidden": "true"
                        }, null, -1 /* CACHED */)),
                        _createTextVNode(_toDisplayString(t('share_open_link', 'Open link')), 1 /* TEXT */)
                      ], 8 /* PROPS */, _hoisted_22),
                      _createElementVNode("button", {
                        class: "btn btn-danger",
                        disabled: loading,
                        onClick: deleteShare
                      }, _toDisplayString(t('stop_sharing') || 'Stop Sharing'), 9 /* TEXT, PROPS */, _hoisted_23)
                    ])
                  ]))
                : (_openBlock(), _createElementBlock("div", _hoisted_24, [
                    _createElementVNode("p", _hoisted_25, _toDisplayString(t('share_desc') || 'Create a public link for this item.'), 1 /* TEXT */),
                    (!isDirectory)
                      ? (_openBlock(), _createElementBlock("div", _hoisted_26, [
                          _createElementVNode("button", {
                            class: "btn btn-outline-primary btn-sm",
                            onClick: sendCopy
                          }, [
                            _cache[8] || (_cache[8] = _createElementVNode("i", { class: "ri-send-plane-fill me-2" }, null, -1 /* CACHED */)),
                            _createTextVNode(" " + _toDisplayString(t('send_files') || 'Send a Copy'), 1 /* TEXT */)
                          ], 8 /* PROPS */, _hoisted_27)
                        ]))
                      : _createCommentVNode("v-if", true),
                    policyLoaded
                      ? (_openBlock(), _createElementBlock("div", _hoisted_28, [
                          (policy.require_password)
                            ? (_openBlock(), _createElementBlock("div", _hoisted_29, [
                                _createElementVNode("strong", null, _toDisplayString(t('share_policy_password_required', 'Password is required by policy.')), 1 /* TEXT */)
                              ]))
                            : _createCommentVNode("v-if", true),
                          (policy.require_expiry)
                            ? (_openBlock(), _createElementBlock("div", _hoisted_30, _toDisplayString(t('share_policy_expiry_required', 'Expiry is required. Default: {days} days.', { days: policy.default_expiry_days })), 1 /* TEXT */))
                            : _createCommentVNode("v-if", true),
                          _createElementVNode("div", null, _toDisplayString(t('share_policy_max_expiry', 'Maximum expiry when set: {days} days.', { days: policy.max_expiry_days })), 1 /* TEXT */),
                          (policy.allow_upload_mode)
                            ? (_openBlock(), _createElementBlock("div", _hoisted_31, _toDisplayString(t('share_policy_upload_allowed', 'Upload-mode shares are allowed for folders.')), 1 /* TEXT */))
                            : _createCommentVNode("v-if", true)
                        ]))
                      : _createCommentVNode("v-if", true),
                    _createElementVNode("div", _hoisted_32, [
                      _createElementVNode("label", _hoisted_33, _toDisplayString(t('share_mode_label') || 'Share Mode'), 1 /* TEXT */),
                      _withDirectives(_createElementVNode("select", {
                        id: "shareModeSelect",
                        class: "form-select",
                        "onUpdate:modelValue": $event => ((form.mode) = $event)
                      }, [
                        (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(modeOptions, (opt) => {
                          return (_openBlock(), _createElementBlock("option", {
                            key: opt.value,
                            value: opt.value
                          }, _toDisplayString(opt.label), 9 /* TEXT, PROPS */, _hoisted_35))
                        }), 128 /* KEYED_FRAGMENT */))
                      ], 8 /* PROPS */, _hoisted_34), [
                        [_vModelSelect, form.mode]
                      ]),
                      (policy.allow_upload_mode && !isDirectory)
                        ? (_openBlock(), _createElementBlock("div", _hoisted_36, _toDisplayString(t('share_mode_upload_folders_only', 'Upload mode is only available for folders.')), 1 /* TEXT */))
                        : _createCommentVNode("v-if", true)
                    ]),
                    (form.mode === 'upload')
                      ? (_openBlock(), _createElementBlock("div", _hoisted_37, [
                          _createElementVNode("div", _hoisted_38, _toDisplayString(t('share_mode_upload_title', 'Upload-only share')), 1 /* TEXT */),
                          _createElementVNode("div", null, _toDisplayString(t('share_mode_upload_desc', 'Recipients can upload files but cannot download existing content.')), 1 /* TEXT */),
                          _createElementVNode("div", _hoisted_39, [
                            (uploadPolicy.max_file_mb > 0)
                              ? (_openBlock(), _createElementBlock("span", _hoisted_40, _toDisplayString(t('share_mode_upload_max_file', 'Max file size: {max} MB', { max: uploadPolicy.max_file_mb })), 1 /* TEXT */))
                              : (_openBlock(), _createElementBlock("span", _hoisted_41, _toDisplayString(t('share_mode_upload_no_max_file', 'No max file size configured.')), 1 /* TEXT */))
                          ]),
                          (uploadPolicy.allowed_extensions_label)
                            ? (_openBlock(), _createElementBlock("div", _hoisted_42, _toDisplayString(t('share_mode_upload_allowed_types', 'Allowed types: {types}', { types: uploadPolicy.allowed_extensions_label })), 1 /* TEXT */))
                            : _createCommentVNode("v-if", true),
                          _createElementVNode("div", _hoisted_43, [
                            (uploadPolicy.quota_mb > 0)
                              ? (_openBlock(), _createElementBlock("span", _hoisted_44, _toDisplayString(t('share_mode_upload_quota', 'Total quota: {quota} MB', { quota: uploadPolicy.quota_mb })), 1 /* TEXT */))
                              : (_openBlock(), _createElementBlock("span", _hoisted_45, _toDisplayString(t('share_mode_upload_no_quota', 'No total quota configured.')), 1 /* TEXT */))
                          ]),
                          _createElementVNode("div", _hoisted_46, [
                            (uploadPolicy.max_files > 0)
                              ? (_openBlock(), _createElementBlock("span", _hoisted_47, _toDisplayString(t('share_mode_upload_max_files', 'Max files: {count}', { count: uploadPolicy.max_files })), 1 /* TEXT */))
                              : (_openBlock(), _createElementBlock("span", _hoisted_48, _toDisplayString(t('share_mode_upload_no_max_files', 'No max file count configured.')), 1 /* TEXT */))
                          ])
                        ]))
                      : _createCommentVNode("v-if", true),
                    _createElementVNode("div", _hoisted_49, [
                      _createElementVNode("label", _hoisted_50, _toDisplayString(policy.require_password ? (t('password') || 'Password') : (t('password_optional') || 'Password (Optional)')), 1 /* TEXT */),
                      _withDirectives(_createElementVNode("input", {
                        id: "sharePasswordInput",
                        type: "password",
                        class: "form-control",
                        "onUpdate:modelValue": $event => ((form.password) = $event),
                        placeholder: policy.require_password ? t('share_password_required_placeholder', 'Enter a password') : t('share_password_optional_placeholder', 'Leave blank for no password'),
                        autocomplete: "new-password"
                      }, null, 8 /* PROPS */, _hoisted_51), [
                        [_vModelText, form.password]
                      ]),
                      _createElementVNode("div", _hoisted_52, _toDisplayString(policy.require_password ? t('share_password_required_hint', 'A password must be set before the link can be created.') : t('share_password_optional_hint', 'Leave this empty to create a link without password protection.')), 1 /* TEXT */)
                    ]),
                    _createElementVNode("div", _hoisted_53, [
                      _createElementVNode("label", _hoisted_54, _toDisplayString(policy.require_expiry ? (t('expiration') || 'Expiration') : (t('expiration_optional') || 'Expiration (Optional)')), 1 /* TEXT */),
                      _withDirectives(_createElementVNode("select", {
                        id: "shareExpirySelect",
                        class: "form-select",
                        "onUpdate:modelValue": $event => ((form.expiryDays) = $event)
                      }, [
                        (!policy.require_expiry)
                          ? (_openBlock(), _createElementBlock("option", {
                              key: 0,
                              value: 0
                            }, _toDisplayString(t('share_no_expiry', 'No expiry')), 9 /* TEXT, PROPS */, _hoisted_56))
                          : _createCommentVNode("v-if", true),
                        (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(expiryOptions, (days) => {
                          return (_openBlock(), _createElementBlock("option", {
                            key: days,
                            value: days
                          }, _toDisplayString(days) + " " + _toDisplayString(days === 1 ? (t('day') || 'Day') : (t('days') || 'Days')), 9 /* TEXT, PROPS */, _hoisted_57))
                        }), 128 /* KEYED_FRAGMENT */))
                      ], 8 /* PROPS */, _hoisted_55), [
                        [_vModelSelect, form.expiryDays]
                      ]),
                      expiryPreview
                        ? (_openBlock(), _createElementBlock("div", _hoisted_58, _toDisplayString(t('share_expires_preview', 'Expires {date}', { date: expiryPreview })), 1 /* TEXT */))
                        : (_openBlock(), _createElementBlock("div", _hoisted_59, _toDisplayString(t('share_no_expiry_hint', 'This link will not expire unless it is revoked.')), 1 /* TEXT */))
                    ]),
                    _createElementVNode("button", {
                      class: "btn btn-primary w-100",
                      disabled: loading,
                      onClick: createShare
                    }, _toDisplayString(t('create_link') || 'Create Link'), 9 /* TEXT, PROPS */, _hoisted_60)
                  ]))
          ])
        ])
      ])
    ]))
  }
}
})(Vue),
    setup(props, context) {
        const { ref, reactive, computed } = Vue;
        const t = (key, fallback = '', params = {}) => {
            const value = i18n.t(key, params);
            if (value === key) {
                return fallback || key;
            }
            return value;
        };
        const loading = ref(false);
        const currentFile = ref(null);
        const currentShare = ref(null);
        const targetPath = ref('');
        const isDirectory = ref(false);
        const form = reactive({ password: '', expiryDays: 0, mode: 'read' });
        const policyLoaded = ref(false);
        const policy = reactive({
            require_password: false,
            require_expiry: false,
            default_expiry_days: 7,
            max_expiry_days: 30,
            allow_upload_mode: false,
            available_modes: ['read'],
            upload_policy: {
                max_file_mb: 0,
                allowed_extensions: [],
                allowed_extensions_label: '',
                quota_mb: 0,
                max_files: 0
            }
        });
        let modalInstance = null;

        const shareUrl = computed(() => {
            if (!currentShare.value) return '';
            return window.baseUrl + 's/' + currentShare.value.hash;
        });
        const uploadPolicy = computed(() => policy.upload_policy || {});
        const modeOptions = computed(() => {
            const options = [
                { value: 'read', label: t('share_mode_read') || 'Read-only (download)' }
            ];
            if (policy.allow_upload_mode && isDirectory.value) {
                options.push({ value: 'upload', label: t('share_mode_upload') || 'Upload-only (no download)' });
            }
            return options;
        });
        const expiryOptions = computed(() => {
            const baseOptions = [1, 3, 7, 14, 30, 60, 90, 180, 365];
            const maxDays = Number(policy.max_expiry_days || 30);
            const filtered = baseOptions.filter((d) => d <= maxDays);
            if (!filtered.includes(maxDays)) {
                filtered.push(maxDays);
            }
            if (!filtered.includes(policy.default_expiry_days)) {
                filtered.push(policy.default_expiry_days);
            }
            return Array.from(new Set(filtered))
                .filter((d) => d > 0)
                .sort((a, b) => a - b);
        });
        const expiryPreview = computed(() => {
            const days = Number(form.expiryDays || 0);
            if (days <= 0) return '';
            const ts = Math.floor(Date.now() / 1000) + (days * 86400);
            return formatDate(ts);
        });

        const formatDate = (ts) => new Date(ts * 1000).toLocaleString();
        const expiryHuman = (ts) => {
            if (!ts) return t('share_no_expiry', 'No expiry');
            const now = Math.floor(Date.now() / 1000);
            const diff = ts - now;
            if (diff <= 0) return t('expired', 'Expired');
            const days = Math.ceil(diff / 86400);
            if (days === 1) return t('share_expiry_in_day', 'in 1 day');
            if (days < 7) return t('share_expiry_in_days', 'in {days} days', { days });
            const weeks = Math.ceil(days / 7);
            if (weeks === 1) return t('share_expiry_in_week', 'in 1 week');
            return t('share_expiry_in_weeks', 'in {weeks} weeks', { weeks });
        };
        const modeLabel = (mode) => {
            if (mode === 'upload') return t('share_mode_upload') || 'Upload-only';
            if (mode === 'write') return t('share_mode_write') || 'Write';
            return t('share_mode_read') || 'Read';
        };

        const open = async (file) => {
            currentFile.value = file;
            targetPath.value = file.path;
            isDirectory.value = file.type === 'dir';
            form.password = '';
            form.expiryDays = 0;
            form.mode = 'read';
            currentShare.value = null;
            policyLoaded.value = false;

            if (!modalInstance) modalInstance = new bootstrap.Modal(document.getElementById('shareModal'));
            modalInstance.show();

            // Check if already shared
            loading.value = true;
            try {
                const policyRes = await Api.get('share/policy');
                policy.require_password = Boolean(policyRes.require_password);
                policy.require_expiry = Boolean(policyRes.require_expiry);
                policy.default_expiry_days = Number(policyRes.default_expiry_days || 7);
                policy.max_expiry_days = Number(policyRes.max_expiry_days || 30);
                policy.allow_upload_mode = Boolean(policyRes.allow_upload_mode);
                policy.available_modes = Array.isArray(policyRes.available_modes) ? policyRes.available_modes : ['read'];
                policy.upload_policy = policyRes.upload_policy || policy.upload_policy;
                policyLoaded.value = true;

                if (policy.require_expiry) {
                    form.expiryDays = policy.default_expiry_days;
                }

                const res = await Api.get('share/list');
                const existing = res.items.find(s => s.path === file.path);
                if (existing) {
                    currentShare.value = existing;
                    form.mode = existing.mode || 'read';
                }
            } catch(e) { console.error(e); }
            finally { loading.value = false; }
        };

        const createShare = async () => {
            loading.value = true;
            try {
                if (form.mode === 'upload' && !isDirectory.value) {
                    throw new Error(t('share_mode_upload_folders_only', 'Upload mode is only available for folders.'));
                }

                if (policy.require_password && !form.password) {
                    throw new Error(t('share_policy_password_required', 'Password is required by policy.'));
                }

                const allowedModes = Array.isArray(policy.available_modes) ? policy.available_modes : ['read'];
                if (!allowedModes.includes(form.mode)) {
                    throw new Error(t('share_mode_not_allowed', 'Share mode is not allowed by policy.'));
                }

                const maxDays = Number(policy.max_expiry_days || 30);
                let expiryDays = Number(form.expiryDays || 0);
                if (policy.require_expiry && expiryDays <= 0) {
                    expiryDays = Number(policy.default_expiry_days || 7);
                    form.expiryDays = expiryDays;
                }
                if (expiryDays > maxDays) {
                    expiryDays = maxDays;
                    form.expiryDays = maxDays;
                }

                let expires = null;
                if (expiryDays > 0) {
                    expires = Math.floor(Date.now() / 1000) + (expiryDays * 86400);
                }

                const res = await Api.post('share/create', {
                    path: targetPath.value,
                    password: form.password,
                    expires: expires,
                    mode: form.mode
                });
                currentShare.value = res.share;
            } catch(e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
            finally { loading.value = false; }
        };

        const deleteShare = async () => {
            if (!currentShare.value) return;
            loading.value = true;
            try {
                await Api.post('share/delete', { hash: currentShare.value.hash });
                currentShare.value = null;
            } catch(e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
            finally { loading.value = false; }
        };

        const copyLink = () => {
            const i = document.getElementById('shareUrlInput');
            if (!i) return;
            const value = i.value || shareUrl.value;
            if (navigator.clipboard && value) {
                navigator.clipboard.writeText(value);
            } else {
                i.select();
                document.execCommand('copy');
            }
            Swal.fire({ title: 'Copied!', icon: 'success', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
        };

        const openLink = () => {
            if (!shareUrl.value) return;
            window.open(shareUrl.value, '_blank', 'noopener');
        };

        const sendCopy = () => {
            if (!currentFile.value) return;
            if (modalInstance) modalInstance.hide();
            context.emit('transfer', currentFile.value);
        };

        return {
            open, loading, currentShare, form, createShare, deleteShare, shareUrl, copyLink, openLink, formatDate, expiryHuman, expiryPreview, expiryOptions, policy, policyLoaded,
            uploadPolicy, modeOptions, isDirectory, modeLabel, sendCopy,
            t
        };
    }
};
