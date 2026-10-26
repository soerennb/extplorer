const UserProfile = {
    render: ((Vue) => {
const _Vue = Vue
const { createElementVNode: _createElementVNode, createCommentVNode: _createCommentVNode, createTextVNode: _createTextVNode } = _Vue

const _hoisted_1 = ["data-bs-keyboard"]
const _hoisted_2 = { class: "modal-dialog modal-lg modal-dialog-centered" }
const _hoisted_3 = { class: "modal-content" }
const _hoisted_4 = { class: "modal-header" }
const _hoisted_5 = {
  class: "modal-title",
  id: "userProfileModalTitle"
}
const _hoisted_6 = ["aria-label"]
const _hoisted_7 = { class: "modal-body" }
const _hoisted_8 = {
  key: 0,
  class: "alert alert-warning d-flex align-items-start",
  role: "alert"
}
const _hoisted_9 = { class: "small" }
const _hoisted_10 = {
  class: "nav nav-tabs mb-3",
  role: "tablist"
}
const _hoisted_11 = {
  class: "nav-item",
  role: "presentation"
}
const _hoisted_12 = ["aria-selected", "onClick"]
const _hoisted_13 = {
  class: "nav-item",
  role: "presentation"
}
const _hoisted_14 = ["aria-selected", "onClick"]
const _hoisted_15 = {
  key: 0,
  class: "nav-item",
  role: "presentation"
}
const _hoisted_16 = ["aria-selected", "onClick"]
const _hoisted_17 = {
  role: "tabpanel",
  id: "profile-panel-general",
  "aria-labelledby": "profile-tab-general",
  tabindex: "0"
}
const _hoisted_18 = {
  key: 0,
  class: "text-center py-4"
}
const _hoisted_19 = ["aria-label"]
const _hoisted_20 = {
  key: 1,
  class: "row"
}
const _hoisted_21 = {
  class: "col-sm-3 col-form-label fw-bold",
  for: "profile-username"
}
const _hoisted_22 = { class: "col-sm-9" }
const _hoisted_23 = ["value"]
const _hoisted_24 = {
  class: "col-sm-3 col-form-label fw-bold",
  for: "profile-role"
}
const _hoisted_25 = { class: "col-sm-9" }
const _hoisted_26 = ["value"]
const _hoisted_27 = {
  class: "col-sm-3 col-form-label fw-bold",
  for: "profile-home-dir"
}
const _hoisted_28 = { class: "col-sm-9" }
const _hoisted_29 = ["value"]
const _hoisted_30 = { class: "mt-4 pt-3 border-top" }
const _hoisted_31 = { class: "mb-2" }
const _hoisted_32 = { class: "small text-muted mb-2" }
const _hoisted_33 = { class: "row g-2 align-items-center" }
const _hoisted_34 = {
  class: "col-sm-3 col-form-label fw-bold",
  for: "profile-language-select"
}
const _hoisted_35 = { class: "col-sm-9" }
const _hoisted_36 = ["onUpdate:modelValue", "onChange"]
const _hoisted_37 = ["value"]
const _hoisted_38 = { class: "mt-4 pt-3 border-top" }
const _hoisted_39 = { class: "mb-3" }
const _hoisted_40 = {
  key: 0,
  class: "mb-2"
}
const _hoisted_41 = { class: "small fw-bold d-block text-success" }
const _hoisted_42 = {
  key: 1,
  class: "mb-2"
}
const _hoisted_43 = { class: "small fw-bold d-block text-danger" }
const _hoisted_44 = { key: 2 }
const _hoisted_45 = { class: "small fw-bold d-block text-secondary" }
const _hoisted_46 = {
  role: "tabpanel",
  id: "profile-panel-security",
  "aria-labelledby": "profile-tab-security",
  tabindex: "0"
}
const _hoisted_47 = {
  key: 0,
  class: "text-muted",
  role: "status"
}
const _hoisted_48 = { class: "border-bottom pb-2 mb-3" }
const _hoisted_49 = { class: "mb-3 row" }
const _hoisted_50 = {
  class: "col-sm-3 col-form-label",
  for: "profile-current-password"
}
const _hoisted_51 = { class: "col-sm-9" }
const _hoisted_52 = ["onUpdate:modelValue", "placeholder"]
const _hoisted_53 = { class: "mb-3 row" }
const _hoisted_54 = {
  class: "col-sm-3 col-form-label",
  for: "profile-new-password"
}
const _hoisted_55 = { class: "col-sm-9" }
const _hoisted_56 = ["onUpdate:modelValue", "placeholder"]
const _hoisted_57 = {
  class: "profile-strength mt-2",
  "aria-live": "polite"
}
const _hoisted_58 = { class: "d-flex align-items-center gap-2" }
const _hoisted_59 = ["aria-valuenow"]
const _hoisted_60 = {
  key: 0,
  class: "invalid-feedback d-block"
}
const _hoisted_61 = { class: "profile-password-hints mt-2 mb-0" }
const _hoisted_62 = { class: "mb-3 row" }
const _hoisted_63 = {
  class: "col-sm-3 col-form-label",
  for: "profile-confirm-password"
}
const _hoisted_64 = { class: "col-sm-9" }
const _hoisted_65 = ["onUpdate:modelValue", "placeholder"]
const _hoisted_66 = {
  key: 0,
  class: "invalid-feedback d-block"
}
const _hoisted_67 = { class: "mb-4 row" }
const _hoisted_68 = { class: "col-sm-9 offset-sm-3" }
const _hoisted_69 = ["onClick", "disabled"]
const _hoisted_70 = {
  key: 0,
  class: "spinner-border spinner-border-sm me-1",
  "aria-hidden": "true"
}
const _hoisted_71 = { class: "border-bottom pb-2 mb-3 mt-4" }
const _hoisted_72 = {
  key: 1,
  class: "profile-stepper mb-3",
  role: "list"
}
const _hoisted_73 = { class: "profile-step-label" }
const _hoisted_74 = { class: "profile-step-label" }
const _hoisted_75 = { class: "profile-step-label" }
const _hoisted_76 = {
  key: 3,
  class: "alert alert-success d-flex flex-column gap-2",
  role: "alert"
}
const _hoisted_77 = { class: "d-flex align-items-center" }
const _hoisted_78 = { class: "small text-muted" }
const _hoisted_79 = ["onClick", "disabled"]
const _hoisted_80 = {
  key: 0,
  class: "spinner-border spinner-border-sm me-1",
  "aria-hidden": "true"
}
const _hoisted_81 = {
  key: 0,
  class: "card bg-body-tertiary"
}
const _hoisted_82 = { class: "card-body" }
const _hoisted_83 = { class: "mb-2" }
const _hoisted_84 = { class: "small text-muted mb-3" }
const _hoisted_85 = { class: "row g-2" }
const _hoisted_86 = { class: "col-md-6" }
const _hoisted_87 = {
  class: "form-label small",
  for: "disable-2fa-password"
}
const _hoisted_88 = ["onUpdate:modelValue"]
const _hoisted_89 = { class: "col-md-6" }
const _hoisted_90 = {
  class: "form-label small",
  for: "disable-2fa-code"
}
const _hoisted_91 = ["onUpdate:modelValue", "placeholder"]
const _hoisted_92 = { class: "d-flex justify-content-end gap-2 mt-3" }
const _hoisted_93 = ["onClick", "disabled"]
const _hoisted_94 = ["onClick", "disabled"]
const _hoisted_95 = {
  key: 0,
  class: "spinner-border spinner-border-sm me-1",
  "aria-hidden": "true"
}
const _hoisted_96 = { key: 4 }
const _hoisted_97 = { class: "small text-muted" }
const _hoisted_98 = ["onClick", "disabled"]
const _hoisted_99 = {
  key: 0,
  class: "spinner-border spinner-border-sm me-1",
  "aria-hidden": "true"
}
const _hoisted_100 = {
  key: 1,
  class: "card bg-body-tertiary mt-3"
}
const _hoisted_101 = { class: "card-body text-center" }
const _hoisted_102 = { class: "card-title" }
const _hoisted_103 = { class: "small text-muted mb-3" }
const _hoisted_104 = { class: "profile-qr-wrap mb-3" }
const _hoisted_105 = ["src", "alt"]
const _hoisted_106 = { class: "profile-secret-block mb-2" }
const _hoisted_107 = { class: "small text-muted" }
const _hoisted_108 = { class: "font-monospace user-select-all" }
const _hoisted_109 = ["onClick", "disabled"]
const _hoisted_110 = { class: "input-group input-group-sm mb-3 qr-input-group" }
const _hoisted_111 = ["placeholder", "onUpdate:modelValue"]
const _hoisted_112 = ["onClick", "disabled"]
const _hoisted_113 = {
  key: 0,
  class: "spinner-border spinner-border-sm me-1",
  "aria-hidden": "true"
}
const _hoisted_114 = ["onClick", "disabled"]
const _hoisted_115 = {
  key: 5,
  class: "mt-3"
}
const _hoisted_116 = {
  class: "alert alert-warning",
  role: "alert"
}
const _hoisted_117 = { class: "small mb-2" }
const _hoisted_118 = { class: "small text-muted mb-2" }
const _hoisted_119 = {
  class: "profile-recovery-list font-monospace small user-select-all mb-3",
  "aria-live": "polite"
}
const _hoisted_120 = { class: "d-flex flex-wrap gap-2" }
const _hoisted_121 = ["onClick"]
const _hoisted_122 = ["onClick"]
const _hoisted_123 = ["onClick"]
const _hoisted_124 = { class: "border-bottom pb-2 mb-3 mt-4" }
const _hoisted_125 = { class: "small text-muted" }
const _hoisted_126 = {
  key: 7,
  class: "alert alert-warning",
  role: "alert"
}
const _hoisted_127 = { class: "d-block mb-2" }
const _hoisted_128 = { class: "user-select-all text-break" }
const _hoisted_129 = ["onClick"]
const _hoisted_130 = { class: "input-group input-group-sm mb-3" }
const _hoisted_131 = ["onUpdate:modelValue", "placeholder"]
const _hoisted_132 = ["onClick", "disabled"]
const _hoisted_133 = {
  key: 0,
  class: "spinner-border spinner-border-sm me-1",
  "aria-hidden": "true"
}
const _hoisted_134 = {
  key: 8,
  class: "list-group list-group-flush border rounded"
}
const _hoisted_135 = { class: "small text-muted" }
const _hoisted_136 = ["onClick", "disabled"]
const _hoisted_137 = {
  key: 9,
  class: "small text-muted"
}
const _hoisted_138 = {
  role: "tabpanel",
  id: "profile-panel-mounts",
  "aria-labelledby": "profile-tab-mounts",
  tabindex: "0"
}
const _hoisted_139 = { class: "border-bottom pb-2 mb-3" }
const _hoisted_140 = { class: "small text-muted" }
const _hoisted_141 = {
  key: 1,
  class: "text-center py-3"
}
const _hoisted_142 = ["aria-label"]
const _hoisted_143 = { key: 2 }
const _hoisted_144 = {
  key: 0,
  class: "text-center py-4 text-muted small"
}
const _hoisted_145 = {
  key: 1,
  class: "list-group list-group-flush mb-4 border rounded"
}
const _hoisted_146 = { class: "me-3" }
const _hoisted_147 = { class: "fw-bold d-flex align-items-center gap-2" }
const _hoisted_148 = { class: "badge text-bg-light border" }
const _hoisted_149 = { class: "small text-muted font-monospace" }
const _hoisted_150 = { class: "d-flex gap-2" }
const _hoisted_151 = ["onClick", "disabled"]
const _hoisted_152 = ["onClick", "disabled"]
const _hoisted_153 = {
  key: 0,
  class: "spinner-border spinner-border-sm",
  "aria-hidden": "true"
}
const _hoisted_154 = {
  key: 1,
  class: "ri-delete-bin-line"
}
const _hoisted_155 = { class: "card bg-body-tertiary" }
const _hoisted_156 = { class: "card-body" }
const _hoisted_157 = { class: "d-flex justify-content-between align-items-center mb-2" }
const _hoisted_158 = { class: "mb-0" }
const _hoisted_159 = ["onClick", "disabled"]
const _hoisted_160 = { class: "row g-2" }
const _hoisted_161 = { class: "col-md-4" }
const _hoisted_162 = {
  class: "form-label small",
  for: "mount-name"
}
const _hoisted_163 = ["onUpdate:modelValue", "placeholder"]
const _hoisted_164 = {
  key: 0,
  class: "invalid-feedback d-block"
}
const _hoisted_165 = { class: "col-md-4" }
const _hoisted_166 = {
  class: "form-label small",
  for: "mount-type"
}
const _hoisted_167 = ["onUpdate:modelValue", "disabled"]
const _hoisted_168 = { value: "local" }
const _hoisted_169 = { value: "ftp" }
const _hoisted_170 = { value: "ftps" }
const _hoisted_171 = { value: "sftp" }
const _hoisted_172 = {
  key: 0,
  class: "col-md-4"
}
const _hoisted_173 = {
  class: "form-label small",
  for: "mount-path"
}
const _hoisted_174 = ["onUpdate:modelValue", "placeholder"]
const _hoisted_175 = {
  key: 0,
  class: "invalid-feedback d-block"
}
const _hoisted_176 = {
  key: 1,
  class: "mt-2"
}
const _hoisted_177 = { class: "row g-2" }
const _hoisted_178 = { class: "col-md-6" }
const _hoisted_179 = {
  class: "form-label small",
  for: "mount-host"
}
const _hoisted_180 = ["onUpdate:modelValue", "placeholder"]
const _hoisted_181 = {
  key: 0,
  class: "invalid-feedback d-block"
}
const _hoisted_182 = { class: "col-md-2" }
const _hoisted_183 = {
  class: "form-label small",
  for: "mount-port"
}
const _hoisted_184 = ["onUpdate:modelValue"]
const _hoisted_185 = { class: "col-md-4" }
const _hoisted_186 = {
  class: "form-label small",
  for: "mount-root"
}
const _hoisted_187 = ["onUpdate:modelValue", "placeholder"]
const _hoisted_188 = {
  key: 0,
  class: "row g-2 mt-1"
}
const _hoisted_189 = { class: "col-12" }
const _hoisted_190 = {
  class: "form-label small",
  for: "mount-host-key-fingerprint"
}
const _hoisted_191 = ["onUpdate:modelValue"]
const _hoisted_192 = { class: "form-text" }
const _hoisted_193 = {
  key: 1,
  class: "row g-2 mt-1"
}
const _hoisted_194 = { class: "col-12" }
const _hoisted_195 = {
  class: "form-label small",
  for: "mount-tls-spki-pin"
}
const _hoisted_196 = ["onUpdate:modelValue"]
const _hoisted_197 = { class: "form-text" }
const _hoisted_198 = {
  key: 2,
  class: "row g-2 mt-1"
}
const _hoisted_199 = { class: "col-md-4" }
const _hoisted_200 = {
  class: "form-label small",
  for: "mount-auth-method"
}
const _hoisted_201 = ["onUpdate:modelValue"]
const _hoisted_202 = { value: "password" }
const _hoisted_203 = { value: "private_key" }
const _hoisted_204 = {
  key: 3,
  class: "row g-2 mt-1"
}
const _hoisted_205 = { class: "col-md-6" }
const _hoisted_206 = {
  class: "form-label small",
  for: "mount-private-key"
}
const _hoisted_207 = ["onUpdate:modelValue", "placeholder"]
const _hoisted_208 = { class: "col-md-6" }
const _hoisted_209 = {
  class: "form-label small",
  for: "mount-public-key"
}
const _hoisted_210 = ["onUpdate:modelValue", "placeholder"]
const _hoisted_211 = { class: "col-12" }
const _hoisted_212 = {
  class: "form-label small",
  for: "mount-private-key-passphrase"
}
const _hoisted_213 = ["onUpdate:modelValue"]
const _hoisted_214 = { class: "row g-2 mt-1" }
const _hoisted_215 = { class: "col-md-6" }
const _hoisted_216 = {
  class: "form-label small",
  for: "mount-user"
}
const _hoisted_217 = ["onUpdate:modelValue", "placeholder"]
const _hoisted_218 = {
  key: 0,
  class: "invalid-feedback d-block"
}
const _hoisted_219 = { class: "col-md-6" }
const _hoisted_220 = {
  class: "form-label small",
  for: "mount-pass"
}
const _hoisted_221 = ["onUpdate:modelValue", "placeholder", "disabled"]
const _hoisted_222 = {
  key: 0,
  class: "invalid-feedback d-block"
}
const _hoisted_223 = {
  key: 1,
  class: "form-text small"
}
const _hoisted_224 = { class: "mt-3 d-flex flex-wrap justify-content-end gap-2" }
const _hoisted_225 = ["onClick", "disabled"]
const _hoisted_226 = {
  key: 0,
  class: "spinner-border spinner-border-sm me-1",
  "aria-hidden": "true"
}
const _hoisted_227 = ["onClick", "disabled"]
const _hoisted_228 = {
  key: 0,
  class: "spinner-border spinner-border-sm me-1",
  "aria-hidden": "true"
}

return function render(_ctx, _cache) {
  with (_ctx) {
    const { createElementVNode: _createElementVNode, toDisplayString: _toDisplayString, createTextVNode: _createTextVNode, openBlock: _openBlock, createElementBlock: _createElementBlock, createCommentVNode: _createCommentVNode, normalizeClass: _normalizeClass, renderList: _renderList, Fragment: _Fragment, vModelSelect: _vModelSelect, withDirectives: _withDirectives, vShow: _vShow, vModelText: _vModelText } = _Vue

    return (_openBlock(), _createElementBlock("div", {
      class: "modal fade",
      id: "userProfileModal",
      tabindex: "-1",
      "data-bs-backdrop": "static",
      "data-bs-keyboard": forcePasswordChange ? 'false' : 'true',
      "aria-labelledby": "userProfileModalTitle",
      "aria-modal": "true",
      role: "dialog"
    }, [
      _createElementVNode("div", _hoisted_2, [
        _createElementVNode("div", _hoisted_3, [
          _createElementVNode("div", _hoisted_4, [
            _createElementVNode("h5", _hoisted_5, [
              _cache[0] || (_cache[0] = _createElementVNode("i", { class: "ri-user-settings-line me-2" }, null, -1 /* CACHED */)),
              _createTextVNode(" " + _toDisplayString(t('profile_settings') || 'Profile & Settings'), 1 /* TEXT */)
            ]),
            (!forcePasswordChange)
              ? (_openBlock(), _createElementBlock("button", {
                  key: 0,
                  type: "button",
                  class: "btn-close",
                  "data-bs-dismiss": "modal",
                  "aria-label": t('close') || 'Close'
                }, null, 8 /* PROPS */, _hoisted_6))
              : _createCommentVNode("v-if", true)
          ]),
          _createElementVNode("div", _hoisted_7, [
            forcePasswordChange
              ? (_openBlock(), _createElementBlock("div", _hoisted_8, [
                  _cache[1] || (_cache[1] = _createElementVNode("i", { class: "ri-alert-line fs-5 me-2 mt-1" }, null, -1 /* CACHED */)),
                  _createElementVNode("div", null, [
                    _createElementVNode("strong", null, _toDisplayString(t('password_change_required') || 'Password change required'), 1 /* TEXT */),
                    _createElementVNode("div", _hoisted_9, _toDisplayString(t('password_change_required_desc') || 'A password change is required before continuing.'), 1 /* TEXT */)
                  ])
                ]))
              : _createCommentVNode("v-if", true),
            _createElementVNode("ul", _hoisted_10, [
              _createElementVNode("li", _hoisted_11, [
                _createElementVNode("button", {
                  class: _normalizeClass(["nav-link", { active: activeTab === 'general' }]),
                  type: "button",
                  role: "tab",
                  id: "profile-tab-general",
                  "aria-selected": activeTab === 'general' ? 'true' : 'false',
                  "aria-controls": "profile-panel-general",
                  onClick: $event => (setTab('general'))
                }, _toDisplayString(t('general') || 'General'), 11 /* TEXT, CLASS, PROPS */, _hoisted_12)
              ]),
              _createElementVNode("li", _hoisted_13, [
                _createElementVNode("button", {
                  class: _normalizeClass(["nav-link", { active: activeTab === 'security' }]),
                  type: "button",
                  role: "tab",
                  id: "profile-tab-security",
                  "aria-selected": activeTab === 'security' ? 'true' : 'false',
                  "aria-controls": "profile-panel-security",
                  onClick: $event => (setTab('security'))
                }, _toDisplayString(t('security') || 'Security'), 11 /* TEXT, CLASS, PROPS */, _hoisted_14)
              ]),
              canMount
                ? (_openBlock(), _createElementBlock("li", _hoisted_15, [
                    _createElementVNode("button", {
                      class: _normalizeClass(["nav-link", { active: activeTab === 'mounts' }]),
                      type: "button",
                      role: "tab",
                      id: "profile-tab-mounts",
                      "aria-selected": activeTab === 'mounts' ? 'true' : 'false',
                      "aria-controls": "profile-panel-mounts",
                      onClick: $event => (setTab('mounts'))
                    }, _toDisplayString(t('mounts') || 'Mounts'), 11 /* TEXT, CLASS, PROPS */, _hoisted_16)
                  ]))
                : _createCommentVNode("v-if", true)
            ]),
            _withDirectives(_createElementVNode("div", _hoisted_17, [
              loading
                ? (_openBlock(), _createElementBlock("div", _hoisted_18, [
                    _createElementVNode("div", {
                      class: "spinner-border",
                      role: "status",
                      "aria-label": t('loading') || 'Loading'
                    }, null, 8 /* PROPS */, _hoisted_19)
                  ]))
                : (_openBlock(), _createElementBlock("div", _hoisted_20, [
                    _createElementVNode("label", _hoisted_21, _toDisplayString(t('username') || 'Username'), 1 /* TEXT */),
                    _createElementVNode("div", _hoisted_22, [
                      _createElementVNode("input", {
                        id: "profile-username",
                        type: "text",
                        readonly: "",
                        class: "form-control-plaintext",
                        value: details.display_name || details.username
                      }, null, 8 /* PROPS */, _hoisted_23)
                    ]),
                    _createElementVNode("label", _hoisted_24, _toDisplayString(t('role') || 'Role'), 1 /* TEXT */),
                    _createElementVNode("div", _hoisted_25, [
                      _createElementVNode("input", {
                        id: "profile-role",
                        type: "text",
                        readonly: "",
                        class: "form-control-plaintext",
                        value: details.role
                      }, null, 8 /* PROPS */, _hoisted_26)
                    ]),
                    _createElementVNode("label", _hoisted_27, _toDisplayString(t('home_dir') || 'Home Directory'), 1 /* TEXT */),
                    _createElementVNode("div", _hoisted_28, [
                      _createElementVNode("input", {
                        id: "profile-home-dir",
                        type: "text",
                        readonly: "",
                        class: "form-control-plaintext",
                        value: details.home_dir
                      }, null, 8 /* PROPS */, _hoisted_29)
                    ])
                  ])),
              _createElementVNode("div", _hoisted_30, [
                _createElementVNode("h6", _hoisted_31, _toDisplayString(t('language') || 'Language'), 1 /* TEXT */),
                _createElementVNode("div", _hoisted_32, _toDisplayString(t('language_desc') || 'Choose the interface language for this browser.'), 1 /* TEXT */),
                _createElementVNode("div", _hoisted_33, [
                  _createElementVNode("label", _hoisted_34, _toDisplayString(t('language') || 'Language'), 1 /* TEXT */),
                  _createElementVNode("div", _hoisted_35, [
                    _withDirectives(_createElementVNode("select", {
                      id: "profile-language-select",
                      class: "form-select form-select-sm",
                      "onUpdate:modelValue": $event => ((selectedLocale) = $event),
                      onChange: updateLocale
                    }, [
                      (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(availableLocales, (loc) => {
                        return (_openBlock(), _createElementBlock("option", {
                          key: loc.code,
                          value: loc.code
                        }, _toDisplayString(t(loc.labelKey) || loc.labelFallback), 9 /* TEXT, PROPS */, _hoisted_37))
                      }), 128 /* KEYED_FRAGMENT */))
                    ], 40 /* PROPS, NEED_HYDRATION */, _hoisted_36), [
                      [_vModelSelect, selectedLocale]
                    ])
                  ])
                ])
              ]),
              _createElementVNode("div", _hoisted_38, [
                _createElementVNode("h6", _hoisted_39, _toDisplayString(t('file_extensions') || 'File Extensions'), 1 /* TEXT */),
                (details.allowed_extensions)
                  ? (_openBlock(), _createElementBlock("div", _hoisted_40, [
                      _createElementVNode("span", _hoisted_41, _toDisplayString(t('allowed') || 'Allowed') + ":", 1 /* TEXT */),
                      (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(details.allowed_extensions.split(','), (ext) => {
                        return (_openBlock(), _createElementBlock("span", {
                          key: ext,
                          class: "badge bg-success-subtle text-success border border-success-subtle me-1"
                        }, _toDisplayString(ext.trim()), 1 /* TEXT */))
                      }), 128 /* KEYED_FRAGMENT */))
                    ]))
                  : _createCommentVNode("v-if", true),
                (details.blocked_extensions)
                  ? (_openBlock(), _createElementBlock("div", _hoisted_42, [
                      _createElementVNode("span", _hoisted_43, _toDisplayString(t('blocked_user') || 'Blocked (User)') + ":", 1 /* TEXT */),
                      (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(details.blocked_extensions.split(','), (ext) => {
                        return (_openBlock(), _createElementBlock("span", {
                          key: ext,
                          class: "badge bg-danger-subtle text-danger border border-danger-subtle me-1"
                        }, _toDisplayString(ext.trim()), 1 /* TEXT */))
                      }), 128 /* KEYED_FRAGMENT */))
                    ]))
                  : _createCommentVNode("v-if", true),
                (details.system_blocklist && details.system_blocklist.length)
                  ? (_openBlock(), _createElementBlock("div", _hoisted_44, [
                      _createElementVNode("span", _hoisted_45, _toDisplayString(t('blocked_system') || 'Blocked (System)') + ":", 1 /* TEXT */),
                      (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(details.system_blocklist, (ext) => {
                        return (_openBlock(), _createElementBlock("span", {
                          key: ext,
                          class: "badge bg-secondary-subtle text-secondary border border-secondary-subtle me-1"
                        }, _toDisplayString(ext), 1 /* TEXT */))
                      }), 128 /* KEYED_FRAGMENT */))
                    ]))
                  : _createCommentVNode("v-if", true)
              ])
            ], 512 /* NEED_PATCH */), [
              [_vShow, activeTab === 'general']
            ]),
            _withDirectives(_createElementVNode("div", _hoisted_46, [
              (details.auth_source === 'external')
                ? (_openBlock(), _createElementBlock("p", _hoisted_47, _toDisplayString(t('external_auth_managed')), 1 /* TEXT */))
                : (_openBlock(), _createElementBlock(_Fragment, { key: 1 }, [
                    _createElementVNode("h6", _hoisted_48, _toDisplayString(t('change_password') || 'Change Password'), 1 /* TEXT */),
                    (passwordMessage.text)
                      ? (_openBlock(), _createElementBlock("div", {
                          key: 0,
                          class: _normalizeClass(["alert", passwordMessage.type === 'success' ? 'alert-success' : 'alert-danger']),
                          role: "alert"
                        }, _toDisplayString(passwordMessage.text), 3 /* TEXT, CLASS */))
                      : _createCommentVNode("v-if", true),
                    _createElementVNode("div", _hoisted_49, [
                      _createElementVNode("label", _hoisted_50, _toDisplayString(t('current_password') || 'Current Password'), 1 /* TEXT */),
                      _createElementVNode("div", _hoisted_51, [
                        _withDirectives(_createElementVNode("input", {
                          id: "profile-current-password",
                          type: "password",
                          class: "form-control",
                          "onUpdate:modelValue": $event => ((passwordForm.current) = $event),
                          placeholder: t('password_placeholder') || '••••••••',
                          autocomplete: "current-password"
                        }, null, 8 /* PROPS */, _hoisted_52), [
                          [_vModelText, passwordForm.current]
                        ])
                      ])
                    ]),
                    _createElementVNode("div", _hoisted_53, [
                      _createElementVNode("label", _hoisted_54, _toDisplayString(t('new_password') || 'New Password'), 1 /* TEXT */),
                      _createElementVNode("div", _hoisted_55, [
                        _withDirectives(_createElementVNode("input", {
                          id: "profile-new-password",
                          type: "password",
                          class: _normalizeClass(["form-control", { 'is-invalid': passwordTouched && passwordInvalid }]),
                          "onUpdate:modelValue": $event => ((passwordForm.new) = $event),
                          placeholder: t('password_min_hint') || 'Min 12 chars',
                          autocomplete: "new-password"
                        }, null, 10 /* CLASS, PROPS */, _hoisted_56), [
                          [_vModelText, passwordForm.new]
                        ]),
                        _createElementVNode("div", _hoisted_57, [
                          _createElementVNode("div", _hoisted_58, [
                            _createElementVNode("div", {
                              class: "progress profile-strength-bar flex-grow-1",
                              role: "progressbar",
                              "aria-valuenow": passwordStrength.score,
                              "aria-valuemin": "0",
                              "aria-valuemax": "4"
                            }, [
                              _createElementVNode("div", {
                                class: _normalizeClass(["progress-bar", passwordStrength.widthClass + ' ' + passwordStrength.colorClass])
                              }, null, 2 /* CLASS */)
                            ], 8 /* PROPS */, _hoisted_59),
                            _createElementVNode("span", {
                              class: _normalizeClass(["small fw-semibold", passwordStrength.textClass])
                            }, _toDisplayString(passwordStrength.label), 3 /* TEXT, CLASS */)
                          ])
                        ]),
                        (passwordTouched && passwordInvalid)
                          ? (_openBlock(), _createElementBlock("div", _hoisted_60, _toDisplayString(passwordInvalidMessage), 1 /* TEXT */))
                          : _createCommentVNode("v-if", true),
                        _createElementVNode("ul", _hoisted_61, [
                          _createElementVNode("li", {
                            class: _normalizeClass(passwordChecks.length ? 'text-success' : 'text-muted')
                          }, _toDisplayString(t('password_rule_length') || 'At least 12 characters'), 3 /* TEXT, CLASS */),
                          _createElementVNode("li", {
                            class: _normalizeClass(passwordChecks.case ? 'text-success' : 'text-muted')
                          }, _toDisplayString(t('password_rule_case') || 'Uppercase and lowercase letters'), 3 /* TEXT, CLASS */),
                          _createElementVNode("li", {
                            class: _normalizeClass(passwordChecks.number ? 'text-success' : 'text-muted')
                          }, _toDisplayString(t('password_rule_number') || 'At least one number'), 3 /* TEXT, CLASS */),
                          _createElementVNode("li", {
                            class: _normalizeClass(passwordChecks.symbol ? 'text-success' : 'text-muted')
                          }, _toDisplayString(t('password_rule_symbol') || 'At least one symbol'), 3 /* TEXT, CLASS */)
                        ])
                      ])
                    ]),
                    _createElementVNode("div", _hoisted_62, [
                      _createElementVNode("label", _hoisted_63, _toDisplayString(t('confirm_new_password') || 'Confirm New Password'), 1 /* TEXT */),
                      _createElementVNode("div", _hoisted_64, [
                        _withDirectives(_createElementVNode("input", {
                          id: "profile-confirm-password",
                          type: "password",
                          class: _normalizeClass(["form-control", { 'is-invalid': passwordTouched && passwordMismatch }]),
                          "onUpdate:modelValue": $event => ((passwordForm.confirm) = $event),
                          placeholder: t('password_confirm_hint') || 'Re-enter new password',
                          autocomplete: "new-password"
                        }, null, 10 /* CLASS, PROPS */, _hoisted_65), [
                          [_vModelText, passwordForm.confirm]
                        ]),
                        (passwordTouched && passwordMismatch)
                          ? (_openBlock(), _createElementBlock("div", _hoisted_66, _toDisplayString(t('password_mismatch') || 'Passwords do not match'), 1 /* TEXT */))
                          : _createCommentVNode("v-if", true)
                      ])
                    ]),
                    _createElementVNode("div", _hoisted_67, [
                      _createElementVNode("div", _hoisted_68, [
                        _createElementVNode("button", {
                          class: "btn btn-primary btn-sm",
                          onClick: updatePassword,
                          disabled: passwordSaving || passwordInvalid
                        }, [
                          passwordSaving
                            ? (_openBlock(), _createElementBlock("span", _hoisted_70))
                            : _createCommentVNode("v-if", true),
                          _createTextVNode(" " + _toDisplayString(passwordSaving ? (t('updating') || 'Updating...') : (t('update') || 'Update')), 1 /* TEXT */)
                        ], 8 /* PROPS */, _hoisted_69)
                      ])
                    ]),
                    _createElementVNode("h6", _hoisted_71, _toDisplayString(t('two_factor_auth') || 'Two-Factor Authentication'), 1 /* TEXT */),
                    twoFaStepperVisible
                      ? (_openBlock(), _createElementBlock("div", _hoisted_72, [
                          _createElementVNode("div", {
                            class: _normalizeClass(["profile-step", twoFaStepClass(1)]),
                            role: "listitem"
                          }, [
                            _cache[2] || (_cache[2] = _createElementVNode("span", { class: "profile-step-index" }, "1", -1 /* CACHED */)),
                            _createElementVNode("span", _hoisted_73, _toDisplayString(t('twofa_step_scan') || 'Scan'), 1 /* TEXT */)
                          ], 2 /* CLASS */),
                          _createElementVNode("div", {
                            class: _normalizeClass(["profile-step", twoFaStepClass(2)]),
                            role: "listitem"
                          }, [
                            _cache[3] || (_cache[3] = _createElementVNode("span", { class: "profile-step-index" }, "2", -1 /* CACHED */)),
                            _createElementVNode("span", _hoisted_74, _toDisplayString(t('twofa_step_verify') || 'Verify'), 1 /* TEXT */)
                          ], 2 /* CLASS */),
                          _createElementVNode("div", {
                            class: _normalizeClass(["profile-step", twoFaStepClass(3)]),
                            role: "listitem"
                          }, [
                            _cache[4] || (_cache[4] = _createElementVNode("span", { class: "profile-step-index" }, "3", -1 /* CACHED */)),
                            _createElementVNode("span", _hoisted_75, _toDisplayString(t('twofa_step_recovery') || 'Recovery Codes'), 1 /* TEXT */)
                          ], 2 /* CLASS */)
                        ]))
                      : _createCommentVNode("v-if", true),
                    (twoFaMessage.text)
                      ? (_openBlock(), _createElementBlock("div", {
                          key: 2,
                          class: _normalizeClass(["alert", twoFaMessage.type === 'success' ? 'alert-success' : 'alert-danger']),
                          role: "alert"
                        }, _toDisplayString(twoFaMessage.text), 3 /* TEXT, CLASS */))
                      : _createCommentVNode("v-if", true),
                    (details['2fa_enabled'])
                      ? (_openBlock(), _createElementBlock("div", _hoisted_76, [
                          _createElementVNode("div", _hoisted_77, [
                            _cache[6] || (_cache[6] = _createElementVNode("i", { class: "ri-shield-check-line fs-4 me-3" }, null, -1 /* CACHED */)),
                            _createElementVNode("div", null, [
                              _createElementVNode("strong", null, _toDisplayString(t('2fa_active') || '2FA is currently enabled.'), 1 /* TEXT */),
                              _cache[5] || (_cache[5] = _createElementVNode("br", null, null, -1 /* CACHED */)),
                              _createElementVNode("span", _hoisted_78, _toDisplayString(t('2fa_enabled_note') || 'Your account is secured with Time-based One-Time Password.'), 1 /* TEXT */)
                            ]),
                            _createElementVNode("button", {
                              class: "btn btn-outline-danger btn-sm ms-auto",
                              onClick: openDisable2fa,
                              disabled: disable2faState.loading
                            }, [
                              (disable2faState.loading)
                                ? (_openBlock(), _createElementBlock("span", _hoisted_80))
                                : _createCommentVNode("v-if", true),
                              _createTextVNode(" " + _toDisplayString(t('disable') || 'Disable'), 1 /* TEXT */)
                            ], 8 /* PROPS */, _hoisted_79)
                          ]),
                          (disable2faState.open)
                            ? (_openBlock(), _createElementBlock("div", _hoisted_81, [
                                _createElementVNode("div", _hoisted_82, [
                                  _createElementVNode("h6", _hoisted_83, _toDisplayString(t('disable_2fa_title') || 'Disable two-factor authentication'), 1 /* TEXT */),
                                  _createElementVNode("p", _hoisted_84, _toDisplayString(t('disable_2fa_desc') || 'Re-authenticate with your password or a current authenticator code to continue.'), 1 /* TEXT */),
                                  _createElementVNode("div", _hoisted_85, [
                                    _createElementVNode("div", _hoisted_86, [
                                      _createElementVNode("label", _hoisted_87, _toDisplayString(t('current_password') || 'Current Password'), 1 /* TEXT */),
                                      _withDirectives(_createElementVNode("input", {
                                        id: "disable-2fa-password",
                                        type: "password",
                                        class: "form-control form-control-sm",
                                        "onUpdate:modelValue": $event => ((disable2faState.password) = $event),
                                        autocomplete: "current-password"
                                      }, null, 8 /* PROPS */, _hoisted_88), [
                                        [_vModelText, disable2faState.password]
                                      ])
                                    ]),
                                    _createElementVNode("div", _hoisted_89, [
                                      _createElementVNode("label", _hoisted_90, _toDisplayString(t('authenticator_code') || 'Authenticator Code'), 1 /* TEXT */),
                                      _withDirectives(_createElementVNode("input", {
                                        id: "disable-2fa-code",
                                        type: "text",
                                        inputmode: "numeric",
                                        class: "form-control form-control-sm",
                                        "onUpdate:modelValue": $event => ((disable2faState.code) = $event),
                                        placeholder: t('authenticator_code_hint') || '123456',
                                        autocomplete: "one-time-code"
                                      }, null, 8 /* PROPS */, _hoisted_91), [
                                        [_vModelText, disable2faState.code]
                                      ])
                                    ])
                                  ]),
                                  _createElementVNode("div", _hoisted_92, [
                                    _createElementVNode("button", {
                                      type: "button",
                                      class: "btn btn-link btn-sm text-muted",
                                      onClick: closeDisable2fa,
                                      disabled: disable2faState.loading
                                    }, _toDisplayString(t('cancel') || 'Cancel'), 9 /* TEXT, PROPS */, _hoisted_93),
                                    _createElementVNode("button", {
                                      type: "button",
                                      class: "btn btn-danger btn-sm",
                                      onClick: submitDisable2fa,
                                      disabled: disable2faState.loading || !canSubmitDisable2fa
                                    }, [
                                      (disable2faState.loading)
                                        ? (_openBlock(), _createElementBlock("span", _hoisted_95))
                                        : _createCommentVNode("v-if", true),
                                      _createTextVNode(" " + _toDisplayString(t('disable_2fa_confirm') || 'Disable 2FA'), 1 /* TEXT */)
                                    ], 8 /* PROPS */, _hoisted_94)
                                  ])
                                ])
                              ]))
                            : _createCommentVNode("v-if", true)
                        ]))
                      : (_openBlock(), _createElementBlock("div", _hoisted_96, [
                          _createElementVNode("p", _hoisted_97, _toDisplayString(t('2fa_desc') || 'Protect your account by requiring a code from your mobile device when logging in.'), 1 /* TEXT */),
                          (!setup.step)
                            ? (_openBlock(), _createElementBlock("button", {
                                key: 0,
                                class: "btn btn-primary btn-sm",
                                onClick: start2faSetup,
                                disabled: twoFaLoading
                              }, [
                                twoFaLoading
                                  ? (_openBlock(), _createElementBlock("span", _hoisted_99))
                                  : _createCommentVNode("v-if", true),
                                _createTextVNode(" " + _toDisplayString(t('enable_2fa') || 'Enable 2FA'), 1 /* TEXT */)
                              ], 8 /* PROPS */, _hoisted_98))
                            : _createCommentVNode("v-if", true),
                          (setup.step === 1)
                            ? (_openBlock(), _createElementBlock("div", _hoisted_100, [
                                _createElementVNode("div", _hoisted_101, [
                                  _createElementVNode("h6", _hoisted_102, _toDisplayString(t('scan_qr') || 'Scan QR Code'), 1 /* TEXT */),
                                  _createElementVNode("p", _hoisted_103, _toDisplayString(t('scan_qr_hint') || 'Use your authenticator app to scan the QR code, then enter the 6-digit code.'), 1 /* TEXT */),
                                  _createElementVNode("div", _hoisted_104, [
                                    _createElementVNode("img", {
                                      src: setup.qr,
                                      class: "qr-image",
                                      alt: t('scan_qr_alt') || 'Two-factor authentication QR code'
                                    }, null, 8 /* PROPS */, _hoisted_105)
                                  ]),
                                  _createElementVNode("div", _hoisted_106, [
                                    _createElementVNode("div", _hoisted_107, _toDisplayString(t('manual_entry_code') || 'Manual entry code'), 1 /* TEXT */),
                                    _createElementVNode("div", _hoisted_108, _toDisplayString(setup.secret), 1 /* TEXT */),
                                    _createElementVNode("button", {
                                      type: "button",
                                      class: "btn btn-outline-secondary btn-sm mt-2",
                                      onClick: copySecret,
                                      disabled: twoFaLoading
                                    }, _toDisplayString(t('copy_code') || 'Copy code'), 9 /* TEXT, PROPS */, _hoisted_109)
                                  ]),
                                  _createElementVNode("div", _hoisted_110, [
                                    _withDirectives(_createElementVNode("input", {
                                      type: "text",
                                      class: "form-control",
                                      placeholder: t('authenticator_code_hint') || '123456',
                                      "onUpdate:modelValue": $event => ((setup.code) = $event),
                                      inputmode: "numeric",
                                      autocomplete: "one-time-code"
                                    }, null, 8 /* PROPS */, _hoisted_111), [
                                      [_vModelText, setup.code]
                                    ]),
                                    _createElementVNode("button", {
                                      class: "btn btn-success",
                                      onClick: finish2faSetup,
                                      disabled: twoFaLoading || !setup.code
                                    }, [
                                      twoFaLoading
                                        ? (_openBlock(), _createElementBlock("span", _hoisted_113))
                                        : _createCommentVNode("v-if", true),
                                      _createTextVNode(" " + _toDisplayString(t('verify') || 'Verify'), 1 /* TEXT */)
                                    ], 8 /* PROPS */, _hoisted_112)
                                  ]),
                                  _createElementVNode("button", {
                                    class: "btn btn-link btn-sm text-muted",
                                    onClick: cancel2faSetup,
                                    disabled: twoFaLoading
                                  }, _toDisplayString(t('cancel') || 'Cancel'), 9 /* TEXT, PROPS */, _hoisted_114)
                                ])
                              ]))
                            : _createCommentVNode("v-if", true)
                        ])),
                    (setup.recoveryCodes.length)
                      ? (_openBlock(), _createElementBlock("div", _hoisted_115, [
                          _createElementVNode("div", _hoisted_116, [
                            _createElementVNode("h6", null, _toDisplayString(t('recovery_codes') || 'Recovery Codes'), 1 /* TEXT */),
                            _createElementVNode("p", _hoisted_117, _toDisplayString(t('recovery_codes_desc') || 'Save these codes in a safe place. You can use them if you lose access to your device.'), 1 /* TEXT */),
                            _createElementVNode("p", _hoisted_118, _toDisplayString(t('recovery_codes_once') || 'These codes will only be shown once.'), 1 /* TEXT */),
                            _createElementVNode("div", _hoisted_119, [
                              (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(setup.recoveryCodes, (code) => {
                                return (_openBlock(), _createElementBlock("div", { key: code }, _toDisplayString(code), 1 /* TEXT */))
                              }), 128 /* KEYED_FRAGMENT */))
                            ]),
                            _createElementVNode("div", _hoisted_120, [
                              _createElementVNode("button", {
                                type: "button",
                                class: "btn btn-outline-secondary btn-sm",
                                onClick: copyRecoveryCodes
                              }, _toDisplayString(t('copy_recovery_codes') || 'Copy codes'), 9 /* TEXT, PROPS */, _hoisted_121),
                              _createElementVNode("button", {
                                type: "button",
                                class: "btn btn-outline-secondary btn-sm",
                                onClick: downloadRecoveryCodes
                              }, _toDisplayString(t('download_recovery_codes') || 'Download codes'), 9 /* TEXT, PROPS */, _hoisted_122),
                              _createElementVNode("button", {
                                type: "button",
                                class: "btn btn-primary btn-sm",
                                onClick: completeRecoveryCodes
                              }, _toDisplayString(t('done') || 'Done'), 9 /* TEXT, PROPS */, _hoisted_123)
                            ])
                          ])
                        ]))
                      : _createCommentVNode("v-if", true),
                    _createElementVNode("h6", _hoisted_124, _toDisplayString(t('webdav_credentials') || 'WebDAV App Passwords'), 1 /* TEXT */),
                    _createElementVNode("p", _hoisted_125, _toDisplayString(t('webdav_credentials_desc') || 'Use a separate, revocable password for WebDAV clients. The secret is shown only once.'), 1 /* TEXT */),
                    (webDavMessage.text)
                      ? (_openBlock(), _createElementBlock("div", {
                          key: 6,
                          class: _normalizeClass(["alert", webDavMessage.type === 'success' ? 'alert-success' : 'alert-danger']),
                          role: "alert"
                        }, _toDisplayString(webDavMessage.text), 3 /* TEXT, CLASS */))
                      : _createCommentVNode("v-if", true),
                    webDavSecret
                      ? (_openBlock(), _createElementBlock("div", _hoisted_126, [
                          _createElementVNode("strong", _hoisted_127, _toDisplayString(t('webdav_secret_once') || 'Copy this password now. It will not be shown again.'), 1 /* TEXT */),
                          _createElementVNode("code", _hoisted_128, _toDisplayString(webDavSecret), 1 /* TEXT */),
                          _createElementVNode("button", {
                            type: "button",
                            class: "btn btn-outline-secondary btn-sm d-block mt-2",
                            onClick: copyWebDavSecret
                          }, _toDisplayString(t('copy_code') || 'Copy'), 9 /* TEXT, PROPS */, _hoisted_129)
                        ]))
                      : _createCommentVNode("v-if", true),
                    _createElementVNode("div", _hoisted_130, [
                      _withDirectives(_createElementVNode("input", {
                        type: "text",
                        class: "form-control",
                        maxlength: "64",
                        "onUpdate:modelValue": $event => ((webDavLabel) = $event),
                        placeholder: t('webdav_label_placeholder') || 'Device name'
                      }, null, 8 /* PROPS */, _hoisted_131), [
                        [_vModelText, webDavLabel]
                      ]),
                      _createElementVNode("button", {
                        type: "button",
                        class: "btn btn-primary",
                        onClick: createWebDavCredential,
                        disabled: webDavLoading || !webDavLabel.trim()
                      }, [
                        webDavLoading
                          ? (_openBlock(), _createElementBlock("span", _hoisted_133))
                          : _createCommentVNode("v-if", true),
                        _createTextVNode(" " + _toDisplayString(t('webdav_create') || 'Create app password'), 1 /* TEXT */)
                      ], 8 /* PROPS */, _hoisted_132)
                    ]),
                    (webDavCredentials.length)
                      ? (_openBlock(), _createElementBlock("div", _hoisted_134, [
                          (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(webDavCredentials, (credential) => {
                            return (_openBlock(), _createElementBlock("div", {
                              key: credential.id,
                              class: "list-group-item d-flex justify-content-between align-items-center"
                            }, [
                              _createElementVNode("div", null, [
                                _createElementVNode("strong", null, _toDisplayString(credential.label), 1 /* TEXT */),
                                _createElementVNode("div", _hoisted_135, _toDisplayString(formatCredentialDate(credential.created_at)), 1 /* TEXT */)
                              ]),
                              _createElementVNode("button", {
                                type: "button",
                                class: "btn btn-outline-danger btn-sm",
                                onClick: $event => (deleteWebDavCredential(credential.id)),
                                disabled: webDavLoading
                              }, _toDisplayString(t('revoke') || 'Revoke'), 9 /* TEXT, PROPS */, _hoisted_136)
                            ]))
                          }), 128 /* KEYED_FRAGMENT */))
                        ]))
                      : (!webDavLoading)
                        ? (_openBlock(), _createElementBlock("div", _hoisted_137, _toDisplayString(t('webdav_empty') || 'No WebDAV app passwords.'), 1 /* TEXT */))
                        : _createCommentVNode("v-if", true)
                  ], 64 /* STABLE_FRAGMENT */))
            ], 512 /* NEED_PATCH */), [
              [_vShow, activeTab === 'security']
            ]),
            _withDirectives(_createElementVNode("div", _hoisted_138, [
              _createElementVNode("h6", _hoisted_139, _toDisplayString(t('manage_mounts') || 'Manage Virtual Mounts'), 1 /* TEXT */),
              _createElementVNode("p", _hoisted_140, _toDisplayString(t('mounts_desc') || 'Mount external directories as folders in your root view.'), 1 /* TEXT */),
              (mountMessage.text)
                ? (_openBlock(), _createElementBlock("div", {
                    key: 0,
                    class: _normalizeClass(["alert", mountMessage.type === 'success' ? 'alert-success' : 'alert-danger']),
                    role: "alert"
                  }, _toDisplayString(mountMessage.text), 3 /* TEXT, CLASS */))
                : _createCommentVNode("v-if", true),
              mountsLoading
                ? (_openBlock(), _createElementBlock("div", _hoisted_141, [
                    _createElementVNode("div", {
                      class: "spinner-border spinner-border-sm",
                      role: "status",
                      "aria-label": t('loading') || 'Loading'
                    }, null, 8 /* PROPS */, _hoisted_142)
                  ]))
                : (_openBlock(), _createElementBlock("div", _hoisted_143, [
                    (mounts.length === 0)
                      ? (_openBlock(), _createElementBlock("div", _hoisted_144, _toDisplayString(t('no_mounts') || 'No mounts defined.'), 1 /* TEXT */))
                      : (_openBlock(), _createElementBlock("div", _hoisted_145, [
                          (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(mounts, (mount) => {
                            return (_openBlock(), _createElementBlock("div", {
                              key: mount.id,
                              class: "list-group-item d-flex justify-content-between align-items-center"
                            }, [
                              _createElementVNode("div", _hoisted_146, [
                                _createElementVNode("div", _hoisted_147, [
                                  _createTextVNode(_toDisplayString(mount.name) + " ", 1 /* TEXT */),
                                  _createElementVNode("span", _hoisted_148, _toDisplayString(mountTypeLabel(mount.type)), 1 /* TEXT */)
                                ]),
                                _createElementVNode("div", _hoisted_149, _toDisplayString(mountSummary(mount)), 1 /* TEXT */)
                              ]),
                              _createElementVNode("div", _hoisted_150, [
                                _createElementVNode("button", {
                                  class: "btn btn-outline-secondary btn-sm",
                                  onClick: $event => (startEditMount(mount)),
                                  disabled: mountSaving || mountsLoading
                                }, [...(_cache[7] || (_cache[7] = [
                                  _createElementVNode("i", { class: "ri-edit-line" }, null, -1 /* CACHED */)
                                ]))], 8 /* PROPS */, _hoisted_151),
                                _createElementVNode("button", {
                                  class: "btn btn-outline-danger btn-sm",
                                  onClick: $event => (removeMount(mount.id)),
                                  disabled: mountRemovingId === mount.id
                                }, [
                                  (mountRemovingId === mount.id)
                                    ? (_openBlock(), _createElementBlock("span", _hoisted_153))
                                    : (_openBlock(), _createElementBlock("i", _hoisted_154))
                                ], 8 /* PROPS */, _hoisted_152)
                              ])
                            ]))
                          }), 128 /* KEYED_FRAGMENT */))
                        ])),
                    _createElementVNode("div", _hoisted_155, [
                      _createElementVNode("div", _hoisted_156, [
                        _createElementVNode("div", _hoisted_157, [
                          _createElementVNode("h6", _hoisted_158, _toDisplayString(mountMode === 'edit' ? (t('edit_mount') || 'Edit Mount') : (t('add_mount') || 'Add New Mount')), 1 /* TEXT */),
                          (mountMode === 'edit')
                            ? (_openBlock(), _createElementBlock("button", {
                                key: 0,
                                type: "button",
                                class: "btn btn-link btn-sm text-muted",
                                onClick: cancelEditMount,
                                disabled: mountSaving
                              }, _toDisplayString(t('cancel_edit') || 'Cancel edit'), 9 /* TEXT, PROPS */, _hoisted_159))
                            : _createCommentVNode("v-if", true)
                        ]),
                        (mountTestResult.text)
                          ? (_openBlock(), _createElementBlock("div", {
                              key: 0,
                              class: _normalizeClass(["alert py-2", mountTestResult.type === 'success' ? 'alert-success' : 'alert-danger']),
                              role: "status",
                              "aria-live": "polite"
                            }, _toDisplayString(mountTestResult.text), 3 /* TEXT, CLASS */))
                          : _createCommentVNode("v-if", true),
                        _createElementVNode("div", _hoisted_160, [
                          _createElementVNode("div", _hoisted_161, [
                            _createElementVNode("label", _hoisted_162, _toDisplayString(t('mount_name') || 'Name (Folder Name)'), 1 /* TEXT */),
                            _withDirectives(_createElementVNode("input", {
                              id: "mount-name",
                              type: "text",
                              class: _normalizeClass(["form-control form-control-sm", mountErrors.name ? 'is-invalid' : '']),
                              "onUpdate:modelValue": $event => ((mountForm.name) = $event),
                              placeholder: t('mount_name_placeholder') || 'e.g. Projects'
                            }, null, 10 /* CLASS, PROPS */, _hoisted_163), [
                              [_vModelText, mountForm.name]
                            ]),
                            (mountErrors.name)
                              ? (_openBlock(), _createElementBlock("div", _hoisted_164, _toDisplayString(mountErrors.name), 1 /* TEXT */))
                              : _createCommentVNode("v-if", true)
                          ]),
                          _createElementVNode("div", _hoisted_165, [
                            _createElementVNode("label", _hoisted_166, _toDisplayString(t('mount_type') || 'Type'), 1 /* TEXT */),
                            _withDirectives(_createElementVNode("select", {
                              id: "mount-type",
                              class: "form-select form-select-sm",
                              "onUpdate:modelValue": $event => ((mountForm.type) = $event),
                              disabled: mountSaving
                            }, [
                              _createElementVNode("option", _hoisted_168, _toDisplayString(t('mount_type_local') || 'Local'), 1 /* TEXT */),
                              _createElementVNode("option", _hoisted_169, _toDisplayString(t('mount_type_ftp') || 'FTP'), 1 /* TEXT */),
                              _createElementVNode("option", _hoisted_170, _toDisplayString(t('mount_type_ftps') || 'FTPS'), 1 /* TEXT */),
                              _createElementVNode("option", _hoisted_171, _toDisplayString(t('mount_type_sftp') || 'SFTP'), 1 /* TEXT */)
                            ], 8 /* PROPS */, _hoisted_167), [
                              [_vModelSelect, mountForm.type]
                            ])
                          ]),
                          (mountForm.type === 'local')
                            ? (_openBlock(), _createElementBlock("div", _hoisted_172, [
                                _createElementVNode("label", _hoisted_173, _toDisplayString(t('mount_path') || 'Path (Local Server Path)'), 1 /* TEXT */),
                                _withDirectives(_createElementVNode("input", {
                                  id: "mount-path",
                                  type: "text",
                                  class: _normalizeClass(["form-control form-control-sm", mountErrors.path ? 'is-invalid' : '']),
                                  "onUpdate:modelValue": $event => ((mountForm.config.path) = $event),
                                  placeholder: t('mount_path_placeholder') || '/absolute/path/on/server'
                                }, null, 10 /* CLASS, PROPS */, _hoisted_174), [
                                  [_vModelText, mountForm.config.path]
                                ]),
                                (mountErrors.path)
                                  ? (_openBlock(), _createElementBlock("div", _hoisted_175, _toDisplayString(mountErrors.path), 1 /* TEXT */))
                                  : _createCommentVNode("v-if", true)
                              ]))
                            : _createCommentVNode("v-if", true)
                        ]),
                        (mountForm.type !== 'local')
                          ? (_openBlock(), _createElementBlock("div", _hoisted_176, [
                              _createElementVNode("div", _hoisted_177, [
                                _createElementVNode("div", _hoisted_178, [
                                  _createElementVNode("label", _hoisted_179, _toDisplayString(t('mount_host') || 'Host'), 1 /* TEXT */),
                                  _withDirectives(_createElementVNode("input", {
                                    id: "mount-host",
                                    type: "text",
                                    class: _normalizeClass(["form-control form-control-sm", mountErrors.host ? 'is-invalid' : '']),
                                    "onUpdate:modelValue": $event => ((mountForm.config.host) = $event),
                                    placeholder: t('mount_host_placeholder') || 'example.com'
                                  }, null, 10 /* CLASS, PROPS */, _hoisted_180), [
                                    [_vModelText, mountForm.config.host]
                                  ]),
                                  (mountErrors.host)
                                    ? (_openBlock(), _createElementBlock("div", _hoisted_181, _toDisplayString(mountErrors.host), 1 /* TEXT */))
                                    : _createCommentVNode("v-if", true)
                                ]),
                                _createElementVNode("div", _hoisted_182, [
                                  _createElementVNode("label", _hoisted_183, _toDisplayString(t('mount_port') || 'Port'), 1 /* TEXT */),
                                  _withDirectives(_createElementVNode("input", {
                                    id: "mount-port",
                                    type: "number",
                                    class: "form-control form-control-sm",
                                    "onUpdate:modelValue": $event => ((mountForm.config.port) = $event),
                                    min: "1",
                                    max: "65535"
                                  }, null, 8 /* PROPS */, _hoisted_184), [
                                    [
                                      _vModelText,
                                      mountForm.config.port,
                                      void 0,
                                      { number: true }
                                    ]
                                  ])
                                ]),
                                _createElementVNode("div", _hoisted_185, [
                                  _createElementVNode("label", _hoisted_186, _toDisplayString(t('mount_root') || 'Root Path'), 1 /* TEXT */),
                                  _withDirectives(_createElementVNode("input", {
                                    id: "mount-root",
                                    type: "text",
                                    class: "form-control form-control-sm",
                                    "onUpdate:modelValue": $event => ((mountForm.config.root) = $event),
                                    placeholder: t('mount_root_placeholder') || '/'
                                  }, null, 8 /* PROPS */, _hoisted_187), [
                                    [_vModelText, mountForm.config.root]
                                  ])
                                ])
                              ]),
                              (mountForm.type === 'sftp')
                                ? (_openBlock(), _createElementBlock("div", _hoisted_188, [
                                    _createElementVNode("div", _hoisted_189, [
                                      _createElementVNode("label", _hoisted_190, _toDisplayString(t('mount_host_key_fingerprint') || 'SFTP host-key fingerprint'), 1 /* TEXT */),
                                      _withDirectives(_createElementVNode("input", {
                                        id: "mount-host-key-fingerprint",
                                        type: "text",
                                        class: "form-control form-control-sm",
                                        "onUpdate:modelValue": $event => ((mountForm.config.host_key_fingerprint) = $event),
                                        placeholder: "SHA256:...",
                                        autocomplete: "off",
                                        spellcheck: "false"
                                      }, null, 8 /* PROPS */, _hoisted_191), [
                                        [_vModelText, mountForm.config.host_key_fingerprint]
                                      ]),
                                      _createElementVNode("div", _hoisted_192, _toDisplayString(t('mount_host_key_fingerprint_hint') || 'Required in strict remote security mode.'), 1 /* TEXT */)
                                    ])
                                  ]))
                                : _createCommentVNode("v-if", true),
                              (mountForm.type === 'ftps')
                                ? (_openBlock(), _createElementBlock("div", _hoisted_193, [
                                    _createElementVNode("div", _hoisted_194, [
                                      _createElementVNode("label", _hoisted_195, _toDisplayString(t('mount_tls_spki_pin') || 'FTPS certificate SHA-256 pin (optional)'), 1 /* TEXT */),
                                      _withDirectives(_createElementVNode("input", {
                                        id: "mount-tls-spki-pin",
                                        type: "text",
                                        class: "form-control form-control-sm",
                                        "onUpdate:modelValue": $event => ((mountForm.config.tls_spki_pin) = $event),
                                        placeholder: "AA:BB:...",
                                        autocomplete: "off",
                                        spellcheck: "false"
                                      }, null, 8 /* PROPS */, _hoisted_196), [
                                        [_vModelText, mountForm.config.tls_spki_pin]
                                      ]),
                                      _createElementVNode("div", _hoisted_197, _toDisplayString(t('mount_tls_spki_pin_hint') || 'The system CA store is always checked in strict mode.'), 1 /* TEXT */)
                                    ])
                                  ]))
                                : _createCommentVNode("v-if", true),
                              (mountForm.type === 'sftp')
                                ? (_openBlock(), _createElementBlock("div", _hoisted_198, [
                                    _createElementVNode("div", _hoisted_199, [
                                      _createElementVNode("label", _hoisted_200, _toDisplayString(t('mount_auth_method') || 'Authentication'), 1 /* TEXT */),
                                      _withDirectives(_createElementVNode("select", {
                                        id: "mount-auth-method",
                                        class: "form-select form-select-sm",
                                        "onUpdate:modelValue": $event => ((mountForm.config.auth_method) = $event)
                                      }, [
                                        _createElementVNode("option", _hoisted_202, _toDisplayString(t('mount_auth_password') || 'Password'), 1 /* TEXT */),
                                        _createElementVNode("option", _hoisted_203, _toDisplayString(t('mount_auth_private_key') || 'Public key'), 1 /* TEXT */)
                                      ], 8 /* PROPS */, _hoisted_201), [
                                        [_vModelSelect, mountForm.config.auth_method]
                                      ])
                                    ])
                                  ]))
                                : _createCommentVNode("v-if", true),
                              (mountForm.type === 'sftp' && mountForm.config.auth_method === 'private_key')
                                ? (_openBlock(), _createElementBlock("div", _hoisted_204, [
                                    _createElementVNode("div", _hoisted_205, [
                                      _createElementVNode("label", _hoisted_206, _toDisplayString(t('mount_private_key') || 'Private key'), 1 /* TEXT */),
                                      _withDirectives(_createElementVNode("textarea", {
                                        id: "mount-private-key",
                                        class: "form-control form-control-sm font-monospace",
                                        rows: "4",
                                        "onUpdate:modelValue": $event => ((mountForm.config.private_key) = $event),
                                        placeholder: mountForm.hasPrivateKey ? (t('mount_private_key_keep') || 'Leave blank to keep current key') : '-----BEGIN OPENSSH PRIVATE KEY-----'
                                      }, null, 8 /* PROPS */, _hoisted_207), [
                                        [_vModelText, mountForm.config.private_key]
                                      ])
                                    ]),
                                    _createElementVNode("div", _hoisted_208, [
                                      _createElementVNode("label", _hoisted_209, _toDisplayString(t('mount_public_key') || 'Public key'), 1 /* TEXT */),
                                      _withDirectives(_createElementVNode("textarea", {
                                        id: "mount-public-key",
                                        class: "form-control form-control-sm font-monospace",
                                        rows: "4",
                                        "onUpdate:modelValue": $event => ((mountForm.config.public_key) = $event),
                                        placeholder: mountForm.hasPublicKey ? (t('mount_public_key_keep') || 'Leave blank to keep current key') : 'ssh-ed25519 AAAA...'
                                      }, null, 8 /* PROPS */, _hoisted_210), [
                                        [_vModelText, mountForm.config.public_key]
                                      ])
                                    ]),
                                    _createElementVNode("div", _hoisted_211, [
                                      _createElementVNode("label", _hoisted_212, _toDisplayString(t('mount_private_key_passphrase') || 'Private-key passphrase'), 1 /* TEXT */),
                                      _withDirectives(_createElementVNode("input", {
                                        id: "mount-private-key-passphrase",
                                        type: "password",
                                        class: "form-control form-control-sm",
                                        "onUpdate:modelValue": $event => ((mountForm.config.private_key_passphrase) = $event),
                                        autocomplete: "new-password"
                                      }, null, 8 /* PROPS */, _hoisted_213), [
                                        [_vModelText, mountForm.config.private_key_passphrase]
                                      ])
                                    ])
                                  ]))
                                : _createCommentVNode("v-if", true),
                              _createElementVNode("div", _hoisted_214, [
                                _createElementVNode("div", _hoisted_215, [
                                  _createElementVNode("label", _hoisted_216, _toDisplayString(t('mount_user') || 'Username'), 1 /* TEXT */),
                                  _withDirectives(_createElementVNode("input", {
                                    id: "mount-user",
                                    type: "text",
                                    class: _normalizeClass(["form-control form-control-sm", mountErrors.user ? 'is-invalid' : '']),
                                    "onUpdate:modelValue": $event => ((mountForm.config.user) = $event),
                                    placeholder: t('mount_user_placeholder') || 'user'
                                  }, null, 10 /* CLASS, PROPS */, _hoisted_217), [
                                    [_vModelText, mountForm.config.user]
                                  ]),
                                  (mountErrors.user)
                                    ? (_openBlock(), _createElementBlock("div", _hoisted_218, _toDisplayString(mountErrors.user), 1 /* TEXT */))
                                    : _createCommentVNode("v-if", true)
                                ]),
                                _createElementVNode("div", _hoisted_219, [
                                  _createElementVNode("label", _hoisted_220, _toDisplayString(t('mount_pass') || 'Password'), 1 /* TEXT */),
                                  _withDirectives(_createElementVNode("input", {
                                    id: "mount-pass",
                                    type: "password",
                                    class: _normalizeClass(["form-control form-control-sm", mountErrors.pass ? 'is-invalid' : '']),
                                    "onUpdate:modelValue": $event => ((mountForm.config.pass) = $event),
                                    placeholder: mountForm.hasStoredPass ? (t('mount_pass_keep') || 'Leave blank to keep current password') : (t('password_placeholder') || '••••••••'),
                                    autocomplete: "new-password",
                                    disabled: mountForm.type === 'sftp' && mountForm.config.auth_method === 'private_key'
                                  }, null, 10 /* CLASS, PROPS */, _hoisted_221), [
                                    [_vModelText, mountForm.config.pass]
                                  ]),
                                  (mountErrors.pass)
                                    ? (_openBlock(), _createElementBlock("div", _hoisted_222, _toDisplayString(mountErrors.pass), 1 /* TEXT */))
                                    : (mountForm.hasStoredPass)
                                      ? (_openBlock(), _createElementBlock("div", _hoisted_223, _toDisplayString(t('mount_pass_keep_hint') || 'Leave the password empty to keep the existing one.'), 1 /* TEXT */))
                                      : _createCommentVNode("v-if", true)
                                ])
                              ])
                            ]))
                          : _createCommentVNode("v-if", true),
                        _createElementVNode("div", _hoisted_224, [
                          _createElementVNode("button", {
                            type: "button",
                            class: "btn btn-outline-secondary btn-sm",
                            onClick: testMount,
                            disabled: mountTesting || mountSaving || !canTestMount
                          }, [
                            mountTesting
                              ? (_openBlock(), _createElementBlock("span", _hoisted_226))
                              : _createCommentVNode("v-if", true),
                            _createTextVNode(" " + _toDisplayString(t('test_connection') || 'Test connection'), 1 /* TEXT */)
                          ], 8 /* PROPS */, _hoisted_225),
                          _createElementVNode("button", {
                            type: "button",
                            class: "btn btn-primary btn-sm",
                            onClick: saveMount,
                            disabled: mountSaving || !canSubmitMount
                          }, [
                            mountSaving
                              ? (_openBlock(), _createElementBlock("span", _hoisted_228))
                              : _createCommentVNode("v-if", true),
                            _createTextVNode(" " + _toDisplayString(mountMode === 'edit' ? (t('mount_save') || 'Save Mount') : (t('mount_add') || 'Add Mount')), 1 /* TEXT */)
                          ], 8 /* PROPS */, _hoisted_227)
                        ])
                      ])
                    ])
                  ]))
            ], 512 /* NEED_PATCH */), [
              [_vShow, activeTab === 'mounts']
            ])
          ])
        ])
      ])
    ], 8 /* PROPS */, _hoisted_1))
  }
}
})(Vue),
    setup() {
        const { ref, reactive, computed, onMounted, watch, nextTick } = Vue;
        const details = ref({});
        const loading = ref(false);
        const activeTab = ref('general');

        const availableLocales = computed(() => i18n.availableLocaleOptions || []);
        const selectedLocale = ref(i18n.locale || (typeof i18n.preferredLocale === 'function' ? i18n.preferredLocale() : 'en'));

        const forcePasswordChange = ref(!!window.forcePasswordChange);
        const profileTabKey = 'extplorer_profile_tab';
        let modalInstance = null;
        let modalEl = null;
        let lastActiveElement = null;

        const canMount = computed(() => {
            const perms = window.userPermissions || [];
            return perms.includes('*') || perms.includes('mount_external');
        });

        const validTabs = computed(() => (canMount.value ? ['general', 'security', 'mounts'] : ['general', 'security']));
        const normalizeTab = (tab) => (validTabs.value.includes(tab) ? tab : 'general');
        const readStoredTab = () => {
            try {
                const stored = localStorage.getItem(profileTabKey);
                if (stored) {
                    return normalizeTab(stored);
                }
            } catch (e) {
                // Ignore storage failures
            }
            return 'general';
        };
        const persistTab = (tab) => {
            try {
                localStorage.setItem(profileTabKey, tab);
            } catch (e) {
                // Ignore storage failures
            }
        };

        const loadDetails = async () => {
            loading.value = true;
            try {
                details.value = await Api.get('profile/details');
            } catch (e) {
                console.error(e);
            } finally {
                loading.value = false;
            }
        };

        const setTab = async (tab, options = {}) => {
            const { persist = true } = options;
            const nextTab = normalizeTab(tab);
            activeTab.value = nextTab;
            if (persist && !forcePasswordChange.value) {
                persistTab(nextTab);
            }
            if (nextTab === 'mounts') {
                resetMountMessages();
                await loadMounts();
            } else if (nextTab === 'security' && !window.externalAuthentication) {
                await loadWebDavCredentials();
            }
        };

        const copyText = async (text, successMessage) => {
            try {
                await navigator.clipboard.writeText(text);
                Swal.fire({ icon: 'success', title: successMessage, timer: 1400, showConfirmButton: false });
            } catch (e) {
                Swal.fire(i18n.t('error') || 'Error', e.message || (t('copy_failed') || 'Copy failed'), 'error');
            }
        };

        const updateLocale = async () => {
            const nextLocale = selectedLocale.value || 'en';
            await i18n.setLocale(nextLocale);
            selectedLocale.value = i18n.locale;
            Swal.fire({
                icon: 'success',
                title: t('language_updated') || 'Language updated',
                timer: 1200,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        };

        watch(() => i18n.locale, (val) => {
            if (val && selectedLocale.value !== val) {
                selectedLocale.value = val;
            }
        });

        watch(canMount, (allowed) => {
            if (!allowed && activeTab.value === 'mounts') {
                setTab('general', { persist: false });
            }
        });

        const { mounts, mountsLoading, mountSaving, mountTesting, mountRemovingId, mountMode, editingMountId, mountMessage, mountTestResult, mountErrors, mountForm, clearMountErrors, resetMountMessages, resetMountForm, setMountMessage, loadMounts, validateMountForm, canSubmitMount, canTestMount, startEditMount, cancelEditMount, saveMount, testMount, removeMount, mountSummary, mountTypeLabel } = useProfileMounts(t);
        const { webDavCredentials, webDavLabel, webDavSecret, webDavLoading, webDavMessage, loadWebDavCredentials, createWebDavCredential, deleteWebDavCredential, copyWebDavSecret, formatCredentialDate } = useProfileWebDav(t, copyText);
        const { passwordForm, passwordSaving, passwordMessage, twoFaMessage, twoFaLoading, setup, disable2faState, passwordChecks, passwordScore, passwordStrength, passwordTouched, passwordMismatch, passwordInvalidMessage, passwordInvalid, twoFaStepperVisible, twoFaStepClass, canSubmitDisable2fa, setTwoFaMessage, setPasswordMessage, updatePassword, start2faSetup, cancel2faSetup, finish2faSetup, openDisable2fa, closeDisable2fa, submitDisable2fa, copySecret, copyRecoveryCodes, downloadRecoveryCodes, completeRecoveryCodes } = useProfileSecurity(t, details, forcePasswordChange, loadWebDavCredentials, copyText);

        const open = async (tab = null) => {
            lastActiveElement = document.activeElement;
            await loadDetails();
            if (forcePasswordChange.value) {
                await setTab('security', { persist: false });
            } else if (tab) {
                await setTab(tab);
            } else {
                await setTab(readStoredTab(), { persist: false });
            }
            if (!modalInstance) {
                modalInstance = new bootstrap.Modal(modalEl, {
                    backdrop: 'static',
                    keyboard: !forcePasswordChange.value,
                });
            }
            modalInstance.show();
            await nextTick();
            const panelId = activeTab.value === 'general' ? 'profile-panel-general' : activeTab.value === 'security' ? 'profile-panel-security' : 'profile-panel-mounts';
            const panel = document.getElementById(panelId);
            panel?.focus();
        };

        onMounted(() => {
            modalEl = document.getElementById('userProfileModal');
            if (!modalEl) return;

            modalEl.addEventListener('hide.bs.modal', (event) => {
                if (forcePasswordChange.value) {
                    event.preventDefault();
                }
            });

            modalEl.addEventListener('hidden.bs.modal', () => {
                if (lastActiveElement && typeof lastActiveElement.focus === 'function') {
                    lastActiveElement.focus();
                }
            });
        });

        return {
            activeTab,
            availableLocales,
            selectedLocale,
            updateLocale,
            details,
            loading,
            passwordForm,
            passwordSaving,
            passwordMessage,
            passwordChecks,
            passwordStrength,
            passwordTouched,
            passwordMismatch,
            passwordInvalid,
            passwordInvalidMessage,
            setup,
            twoFaLoading,
            twoFaMessage,
            twoFaStepperVisible,
            twoFaStepClass,
            disable2faState,
            webDavCredentials,
            webDavLabel,
            webDavSecret,
            webDavLoading,
            webDavMessage,
            canSubmitDisable2fa,
            canMount,
            mounts,
            mountsLoading,
            mountForm,
            mountMode,
            mountSaving,
            mountTesting,
            mountRemovingId,
            mountMessage,
            mountTestResult,
            mountErrors,
            canSubmitMount,
            canTestMount,
            setTab,
            loadMounts,
            saveMount,
            testMount,
            removeMount,
            startEditMount,
            cancelEditMount,
            updatePassword,
            start2faSetup,
            cancel2faSetup,
            finish2faSetup,
            openDisable2fa,
            closeDisable2fa,
            submitDisable2fa,
            createWebDavCredential,
            deleteWebDavCredential,
            copyWebDavSecret,
            formatCredentialDate,
            copySecret,
            copyRecoveryCodes,
            downloadRecoveryCodes,
            completeRecoveryCodes,
            mountSummary,
            mountTypeLabel,
            open,
            forcePasswordChange,
            t,
        };
    },
};

function t(key) {
    return i18n.t(key);
}
