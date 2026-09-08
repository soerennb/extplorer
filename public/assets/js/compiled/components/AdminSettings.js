const AdminSettings = {
    props: {
        initialTab: {
            type: String,
            default: 'email'
        },
        onTabChange: {
            type: Function,
            default: null
        }
    },
    render: ((Vue) => {
const _Vue = Vue
const { createElementVNode: _createElementVNode, createCommentVNode: _createCommentVNode } = _Vue

const _hoisted_1 = {
  key: 0,
  class: "alert alert-danger small"
}
const _hoisted_2 = {
  key: 1,
  class: "text-center text-muted py-5"
}
const _hoisted_3 = { key: 2 }
const _hoisted_4 = { class: "nav nav-pills mb-3 flex-wrap gap-1" }
const _hoisted_5 = { class: "nav-item" }
const _hoisted_6 = ["onClick"]
const _hoisted_7 = { class: "nav-item" }
const _hoisted_8 = ["onClick"]
const _hoisted_9 = { class: "nav-item" }
const _hoisted_10 = ["onClick"]
const _hoisted_11 = { class: "nav-item" }
const _hoisted_12 = ["onClick"]
const _hoisted_13 = { class: "nav-item" }
const _hoisted_14 = ["onClick"]
const _hoisted_15 = { class: "nav-item" }
const _hoisted_16 = ["onClick"]
const _hoisted_17 = { class: "nav-item" }
const _hoisted_18 = ["onClick"]
const _hoisted_19 = ["onSubmit"]
const _hoisted_20 = { key: 0 }
const _hoisted_21 = { class: "border-bottom pb-2 mb-3" }
const _hoisted_22 = {
  key: 0,
  class: "alert alert-success small"
}
const _hoisted_23 = {
  key: 1,
  class: "alert alert-warning small"
}
const _hoisted_24 = { class: "row g-3" }
const _hoisted_25 = { class: "col-md-4" }
const _hoisted_26 = { class: "form-label small fw-bold" }
const _hoisted_27 = ["onUpdate:modelValue", "aria-label"]
const _hoisted_28 = { value: "smtp" }
const _hoisted_29 = { value: "sendmail" }
const _hoisted_30 = { value: "mail" }
const _hoisted_31 = {
  key: 0,
  class: "col-md-8"
}
const _hoisted_32 = { class: "form-label small fw-bold" }
const _hoisted_33 = ["onUpdate:modelValue", "aria-label", "placeholder"]
const _hoisted_34 = {
  key: 3,
  class: "row g-3 mt-1"
}
const _hoisted_35 = { class: "col-md-8" }
const _hoisted_36 = { class: "form-label small fw-bold" }
const _hoisted_37 = ["onUpdate:modelValue", "aria-label", "placeholder"]
const _hoisted_38 = { class: "col-md-4" }
const _hoisted_39 = { class: "form-label small fw-bold" }
const _hoisted_40 = ["onUpdate:modelValue", "aria-label", "placeholder"]
const _hoisted_41 = { class: "col-md-6" }
const _hoisted_42 = { class: "form-label small fw-bold" }
const _hoisted_43 = ["onUpdate:modelValue", "aria-label"]
const _hoisted_44 = { class: "col-md-6" }
const _hoisted_45 = { class: "form-label small fw-bold" }
const _hoisted_46 = ["onUpdate:modelValue", "aria-label", "placeholder"]
const _hoisted_47 = { class: "form-text" }
const _hoisted_48 = { class: "col-md-4" }
const _hoisted_49 = { class: "form-label small fw-bold" }
const _hoisted_50 = ["onUpdate:modelValue", "aria-label"]
const _hoisted_51 = { value: "tls" }
const _hoisted_52 = { value: "ssl" }
const _hoisted_53 = { value: "" }
const _hoisted_54 = { class: "col-md-4" }
const _hoisted_55 = { class: "form-label small fw-bold" }
const _hoisted_56 = ["onUpdate:modelValue", "aria-label"]
const _hoisted_57 = { class: "col-md-4" }
const _hoisted_58 = { class: "form-label small fw-bold" }
const _hoisted_59 = ["onUpdate:modelValue", "aria-label"]
const _hoisted_60 = {
  key: 4,
  class: "row g-3 mt-1"
}
const _hoisted_61 = { class: "col-md-6" }
const _hoisted_62 = { class: "form-label small fw-bold" }
const _hoisted_63 = ["onUpdate:modelValue", "aria-label"]
const _hoisted_64 = { class: "col-md-6" }
const _hoisted_65 = { class: "form-label small fw-bold" }
const _hoisted_66 = ["onUpdate:modelValue", "aria-label"]
const _hoisted_67 = { key: 1 }
const _hoisted_68 = { class: "border-bottom pb-2 mb-3" }
const _hoisted_69 = { class: "row g-3" }
const _hoisted_70 = { class: "col-md-4" }
const _hoisted_71 = { class: "form-label small fw-bold" }
const _hoisted_72 = ["onUpdate:modelValue", "aria-label"]
const _hoisted_73 = { class: "form-text" }
const _hoisted_74 = { class: "col-md-4" }
const _hoisted_75 = { class: "form-label small fw-bold" }
const _hoisted_76 = ["onUpdate:modelValue", "aria-label"]
const _hoisted_77 = { class: "form-text" }
const _hoisted_78 = { class: "col-md-4 d-flex align-items-end" }
const _hoisted_79 = { class: "form-check form-switch mb-1" }
const _hoisted_80 = ["onUpdate:modelValue"]
const _hoisted_81 = {
  class: "form-check-label small fw-bold",
  for: "adminTransferNotifyDownload"
}
const _hoisted_82 = { key: 2 }
const _hoisted_83 = { class: "border-bottom pb-2 mb-3" }
const _hoisted_84 = { class: "row g-3" }
const _hoisted_85 = { class: "col-md-4" }
const _hoisted_86 = { class: "form-label small fw-bold" }
const _hoisted_87 = ["onUpdate:modelValue", "aria-label"]
const _hoisted_88 = { class: "form-text" }
const _hoisted_89 = { class: "col-md-4" }
const _hoisted_90 = { class: "form-label small fw-bold" }
const _hoisted_91 = ["onUpdate:modelValue", "aria-label"]
const _hoisted_92 = { class: "form-text" }
const _hoisted_93 = { class: "col-md-4 d-flex align-items-end" }
const _hoisted_94 = { class: "form-check form-switch mb-1" }
const _hoisted_95 = ["onUpdate:modelValue"]
const _hoisted_96 = {
  class: "form-check-label small fw-bold",
  for: "adminShareRequireExpiry"
}
const _hoisted_97 = { class: "col-md-6 d-flex align-items-end" }
const _hoisted_98 = { class: "form-check form-switch mb-1" }
const _hoisted_99 = ["onUpdate:modelValue"]
const _hoisted_100 = {
  class: "form-check-label small fw-bold",
  for: "adminShareRequirePassword"
}
const _hoisted_101 = { class: "border-bottom pb-2 mb-3 mt-4" }
const _hoisted_102 = { class: "row g-3" }
const _hoisted_103 = { class: "col-md-6 d-flex align-items-end" }
const _hoisted_104 = { class: "form-check form-switch mb-1" }
const _hoisted_105 = ["onUpdate:modelValue"]
const _hoisted_106 = {
  class: "form-check-label small fw-bold",
  for: "adminAllowUploadMode"
}
const _hoisted_107 = { class: "col-md-6" }
const _hoisted_108 = { class: "form-text" }
const _hoisted_109 = { class: "col-md-6" }
const _hoisted_110 = { class: "form-label small fw-bold" }
const _hoisted_111 = ["aria-label", "onUpdate:modelValue", "placeholder"]
const _hoisted_112 = { class: "form-text" }
const _hoisted_113 = { class: "col-md-3" }
const _hoisted_114 = { class: "form-label small fw-bold" }
const _hoisted_115 = ["onUpdate:modelValue", "aria-label"]
const _hoisted_116 = { class: "form-text" }
const _hoisted_117 = { class: "col-md-3" }
const _hoisted_118 = { class: "form-label small fw-bold" }
const _hoisted_119 = ["onUpdate:modelValue", "aria-label"]
const _hoisted_120 = { class: "form-text" }
const _hoisted_121 = { key: 3 }
const _hoisted_122 = { class: "border-bottom pb-2 mb-3" }
const _hoisted_123 = { class: "row g-3" }
const _hoisted_124 = { class: "col-md-4" }
const _hoisted_125 = { class: "form-label small fw-bold" }
const _hoisted_126 = ["onUpdate:modelValue", "aria-label"]
const _hoisted_127 = { class: "form-text" }
const _hoisted_128 = { class: "col-md-4" }
const _hoisted_129 = { class: "form-label small fw-bold" }
const _hoisted_130 = ["onUpdate:modelValue", "aria-label"]
const _hoisted_131 = { class: "form-text" }
const _hoisted_132 = { key: 4 }
const _hoisted_133 = { class: "border-bottom pb-2 mb-3" }
const _hoisted_134 = { class: "row g-3" }
const _hoisted_135 = { class: "col-md-6" }
const _hoisted_136 = { class: "form-label small fw-bold" }
const _hoisted_137 = ["onUpdate:modelValue", "aria-label", "placeholder"]
const _hoisted_138 = { class: "form-text" }
const _hoisted_139 = { class: "col-md-6" }
const _hoisted_140 = { class: "form-check form-switch mb-2" }
const _hoisted_141 = ["onUpdate:modelValue"]
const _hoisted_142 = {
  class: "form-check-label small fw-bold",
  for: "remoteLoginEnabled"
}
const _hoisted_143 = { class: "form-label small fw-bold" }
const _hoisted_144 = ["onUpdate:modelValue", "aria-label", "placeholder"]
const _hoisted_145 = { class: "form-text" }
const _hoisted_146 = { key: 5 }
const _hoisted_147 = { class: "border-bottom pb-2 mb-3" }
const _hoisted_148 = { class: "row g-3" }
const _hoisted_149 = { class: "col-md-6" }
const _hoisted_150 = { class: "form-label small fw-bold" }
const _hoisted_151 = ["onUpdate:modelValue", "aria-label"]
const _hoisted_152 = { class: "form-text" }
const _hoisted_153 = { key: 6 }
const _hoisted_154 = { class: "border-bottom pb-2 mb-3" }
const _hoisted_155 = { class: "row g-3" }
const _hoisted_156 = { class: "col-md-4" }
const _hoisted_157 = { class: "form-label small fw-bold" }
const _hoisted_158 = ["onUpdate:modelValue", "aria-label"]
const _hoisted_159 = { class: "form-text" }
const _hoisted_160 = { class: "border-bottom pb-2 mb-3 mt-4" }
const _hoisted_161 = { class: "row g-3" }
const _hoisted_162 = { class: "col-md-12" }
const _hoisted_163 = { class: "form-check form-switch mb-0" }
const _hoisted_164 = ["onUpdate:modelValue"]
const _hoisted_165 = {
  class: "form-check-label small fw-bold",
  for: "adminDavEnabled"
}
const _hoisted_166 = { class: "form-text mt-1" }
const _hoisted_167 = { class: "mt-4 pt-3 border-top text-end" }
const _hoisted_168 = {
  key: 0,
  class: "text-warning small mb-2 text-start"
}
const _hoisted_169 = ["onClick", "disabled"]
const _hoisted_170 = ["onClick", "disabled"]
const _hoisted_171 = ["disabled"]

return function render(_ctx, _cache) {
  with (_ctx) {
    const { toDisplayString: _toDisplayString, openBlock: _openBlock, createElementBlock: _createElementBlock, createCommentVNode: _createCommentVNode, withModifiers: _withModifiers, normalizeClass: _normalizeClass, createElementVNode: _createElementVNode, vModelSelect: _vModelSelect, withDirectives: _withDirectives, vModelText: _vModelText, vModelCheckbox: _vModelCheckbox } = _Vue

    return (_openBlock(), _createElementBlock("div", null, [
      error
        ? (_openBlock(), _createElementBlock("div", _hoisted_1, _toDisplayString(error), 1 /* TEXT */))
        : _createCommentVNode("v-if", true),
      (!settings)
        ? (_openBlock(), _createElementBlock("div", _hoisted_2, _toDisplayString(t('admin_settings_loading', 'Loading settings…')), 1 /* TEXT */))
        : (_openBlock(), _createElementBlock("div", _hoisted_3, [
            _createElementVNode("ul", _hoisted_4, [
              _createElementVNode("li", _hoisted_5, [
                _createElementVNode("a", {
                  class: _normalizeClass(["nav-link", {active: settingsTab === 'email'}]),
                  href: "#",
                  onClick: _withModifiers($event => (settingsTab = 'email'), ["prevent"])
                }, _toDisplayString(t('admin_settings_tab_email', 'Email')), 11 /* TEXT, CLASS, PROPS */, _hoisted_6)
              ]),
              _createElementVNode("li", _hoisted_7, [
                _createElementVNode("a", {
                  class: _normalizeClass(["nav-link", {active: settingsTab === 'transfers'}]),
                  href: "#",
                  onClick: _withModifiers($event => (settingsTab = 'transfers'), ["prevent"])
                }, _toDisplayString(t('admin_settings_tab_transfers', 'Transfers')), 11 /* TEXT, CLASS, PROPS */, _hoisted_8)
              ]),
              _createElementVNode("li", _hoisted_9, [
                _createElementVNode("a", {
                  class: _normalizeClass(["nav-link", {active: settingsTab === 'sharing'}]),
                  href: "#",
                  onClick: _withModifiers($event => (settingsTab = 'sharing'), ["prevent"])
                }, _toDisplayString(t('admin_settings_tab_sharing', 'Sharing')), 11 /* TEXT, CLASS, PROPS */, _hoisted_10)
              ]),
              _createElementVNode("li", _hoisted_11, [
                _createElementVNode("a", {
                  class: _normalizeClass(["nav-link", {active: settingsTab === 'governance'}]),
                  href: "#",
                  onClick: _withModifiers($event => (settingsTab = 'governance'), ["prevent"])
                }, _toDisplayString(t('admin_settings_tab_governance', 'Governance')), 11 /* TEXT, CLASS, PROPS */, _hoisted_12)
              ]),
              _createElementVNode("li", _hoisted_13, [
                _createElementVNode("a", {
                  class: _normalizeClass(["nav-link", {active: settingsTab === 'mounts'}]),
                  href: "#",
                  onClick: _withModifiers($event => (settingsTab = 'mounts'), ["prevent"])
                }, _toDisplayString(t('admin_settings_tab_mounts', 'Mounts')), 11 /* TEXT, CLASS, PROPS */, _hoisted_14)
              ]),
              _createElementVNode("li", _hoisted_15, [
                _createElementVNode("a", {
                  class: _normalizeClass(["nav-link", {active: settingsTab === 'logging'}]),
                  href: "#",
                  onClick: _withModifiers($event => (settingsTab = 'logging'), ["prevent"])
                }, _toDisplayString(t('admin_settings_tab_logging', 'Logging')), 11 /* TEXT, CLASS, PROPS */, _hoisted_16)
              ]),
              _createElementVNode("li", _hoisted_17, [
                _createElementVNode("a", {
                  class: _normalizeClass(["nav-link", {active: settingsTab === 'security'}]),
                  href: "#",
                  onClick: _withModifiers($event => (settingsTab = 'security'), ["prevent"])
                }, _toDisplayString(t('admin_settings_tab_security', 'Security')), 11 /* TEXT, CLASS, PROPS */, _hoisted_18)
              ])
            ]),
            _createElementVNode("form", {
              onSubmit: _withModifiers(saveSettings, ["prevent"])
            }, [
              (settingsTab === 'email')
                ? (_openBlock(), _createElementBlock("div", _hoisted_20, [
                    _createElementVNode("h6", _hoisted_21, _toDisplayString(t('admin_settings_email_heading', 'Email Configuration')), 1 /* TEXT */),
                    (settings.email_delivery_ready)
                      ? (_openBlock(), _createElementBlock("div", _hoisted_22, _toDisplayString(t('admin_settings_email_ready', 'Email delivery is ready for file transfers.')), 1 /* TEXT */))
                      : (_openBlock(), _createElementBlock("div", _hoisted_23, _toDisplayString(settingsDirty ? t('admin_settings_email_save_first', 'Save the email settings, then send a test email before sending files.') : t('admin_settings_email_test_required', 'Send a successful test email before sending files.')), 1 /* TEXT */)),
                    (emailValidation.message)
                      ? (_openBlock(), _createElementBlock("div", {
                          key: 2,
                          class: _normalizeClass(["alert small", emailValidation.ok ? 'alert-success' : 'alert-danger'])
                        }, _toDisplayString(emailValidation.message), 3 /* TEXT, CLASS */))
                      : _createCommentVNode("v-if", true),
                    _createElementVNode("div", _hoisted_24, [
                      _createElementVNode("div", _hoisted_25, [
                        _createElementVNode("label", _hoisted_26, _toDisplayString(t('admin_settings_email_protocol', 'Protocol')), 1 /* TEXT */),
                        _withDirectives(_createElementVNode("select", {
                          class: "form-select form-select-sm",
                          "onUpdate:modelValue": $event => ((settings.email_protocol) = $event),
                          "aria-label": t('admin_settings_email_protocol', 'Protocol')
                        }, [
                          _createElementVNode("option", _hoisted_28, _toDisplayString(t('admin_settings_email_protocol_smtp', 'SMTP')), 1 /* TEXT */),
                          _createElementVNode("option", _hoisted_29, _toDisplayString(t('admin_settings_email_protocol_sendmail', 'Sendmail')), 1 /* TEXT */),
                          _createElementVNode("option", _hoisted_30, _toDisplayString(t('admin_settings_email_protocol_mail', 'PHP mail()')), 1 /* TEXT */)
                        ], 8 /* PROPS */, _hoisted_27), [
                          [_vModelSelect, settings.email_protocol]
                        ])
                      ]),
                      (settings.email_protocol === 'sendmail')
                        ? (_openBlock(), _createElementBlock("div", _hoisted_31, [
                            _createElementVNode("label", _hoisted_32, _toDisplayString(t('admin_settings_email_sendmail_path', 'Sendmail Path')), 1 /* TEXT */),
                            _withDirectives(_createElementVNode("input", {
                              type: "text",
                              class: "form-control form-control-sm",
                              "onUpdate:modelValue": $event => ((settings.sendmail_path) = $event),
                              "aria-label": t('admin_settings_email_sendmail_path', 'Sendmail Path'),
                              placeholder: t('admin_settings_email_sendmail_placeholder', '/usr/sbin/sendmail')
                            }, null, 8 /* PROPS */, _hoisted_33), [
                              [_vModelText, settings.sendmail_path]
                            ])
                          ]))
                        : _createCommentVNode("v-if", true)
                    ]),
                    (settings.email_protocol === 'smtp')
                      ? (_openBlock(), _createElementBlock("div", _hoisted_34, [
                          _createElementVNode("div", _hoisted_35, [
                            _createElementVNode("label", _hoisted_36, _toDisplayString(t('admin_settings_email_smtp_host', 'SMTP Host')), 1 /* TEXT */),
                            _withDirectives(_createElementVNode("input", {
                              type: "text",
                              class: "form-control form-control-sm",
                              "onUpdate:modelValue": $event => ((settings.smtp_host) = $event),
                              "aria-label": t('admin_settings_email_smtp_host', 'SMTP Host'),
                              placeholder: t('admin_settings_email_smtp_host_placeholder', 'smtp.example.com')
                            }, null, 8 /* PROPS */, _hoisted_37), [
                              [_vModelText, settings.smtp_host]
                            ])
                          ]),
                          _createElementVNode("div", _hoisted_38, [
                            _createElementVNode("label", _hoisted_39, _toDisplayString(t('admin_settings_email_port', 'Port')), 1 /* TEXT */),
                            _withDirectives(_createElementVNode("input", {
                              type: "number",
                              class: "form-control form-control-sm",
                              "onUpdate:modelValue": $event => ((settings.smtp_port) = $event),
                              "aria-label": t('admin_settings_email_port', 'Port'),
                              placeholder: t('admin_settings_email_port_placeholder', '587')
                            }, null, 8 /* PROPS */, _hoisted_40), [
                              [
                                _vModelText,
                                settings.smtp_port,
                                void 0,
                                { number: true }
                              ]
                            ])
                          ]),
                          _createElementVNode("div", _hoisted_41, [
                            _createElementVNode("label", _hoisted_42, _toDisplayString(t('admin_settings_email_username', 'Username')), 1 /* TEXT */),
                            _withDirectives(_createElementVNode("input", {
                              type: "text",
                              class: "form-control form-control-sm",
                              "onUpdate:modelValue": $event => ((settings.smtp_user) = $event),
                              "aria-label": t('admin_settings_email_username', 'Username'),
                              autocomplete: "username"
                            }, null, 8 /* PROPS */, _hoisted_43), [
                              [_vModelText, settings.smtp_user]
                            ])
                          ]),
                          _createElementVNode("div", _hoisted_44, [
                            _createElementVNode("label", _hoisted_45, _toDisplayString(t('admin_settings_email_password', 'Password')), 1 /* TEXT */),
                            _withDirectives(_createElementVNode("input", {
                              type: "password",
                              class: "form-control form-control-sm",
                              "onUpdate:modelValue": $event => ((settings.smtp_pass) = $event),
                              "aria-label": t('admin_settings_email_password', 'Password'),
                              placeholder: t('admin_settings_email_password_placeholder', '********'),
                              autocomplete: "new-password"
                            }, null, 8 /* PROPS */, _hoisted_46), [
                              [_vModelText, settings.smtp_pass]
                            ]),
                            _createElementVNode("div", _hoisted_47, _toDisplayString(t('admin_settings_email_password_hint', 'Leave masked to keep the existing password.')), 1 /* TEXT */)
                          ]),
                          _createElementVNode("div", _hoisted_48, [
                            _createElementVNode("label", _hoisted_49, _toDisplayString(t('admin_settings_email_encryption', 'Encryption')), 1 /* TEXT */),
                            _withDirectives(_createElementVNode("select", {
                              class: "form-select form-select-sm",
                              "onUpdate:modelValue": $event => ((settings.smtp_crypto) = $event),
                              "aria-label": t('admin_settings_email_encryption', 'Encryption')
                            }, [
                              _createElementVNode("option", _hoisted_51, _toDisplayString(t('admin_settings_email_encryption_tls', 'TLS')), 1 /* TEXT */),
                              _createElementVNode("option", _hoisted_52, _toDisplayString(t('admin_settings_email_encryption_ssl', 'SSL')), 1 /* TEXT */),
                              _createElementVNode("option", _hoisted_53, _toDisplayString(t('admin_settings_email_encryption_none', 'None')), 1 /* TEXT */)
                            ], 8 /* PROPS */, _hoisted_50), [
                              [_vModelSelect, settings.smtp_crypto]
                            ])
                          ]),
                          _createElementVNode("div", _hoisted_54, [
                            _createElementVNode("label", _hoisted_55, _toDisplayString(t('admin_settings_email_from_email', 'From Email')), 1 /* TEXT */),
                            _withDirectives(_createElementVNode("input", {
                              type: "email",
                              class: "form-control form-control-sm",
                              "onUpdate:modelValue": $event => ((settings.email_from) = $event),
                              "aria-label": t('admin_settings_email_from_email', 'From Email')
                            }, null, 8 /* PROPS */, _hoisted_56), [
                              [_vModelText, settings.email_from]
                            ])
                          ]),
                          _createElementVNode("div", _hoisted_57, [
                            _createElementVNode("label", _hoisted_58, _toDisplayString(t('admin_settings_email_from_name', 'From Name')), 1 /* TEXT */),
                            _withDirectives(_createElementVNode("input", {
                              type: "text",
                              class: "form-control form-control-sm",
                              "onUpdate:modelValue": $event => ((settings.email_from_name) = $event),
                              "aria-label": t('admin_settings_email_from_name', 'From Name')
                            }, null, 8 /* PROPS */, _hoisted_59), [
                              [_vModelText, settings.email_from_name]
                            ])
                          ])
                        ]))
                      : _createCommentVNode("v-if", true),
                    (settings.email_protocol !== 'smtp')
                      ? (_openBlock(), _createElementBlock("div", _hoisted_60, [
                          _createElementVNode("div", _hoisted_61, [
                            _createElementVNode("label", _hoisted_62, _toDisplayString(t('admin_settings_email_from_email', 'From Email')), 1 /* TEXT */),
                            _withDirectives(_createElementVNode("input", {
                              type: "email",
                              class: "form-control form-control-sm",
                              "onUpdate:modelValue": $event => ((settings.email_from) = $event),
                              "aria-label": t('admin_settings_email_from_email', 'From Email')
                            }, null, 8 /* PROPS */, _hoisted_63), [
                              [_vModelText, settings.email_from]
                            ])
                          ]),
                          _createElementVNode("div", _hoisted_64, [
                            _createElementVNode("label", _hoisted_65, _toDisplayString(t('admin_settings_email_from_name', 'From Name')), 1 /* TEXT */),
                            _withDirectives(_createElementVNode("input", {
                              type: "text",
                              class: "form-control form-control-sm",
                              "onUpdate:modelValue": $event => ((settings.email_from_name) = $event),
                              "aria-label": t('admin_settings_email_from_name', 'From Name')
                            }, null, 8 /* PROPS */, _hoisted_66), [
                              [_vModelText, settings.email_from_name]
                            ])
                          ])
                        ]))
                      : _createCommentVNode("v-if", true)
                  ]))
                : _createCommentVNode("v-if", true),
              (settingsTab === 'transfers')
                ? (_openBlock(), _createElementBlock("div", _hoisted_67, [
                    _createElementVNode("h6", _hoisted_68, _toDisplayString(t('admin_settings_transfers_heading', 'Transfers')), 1 /* TEXT */),
                    _createElementVNode("div", _hoisted_69, [
                      _createElementVNode("div", _hoisted_70, [
                        _createElementVNode("label", _hoisted_71, _toDisplayString(t('admin_settings_transfers_default_expiry', 'Default Expiry (Days)')), 1 /* TEXT */),
                        _withDirectives(_createElementVNode("input", {
                          type: "number",
                          min: "1",
                          class: "form-control form-control-sm",
                          "onUpdate:modelValue": $event => ((settings.default_transfer_expiry) = $event),
                          "aria-label": t('admin_settings_transfers_default_expiry', 'Default Expiry (Days)')
                        }, null, 8 /* PROPS */, _hoisted_72), [
                          [
                            _vModelText,
                            settings.default_transfer_expiry,
                            void 0,
                            { number: true }
                          ]
                        ]),
                        _createElementVNode("div", _hoisted_73, _toDisplayString(t('admin_settings_transfers_default_expiry_hint', 'Used when no expiry is provided.')), 1 /* TEXT */)
                      ]),
                      _createElementVNode("div", _hoisted_74, [
                        _createElementVNode("label", _hoisted_75, _toDisplayString(t('admin_settings_transfers_max_expiry', 'Max Expiry (Days)')), 1 /* TEXT */),
                        _withDirectives(_createElementVNode("input", {
                          type: "number",
                          min: "1",
                          max: "365",
                          class: "form-control form-control-sm",
                          "onUpdate:modelValue": $event => ((settings.transfer_max_expiry_days) = $event),
                          "aria-label": t('admin_settings_transfers_max_expiry', 'Max Expiry (Days)')
                        }, null, 8 /* PROPS */, _hoisted_76), [
                          [
                            _vModelText,
                            settings.transfer_max_expiry_days,
                            void 0,
                            { number: true }
                          ]
                        ]),
                        _createElementVNode("div", _hoisted_77, _toDisplayString(t('admin_settings_transfers_max_expiry_hint', 'Upper bound for all transfers.')), 1 /* TEXT */)
                      ]),
                      _createElementVNode("div", _hoisted_78, [
                        _createElementVNode("div", _hoisted_79, [
                          _withDirectives(_createElementVNode("input", {
                            class: "form-check-input",
                            type: "checkbox",
                            id: "adminTransferNotifyDownload",
                            "onUpdate:modelValue": $event => ((settings.transfer_default_notify_download) = $event)
                          }, null, 8 /* PROPS */, _hoisted_80), [
                            [_vModelCheckbox, settings.transfer_default_notify_download]
                          ]),
                          _createElementVNode("label", _hoisted_81, _toDisplayString(t('admin_settings_transfers_notify_download', 'Notify On Download (Default)')), 1 /* TEXT */)
                        ])
                      ])
                    ])
                  ]))
                : _createCommentVNode("v-if", true),
              (settingsTab === 'sharing')
                ? (_openBlock(), _createElementBlock("div", _hoisted_82, [
                    _createElementVNode("h6", _hoisted_83, _toDisplayString(t('admin_settings_sharing_heading', 'Share Links')), 1 /* TEXT */),
                    _createElementVNode("div", _hoisted_84, [
                      _createElementVNode("div", _hoisted_85, [
                        _createElementVNode("label", _hoisted_86, _toDisplayString(t('admin_settings_sharing_default_expiry', 'Default Expiry (Days)')), 1 /* TEXT */),
                        _withDirectives(_createElementVNode("input", {
                          type: "number",
                          min: "1",
                          max: "365",
                          class: "form-control form-control-sm",
                          "onUpdate:modelValue": $event => ((settings.share_default_expiry_days) = $event),
                          "aria-label": t('admin_settings_sharing_default_expiry', 'Default Expiry (Days)')
                        }, null, 8 /* PROPS */, _hoisted_87), [
                          [
                            _vModelText,
                            settings.share_default_expiry_days,
                            void 0,
                            { number: true }
                          ]
                        ]),
                        _createElementVNode("div", _hoisted_88, _toDisplayString(t('admin_settings_sharing_default_expiry_hint', 'Used when expiry is required but not provided.')), 1 /* TEXT */)
                      ]),
                      _createElementVNode("div", _hoisted_89, [
                        _createElementVNode("label", _hoisted_90, _toDisplayString(t('admin_settings_sharing_max_expiry', 'Max Expiry (Days)')), 1 /* TEXT */),
                        _withDirectives(_createElementVNode("input", {
                          type: "number",
                          min: "1",
                          max: "365",
                          class: "form-control form-control-sm",
                          "onUpdate:modelValue": $event => ((settings.share_max_expiry_days) = $event),
                          "aria-label": t('admin_settings_sharing_max_expiry', 'Max Expiry (Days)')
                        }, null, 8 /* PROPS */, _hoisted_91), [
                          [
                            _vModelText,
                            settings.share_max_expiry_days,
                            void 0,
                            { number: true }
                          ]
                        ]),
                        _createElementVNode("div", _hoisted_92, _toDisplayString(t('admin_settings_sharing_max_expiry_hint', 'Upper bound for all share links.')), 1 /* TEXT */)
                      ]),
                      _createElementVNode("div", _hoisted_93, [
                        _createElementVNode("div", _hoisted_94, [
                          _withDirectives(_createElementVNode("input", {
                            class: "form-check-input",
                            type: "checkbox",
                            id: "adminShareRequireExpiry",
                            "onUpdate:modelValue": $event => ((settings.share_require_expiry) = $event)
                          }, null, 8 /* PROPS */, _hoisted_95), [
                            [_vModelCheckbox, settings.share_require_expiry]
                          ]),
                          _createElementVNode("label", _hoisted_96, _toDisplayString(t('admin_settings_sharing_require_expiry', 'Require Expiry')), 1 /* TEXT */)
                        ])
                      ]),
                      _createElementVNode("div", _hoisted_97, [
                        _createElementVNode("div", _hoisted_98, [
                          _withDirectives(_createElementVNode("input", {
                            class: "form-check-input",
                            type: "checkbox",
                            id: "adminShareRequirePassword",
                            "onUpdate:modelValue": $event => ((settings.share_require_password) = $event)
                          }, null, 8 /* PROPS */, _hoisted_99), [
                            [_vModelCheckbox, settings.share_require_password]
                          ]),
                          _createElementVNode("label", _hoisted_100, _toDisplayString(t('admin_settings_sharing_require_password', 'Require Password')), 1 /* TEXT */)
                        ])
                      ])
                    ]),
                    _createElementVNode("h6", _hoisted_101, _toDisplayString(t('admin_settings_sharing_upload_heading', 'Upload-Mode Policy')), 1 /* TEXT */),
                    _createElementVNode("div", _hoisted_102, [
                      _createElementVNode("div", _hoisted_103, [
                        _createElementVNode("div", _hoisted_104, [
                          _withDirectives(_createElementVNode("input", {
                            class: "form-check-input",
                            type: "checkbox",
                            id: "adminAllowUploadMode",
                            "onUpdate:modelValue": $event => ((settings.allow_public_uploads) = $event)
                          }, null, 8 /* PROPS */, _hoisted_105), [
                            [_vModelCheckbox, settings.allow_public_uploads]
                          ]),
                          _createElementVNode("label", _hoisted_106, _toDisplayString(t('admin_settings_sharing_allow_upload_mode', 'Allow upload-mode shares')), 1 /* TEXT */)
                        ])
                      ]),
                      _createElementVNode("div", _hoisted_107, [
                        _createElementVNode("div", _hoisted_108, _toDisplayString(t('admin_settings_sharing_allow_upload_mode_hint', 'Upload-mode shares are folder-only and block downloads.')), 1 /* TEXT */)
                      ]),
                      _createElementVNode("div", _hoisted_109, [
                        _createElementVNode("label", _hoisted_110, _toDisplayString(t('admin_settings_sharing_allowed_types', 'Allowed File Extensions')), 1 /* TEXT */),
                        _withDirectives(_createElementVNode("textarea", {
                          class: "form-control form-control-sm",
                          "aria-label": t('admin_settings_sharing_allowed_types', 'Allowed File Extensions'),
                          rows: "3",
                          "onUpdate:modelValue": $event => ((settings.share_upload_allowed_extensions_text) = $event),
                          placeholder: t('admin_settings_sharing_allowed_types_placeholder', 'pdf\\njpg\\npng')
                        }, null, 8 /* PROPS */, _hoisted_111), [
                          [_vModelText, settings.share_upload_allowed_extensions_text]
                        ]),
                        _createElementVNode("div", _hoisted_112, _toDisplayString(t('admin_settings_sharing_allowed_types_hint', 'Leave empty to allow all types. One extension per line, without dots.')), 1 /* TEXT */)
                      ]),
                      _createElementVNode("div", _hoisted_113, [
                        _createElementVNode("label", _hoisted_114, _toDisplayString(t('admin_settings_sharing_quota_mb', 'Total Quota (MB)')), 1 /* TEXT */),
                        _withDirectives(_createElementVNode("input", {
                          type: "number",
                          min: "0",
                          max: "1024000",
                          class: "form-control form-control-sm",
                          "onUpdate:modelValue": $event => ((settings.share_upload_quota_mb) = $event),
                          "aria-label": t('admin_settings_sharing_quota_mb', 'Total Quota (MB)')
                        }, null, 8 /* PROPS */, _hoisted_115), [
                          [
                            _vModelText,
                            settings.share_upload_quota_mb,
                            void 0,
                            { number: true }
                          ]
                        ]),
                        _createElementVNode("div", _hoisted_116, _toDisplayString(t('admin_settings_sharing_quota_mb_hint', '0 disables the quota.')), 1 /* TEXT */)
                      ]),
                      _createElementVNode("div", _hoisted_117, [
                        _createElementVNode("label", _hoisted_118, _toDisplayString(t('admin_settings_sharing_max_files', 'Max Files')), 1 /* TEXT */),
                        _withDirectives(_createElementVNode("input", {
                          type: "number",
                          min: "0",
                          max: "100000",
                          class: "form-control form-control-sm",
                          "onUpdate:modelValue": $event => ((settings.share_upload_max_files) = $event),
                          "aria-label": t('admin_settings_sharing_max_files', 'Max Files')
                        }, null, 8 /* PROPS */, _hoisted_119), [
                          [
                            _vModelText,
                            settings.share_upload_max_files,
                            void 0,
                            { number: true }
                          ]
                        ]),
                        _createElementVNode("div", _hoisted_120, _toDisplayString(t('admin_settings_sharing_max_files_hint', '0 disables the file-count limit.')), 1 /* TEXT */)
                      ])
                    ])
                  ]))
                : _createCommentVNode("v-if", true),
              (settingsTab === 'governance')
                ? (_openBlock(), _createElementBlock("div", _hoisted_121, [
                    _createElementVNode("h6", _hoisted_122, _toDisplayString(t('admin_settings_governance_heading', 'Uploads & Quotas')), 1 /* TEXT */),
                    _createElementVNode("div", _hoisted_123, [
                      _createElementVNode("div", _hoisted_124, [
                        _createElementVNode("label", _hoisted_125, _toDisplayString(t('admin_settings_governance_max_upload', 'Max Upload Size (MB)')), 1 /* TEXT */),
                        _withDirectives(_createElementVNode("input", {
                          type: "number",
                          min: "0",
                          max: "10240",
                          class: "form-control form-control-sm",
                          "onUpdate:modelValue": $event => ((settings.upload_max_file_mb) = $event),
                          "aria-label": t('admin_settings_governance_max_upload', 'Max Upload Size (MB)')
                        }, null, 8 /* PROPS */, _hoisted_126), [
                          [
                            _vModelText,
                            settings.upload_max_file_mb,
                            void 0,
                            { number: true }
                          ]
                        ]),
                        _createElementVNode("div", _hoisted_127, _toDisplayString(t('admin_settings_governance_max_upload_hint', '0 disables the limit. Applies to single and chunked uploads.')), 1 /* TEXT */)
                      ]),
                      _createElementVNode("div", _hoisted_128, [
                        _createElementVNode("label", _hoisted_129, _toDisplayString(t('admin_settings_governance_quota', 'Per-User Quota (MB)')), 1 /* TEXT */),
                        _withDirectives(_createElementVNode("input", {
                          type: "number",
                          min: "0",
                          max: "102400",
                          class: "form-control form-control-sm",
                          "onUpdate:modelValue": $event => ((settings.quota_per_user_mb) = $event),
                          "aria-label": t('admin_settings_governance_quota', 'Per-User Quota (MB)')
                        }, null, 8 /* PROPS */, _hoisted_130), [
                          [
                            _vModelText,
                            settings.quota_per_user_mb,
                            void 0,
                            { number: true }
                          ]
                        ]),
                        _createElementVNode("div", _hoisted_131, _toDisplayString(t('admin_settings_governance_quota_hint', '0 disables the quota. Applies to the user home directory.')), 1 /* TEXT */)
                      ])
                    ])
                  ]))
                : _createCommentVNode("v-if", true),
              (settingsTab === 'mounts')
                ? (_openBlock(), _createElementBlock("div", _hoisted_132, [
                    _createElementVNode("h6", _hoisted_133, _toDisplayString(t('admin_settings_mounts_heading', 'External Mounts')), 1 /* TEXT */),
                    _createElementVNode("div", _hoisted_134, [
                      _createElementVNode("div", _hoisted_135, [
                        _createElementVNode("label", _hoisted_136, _toDisplayString(t('admin_settings_mounts_allowlist', 'Allowlist (one path per line)')), 1 /* TEXT */),
                        _withDirectives(_createElementVNode("textarea", {
                          class: "form-control form-control-sm",
                          rows: "4",
                          "onUpdate:modelValue": $event => ((settings.mount_root_allowlist_text) = $event),
                          "aria-label": t('admin_settings_mounts_allowlist', 'Allowlist (one path per line)'),
                          placeholder: t('admin_settings_mounts_allowlist_placeholder', '/srv/data\\n/mnt/storage')
                        }, null, 8 /* PROPS */, _hoisted_137), [
                          [_vModelText, settings.mount_root_allowlist_text]
                        ]),
                        _createElementVNode("div", _hoisted_138, _toDisplayString(t('admin_settings_mounts_allowlist_hint', 'Only paths under these roots can be mounted. Leave empty to disable external mounts.')), 1 /* TEXT */)
                      ]),
                      _createElementVNode("div", _hoisted_139, [
                        _createElementVNode("div", _hoisted_140, [
                          _withDirectives(_createElementVNode("input", {
                            class: "form-check-input",
                            type: "checkbox",
                            id: "remoteLoginEnabled",
                            "onUpdate:modelValue": $event => ((settings.remote_login_enabled) = $event)
                          }, null, 8 /* PROPS */, _hoisted_141), [
                            [_vModelCheckbox, settings.remote_login_enabled]
                          ]),
                          _createElementVNode("label", _hoisted_142, _toDisplayString(t('admin_settings_remote_login_enabled', 'Enable direct remote login')), 1 /* TEXT */)
                        ]),
                        _createElementVNode("label", _hoisted_143, _toDisplayString(t('admin_settings_remote_endpoint_allowlist', 'Remote endpoint allowlist (one exact endpoint per line)')), 1 /* TEXT */),
                        _withDirectives(_createElementVNode("textarea", {
                          class: "form-control form-control-sm",
                          rows: "4",
                          "onUpdate:modelValue": $event => ((settings.remote_endpoint_allowlist_text) = $event),
                          "aria-label": t('admin_settings_remote_endpoint_allowlist', 'Remote endpoint allowlist (one exact endpoint per line)'),
                          placeholder: t('admin_settings_remote_endpoint_allowlist_placeholder', 'sftp://files.example.com:22\\nftps://ftp.example.com:990')
                        }, null, 8 /* PROPS */, _hoisted_144), [
                          [_vModelText, settings.remote_endpoint_allowlist_text]
                        ]),
                        _createElementVNode("div", _hoisted_145, _toDisplayString(t('admin_settings_remote_endpoint_allowlist_hint', 'Use protocol://hostname:port. Empty means deny all remote connections; wildcards and open networks are not accepted.')), 1 /* TEXT */)
                      ])
                    ])
                  ]))
                : _createCommentVNode("v-if", true),
              (settingsTab === 'logging')
                ? (_openBlock(), _createElementBlock("div", _hoisted_146, [
                    _createElementVNode("h6", _hoisted_147, _toDisplayString(t('admin_settings_logging_heading', 'Audit Logging')), 1 /* TEXT */),
                    _createElementVNode("div", _hoisted_148, [
                      _createElementVNode("div", _hoisted_149, [
                        _createElementVNode("label", _hoisted_150, _toDisplayString(t('admin_settings_logging_retention', 'Retention Count')), 1 /* TEXT */),
                        _withDirectives(_createElementVNode("input", {
                          type: "number",
                          min: "100",
                          max: "20000",
                          step: "100",
                          class: "form-control form-control-sm",
                          "onUpdate:modelValue": $event => ((settings.log_retention_count) = $event),
                          "aria-label": t('admin_settings_logging_retention', 'Retention Count')
                        }, null, 8 /* PROPS */, _hoisted_151), [
                          [
                            _vModelText,
                            settings.log_retention_count,
                            void 0,
                            { number: true }
                          ]
                        ]),
                        _createElementVNode("div", _hoisted_152, _toDisplayString(t('admin_settings_logging_retention_hint', 'How many recent audit log entries to keep (100-20000).')), 1 /* TEXT */)
                      ])
                    ])
                  ]))
                : _createCommentVNode("v-if", true),
              (settingsTab === 'security')
                ? (_openBlock(), _createElementBlock("div", _hoisted_153, [
                    _createElementVNode("h6", _hoisted_154, _toDisplayString(t('admin_settings_security_heading', 'Session Security')), 1 /* TEXT */),
                    _createElementVNode("div", _hoisted_155, [
                      _createElementVNode("div", _hoisted_156, [
                        _createElementVNode("label", _hoisted_157, _toDisplayString(t('admin_settings_security_idle_timeout', 'Idle Timeout (Minutes)')), 1 /* TEXT */),
                        _withDirectives(_createElementVNode("input", {
                          type: "number",
                          min: "0",
                          max: "1440",
                          class: "form-control form-control-sm",
                          "onUpdate:modelValue": $event => ((settings.session_idle_timeout_minutes) = $event),
                          "aria-label": t('admin_settings_security_idle_timeout', 'Idle Timeout (Minutes)')
                        }, null, 8 /* PROPS */, _hoisted_158), [
                          [
                            _vModelText,
                            settings.session_idle_timeout_minutes,
                            void 0,
                            { number: true }
                          ]
                        ]),
                        _createElementVNode("div", _hoisted_159, _toDisplayString(t('admin_settings_security_idle_timeout_hint', '0 disables idle timeout. Applies to API and UI sessions.')), 1 /* TEXT */)
                      ])
                    ]),
                    _createElementVNode("h6", _hoisted_160, _toDisplayString(t('admin_settings_dav_heading', 'WebDAV')), 1 /* TEXT */),
                    _createElementVNode("div", _hoisted_161, [
                      _createElementVNode("div", _hoisted_162, [
                        _createElementVNode("div", _hoisted_163, [
                          _withDirectives(_createElementVNode("input", {
                            class: "form-check-input",
                            type: "checkbox",
                            id: "adminDavEnabled",
                            "onUpdate:modelValue": $event => ((settings.webdav_enabled) = $event)
                          }, null, 8 /* PROPS */, _hoisted_164), [
                            [_vModelCheckbox, settings.webdav_enabled]
                          ]),
                          _createElementVNode("label", _hoisted_165, _toDisplayString(t('admin_settings_dav_enabled', 'Enable WebDAV')), 1 /* TEXT */)
                        ]),
                        _createElementVNode("div", _hoisted_166, _toDisplayString(t('admin_settings_dav_enabled_hint', 'Allow users to access files via WebDAV.')), 1 /* TEXT */)
                      ])
                    ])
                  ]))
                : _createCommentVNode("v-if", true),
              _createElementVNode("div", _hoisted_167, [
                settingsDirty
                  ? (_openBlock(), _createElementBlock("div", _hoisted_168, _toDisplayString(t('admin_settings_unsaved', 'You have unsaved settings changes.')), 1 /* TEXT */))
                  : _createCommentVNode("v-if", true),
                (settingsTab === 'email')
                  ? (_openBlock(), _createElementBlock("button", {
                      key: 1,
                      type: "button",
                      class: "btn btn-outline-secondary btn-sm me-2",
                      onClick: validateEmailSettings,
                      disabled: !settings || isValidatingEmail
                    }, _toDisplayString(isValidatingEmail ? t('admin_settings_validating', 'Validating…') : t('admin_settings_validate', 'Validate')), 9 /* TEXT, PROPS */, _hoisted_169))
                  : _createCommentVNode("v-if", true),
                (settingsTab === 'email')
                  ? (_openBlock(), _createElementBlock("button", {
                      key: 2,
                      type: "button",
                      class: "btn btn-outline-secondary btn-sm me-2",
                      onClick: testEmail,
                      disabled: !settings || settingsDirty || isTestingEmail
                    }, _toDisplayString(isTestingEmail ? t('admin_settings_sending', 'Sending…') : t('admin_settings_send_test_email', 'Send Test Email')), 9 /* TEXT, PROPS */, _hoisted_170))
                  : _createCommentVNode("v-if", true),
                _createElementVNode("button", {
                  type: "submit",
                  class: "btn btn-primary btn-sm",
                  disabled: !settingsDirty || isSavingSettings
                }, _toDisplayString(isSavingSettings ? t('admin_settings_saving', 'Saving…') : t('admin_settings_save', 'Save Settings')), 9 /* TEXT, PROPS */, _hoisted_171)
              ])
            ], 40 /* PROPS, NEED_HYDRATION */, _hoisted_19)
          ]))
    ]))
  }
}
})(Vue),
    data() {
        return {
            settings: null,
            settingsOriginal: null,
            settingsTab: this.initialTab || 'email',
            emailValidation: { ok: null, message: '', timeoutId: null },
            error: null,
            isSavingSettings: false,
            isValidatingEmail: false,
            isTestingEmail: false
        };
    },
    computed: {
        settingsDirty() {
            if (!this.settings || !this.settingsOriginal) return false;
            try {
                return JSON.stringify(this.settings) !== JSON.stringify(this.settingsOriginal);
            } catch (e) {
                return false;
            }
        }
    },
    watch: {
        settingsTab(newTab) {
            if (this.onTabChange) {
                this.onTabChange(newTab);
            }
        },
        initialTab(newTab) {
            if (newTab && newTab !== this.settingsTab) {
                this.settingsTab = newTab;
            }
        }
    },
    mounted() {
        this.loadSettings();
    },
    methods: {
        t(key, fallback = '') {
            const value = i18n.t(key);
            if (value === key) {
                return fallback || key;
            }
            return value;
        },
        async loadSettings() {
            this.error = null;
            try {
                this.settings = await Api.get('settings');
                if (!this.settings.mount_root_allowlist_text && Array.isArray(this.settings.mount_root_allowlist)) {
                    this.settings.mount_root_allowlist_text = this.settings.mount_root_allowlist.join('\n');
                }
                if (typeof this.settings.remote_endpoint_allowlist_text !== 'string' && Array.isArray(this.settings.remote_endpoint_allowlist)) {
                    this.settings.remote_endpoint_allowlist_text = this.settings.remote_endpoint_allowlist.map((entry) => {
                        if (typeof entry === 'string') return entry;
                        return `${entry.protocol}://${entry.host}:${entry.port}`;
                    }).join('\n');
                }
                if (!this.settings.share_upload_allowed_extensions_text && Array.isArray(this.settings.share_upload_allowed_extensions)) {
                    this.settings.share_upload_allowed_extensions_text = this.settings.share_upload_allowed_extensions.join('\n');
                }
                if (!this.settings.email_protocol) {
                    this.settings.email_protocol = 'smtp';
                }
                if (!this.settings.sendmail_path) {
                    this.settings.sendmail_path = '/usr/sbin/sendmail';
                }
                this.normalizeSettings();
                this.settingsOriginal = JSON.parse(JSON.stringify(this.settings));
                this.clearEmailValidation();
            } catch (e) {
                this.error = e.message;
            }
        },
        normalizeSettings() {
            if (!this.settings) return;
            if (typeof this.settings.log_retention_count !== 'number' || Number.isNaN(this.settings.log_retention_count)) {
                this.settings.log_retention_count = 2000;
            }
            if (typeof this.settings.transfer_max_expiry_days !== 'number' || Number.isNaN(this.settings.transfer_max_expiry_days)) {
                this.settings.transfer_max_expiry_days = 30;
            }
            if (typeof this.settings.default_transfer_expiry !== 'number' || Number.isNaN(this.settings.default_transfer_expiry)) {
                this.settings.default_transfer_expiry = 7;
            }
            if (typeof this.settings.transfer_default_notify_download !== 'boolean') {
                this.settings.transfer_default_notify_download = false;
            }
            if (typeof this.settings.share_default_expiry_days !== 'number' || Number.isNaN(this.settings.share_default_expiry_days)) {
                this.settings.share_default_expiry_days = 7;
            }
            if (typeof this.settings.share_max_expiry_days !== 'number' || Number.isNaN(this.settings.share_max_expiry_days)) {
                this.settings.share_max_expiry_days = 30;
            }
            if (typeof this.settings.share_require_expiry !== 'boolean') {
                this.settings.share_require_expiry = false;
            }
            if (typeof this.settings.share_require_password !== 'boolean') {
                this.settings.share_require_password = false;
            }
            if (typeof this.settings.allow_public_uploads !== 'boolean') {
                this.settings.allow_public_uploads = false;
            }
            if (typeof this.settings.webdav_enabled !== 'boolean') {
                this.settings.webdav_enabled = true;
            }
            if (typeof this.settings.remote_login_enabled !== 'boolean') {
                this.settings.remote_login_enabled = false;
            }
            if (typeof this.settings.remote_endpoint_allowlist_text !== 'string') {
                if (Array.isArray(this.settings.remote_endpoint_allowlist)) {
                    this.settings.remote_endpoint_allowlist_text = this.settings.remote_endpoint_allowlist.map((entry) => {
                        if (typeof entry === 'string') return entry;
                        return `${entry.protocol}://${entry.host}:${entry.port}`;
                    }).join('\n');
                } else {
                    this.settings.remote_endpoint_allowlist_text = '';
                }
            }
            if (typeof this.settings.share_upload_quota_mb !== 'number' || Number.isNaN(this.settings.share_upload_quota_mb)) {
                this.settings.share_upload_quota_mb = 0;
            }
            if (typeof this.settings.share_upload_max_files !== 'number' || Number.isNaN(this.settings.share_upload_max_files)) {
                this.settings.share_upload_max_files = 0;
            }
            if (typeof this.settings.share_upload_allowed_extensions_text !== 'string') {
                if (Array.isArray(this.settings.share_upload_allowed_extensions)) {
                    this.settings.share_upload_allowed_extensions_text = this.settings.share_upload_allowed_extensions.join('\n');
                } else {
                    this.settings.share_upload_allowed_extensions_text = '';
                }
            }
            if (typeof this.settings.session_idle_timeout_minutes !== 'number' || Number.isNaN(this.settings.session_idle_timeout_minutes)) {
                this.settings.session_idle_timeout_minutes = 120;
            }
            if (typeof this.settings.upload_max_file_mb !== 'number' || Number.isNaN(this.settings.upload_max_file_mb)) {
                this.settings.upload_max_file_mb = 0;
            }
            if (typeof this.settings.quota_per_user_mb !== 'number' || Number.isNaN(this.settings.quota_per_user_mb)) {
                this.settings.quota_per_user_mb = 0;
            }
        },
        async saveSettings() {
            if (!this.settingsDirty) return;
            this.isSavingSettings = true;
            try {
                const response = await Api.post('settings', this.settings);
                if (typeof response.email_delivery_ready === 'boolean') {
                    this.settings.email_delivery_ready = response.email_delivery_ready;
                }
                if (Object.prototype.hasOwnProperty.call(response, 'email_delivery_verified_at')) {
                    this.settings.email_delivery_verified_at = response.email_delivery_verified_at;
                }
                this.settingsOriginal = JSON.parse(JSON.stringify(this.settings));
                this.toastSuccess(this.t('admin_settings_saved', 'Settings saved.'));
            } catch (e) {
                this.error = e.message;
                this.toastError(this.t('admin_settings_save_failed', 'Failed to save settings.'));
            } finally {
                this.isSavingSettings = false;
            }
        },
        async testEmail() {
            if (!this.settings) return;
            const email = await this.promptForEmail();
            if (!email) return;
            this.isTestingEmail = true;
            try {
                const response = await Api.post('settings/test-email', { ...this.settings, email });
                this.settings.email_delivery_ready = response.email_delivery_ready === true;
                this.settings.email_delivery_verified_at = response.email_delivery_verified_at || null;
                this.settingsOriginal = JSON.parse(JSON.stringify(this.settings));
                this.setEmailValidation(true, this.t('admin_settings_test_email_sent', 'Test email sent successfully.'));
                this.toastSuccess(this.t('admin_settings_test_email_sent_short', 'Test email sent.'));
            } catch (e) {
                this.setEmailValidation(false, this.t('admin_settings_test_failed_prefix', 'Test failed: ') + e.message);
                this.toastError(this.t('admin_settings_test_email_failed', 'Test email failed.'));
            } finally {
                this.isTestingEmail = false;
            }
        },
        async validateEmailSettings() {
            if (!this.settings) return;
            this.isValidatingEmail = true;
            try {
                const res = await Api.post('settings/validate-email', this.settings);
                this.setEmailValidation(true, res.message || this.t('admin_settings_validation_success', 'Validation successful'));
            } catch (e) {
                this.setEmailValidation(false, this.t('admin_settings_validation_failed_prefix', 'Validation failed: ') + e.message);
            } finally {
                this.isValidatingEmail = false;
            }
        },
        setEmailValidation(ok, message) {
            this.clearEmailValidation();
            this.emailValidation.ok = ok;
            this.emailValidation.message = message;
            this.emailValidation.timeoutId = setTimeout(() => {
                this.clearEmailValidation();
            }, 5000);
        },
        clearEmailValidation() {
            if (this.emailValidation.timeoutId) {
                clearTimeout(this.emailValidation.timeoutId);
            }
            this.emailValidation.ok = null;
            this.emailValidation.message = '';
            this.emailValidation.timeoutId = null;
        },
        toastBase() {
            return Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true
            });
        },
        toastSuccess(message) {
            this.toastBase().fire({ icon: 'success', title: message });
        },
        toastError(message) {
            this.toastBase().fire({ icon: 'error', title: message });
        },
        async promptForEmail() {
            const result = await Swal.fire({
                title: this.t('admin_settings_send_test_email', 'Send test email'),
                input: 'email',
                inputLabel: this.t('admin_settings_recipient_email', 'Recipient email'),
                inputPlaceholder: this.t('admin_settings_recipient_email_placeholder', 'name@example.com'),
                showCancelButton: true,
                confirmButtonText: this.t('admin_settings_send', 'Send'),
                inputValidator: (value) => {
                    if (!value) return this.t('admin_settings_email_required', 'Email is required');
                    return null;
                }
            });
            if (!result.isConfirmed) return null;
            return result.value;
        }
    }
};
