const UserAdmin = {
    render: ((Vue) => {
const _Vue = Vue
const { createElementVNode: _createElementVNode, createCommentVNode: _createCommentVNode, createTextVNode: _createTextVNode } = _Vue

const _hoisted_1 = {
  class: "modal fade",
  id: "userAdminModal",
  tabindex: "-1"
}
const _hoisted_2 = { class: "modal-dialog modal-lg" }
const _hoisted_3 = { class: "modal-content" }
const _hoisted_4 = { class: "modal-header" }
const _hoisted_5 = { class: "modal-title" }
const _hoisted_6 = { class: "modal-body" }
const _hoisted_7 = {
  key: 0,
  class: "alert alert-light border small d-flex align-items-center justify-content-between"
}
const _hoisted_8 = { class: "text-muted" }
const _hoisted_9 = ["href"]
const _hoisted_10 = { key: 1 }
const _hoisted_11 = { class: "row g-2 mb-3" }
const _hoisted_12 = { class: "col-sm-4" }
const _hoisted_13 = { class: "border rounded p-3 h-100 bg-body-tertiary" }
const _hoisted_14 = { class: "small text-muted" }
const _hoisted_15 = { class: "fs-4 fw-semibold" }
const _hoisted_16 = ["href"]
const _hoisted_17 = { class: "col-sm-4" }
const _hoisted_18 = { class: "border rounded p-3 h-100 bg-body-tertiary" }
const _hoisted_19 = { class: "small text-muted" }
const _hoisted_20 = { class: "fs-4 fw-semibold" }
const _hoisted_21 = ["href"]
const _hoisted_22 = { class: "col-sm-4" }
const _hoisted_23 = { class: "border rounded p-3 h-100 bg-body-tertiary" }
const _hoisted_24 = { class: "small text-muted" }
const _hoisted_25 = { class: "fs-4 fw-semibold" }
const _hoisted_26 = ["href"]
const _hoisted_27 = { class: "d-flex flex-wrap gap-2 mb-3" }
const _hoisted_28 = ["href"]
const _hoisted_29 = ["href"]
const _hoisted_30 = ["href"]
const _hoisted_31 = ["href"]
const _hoisted_32 = { class: "border rounded" }
const _hoisted_33 = { class: "d-flex align-items-center justify-content-between gap-2 px-3 py-2 border-bottom bg-body-tertiary" }
const _hoisted_34 = { class: "fw-semibold" }
const _hoisted_35 = ["onClick", "disabled"]
const _hoisted_36 = {
  key: 0,
  class: "text-muted small p-3"
}
const _hoisted_37 = {
  key: 1,
  class: "text-center text-muted small p-4"
}
const _hoisted_38 = {
  key: 2,
  class: "table-responsive"
}
const _hoisted_39 = { class: "table table-sm table-hover small mb-0" }
const _hoisted_40 = { class: "table-light" }
const _hoisted_41 = { class: "text-nowrap" }
const _hoisted_42 = ["title"]
const _hoisted_43 = { class: "nav nav-tabs mb-3" }
const _hoisted_44 = { class: "nav-item" }
const _hoisted_45 = ["onClick"]
const _hoisted_46 = { class: "nav-item" }
const _hoisted_47 = ["onClick"]
const _hoisted_48 = {
  key: 0,
  class: "alert alert-danger"
}
const _hoisted_49 = { key: 1 }
const _hoisted_50 = {
  key: 0,
  class: "text-center text-muted"
}
const _hoisted_51 = { key: 1 }
const _hoisted_52 = { class: "nav nav-pills mb-3" }
const _hoisted_53 = { class: "nav-item" }
const _hoisted_54 = ["onClick"]
const _hoisted_55 = { class: "nav-item" }
const _hoisted_56 = ["onClick"]
const _hoisted_57 = { class: "nav-item" }
const _hoisted_58 = ["onClick"]
const _hoisted_59 = { class: "nav-item" }
const _hoisted_60 = ["onClick"]
const _hoisted_61 = { class: "nav-item" }
const _hoisted_62 = ["onClick"]
const _hoisted_63 = { class: "nav-item" }
const _hoisted_64 = ["onClick"]
const _hoisted_65 = { class: "nav-item" }
const _hoisted_66 = ["onClick"]
const _hoisted_67 = ["onSubmit"]
const _hoisted_68 = { key: 0 }
const _hoisted_69 = {
  key: 0,
  class: "alert alert-warning small"
}
const _hoisted_70 = { class: "row g-3" }
const _hoisted_71 = { class: "col-md-4" }
const _hoisted_72 = ["onUpdate:modelValue"]
const _hoisted_73 = {
  key: 0,
  class: "col-md-8"
}
const _hoisted_74 = ["onUpdate:modelValue"]
const _hoisted_75 = {
  key: 2,
  class: "row g-3 mt-1"
}
const _hoisted_76 = { class: "col-md-8" }
const _hoisted_77 = ["onUpdate:modelValue"]
const _hoisted_78 = { class: "col-md-4" }
const _hoisted_79 = ["onUpdate:modelValue"]
const _hoisted_80 = { class: "col-md-6" }
const _hoisted_81 = ["onUpdate:modelValue"]
const _hoisted_82 = { class: "col-md-6" }
const _hoisted_83 = ["onUpdate:modelValue"]
const _hoisted_84 = { class: "col-md-4" }
const _hoisted_85 = ["onUpdate:modelValue"]
const _hoisted_86 = { class: "col-md-4" }
const _hoisted_87 = ["onUpdate:modelValue"]
const _hoisted_88 = { class: "col-md-4" }
const _hoisted_89 = ["onUpdate:modelValue"]
const _hoisted_90 = {
  key: 3,
  class: "row g-3 mt-1"
}
const _hoisted_91 = { class: "col-md-6" }
const _hoisted_92 = ["onUpdate:modelValue"]
const _hoisted_93 = { class: "col-md-6" }
const _hoisted_94 = ["onUpdate:modelValue"]
const _hoisted_95 = { key: 1 }
const _hoisted_96 = { class: "row g-3" }
const _hoisted_97 = { class: "col-md-4" }
const _hoisted_98 = ["onUpdate:modelValue"]
const _hoisted_99 = { class: "col-md-4" }
const _hoisted_100 = ["onUpdate:modelValue"]
const _hoisted_101 = { class: "col-md-4 d-flex align-items-end" }
const _hoisted_102 = { class: "form-check form-switch mb-1" }
const _hoisted_103 = ["onUpdate:modelValue"]
const _hoisted_104 = { key: 2 }
const _hoisted_105 = { class: "row g-3" }
const _hoisted_106 = { class: "col-md-4" }
const _hoisted_107 = ["onUpdate:modelValue"]
const _hoisted_108 = { class: "col-md-4" }
const _hoisted_109 = ["onUpdate:modelValue"]
const _hoisted_110 = { class: "col-md-4 d-flex align-items-end" }
const _hoisted_111 = { class: "form-check form-switch mb-1" }
const _hoisted_112 = ["onUpdate:modelValue"]
const _hoisted_113 = { class: "col-md-6 d-flex align-items-end" }
const _hoisted_114 = { class: "form-check form-switch mb-1" }
const _hoisted_115 = ["onUpdate:modelValue"]
const _hoisted_116 = { key: 3 }
const _hoisted_117 = { class: "row g-3" }
const _hoisted_118 = { class: "col-md-6" }
const _hoisted_119 = ["onUpdate:modelValue"]
const _hoisted_120 = { class: "col-md-6" }
const _hoisted_121 = ["onUpdate:modelValue"]
const _hoisted_122 = { key: 4 }
const _hoisted_123 = { class: "row g-3" }
const _hoisted_124 = { class: "col-md-6" }
const _hoisted_125 = ["onUpdate:modelValue"]
const _hoisted_126 = { key: 5 }
const _hoisted_127 = { class: "row g-3" }
const _hoisted_128 = { class: "col-md-4" }
const _hoisted_129 = ["onUpdate:modelValue"]
const _hoisted_130 = { class: "col-md-4" }
const _hoisted_131 = ["onUpdate:modelValue"]
const _hoisted_132 = { key: 6 }
const _hoisted_133 = { class: "row g-3" }
const _hoisted_134 = { class: "col-md-4" }
const _hoisted_135 = ["onUpdate:modelValue"]
const _hoisted_136 = { class: "mt-4 pt-3 border-top text-end" }
const _hoisted_137 = {
  key: 0,
  class: "text-warning small mb-2 text-start"
}
const _hoisted_138 = ["onClick", "disabled"]
const _hoisted_139 = ["onClick", "disabled"]
const _hoisted_140 = ["disabled"]
const _hoisted_141 = { key: 2 }
const _hoisted_142 = { class: "table table-striped table-hover small" }
const _hoisted_143 = { class: "badge bg-secondary me-1" }
const _hoisted_144 = { class: "text-end" }
const _hoisted_145 = ["onClick", "aria-label"]
const _hoisted_146 = ["onClick", "disabled", "aria-label"]
const _hoisted_147 = ["onClick"]
const _hoisted_148 = {
  key: 0,
  class: "card mt-3 bg-body-tertiary"
}
const _hoisted_149 = { class: "card-body" }
const _hoisted_150 = { class: "row g-2" }
const _hoisted_151 = {
  key: 0,
  class: "col-md-6"
}
const _hoisted_152 = ["onUpdate:modelValue"]
const _hoisted_153 = { class: "col-md-6" }
const _hoisted_154 = {
  class: "form-label small",
  for: "quickAdminUserPassword"
}
const _hoisted_155 = ["onUpdate:modelValue"]
const _hoisted_156 = { class: "col-md-6" }
const _hoisted_157 = ["onUpdate:modelValue"]
const _hoisted_158 = { class: "col-md-6" }
const _hoisted_159 = ["onUpdate:modelValue"]
const _hoisted_160 = { class: "col-md-6" }
const _hoisted_161 = ["onUpdate:modelValue"]
const _hoisted_162 = {
  key: 0,
  class: "mt-1"
}
const _hoisted_163 = { class: "col-md-12" }
const _hoisted_164 = { class: "d-flex flex-wrap gap-2" }
const _hoisted_165 = ["id", "value", "onUpdate:modelValue"]
const _hoisted_166 = ["for"]
const _hoisted_167 = {
  key: 1,
  class: "col-md-12"
}
const _hoisted_168 = { class: "d-flex align-items-center justify-content-between" }
const _hoisted_169 = ["onClick", "disabled"]
const _hoisted_170 = {
  key: 0,
  class: "text-muted small"
}
const _hoisted_171 = {
  key: 1,
  class: "text-muted small"
}
const _hoisted_172 = {
  key: 2,
  class: "d-flex flex-wrap gap-1"
}
const _hoisted_173 = { class: "mt-3 text-end" }
const _hoisted_174 = ["onClick", "disabled"]
const _hoisted_175 = ["onClick", "disabled"]
const _hoisted_176 = { key: 3 }
const _hoisted_177 = { class: "table table-striped table-hover small" }
const _hoisted_178 = { class: "badge bg-info me-1" }
const _hoisted_179 = { class: "text-end" }
const _hoisted_180 = ["onClick", "aria-label"]
const _hoisted_181 = ["onClick", "aria-label"]
const _hoisted_182 = ["onClick"]
const _hoisted_183 = {
  key: 0,
  class: "card mt-3 bg-body-tertiary"
}
const _hoisted_184 = { class: "card-body" }
const _hoisted_185 = ["onUpdate:modelValue"]
const _hoisted_186 = { class: "d-flex flex-wrap gap-2" }
const _hoisted_187 = ["id", "value", "onUpdate:modelValue"]
const _hoisted_188 = ["for"]
const _hoisted_189 = { class: "mt-3 text-end" }
const _hoisted_190 = ["onClick", "disabled"]
const _hoisted_191 = ["onClick", "disabled"]
const _hoisted_192 = { key: 4 }
const _hoisted_193 = { class: "table table-striped table-hover small" }
const _hoisted_194 = { class: "small" }
const _hoisted_195 = { class: "text-end" }
const _hoisted_196 = ["onClick", "aria-label"]
const _hoisted_197 = ["onClick", "aria-label"]
const _hoisted_198 = ["onClick"]
const _hoisted_199 = {
  key: 0,
  class: "card mt-3 bg-body-tertiary"
}
const _hoisted_200 = { class: "card-body" }
const _hoisted_201 = ["onUpdate:modelValue"]
const _hoisted_202 = ["onUpdate:modelValue"]
const _hoisted_203 = {
  key: 1,
  class: "mt-2"
}
const _hoisted_204 = { class: "mt-3 text-end" }
const _hoisted_205 = ["onClick", "disabled"]
const _hoisted_206 = ["onClick", "disabled"]
const _hoisted_207 = { key: 5 }
const _hoisted_208 = {
  key: 0,
  class: "border rounded p-2 mb-2 bg-body-tertiary"
}
const _hoisted_209 = { class: "row g-2 align-items-end" }
const _hoisted_210 = { class: "col-md-2" }
const _hoisted_211 = ["onUpdate:modelValue"]
const _hoisted_212 = { class: "col-md-2" }
const _hoisted_213 = ["onUpdate:modelValue"]
const _hoisted_214 = { class: "col-md-3" }
const _hoisted_215 = ["onUpdate:modelValue"]
const _hoisted_216 = { class: "col-md-2" }
const _hoisted_217 = ["onUpdate:modelValue"]
const _hoisted_218 = { class: "col-md-2" }
const _hoisted_219 = ["onUpdate:modelValue"]
const _hoisted_220 = { class: "col-md-1 d-grid" }
const _hoisted_221 = ["onClick", "disabled"]
const _hoisted_222 = { class: "mt-2 d-flex flex-wrap gap-2" }
const _hoisted_223 = ["onClick", "disabled"]
const _hoisted_224 = ["onClick", "disabled"]
const _hoisted_225 = ["onClick", "disabled"]
const _hoisted_226 = {
  key: 1,
  class: "text-muted small mb-2"
}
const _hoisted_227 = {
  key: 2,
  class: "text-muted small mb-2"
}
const _hoisted_228 = { class: "table-responsive admin-table-scroll" }
const _hoisted_229 = { class: "table table-sm table-striped table-hover small" }
const _hoisted_230 = { class: "text-nowrap" }
const _hoisted_231 = ["title"]
const _hoisted_232 = {
  key: 3,
  class: "d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2 small"
}
const _hoisted_233 = { class: "text-muted" }
const _hoisted_234 = { class: "d-flex align-items-center gap-2" }
const _hoisted_235 = ["onUpdate:modelValue", "onChange"]
const _hoisted_236 = ["value"]
const _hoisted_237 = ["onClick", "disabled"]
const _hoisted_238 = ["onClick", "disabled"]
const _hoisted_239 = { key: 6 }
const _hoisted_240 = {
  key: 0,
  class: "text-center text-muted"
}
const _hoisted_241 = { key: 1 }
const _hoisted_242 = { class: "table table-sm table-bordered" }
const _hoisted_243 = { class: "badge bg-success" }
const _hoisted_244 = { class: "small text-muted p-2 bg-light border rounded admin-config-box" }

return function render(_ctx, _cache) {
  with (_ctx) {
    const { toDisplayString: _toDisplayString, createElementVNode: _createElementVNode, createTextVNode: _createTextVNode, openBlock: _openBlock, createElementBlock: _createElementBlock, createCommentVNode: _createCommentVNode, renderList: _renderList, Fragment: _Fragment, withModifiers: _withModifiers, normalizeClass: _normalizeClass, vModelSelect: _vModelSelect, withDirectives: _withDirectives, vModelText: _vModelText, vModelCheckbox: _vModelCheckbox } = _Vue

    return (_openBlock(), _createElementBlock("div", _hoisted_1, [
      _createElementVNode("div", _hoisted_2, [
        _createElementVNode("div", _hoisted_3, [
          _createElementVNode("div", _hoisted_4, [
            _createElementVNode("h5", _hoisted_5, _toDisplayString(t('admin_panel', 'Admin Panel')), 1 /* TEXT */),
            _cache[0] || (_cache[0] = _createElementVNode("button", {
              type: "button",
              class: "btn-close",
              "data-bs-dismiss": "modal",
              "aria-label": "Close"
            }, null, -1 /* CACHED */))
          ]),
          _createElementVNode("div", _hoisted_6, [
            quickAdmin
              ? (_openBlock(), _createElementBlock("div", _hoisted_7, [
                  _createElementVNode("div", null, [
                    _createElementVNode("strong", null, _toDisplayString(t('quick_admin', 'Quick Admin')), 1 /* TEXT */),
                    _createElementVNode("div", _hoisted_8, _toDisplayString(t('quick_admin_desc', 'For full settings, logs, and system tools, use the admin console.')), 1 /* TEXT */)
                  ]),
                  _createElementVNode("a", {
                    class: "btn btn-sm btn-primary",
                    href: adminConsoleUrl
                  }, [
                    _cache[1] || (_cache[1] = _createElementVNode("i", { class: "ri-external-link-line me-1" }, null, -1 /* CACHED */)),
                    _createTextVNode(" " + _toDisplayString(t('admin_open_console', 'Open Admin Console')), 1 /* TEXT */)
                  ], 8 /* PROPS */, _hoisted_9)
                ]))
              : _createCommentVNode("v-if", true),
            quickAdmin
              ? (_openBlock(), _createElementBlock("div", _hoisted_10, [
                  _createElementVNode("div", _hoisted_11, [
                    _createElementVNode("div", _hoisted_12, [
                      _createElementVNode("div", _hoisted_13, [
                        _createElementVNode("div", _hoisted_14, _toDisplayString(t('admin_nav_users', 'Users')), 1 /* TEXT */),
                        _createElementVNode("div", _hoisted_15, _toDisplayString(users.length), 1 /* TEXT */),
                        _createElementVNode("a", {
                          class: "small",
                          href: consoleUrl('users')
                        }, _toDisplayString(t('quick_admin_manage_users', 'Manage users')), 9 /* TEXT, PROPS */, _hoisted_16)
                      ])
                    ]),
                    _createElementVNode("div", _hoisted_17, [
                      _createElementVNode("div", _hoisted_18, [
                        _createElementVNode("div", _hoisted_19, _toDisplayString(t('admin_nav_groups', 'Groups')), 1 /* TEXT */),
                        _createElementVNode("div", _hoisted_20, _toDisplayString(Object.keys(groupsList).length), 1 /* TEXT */),
                        _createElementVNode("a", {
                          class: "small",
                          href: consoleUrl('groups')
                        }, _toDisplayString(t('quick_admin_manage_groups', 'Manage groups')), 9 /* TEXT, PROPS */, _hoisted_21)
                      ])
                    ]),
                    _createElementVNode("div", _hoisted_22, [
                      _createElementVNode("div", _hoisted_23, [
                        _createElementVNode("div", _hoisted_24, _toDisplayString(t('admin_nav_roles', 'Roles')), 1 /* TEXT */),
                        _createElementVNode("div", _hoisted_25, _toDisplayString(Object.keys(rolesList).length), 1 /* TEXT */),
                        _createElementVNode("a", {
                          class: "small",
                          href: consoleUrl('roles')
                        }, _toDisplayString(t('quick_admin_manage_roles', 'Manage roles')), 9 /* TEXT, PROPS */, _hoisted_26)
                      ])
                    ])
                  ]),
                  _createElementVNode("div", _hoisted_27, [
                    _createElementVNode("a", {
                      class: "btn btn-outline-primary btn-sm",
                      href: consoleUrl('users')
                    }, [
                      _cache[2] || (_cache[2] = _createElementVNode("i", {
                        class: "ri-user-settings-line me-1",
                        "aria-hidden": "true"
                      }, null, -1 /* CACHED */)),
                      _createTextVNode(_toDisplayString(t('quick_admin_open_users', 'Open user administration')), 1 /* TEXT */)
                    ], 8 /* PROPS */, _hoisted_28),
                    _createElementVNode("a", {
                      class: "btn btn-outline-primary btn-sm",
                      href: consoleUrl('settings', 'sharing')
                    }, [
                      _cache[3] || (_cache[3] = _createElementVNode("i", {
                        class: "ri-share-line me-1",
                        "aria-hidden": "true"
                      }, null, -1 /* CACHED */)),
                      _createTextVNode(_toDisplayString(t('quick_admin_open_sharing', 'Open sharing settings')), 1 /* TEXT */)
                    ], 8 /* PROPS */, _hoisted_29),
                    _createElementVNode("a", {
                      class: "btn btn-outline-primary btn-sm",
                      href: consoleUrl('settings', 'mounts')
                    }, [
                      _cache[4] || (_cache[4] = _createElementVNode("i", {
                        class: "ri-hard-drive-2-line me-1",
                        "aria-hidden": "true"
                      }, null, -1 /* CACHED */)),
                      _createTextVNode(_toDisplayString(t('quick_admin_open_mounts', 'Open mount settings')), 1 /* TEXT */)
                    ], 8 /* PROPS */, _hoisted_30),
                    _createElementVNode("a", {
                      class: "btn btn-outline-secondary btn-sm",
                      href: consoleUrl('logs')
                    }, [
                      _cache[5] || (_cache[5] = _createElementVNode("i", {
                        class: "ri-file-list-3-line me-1",
                        "aria-hidden": "true"
                      }, null, -1 /* CACHED */)),
                      _createTextVNode(_toDisplayString(t('quick_admin_view_all_logs', 'View all audit logs')), 1 /* TEXT */)
                    ], 8 /* PROPS */, _hoisted_31)
                  ]),
                  _createElementVNode("div", _hoisted_32, [
                    _createElementVNode("div", _hoisted_33, [
                      _createElementVNode("div", _hoisted_34, _toDisplayString(t('quick_admin_recent_activity', 'Recent activity')), 1 /* TEXT */),
                      _createElementVNode("button", {
                        class: "btn btn-outline-secondary btn-sm",
                        type: "button",
                        onClick: loadLogs,
                        disabled: isLoadingLogs
                      }, [
                        _cache[6] || (_cache[6] = _createElementVNode("i", {
                          class: "ri-refresh-line me-1",
                          "aria-hidden": "true"
                        }, null, -1 /* CACHED */)),
                        _createTextVNode(_toDisplayString(t('refresh', 'Refresh')), 1 /* TEXT */)
                      ], 8 /* PROPS */, _hoisted_35)
                    ]),
                    isLoadingLogs
                      ? (_openBlock(), _createElementBlock("div", _hoisted_36, _toDisplayString(t('admin_logs_loading', 'Loading logs…')), 1 /* TEXT */))
                      : (logs.length === 0)
                        ? (_openBlock(), _createElementBlock("div", _hoisted_37, [
                            _cache[7] || (_cache[7] = _createElementVNode("i", {
                              class: "ri-inbox-line d-block fs-3 mb-2",
                              "aria-hidden": "true"
                            }, null, -1 /* CACHED */)),
                            _createTextVNode(" " + _toDisplayString(t('admin_logs_empty_unfiltered', 'No audit activity has been recorded yet.')), 1 /* TEXT */)
                          ]))
                        : (_openBlock(), _createElementBlock("div", _hoisted_38, [
                            _createElementVNode("table", _hoisted_39, [
                              _createElementVNode("thead", _hoisted_40, [
                                _createElementVNode("tr", null, [
                                  _createElementVNode("th", null, _toDisplayString(t('admin_logs_col_date', 'Date')), 1 /* TEXT */),
                                  _createElementVNode("th", null, _toDisplayString(t('admin_logs_col_user', 'User')), 1 /* TEXT */),
                                  _createElementVNode("th", null, _toDisplayString(t('admin_logs_col_action', 'Action')), 1 /* TEXT */),
                                  _createElementVNode("th", null, _toDisplayString(t('admin_logs_col_path', 'Path')), 1 /* TEXT */)
                                ])
                              ]),
                              _createElementVNode("tbody", null, [
                                (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(logs.slice(0, 8), (log) => {
                                  return (_openBlock(), _createElementBlock("tr", { key: log.timestamp + log.action + log.path }, [
                                    _createElementVNode("td", _hoisted_41, _toDisplayString(formatDate(log.timestamp)), 1 /* TEXT */),
                                    _createElementVNode("td", null, _toDisplayString(log.user), 1 /* TEXT */),
                                    _createElementVNode("td", null, [
                                      _createElementVNode("strong", null, _toDisplayString(log.action), 1 /* TEXT */)
                                    ]),
                                    _createElementVNode("td", {
                                      class: "text-truncate admin-log-path",
                                      title: log.path
                                    }, _toDisplayString(log.path), 9 /* TEXT, PROPS */, _hoisted_42)
                                  ]))
                                }), 128 /* KEYED_FRAGMENT */))
                              ])
                            ])
                          ]))
                  ])
                ]))
              : (_openBlock(), _createElementBlock(_Fragment, { key: 2 }, [
                  _createElementVNode("ul", _hoisted_43, [
                    _createElementVNode("li", _hoisted_44, [
                      _createElementVNode("a", {
                        class: _normalizeClass(["nav-link", {active: tab === 'users'}]),
                        href: "#",
                        onClick: _withModifiers($event => (tab = 'users'), ["prevent"])
                      }, _toDisplayString(t('admin_nav_users', 'Users')), 11 /* TEXT, CLASS, PROPS */, _hoisted_45)
                    ]),
                    _createElementVNode("li", _hoisted_46, [
                      _createElementVNode("a", {
                        class: _normalizeClass(["nav-link", {active: tab === 'logs'}]),
                        href: "#",
                        onClick: _withModifiers(loadLogsTab, ["prevent"])
                      }, _toDisplayString(t('admin_nav_logs', 'Logs')), 11 /* TEXT, CLASS, PROPS */, _hoisted_47)
                    ])
                  ]),
                  error
                    ? (_openBlock(), _createElementBlock("div", _hoisted_48, _toDisplayString(error), 1 /* TEXT */))
                    : _createCommentVNode("v-if", true),
                  _createCommentVNode(" Settings Tab "),
                  (tab === 'settings')
                    ? (_openBlock(), _createElementBlock("div", _hoisted_49, [
                        (!settings)
                          ? (_openBlock(), _createElementBlock("div", _hoisted_50, "Loading..."))
                          : (_openBlock(), _createElementBlock("div", _hoisted_51, [
                              _createElementVNode("ul", _hoisted_52, [
                                _createElementVNode("li", _hoisted_53, [
                                  _createElementVNode("a", {
                                    class: _normalizeClass(["nav-link", {active: settingsTab === 'email'}]),
                                    href: "#",
                                    onClick: _withModifiers($event => (settingsTab = 'email'), ["prevent"])
                                  }, "Email", 10 /* CLASS, PROPS */, _hoisted_54)
                                ]),
                                _createElementVNode("li", _hoisted_55, [
                                  _createElementVNode("a", {
                                    class: _normalizeClass(["nav-link", {active: settingsTab === 'transfers'}]),
                                    href: "#",
                                    onClick: _withModifiers($event => (settingsTab = 'transfers'), ["prevent"])
                                  }, "Transfers", 10 /* CLASS, PROPS */, _hoisted_56)
                                ]),
                                _createElementVNode("li", _hoisted_57, [
                                  _createElementVNode("a", {
                                    class: _normalizeClass(["nav-link", {active: settingsTab === 'sharing'}]),
                                    href: "#",
                                    onClick: _withModifiers($event => (settingsTab = 'sharing'), ["prevent"])
                                  }, "Sharing", 10 /* CLASS, PROPS */, _hoisted_58)
                                ]),
                                _createElementVNode("li", _hoisted_59, [
                                  _createElementVNode("a", {
                                    class: _normalizeClass(["nav-link", {active: settingsTab === 'mounts'}]),
                                    href: "#",
                                    onClick: _withModifiers($event => (settingsTab = 'mounts'), ["prevent"])
                                  }, "Mounts", 10 /* CLASS, PROPS */, _hoisted_60)
                                ]),
                                _createElementVNode("li", _hoisted_61, [
                                  _createElementVNode("a", {
                                    class: _normalizeClass(["nav-link", {active: settingsTab === 'logging'}]),
                                    href: "#",
                                    onClick: _withModifiers($event => (settingsTab = 'logging'), ["prevent"])
                                  }, "Logging", 10 /* CLASS, PROPS */, _hoisted_62)
                                ]),
                                _createElementVNode("li", _hoisted_63, [
                                  _createElementVNode("a", {
                                    class: _normalizeClass(["nav-link", {active: settingsTab === 'governance'}]),
                                    href: "#",
                                    onClick: _withModifiers($event => (settingsTab = 'governance'), ["prevent"])
                                  }, "Governance", 10 /* CLASS, PROPS */, _hoisted_64)
                                ]),
                                _createElementVNode("li", _hoisted_65, [
                                  _createElementVNode("a", {
                                    class: _normalizeClass(["nav-link", {active: settingsTab === 'security'}]),
                                    href: "#",
                                    onClick: _withModifiers($event => (settingsTab = 'security'), ["prevent"])
                                  }, "Security", 10 /* CLASS, PROPS */, _hoisted_66)
                                ])
                              ]),
                              _createElementVNode("form", {
                                onSubmit: _withModifiers(saveSettings, ["prevent"])
                              }, [
                                (settingsTab === 'email')
                                  ? (_openBlock(), _createElementBlock("div", _hoisted_68, [
                                      _cache[21] || (_cache[21] = _createElementVNode("h6", { class: "border-bottom pb-2 mb-3" }, "Email Configuration", -1 /* CACHED */)),
                                      (settings && settings.email_configured === false)
                                        ? (_openBlock(), _createElementBlock("div", _hoisted_69, " Email sending is currently disabled because the configuration is incomplete. "))
                                        : _createCommentVNode("v-if", true),
                                      (emailValidation.message)
                                        ? (_openBlock(), _createElementBlock("div", {
                                            key: 1,
                                            class: _normalizeClass(["alert small", emailValidation.ok ? 'alert-success' : 'alert-danger'])
                                          }, _toDisplayString(emailValidation.message), 3 /* TEXT, CLASS */))
                                        : _createCommentVNode("v-if", true),
                                      _createElementVNode("div", _hoisted_70, [
                                        _createElementVNode("div", _hoisted_71, [
                                          _cache[9] || (_cache[9] = _createElementVNode("label", { class: "form-label small fw-bold" }, "Protocol", -1 /* CACHED */)),
                                          _withDirectives(_createElementVNode("select", {
                                            class: "form-select form-select-sm",
                                            "onUpdate:modelValue": $event => ((settings.email_protocol) = $event)
                                          }, [...(_cache[8] || (_cache[8] = [
                                            _createElementVNode("option", { value: "smtp" }, "SMTP", -1 /* CACHED */),
                                            _createElementVNode("option", { value: "sendmail" }, "Sendmail", -1 /* CACHED */),
                                            _createElementVNode("option", { value: "mail" }, "PHP mail()", -1 /* CACHED */)
                                          ]))], 8 /* PROPS */, _hoisted_72), [
                                            [_vModelSelect, settings.email_protocol]
                                          ])
                                        ]),
                                        (settings.email_protocol === 'sendmail')
                                          ? (_openBlock(), _createElementBlock("div", _hoisted_73, [
                                              _cache[10] || (_cache[10] = _createElementVNode("label", { class: "form-label small fw-bold" }, "Sendmail Path", -1 /* CACHED */)),
                                              _withDirectives(_createElementVNode("input", {
                                                type: "text",
                                                class: "form-control form-control-sm",
                                                "onUpdate:modelValue": $event => ((settings.sendmail_path) = $event),
                                                placeholder: "/usr/sbin/sendmail"
                                              }, null, 8 /* PROPS */, _hoisted_74), [
                                                [_vModelText, settings.sendmail_path]
                                              ])
                                            ]))
                                          : _createCommentVNode("v-if", true)
                                      ]),
                                      (settings.email_protocol === 'smtp')
                                        ? (_openBlock(), _createElementBlock("div", _hoisted_75, [
                                            _createElementVNode("div", _hoisted_76, [
                                              _cache[11] || (_cache[11] = _createElementVNode("label", { class: "form-label small fw-bold" }, "SMTP Host", -1 /* CACHED */)),
                                              _withDirectives(_createElementVNode("input", {
                                                type: "text",
                                                class: "form-control form-control-sm",
                                                "onUpdate:modelValue": $event => ((settings.smtp_host) = $event),
                                                placeholder: "smtp.example.com"
                                              }, null, 8 /* PROPS */, _hoisted_77), [
                                                [_vModelText, settings.smtp_host]
                                              ])
                                            ]),
                                            _createElementVNode("div", _hoisted_78, [
                                              _cache[12] || (_cache[12] = _createElementVNode("label", { class: "form-label small fw-bold" }, "Port", -1 /* CACHED */)),
                                              _withDirectives(_createElementVNode("input", {
                                                type: "number",
                                                class: "form-control form-control-sm",
                                                "onUpdate:modelValue": $event => ((settings.smtp_port) = $event),
                                                placeholder: "587"
                                              }, null, 8 /* PROPS */, _hoisted_79), [
                                                [_vModelText, settings.smtp_port]
                                              ])
                                            ]),
                                            _createElementVNode("div", _hoisted_80, [
                                              _cache[13] || (_cache[13] = _createElementVNode("label", { class: "form-label small fw-bold" }, "Username", -1 /* CACHED */)),
                                              _withDirectives(_createElementVNode("input", {
                                                type: "text",
                                                class: "form-control form-control-sm",
                                                "onUpdate:modelValue": $event => ((settings.smtp_user) = $event)
                                              }, null, 8 /* PROPS */, _hoisted_81), [
                                                [_vModelText, settings.smtp_user]
                                              ])
                                            ]),
                                            _createElementVNode("div", _hoisted_82, [
                                              _cache[14] || (_cache[14] = _createElementVNode("label", { class: "form-label small fw-bold" }, "Password", -1 /* CACHED */)),
                                              _withDirectives(_createElementVNode("input", {
                                                type: "password",
                                                class: "form-control form-control-sm",
                                                "onUpdate:modelValue": $event => ((settings.smtp_pass) = $event),
                                                placeholder: "********"
                                              }, null, 8 /* PROPS */, _hoisted_83), [
                                                [_vModelText, settings.smtp_pass]
                                              ])
                                            ]),
                                            _createElementVNode("div", _hoisted_84, [
                                              _cache[16] || (_cache[16] = _createElementVNode("label", { class: "form-label small fw-bold" }, "Encryption", -1 /* CACHED */)),
                                              _withDirectives(_createElementVNode("select", {
                                                class: "form-select form-select-sm",
                                                "onUpdate:modelValue": $event => ((settings.smtp_crypto) = $event)
                                              }, [...(_cache[15] || (_cache[15] = [
                                                _createElementVNode("option", { value: "tls" }, "TLS", -1 /* CACHED */),
                                                _createElementVNode("option", { value: "ssl" }, "SSL", -1 /* CACHED */),
                                                _createElementVNode("option", { value: "" }, "None", -1 /* CACHED */)
                                              ]))], 8 /* PROPS */, _hoisted_85), [
                                                [_vModelSelect, settings.smtp_crypto]
                                              ])
                                            ]),
                                            _createElementVNode("div", _hoisted_86, [
                                              _cache[17] || (_cache[17] = _createElementVNode("label", { class: "form-label small fw-bold" }, "From Email", -1 /* CACHED */)),
                                              _withDirectives(_createElementVNode("input", {
                                                type: "email",
                                                class: "form-control form-control-sm",
                                                "onUpdate:modelValue": $event => ((settings.email_from) = $event)
                                              }, null, 8 /* PROPS */, _hoisted_87), [
                                                [_vModelText, settings.email_from]
                                              ])
                                            ]),
                                            _createElementVNode("div", _hoisted_88, [
                                              _cache[18] || (_cache[18] = _createElementVNode("label", { class: "form-label small fw-bold" }, "From Name", -1 /* CACHED */)),
                                              _withDirectives(_createElementVNode("input", {
                                                type: "text",
                                                class: "form-control form-control-sm",
                                                "onUpdate:modelValue": $event => ((settings.email_from_name) = $event)
                                              }, null, 8 /* PROPS */, _hoisted_89), [
                                                [_vModelText, settings.email_from_name]
                                              ])
                                            ])
                                          ]))
                                        : _createCommentVNode("v-if", true),
                                      (settings.email_protocol !== 'smtp')
                                        ? (_openBlock(), _createElementBlock("div", _hoisted_90, [
                                            _createElementVNode("div", _hoisted_91, [
                                              _cache[19] || (_cache[19] = _createElementVNode("label", { class: "form-label small fw-bold" }, "From Email", -1 /* CACHED */)),
                                              _withDirectives(_createElementVNode("input", {
                                                type: "email",
                                                class: "form-control form-control-sm",
                                                "onUpdate:modelValue": $event => ((settings.email_from) = $event)
                                              }, null, 8 /* PROPS */, _hoisted_92), [
                                                [_vModelText, settings.email_from]
                                              ])
                                            ]),
                                            _createElementVNode("div", _hoisted_93, [
                                              _cache[20] || (_cache[20] = _createElementVNode("label", { class: "form-label small fw-bold" }, "From Name", -1 /* CACHED */)),
                                              _withDirectives(_createElementVNode("input", {
                                                type: "text",
                                                class: "form-control form-control-sm",
                                                "onUpdate:modelValue": $event => ((settings.email_from_name) = $event)
                                              }, null, 8 /* PROPS */, _hoisted_94), [
                                                [_vModelText, settings.email_from_name]
                                              ])
                                            ])
                                          ]))
                                        : _createCommentVNode("v-if", true)
                                    ]))
                                  : _createCommentVNode("v-if", true),
                                (settingsTab === 'transfers')
                                  ? (_openBlock(), _createElementBlock("div", _hoisted_95, [
                                      _cache[27] || (_cache[27] = _createElementVNode("h6", { class: "border-bottom pb-2 mb-3" }, "Transfers", -1 /* CACHED */)),
                                      _createElementVNode("div", _hoisted_96, [
                                        _createElementVNode("div", _hoisted_97, [
                                          _cache[22] || (_cache[22] = _createElementVNode("label", { class: "form-label small fw-bold" }, "Default Expiry (Days)", -1 /* CACHED */)),
                                          _withDirectives(_createElementVNode("input", {
                                            type: "number",
                                            min: "1",
                                            class: "form-control form-control-sm",
                                            "onUpdate:modelValue": $event => ((settings.default_transfer_expiry) = $event)
                                          }, null, 8 /* PROPS */, _hoisted_98), [
                                            [
                                              _vModelText,
                                              settings.default_transfer_expiry,
                                              void 0,
                                              { number: true }
                                            ]
                                          ]),
                                          _cache[23] || (_cache[23] = _createElementVNode("div", { class: "form-text" }, "Used when no expiry is provided.", -1 /* CACHED */))
                                        ]),
                                        _createElementVNode("div", _hoisted_99, [
                                          _cache[24] || (_cache[24] = _createElementVNode("label", { class: "form-label small fw-bold" }, "Max Expiry (Days)", -1 /* CACHED */)),
                                          _withDirectives(_createElementVNode("input", {
                                            type: "number",
                                            min: "1",
                                            max: "365",
                                            class: "form-control form-control-sm",
                                            "onUpdate:modelValue": $event => ((settings.transfer_max_expiry_days) = $event)
                                          }, null, 8 /* PROPS */, _hoisted_100), [
                                            [
                                              _vModelText,
                                              settings.transfer_max_expiry_days,
                                              void 0,
                                              { number: true }
                                            ]
                                          ]),
                                          _cache[25] || (_cache[25] = _createElementVNode("div", { class: "form-text" }, "Upper bound for all transfers.", -1 /* CACHED */))
                                        ]),
                                        _createElementVNode("div", _hoisted_101, [
                                          _createElementVNode("div", _hoisted_102, [
                                            _withDirectives(_createElementVNode("input", {
                                              class: "form-check-input",
                                              type: "checkbox",
                                              id: "transferNotifyDownload",
                                              "onUpdate:modelValue": $event => ((settings.transfer_default_notify_download) = $event)
                                            }, null, 8 /* PROPS */, _hoisted_103), [
                                              [_vModelCheckbox, settings.transfer_default_notify_download]
                                            ]),
                                            _cache[26] || (_cache[26] = _createElementVNode("label", {
                                              class: "form-check-label small fw-bold",
                                              for: "transferNotifyDownload"
                                            }, "Notify On Download (Default)", -1 /* CACHED */))
                                          ])
                                        ])
                                      ])
                                    ]))
                                  : _createCommentVNode("v-if", true),
                                (settingsTab === 'sharing')
                                  ? (_openBlock(), _createElementBlock("div", _hoisted_104, [
                                      _cache[34] || (_cache[34] = _createElementVNode("h6", { class: "border-bottom pb-2 mb-3" }, "Share Links", -1 /* CACHED */)),
                                      _createElementVNode("div", _hoisted_105, [
                                        _createElementVNode("div", _hoisted_106, [
                                          _cache[28] || (_cache[28] = _createElementVNode("label", { class: "form-label small fw-bold" }, "Default Expiry (Days)", -1 /* CACHED */)),
                                          _withDirectives(_createElementVNode("input", {
                                            type: "number",
                                            min: "1",
                                            max: "365",
                                            class: "form-control form-control-sm",
                                            "onUpdate:modelValue": $event => ((settings.share_default_expiry_days) = $event)
                                          }, null, 8 /* PROPS */, _hoisted_107), [
                                            [
                                              _vModelText,
                                              settings.share_default_expiry_days,
                                              void 0,
                                              { number: true }
                                            ]
                                          ]),
                                          _cache[29] || (_cache[29] = _createElementVNode("div", { class: "form-text" }, "Used when expiry is required but not provided.", -1 /* CACHED */))
                                        ]),
                                        _createElementVNode("div", _hoisted_108, [
                                          _cache[30] || (_cache[30] = _createElementVNode("label", { class: "form-label small fw-bold" }, "Max Expiry (Days)", -1 /* CACHED */)),
                                          _withDirectives(_createElementVNode("input", {
                                            type: "number",
                                            min: "1",
                                            max: "365",
                                            class: "form-control form-control-sm",
                                            "onUpdate:modelValue": $event => ((settings.share_max_expiry_days) = $event)
                                          }, null, 8 /* PROPS */, _hoisted_109), [
                                            [
                                              _vModelText,
                                              settings.share_max_expiry_days,
                                              void 0,
                                              { number: true }
                                            ]
                                          ]),
                                          _cache[31] || (_cache[31] = _createElementVNode("div", { class: "form-text" }, "Upper bound for all share links.", -1 /* CACHED */))
                                        ]),
                                        _createElementVNode("div", _hoisted_110, [
                                          _createElementVNode("div", _hoisted_111, [
                                            _withDirectives(_createElementVNode("input", {
                                              class: "form-check-input",
                                              type: "checkbox",
                                              id: "shareRequireExpiry",
                                              "onUpdate:modelValue": $event => ((settings.share_require_expiry) = $event)
                                            }, null, 8 /* PROPS */, _hoisted_112), [
                                              [_vModelCheckbox, settings.share_require_expiry]
                                            ]),
                                            _cache[32] || (_cache[32] = _createElementVNode("label", {
                                              class: "form-check-label small fw-bold",
                                              for: "shareRequireExpiry"
                                            }, "Require Expiry", -1 /* CACHED */))
                                          ])
                                        ]),
                                        _createElementVNode("div", _hoisted_113, [
                                          _createElementVNode("div", _hoisted_114, [
                                            _withDirectives(_createElementVNode("input", {
                                              class: "form-check-input",
                                              type: "checkbox",
                                              id: "shareRequirePassword",
                                              "onUpdate:modelValue": $event => ((settings.share_require_password) = $event)
                                            }, null, 8 /* PROPS */, _hoisted_115), [
                                              [_vModelCheckbox, settings.share_require_password]
                                            ]),
                                            _cache[33] || (_cache[33] = _createElementVNode("label", {
                                              class: "form-check-label small fw-bold",
                                              for: "shareRequirePassword"
                                            }, "Require Password", -1 /* CACHED */))
                                          ])
                                        ])
                                      ])
                                    ]))
                                  : _createCommentVNode("v-if", true),
                                (settingsTab === 'mounts')
                                  ? (_openBlock(), _createElementBlock("div", _hoisted_116, [
                                      _cache[39] || (_cache[39] = _createElementVNode("h6", { class: "border-bottom pb-2 mb-3" }, "External Mounts", -1 /* CACHED */)),
                                      _createElementVNode("div", _hoisted_117, [
                                        _createElementVNode("div", _hoisted_118, [
                                          _cache[35] || (_cache[35] = _createElementVNode("label", { class: "form-label small fw-bold" }, "Allowlist (one path per line)", -1 /* CACHED */)),
                                          _withDirectives(_createElementVNode("textarea", {
                                            class: "form-control form-control-sm",
                                            rows: "4",
                                            "onUpdate:modelValue": $event => ((settings.mount_root_allowlist_text) = $event),
                                            placeholder: "/srv/data\n/mnt/storage"
                                          }, null, 8 /* PROPS */, _hoisted_119), [
                                            [_vModelText, settings.mount_root_allowlist_text]
                                          ]),
                                          _cache[36] || (_cache[36] = _createElementVNode("div", { class: "form-text" }, "Only paths under these roots can be mounted. Leave empty to disable external mounts.", -1 /* CACHED */))
                                        ]),
                                        _createElementVNode("div", _hoisted_120, [
                                          _cache[37] || (_cache[37] = _createElementVNode("label", { class: "form-label small fw-bold" }, "Remote Host Allowlist (one entry per line)", -1 /* CACHED */)),
                                          _withDirectives(_createElementVNode("textarea", {
                                            class: "form-control form-control-sm",
                                            rows: "4",
                                            "onUpdate:modelValue": $event => ((settings.mount_remote_host_allowlist_text) = $event),
                                            placeholder: "files.example.com\n10.0.0.0/8\n*.corp.example"
                                          }, null, 8 /* PROPS */, _hoisted_121), [
                                            [_vModelText, settings.mount_remote_host_allowlist_text]
                                          ]),
                                          _cache[38] || (_cache[38] = _createElementVNode("div", { class: "form-text" }, "Allow hostnames, IPs, CIDR ranges, and wildcard domains. Leave empty to block private/reserved targets only.", -1 /* CACHED */))
                                        ])
                                      ])
                                    ]))
                                  : _createCommentVNode("v-if", true),
                                (settingsTab === 'logging')
                                  ? (_openBlock(), _createElementBlock("div", _hoisted_122, [
                                      _cache[42] || (_cache[42] = _createElementVNode("h6", { class: "border-bottom pb-2 mb-3" }, "Audit Logging", -1 /* CACHED */)),
                                      _createElementVNode("div", _hoisted_123, [
                                        _createElementVNode("div", _hoisted_124, [
                                          _cache[40] || (_cache[40] = _createElementVNode("label", { class: "form-label small fw-bold" }, "Retention Count", -1 /* CACHED */)),
                                          _withDirectives(_createElementVNode("input", {
                                            type: "number",
                                            min: "100",
                                            max: "20000",
                                            step: "100",
                                            class: "form-control form-control-sm",
                                            "onUpdate:modelValue": $event => ((settings.log_retention_count) = $event)
                                          }, null, 8 /* PROPS */, _hoisted_125), [
                                            [
                                              _vModelText,
                                              settings.log_retention_count,
                                              void 0,
                                              { number: true }
                                            ]
                                          ]),
                                          _cache[41] || (_cache[41] = _createElementVNode("div", { class: "form-text" }, "How many recent audit log entries to keep (100-20000).", -1 /* CACHED */))
                                        ])
                                      ])
                                    ]))
                                  : _createCommentVNode("v-if", true),
                                (settingsTab === 'governance')
                                  ? (_openBlock(), _createElementBlock("div", _hoisted_126, [
                                      _cache[47] || (_cache[47] = _createElementVNode("h6", { class: "border-bottom pb-2 mb-3" }, "Uploads & Quotas", -1 /* CACHED */)),
                                      _createElementVNode("div", _hoisted_127, [
                                        _createElementVNode("div", _hoisted_128, [
                                          _cache[43] || (_cache[43] = _createElementVNode("label", { class: "form-label small fw-bold" }, "Max Upload Size (MB)", -1 /* CACHED */)),
                                          _withDirectives(_createElementVNode("input", {
                                            type: "number",
                                            min: "0",
                                            max: "10240",
                                            class: "form-control form-control-sm",
                                            "onUpdate:modelValue": $event => ((settings.upload_max_file_mb) = $event)
                                          }, null, 8 /* PROPS */, _hoisted_129), [
                                            [
                                              _vModelText,
                                              settings.upload_max_file_mb,
                                              void 0,
                                              { number: true }
                                            ]
                                          ]),
                                          _cache[44] || (_cache[44] = _createElementVNode("div", { class: "form-text" }, "0 disables the limit. Applies to single and chunked uploads.", -1 /* CACHED */))
                                        ]),
                                        _createElementVNode("div", _hoisted_130, [
                                          _cache[45] || (_cache[45] = _createElementVNode("label", { class: "form-label small fw-bold" }, "Per-User Quota (MB)", -1 /* CACHED */)),
                                          _withDirectives(_createElementVNode("input", {
                                            type: "number",
                                            min: "0",
                                            max: "102400",
                                            class: "form-control form-control-sm",
                                            "onUpdate:modelValue": $event => ((settings.quota_per_user_mb) = $event)
                                          }, null, 8 /* PROPS */, _hoisted_131), [
                                            [
                                              _vModelText,
                                              settings.quota_per_user_mb,
                                              void 0,
                                              { number: true }
                                            ]
                                          ]),
                                          _cache[46] || (_cache[46] = _createElementVNode("div", { class: "form-text" }, "0 disables the quota. Applies to the user home directory.", -1 /* CACHED */))
                                        ])
                                      ])
                                    ]))
                                  : _createCommentVNode("v-if", true),
                                (settingsTab === 'security')
                                  ? (_openBlock(), _createElementBlock("div", _hoisted_132, [
                                      _cache[50] || (_cache[50] = _createElementVNode("h6", { class: "border-bottom pb-2 mb-3" }, "Session Security", -1 /* CACHED */)),
                                      _createElementVNode("div", _hoisted_133, [
                                        _createElementVNode("div", _hoisted_134, [
                                          _cache[48] || (_cache[48] = _createElementVNode("label", { class: "form-label small fw-bold" }, "Idle Timeout (Minutes)", -1 /* CACHED */)),
                                          _withDirectives(_createElementVNode("input", {
                                            type: "number",
                                            min: "0",
                                            max: "1440",
                                            class: "form-control form-control-sm",
                                            "onUpdate:modelValue": $event => ((settings.session_idle_timeout_minutes) = $event)
                                          }, null, 8 /* PROPS */, _hoisted_135), [
                                            [
                                              _vModelText,
                                              settings.session_idle_timeout_minutes,
                                              void 0,
                                              { number: true }
                                            ]
                                          ]),
                                          _cache[49] || (_cache[49] = _createElementVNode("div", { class: "form-text" }, "0 disables idle timeout. Applies to API and UI sessions.", -1 /* CACHED */))
                                        ])
                                      ])
                                    ]))
                                  : _createCommentVNode("v-if", true),
                                _createElementVNode("div", _hoisted_136, [
                                  settingsDirty
                                    ? (_openBlock(), _createElementBlock("div", _hoisted_137, " You have unsaved settings changes. "))
                                    : _createCommentVNode("v-if", true),
                                  (settingsTab === 'email')
                                    ? (_openBlock(), _createElementBlock("button", {
                                        key: 1,
                                        type: "button",
                                        class: "btn btn-outline-secondary btn-sm me-2",
                                        onClick: validateEmailSettings,
                                        disabled: !settings || isValidatingEmail
                                      }, _toDisplayString(isValidatingEmail ? 'Validating…' : 'Validate'), 9 /* TEXT, PROPS */, _hoisted_138))
                                    : _createCommentVNode("v-if", true),
                                  (settingsTab === 'email')
                                    ? (_openBlock(), _createElementBlock("button", {
                                        key: 2,
                                        type: "button",
                                        class: "btn btn-outline-secondary btn-sm me-2",
                                        onClick: testEmail,
                                        disabled: !settings || isTestingEmail
                                      }, _toDisplayString(isTestingEmail ? 'Sending…' : 'Send Test Email'), 9 /* TEXT, PROPS */, _hoisted_139))
                                    : _createCommentVNode("v-if", true),
                                  _createElementVNode("button", {
                                    type: "submit",
                                    class: "btn btn-primary btn-sm",
                                    disabled: !settingsDirty || isSavingSettings
                                  }, _toDisplayString(isSavingSettings ? 'Saving…' : 'Save Settings'), 9 /* TEXT, PROPS */, _hoisted_140)
                                ])
                              ], 40 /* PROPS, NEED_HYDRATION */, _hoisted_67)
                            ]))
                      ]))
                    : _createCommentVNode("v-if", true),
                  _createCommentVNode(" Users Tab "),
                  (tab === 'users')
                    ? (_openBlock(), _createElementBlock("div", _hoisted_141, [
                        _createElementVNode("table", _hoisted_142, [
                          _cache[53] || (_cache[53] = _createElementVNode("thead", null, [
                            _createElementVNode("tr", null, [
                              _createElementVNode("th", null, "Username"),
                              _createElementVNode("th", null, "Groups"),
                              _createElementVNode("th", null, "Home Dir"),
                              _createElementVNode("th", { class: "text-end" }, "Actions")
                            ])
                          ], -1 /* CACHED */)),
                          _createElementVNode("tbody", null, [
                            (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(users, (user) => {
                              return (_openBlock(), _createElementBlock("tr", { key: user.username }, [
                                _createElementVNode("td", null, _toDisplayString(user.username), 1 /* TEXT */),
                                _createElementVNode("td", null, [
                                  (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(user.groups, (g) => {
                                    return (_openBlock(), _createElementBlock("span", _hoisted_143, _toDisplayString(g), 1 /* TEXT */))
                                  }), 256 /* UNKEYED_FRAGMENT */))
                                ]),
                                _createElementVNode("td", null, _toDisplayString(user.home_dir), 1 /* TEXT */),
                                _createElementVNode("td", _hoisted_144, [
                                  _createElementVNode("button", {
                                    class: "btn btn-sm btn-outline-primary me-1",
                                    onClick: $event => (editUser(user)),
                                    "aria-label": 'Edit User: ' + user.username
                                  }, [...(_cache[51] || (_cache[51] = [
                                    _createElementVNode("i", {
                                      class: "ri-edit-line",
                                      "aria-hidden": "true"
                                    }, null, -1 /* CACHED */)
                                  ]))], 8 /* PROPS */, _hoisted_145),
                                  _createElementVNode("button", {
                                    class: "btn btn-sm btn-outline-danger",
                                    onClick: $event => (deleteUser(user)),
                                    disabled: user.username === currentUsername,
                                    "aria-label": 'Delete: ' + user.username
                                  }, [...(_cache[52] || (_cache[52] = [
                                    _createElementVNode("i", {
                                      class: "ri-delete-bin-line",
                                      "aria-hidden": "true"
                                    }, null, -1 /* CACHED */)
                                  ]))], 8 /* PROPS */, _hoisted_146)
                                ])
                              ]))
                            }), 128 /* KEYED_FRAGMENT */))
                          ])
                        ]),
                        _createElementVNode("button", {
                          class: "btn btn-success btn-sm",
                          onClick: showAddForm
                        }, [...(_cache[54] || (_cache[54] = [
                          _createElementVNode("i", { class: "ri-user-add-line" }, null, -1 /* CACHED */),
                          _createTextVNode(" Add User ", -1 /* CACHED */)
                        ]))], 8 /* PROPS */, _hoisted_147),
                        editingUser
                          ? (_openBlock(), _createElementBlock("div", _hoisted_148, [
                              _createElementVNode("div", _hoisted_149, [
                                _createElementVNode("h6", null, _toDisplayString(isNew ? 'Add User' : 'Edit User: ' + editingUser.username), 1 /* TEXT */),
                                _createElementVNode("div", _hoisted_150, [
                                  isNew
                                    ? (_openBlock(), _createElementBlock("div", _hoisted_151, [
                                        _cache[55] || (_cache[55] = _createElementVNode("label", {
                                          class: "form-label small",
                                          for: "quickAdminUserUsername"
                                        }, "Username", -1 /* CACHED */)),
                                        _withDirectives(_createElementVNode("input", {
                                          id: "quickAdminUserUsername",
                                          type: "text",
                                          class: "form-control form-control-sm",
                                          "onUpdate:modelValue": $event => ((editingUser.username) = $event),
                                          autocomplete: "username"
                                        }, null, 8 /* PROPS */, _hoisted_152), [
                                          [_vModelText, editingUser.username]
                                        ])
                                      ]))
                                    : _createCommentVNode("v-if", true),
                                  _createElementVNode("div", _hoisted_153, [
                                    _createElementVNode("label", _hoisted_154, "Password " + _toDisplayString(!isNew ? '(Leave blank to keep)' : ''), 1 /* TEXT */),
                                    _withDirectives(_createElementVNode("input", {
                                      id: "quickAdminUserPassword",
                                      type: "password",
                                      class: "form-control form-control-sm",
                                      "onUpdate:modelValue": $event => ((editingUser.password) = $event),
                                      autocomplete: "new-password"
                                    }, null, 8 /* PROPS */, _hoisted_155), [
                                      [_vModelText, editingUser.password]
                                    ])
                                  ]),
                                  _createElementVNode("div", _hoisted_156, [
                                    _cache[56] || (_cache[56] = _createElementVNode("label", {
                                      class: "form-label small",
                                      for: "quickAdminUserHome"
                                    }, "Home Dir", -1 /* CACHED */)),
                                    _withDirectives(_createElementVNode("input", {
                                      id: "quickAdminUserHome",
                                      type: "text",
                                      class: "form-control form-control-sm",
                                      "onUpdate:modelValue": $event => ((editingUser.home_dir) = $event)
                                    }, null, 8 /* PROPS */, _hoisted_157), [
                                      [_vModelText, editingUser.home_dir]
                                    ])
                                  ]),
                                  _createElementVNode("div", _hoisted_158, [
                                    _cache[57] || (_cache[57] = _createElementVNode("label", {
                                      class: "form-label small",
                                      for: "quickAdminUserAllowed"
                                    }, "Allowed Extensions (csv)", -1 /* CACHED */)),
                                    _withDirectives(_createElementVNode("input", {
                                      id: "quickAdminUserAllowed",
                                      type: "text",
                                      class: "form-control form-control-sm",
                                      "onUpdate:modelValue": $event => ((editingUser.allowed_extensions) = $event),
                                      placeholder: "e.g. jpg,png,pdf"
                                    }, null, 8 /* PROPS */, _hoisted_159), [
                                      [_vModelText, editingUser.allowed_extensions]
                                    ])
                                  ]),
                                  _createElementVNode("div", _hoisted_160, [
                                    _cache[59] || (_cache[59] = _createElementVNode("label", {
                                      class: "form-label small",
                                      for: "quickAdminUserBlocked"
                                    }, "Blocked Extensions (csv)", -1 /* CACHED */)),
                                    _withDirectives(_createElementVNode("input", {
                                      id: "quickAdminUserBlocked",
                                      type: "text",
                                      class: "form-control form-control-sm",
                                      "onUpdate:modelValue": $event => ((editingUser.blocked_extensions) = $event),
                                      placeholder: "e.g. php,exe"
                                    }, null, 8 /* PROPS */, _hoisted_161), [
                                      [_vModelText, editingUser.blocked_extensions]
                                    ]),
                                    (system && system.system_blocklist)
                                      ? (_openBlock(), _createElementBlock("div", _hoisted_162, [
                                          _cache[58] || (_cache[58] = _createElementVNode("span", { class: "small text-muted d-block admin-note" }, "System Blocklist (Always Applied):", -1 /* CACHED */)),
                                          (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(system.system_blocklist, (ext) => {
                                            return (_openBlock(), _createElementBlock("span", {
                                              key: ext,
                                              class: "badge bg-secondary-subtle text-secondary border border-secondary-subtle me-1 admin-badge"
                                            }, _toDisplayString(ext), 1 /* TEXT */))
                                          }), 128 /* KEYED_FRAGMENT */))
                                        ]))
                                      : _createCommentVNode("v-if", true)
                                  ]),
                                  _createElementVNode("div", _hoisted_163, [
                                    _cache[60] || (_cache[60] = _createElementVNode("label", { class: "form-label small d-block" }, "Groups", -1 /* CACHED */)),
                                    _createElementVNode("div", _hoisted_164, [
                                      (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(groupsList, (roles, gname) => {
                                        return (_openBlock(), _createElementBlock("div", {
                                          key: gname,
                                          class: "form-check small"
                                        }, [
                                          _withDirectives(_createElementVNode("input", {
                                            class: "form-check-input",
                                            type: "checkbox",
                                            id: 'chk_g_'+gname,
                                            value: gname,
                                            "onUpdate:modelValue": $event => ((editingUser.groups) = $event)
                                          }, null, 8 /* PROPS */, _hoisted_165), [
                                            [_vModelCheckbox, editingUser.groups]
                                          ]),
                                          _createElementVNode("label", {
                                            class: "form-check-label",
                                            for: 'chk_g_'+gname
                                          }, _toDisplayString(gname), 9 /* TEXT, PROPS */, _hoisted_166)
                                        ]))
                                      }), 128 /* KEYED_FRAGMENT */))
                                    ])
                                  ]),
                                  (!isNew)
                                    ? (_openBlock(), _createElementBlock("div", _hoisted_167, [
                                        _createElementVNode("div", _hoisted_168, [
                                          _cache[61] || (_cache[61] = _createElementVNode("label", { class: "form-label small fw-bold mb-1" }, "Effective Permissions", -1 /* CACHED */)),
                                          _createElementVNode("button", {
                                            class: "btn btn-outline-secondary btn-sm py-0 px-2",
                                            onClick: $event => (loadEffectivePermissions(editingUser.username)),
                                            disabled: effectivePermissions.loading
                                          }, _toDisplayString(effectivePermissions.loading ? '…' : 'Refresh'), 9 /* TEXT, PROPS */, _hoisted_169)
                                        ]),
                                        (effectivePermissions.loading)
                                          ? (_openBlock(), _createElementBlock("div", _hoisted_170, "Loading permissions…"))
                                          : (effectivePermissions.perms.length === 0)
                                            ? (_openBlock(), _createElementBlock("div", _hoisted_171, "No permissions resolved."))
                                            : (_openBlock(), _createElementBlock("div", _hoisted_172, [
                                                (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(effectivePermissions.perms, (perm) => {
                                                  return (_openBlock(), _createElementBlock("span", {
                                                    key: perm,
                                                    class: "badge bg-primary-subtle text-primary border border-primary-subtle admin-badge"
                                                  }, _toDisplayString(perm), 1 /* TEXT */))
                                                }), 128 /* KEYED_FRAGMENT */))
                                              ]))
                                      ]))
                                    : _createCommentVNode("v-if", true)
                                ]),
                                _createElementVNode("div", _hoisted_173, [
                                  _createElementVNode("button", {
                                    class: "btn btn-secondary btn-sm me-2",
                                    onClick: cancelEdit,
                                    disabled: isSavingUser
                                  }, "Cancel", 8 /* PROPS */, _hoisted_174),
                                  _createElementVNode("button", {
                                    class: "btn btn-primary btn-sm",
                                    onClick: saveUser,
                                    disabled: isSavingUser
                                  }, _toDisplayString(isSavingUser ? 'Saving…' : 'Save'), 9 /* TEXT, PROPS */, _hoisted_175)
                                ])
                              ])
                            ]))
                          : _createCommentVNode("v-if", true)
                      ]))
                    : _createCommentVNode("v-if", true),
                  _createCommentVNode(" Groups Tab "),
                  (tab === 'groups')
                    ? (_openBlock(), _createElementBlock("div", _hoisted_176, [
                        _createElementVNode("table", _hoisted_177, [
                          _cache[64] || (_cache[64] = _createElementVNode("thead", null, [
                            _createElementVNode("tr", null, [
                              _createElementVNode("th", null, "Group Name"),
                              _createElementVNode("th", null, "Assigned Roles"),
                              _createElementVNode("th", { class: "text-end" }, "Actions")
                            ])
                          ], -1 /* CACHED */)),
                          _createElementVNode("tbody", null, [
                            (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(groupsList, (roles, name) => {
                              return (_openBlock(), _createElementBlock("tr", { key: name }, [
                                _createElementVNode("td", null, _toDisplayString(name), 1 /* TEXT */),
                                _createElementVNode("td", null, [
                                  (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(roles, (r) => {
                                    return (_openBlock(), _createElementBlock("span", _hoisted_178, _toDisplayString(r), 1 /* TEXT */))
                                  }), 256 /* UNKEYED_FRAGMENT */))
                                ]),
                                _createElementVNode("td", _hoisted_179, [
                                  _createElementVNode("button", {
                                    class: "btn btn-sm btn-outline-primary me-1",
                                    onClick: $event => (editGroup(name, roles)),
                                    "aria-label": 'Group: ' + name
                                  }, [...(_cache[62] || (_cache[62] = [
                                    _createElementVNode("i", {
                                      class: "ri-edit-line",
                                      "aria-hidden": "true"
                                    }, null, -1 /* CACHED */)
                                  ]))], 8 /* PROPS */, _hoisted_180),
                                  _createElementVNode("button", {
                                    class: "btn btn-sm btn-outline-danger",
                                    onClick: $event => (deleteGroup(name)),
                                    "aria-label": 'Delete: ' + name
                                  }, [...(_cache[63] || (_cache[63] = [
                                    _createElementVNode("i", {
                                      class: "ri-delete-bin-line",
                                      "aria-hidden": "true"
                                    }, null, -1 /* CACHED */)
                                  ]))], 8 /* PROPS */, _hoisted_181)
                                ])
                              ]))
                            }), 128 /* KEYED_FRAGMENT */))
                          ])
                        ]),
                        _createElementVNode("button", {
                          class: "btn btn-success btn-sm",
                          onClick: showAddGroupForm
                        }, [...(_cache[65] || (_cache[65] = [
                          _createElementVNode("i", { class: "ri-add-line" }, null, -1 /* CACHED */),
                          _createTextVNode(" Add Group ", -1 /* CACHED */)
                        ]))], 8 /* PROPS */, _hoisted_182),
                        editingGroup
                          ? (_openBlock(), _createElementBlock("div", _hoisted_183, [
                              _createElementVNode("div", _hoisted_184, [
                                _createElementVNode("h6", null, "Group: " + _toDisplayString(editingGroup.name || 'New'), 1 /* TEXT */),
                                (!editingGroup.isEdit)
                                  ? _withDirectives((_openBlock(), _createElementBlock("input", {
                                      key: 0,
                                      type: "text",
                                      class: "form-control form-control-sm mb-2",
                                      placeholder: "Group Name",
                                      "onUpdate:modelValue": $event => ((editingGroup.name) = $event),
                                      "aria-label": "Group Name"
                                    }, null, 8 /* PROPS */, _hoisted_185)), [
                                      [_vModelText, editingGroup.name]
                                    ])
                                  : _createCommentVNode("v-if", true),
                                _cache[66] || (_cache[66] = _createElementVNode("label", { class: "small d-block mb-1" }, "Roles", -1 /* CACHED */)),
                                _createElementVNode("div", _hoisted_186, [
                                  (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(rolesList, (perms, rname) => {
                                    return (_openBlock(), _createElementBlock("div", {
                                      key: rname,
                                      class: "form-check small"
                                    }, [
                                      _withDirectives(_createElementVNode("input", {
                                        class: "form-check-input",
                                        type: "checkbox",
                                        id: 'chk_r_'+rname,
                                        value: rname,
                                        "onUpdate:modelValue": $event => ((editingGroup.roles) = $event)
                                      }, null, 8 /* PROPS */, _hoisted_187), [
                                        [_vModelCheckbox, editingGroup.roles]
                                      ]),
                                      _createElementVNode("label", {
                                        class: "form-check-label",
                                        for: 'chk_r_'+rname
                                      }, _toDisplayString(rname), 9 /* TEXT, PROPS */, _hoisted_188)
                                    ]))
                                  }), 128 /* KEYED_FRAGMENT */))
                                ]),
                                _createElementVNode("div", _hoisted_189, [
                                  _createElementVNode("button", {
                                    class: "btn btn-secondary btn-sm me-2",
                                    onClick: $event => (editingGroup = null),
                                    disabled: isSavingGroup
                                  }, "Cancel", 8 /* PROPS */, _hoisted_190),
                                  _createElementVNode("button", {
                                    class: "btn btn-primary btn-sm",
                                    onClick: saveGroup,
                                    disabled: isSavingGroup
                                  }, _toDisplayString(isSavingGroup ? 'Saving…' : 'Save'), 9 /* TEXT, PROPS */, _hoisted_191)
                                ])
                              ])
                            ]))
                          : _createCommentVNode("v-if", true)
                      ]))
                    : _createCommentVNode("v-if", true),
                  _createCommentVNode(" Roles Tab "),
                  (tab === 'roles')
                    ? (_openBlock(), _createElementBlock("div", _hoisted_192, [
                        _createElementVNode("table", _hoisted_193, [
                          _cache[69] || (_cache[69] = _createElementVNode("thead", null, [
                            _createElementVNode("tr", null, [
                              _createElementVNode("th", null, "Role Name"),
                              _createElementVNode("th", null, "Permissions"),
                              _createElementVNode("th", { class: "text-end" }, "Actions")
                            ])
                          ], -1 /* CACHED */)),
                          _createElementVNode("tbody", null, [
                            (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(rolesList, (perms, name) => {
                              return (_openBlock(), _createElementBlock("tr", { key: name }, [
                                _createElementVNode("td", null, _toDisplayString(name), 1 /* TEXT */),
                                _createElementVNode("td", null, [
                                  _createElementVNode("code", _hoisted_194, _toDisplayString(perms.join(', ')), 1 /* TEXT */)
                                ]),
                                _createElementVNode("td", _hoisted_195, [
                                  _createElementVNode("button", {
                                    class: "btn btn-sm btn-outline-primary me-1",
                                    onClick: $event => (editRole(name, perms)),
                                    "aria-label": 'Role: ' + name
                                  }, [...(_cache[67] || (_cache[67] = [
                                    _createElementVNode("i", {
                                      class: "ri-edit-line",
                                      "aria-hidden": "true"
                                    }, null, -1 /* CACHED */)
                                  ]))], 8 /* PROPS */, _hoisted_196),
                                  _createElementVNode("button", {
                                    class: "btn btn-sm btn-outline-danger",
                                    onClick: $event => (deleteRole(name)),
                                    "aria-label": 'Delete: ' + name
                                  }, [...(_cache[68] || (_cache[68] = [
                                    _createElementVNode("i", {
                                      class: "ri-delete-bin-line",
                                      "aria-hidden": "true"
                                    }, null, -1 /* CACHED */)
                                  ]))], 8 /* PROPS */, _hoisted_197)
                                ])
                              ]))
                            }), 128 /* KEYED_FRAGMENT */))
                          ])
                        ]),
                        _createElementVNode("button", {
                          class: "btn btn-success btn-sm",
                          onClick: showAddRoleForm
                        }, [...(_cache[70] || (_cache[70] = [
                          _createElementVNode("i", { class: "ri-add-line" }, null, -1 /* CACHED */),
                          _createTextVNode(" Add Role ", -1 /* CACHED */)
                        ]))], 8 /* PROPS */, _hoisted_198),
                        editingRole
                          ? (_openBlock(), _createElementBlock("div", _hoisted_199, [
                              _createElementVNode("div", _hoisted_200, [
                                _createElementVNode("h6", null, "Role: " + _toDisplayString(editingRole.name || 'New'), 1 /* TEXT */),
                                (!editingRole.isEdit)
                                  ? _withDirectives((_openBlock(), _createElementBlock("input", {
                                      key: 0,
                                      type: "text",
                                      class: "form-control form-control-sm mb-2",
                                      placeholder: "Role Name",
                                      "onUpdate:modelValue": $event => ((editingRole.name) = $event),
                                      "aria-label": "Role Name"
                                    }, null, 8 /* PROPS */, _hoisted_201)), [
                                      [_vModelText, editingRole.name]
                                    ])
                                  : _createCommentVNode("v-if", true),
                                _cache[72] || (_cache[72] = _createElementVNode("label", {
                                  class: "small d-block mb-1",
                                  for: "quickAdminRolePerms"
                                }, "Permissions (comma separated or *)", -1 /* CACHED */)),
                                _withDirectives(_createElementVNode("input", {
                                  id: "quickAdminRolePerms",
                                  type: "text",
                                  class: "form-control form-control-sm",
                                  "onUpdate:modelValue": $event => ((editingRole.permsString) = $event)
                                }, null, 8 /* PROPS */, _hoisted_202), [
                                  [_vModelText, editingRole.permsString]
                                ]),
                                (permissionCatalog.length)
                                  ? (_openBlock(), _createElementBlock("div", _hoisted_203, [
                                      _cache[71] || (_cache[71] = _createElementVNode("span", { class: "small text-muted d-block admin-note" }, "Known permissions:", -1 /* CACHED */)),
                                      (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(permissionCatalog, (perm) => {
                                        return (_openBlock(), _createElementBlock("span", {
                                          key: perm,
                                          class: "badge bg-secondary-subtle text-secondary border border-secondary-subtle me-1 admin-badge"
                                        }, _toDisplayString(perm), 1 /* TEXT */))
                                      }), 128 /* KEYED_FRAGMENT */))
                                    ]))
                                  : _createCommentVNode("v-if", true),
                                _createElementVNode("div", _hoisted_204, [
                                  _createElementVNode("button", {
                                    class: "btn btn-secondary btn-sm me-2",
                                    onClick: $event => (editingRole = null),
                                    disabled: isSavingRole
                                  }, "Cancel", 8 /* PROPS */, _hoisted_205),
                                  _createElementVNode("button", {
                                    class: "btn btn-primary btn-sm",
                                    onClick: saveRole,
                                    disabled: isSavingRole
                                  }, _toDisplayString(isSavingRole ? 'Saving…' : 'Save'), 9 /* TEXT, PROPS */, _hoisted_206)
                                ])
                              ])
                            ]))
                          : _createCommentVNode("v-if", true)
                      ]))
                    : _createCommentVNode("v-if", true),
                  _createCommentVNode(" Logs Tab "),
                  (tab === 'logs')
                    ? (_openBlock(), _createElementBlock("div", _hoisted_207, [
                        (!quickAdmin)
                          ? (_openBlock(), _createElementBlock("div", _hoisted_208, [
                              _createElementVNode("div", _hoisted_209, [
                                _createElementVNode("div", _hoisted_210, [
                                  _cache[73] || (_cache[73] = _createElementVNode("label", {
                                    class: "form-label small mb-1",
                                    for: "quickAdminLogUser"
                                  }, "User", -1 /* CACHED */)),
                                  _withDirectives(_createElementVNode("input", {
                                    id: "quickAdminLogUser",
                                    type: "text",
                                    class: "form-control form-control-sm",
                                    "onUpdate:modelValue": $event => ((logFilters.user) = $event),
                                    placeholder: "username"
                                  }, null, 8 /* PROPS */, _hoisted_211), [
                                    [
                                      _vModelText,
                                      logFilters.user,
                                      void 0,
                                      { trim: true }
                                    ]
                                  ])
                                ]),
                                _createElementVNode("div", _hoisted_212, [
                                  _cache[74] || (_cache[74] = _createElementVNode("label", {
                                    class: "form-label small mb-1",
                                    for: "quickAdminLogAction"
                                  }, "Action", -1 /* CACHED */)),
                                  _withDirectives(_createElementVNode("input", {
                                    id: "quickAdminLogAction",
                                    type: "text",
                                    class: "form-control form-control-sm",
                                    "onUpdate:modelValue": $event => ((logFilters.action) = $event),
                                    placeholder: "action"
                                  }, null, 8 /* PROPS */, _hoisted_213), [
                                    [
                                      _vModelText,
                                      logFilters.action,
                                      void 0,
                                      { trim: true }
                                    ]
                                  ])
                                ]),
                                _createElementVNode("div", _hoisted_214, [
                                  _cache[75] || (_cache[75] = _createElementVNode("label", {
                                    class: "form-label small mb-1",
                                    for: "quickAdminLogPath"
                                  }, "Path contains", -1 /* CACHED */)),
                                  _withDirectives(_createElementVNode("input", {
                                    id: "quickAdminLogPath",
                                    type: "text",
                                    class: "form-control form-control-sm",
                                    "onUpdate:modelValue": $event => ((logFilters.path_contains) = $event),
                                    placeholder: "/path"
                                  }, null, 8 /* PROPS */, _hoisted_215), [
                                    [
                                      _vModelText,
                                      logFilters.path_contains,
                                      void 0,
                                      { trim: true }
                                    ]
                                  ])
                                ]),
                                _createElementVNode("div", _hoisted_216, [
                                  _cache[76] || (_cache[76] = _createElementVNode("label", {
                                    class: "form-label small mb-1",
                                    for: "quickAdminLogFrom"
                                  }, "From", -1 /* CACHED */)),
                                  _withDirectives(_createElementVNode("input", {
                                    id: "quickAdminLogFrom",
                                    type: "date",
                                    class: "form-control form-control-sm",
                                    "onUpdate:modelValue": $event => ((logFilters.date_from) = $event)
                                  }, null, 8 /* PROPS */, _hoisted_217), [
                                    [_vModelText, logFilters.date_from]
                                  ])
                                ]),
                                _createElementVNode("div", _hoisted_218, [
                                  _cache[77] || (_cache[77] = _createElementVNode("label", {
                                    class: "form-label small mb-1",
                                    for: "quickAdminLogTo"
                                  }, "To", -1 /* CACHED */)),
                                  _withDirectives(_createElementVNode("input", {
                                    id: "quickAdminLogTo",
                                    type: "date",
                                    class: "form-control form-control-sm",
                                    "onUpdate:modelValue": $event => ((logFilters.date_to) = $event)
                                  }, null, 8 /* PROPS */, _hoisted_219), [
                                    [_vModelText, logFilters.date_to]
                                  ])
                                ]),
                                _createElementVNode("div", _hoisted_220, [
                                  _createElementVNode("button", {
                                    class: "btn btn-primary btn-sm",
                                    onClick: applyLogFilters,
                                    disabled: isLoadingLogs
                                  }, _toDisplayString(isLoadingLogs ? '…' : 'Apply'), 9 /* TEXT, PROPS */, _hoisted_221)
                                ])
                              ]),
                              _createElementVNode("div", _hoisted_222, [
                                _createElementVNode("button", {
                                  class: "btn btn-outline-secondary btn-sm",
                                  onClick: resetLogFilters,
                                  disabled: isLoadingLogs
                                }, "Reset", 8 /* PROPS */, _hoisted_223),
                                _createElementVNode("button", {
                                  class: "btn btn-outline-secondary btn-sm",
                                  onClick: $event => (exportLogs('json')),
                                  disabled: isLoadingLogs || logs.length === 0
                                }, "Export JSON", 8 /* PROPS */, _hoisted_224),
                                _createElementVNode("button", {
                                  class: "btn btn-outline-secondary btn-sm",
                                  onClick: $event => (exportLogs('csv')),
                                  disabled: isLoadingLogs || logs.length === 0
                                }, "Export CSV", 8 /* PROPS */, _hoisted_225)
                              ])
                            ]))
                          : (_openBlock(), _createElementBlock("div", _hoisted_226, " Showing the most recent activity. Use the admin console for full audit tools. ")),
                        isLoadingLogs
                          ? (_openBlock(), _createElementBlock("div", _hoisted_227, "Loading logs…"))
                          : _createCommentVNode("v-if", true),
                        _createElementVNode("div", _hoisted_228, [
                          _createElementVNode("table", _hoisted_229, [
                            _cache[78] || (_cache[78] = _createElementVNode("thead", { class: "sticky-top bg-body" }, [
                              _createElementVNode("tr", null, [
                                _createElementVNode("th", null, "Date"),
                                _createElementVNode("th", null, "User"),
                                _createElementVNode("th", null, "Action"),
                                _createElementVNode("th", null, "Path"),
                                _createElementVNode("th", null, "IP")
                              ])
                            ], -1 /* CACHED */)),
                            _createElementVNode("tbody", null, [
                              (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(logs, (log) => {
                                return (_openBlock(), _createElementBlock("tr", { key: log.timestamp + log.action }, [
                                  _createElementVNode("td", _hoisted_230, _toDisplayString(formatDate(log.timestamp)), 1 /* TEXT */),
                                  _createElementVNode("td", null, _toDisplayString(log.user), 1 /* TEXT */),
                                  _createElementVNode("td", null, [
                                    _createElementVNode("strong", null, _toDisplayString(log.action), 1 /* TEXT */)
                                  ]),
                                  _createElementVNode("td", {
                                    class: "text-truncate admin-log-path",
                                    title: log.path
                                  }, _toDisplayString(log.path), 9 /* TEXT, PROPS */, _hoisted_231),
                                  _createElementVNode("td", null, _toDisplayString(log.ip), 1 /* TEXT */)
                                ]))
                              }), 128 /* KEYED_FRAGMENT */))
                            ])
                          ])
                        ]),
                        (!quickAdmin)
                          ? (_openBlock(), _createElementBlock("div", _hoisted_232, [
                              _createElementVNode("div", _hoisted_233, " Showing page " + _toDisplayString(logsMeta.page) + " of " + _toDisplayString(logsMeta.totalPages) + " (" + _toDisplayString(logsMeta.total) + " total) ", 1 /* TEXT */),
                              _createElementVNode("div", _hoisted_234, [
                                _cache[79] || (_cache[79] = _createElementVNode("label", {
                                  class: "mb-0",
                                  for: "quickAdminLogPageSize"
                                }, "Page size", -1 /* CACHED */)),
                                _withDirectives(_createElementVNode("select", {
                                  id: "quickAdminLogPageSize",
                                  class: "form-select form-select-sm select-auto-width",
                                  "onUpdate:modelValue": $event => ((logsMeta.pageSize) = $event),
                                  onChange: changeLogsPageSize
                                }, [
                                  (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(logPageSizeOptions, (size) => {
                                    return (_openBlock(), _createElementBlock("option", {
                                      key: size,
                                      value: size
                                    }, _toDisplayString(size), 9 /* TEXT, PROPS */, _hoisted_236))
                                  }), 128 /* KEYED_FRAGMENT */))
                                ], 40 /* PROPS, NEED_HYDRATION */, _hoisted_235), [
                                  [
                                    _vModelSelect,
                                    logsMeta.pageSize,
                                    void 0,
                                    { number: true }
                                  ]
                                ]),
                                _createElementVNode("button", {
                                  class: "btn btn-outline-secondary btn-sm",
                                  onClick: $event => (changeLogsPage(-1)),
                                  disabled: isLoadingLogs || logsMeta.page <= 1
                                }, "Prev", 8 /* PROPS */, _hoisted_237),
                                _createElementVNode("button", {
                                  class: "btn btn-outline-secondary btn-sm",
                                  onClick: $event => (changeLogsPage(1)),
                                  disabled: isLoadingLogs || logsMeta.page >= logsMeta.totalPages
                                }, "Next", 8 /* PROPS */, _hoisted_238)
                              ])
                            ]))
                          : _createCommentVNode("v-if", true)
                      ]))
                    : _createCommentVNode("v-if", true),
                  _createCommentVNode(" System Info Tab "),
                  (tab === 'system')
                    ? (_openBlock(), _createElementBlock("div", _hoisted_239, [
                        (!system)
                          ? (_openBlock(), _createElementBlock("div", _hoisted_240, "Loading..."))
                          : (_openBlock(), _createElementBlock("div", _hoisted_241, [
                              _createElementVNode("table", _hoisted_242, [
                                _createElementVNode("tbody", null, [
                                  _createElementVNode("tr", null, [
                                    _cache[80] || (_cache[80] = _createElementVNode("th", { class: "admin-meta-label" }, "eXtplorer Version", -1 /* CACHED */)),
                                    _createElementVNode("td", null, [
                                      _createElementVNode("span", _hoisted_243, _toDisplayString(system.app_version), 1 /* TEXT */)
                                    ])
                                  ]),
                                  _createElementVNode("tr", null, [
                                    _cache[81] || (_cache[81] = _createElementVNode("th", null, "PHP Version", -1 /* CACHED */)),
                                    _createElementVNode("td", null, _toDisplayString(system.php_version), 1 /* TEXT */)
                                  ]),
                                  _createElementVNode("tr", null, [
                                    _cache[82] || (_cache[82] = _createElementVNode("th", null, "OS", -1 /* CACHED */)),
                                    _createElementVNode("td", null, _toDisplayString(system.server_os), 1 /* TEXT */)
                                  ]),
                                  _createElementVNode("tr", null, [
                                    _cache[83] || (_cache[83] = _createElementVNode("th", null, "Server Software", -1 /* CACHED */)),
                                    _createElementVNode("td", null, _toDisplayString(system.server_software), 1 /* TEXT */)
                                  ]),
                                  _createElementVNode("tr", null, [
                                    _cache[84] || (_cache[84] = _createElementVNode("th", null, "Memory Limit", -1 /* CACHED */)),
                                    _createElementVNode("td", null, _toDisplayString(system.memory_limit), 1 /* TEXT */)
                                  ]),
                                  _createElementVNode("tr", null, [
                                    _cache[85] || (_cache[85] = _createElementVNode("th", null, "Upload Limit", -1 /* CACHED */)),
                                    _createElementVNode("td", null, _toDisplayString(system.upload_max_filesize), 1 /* TEXT */)
                                  ]),
                                  _createElementVNode("tr", null, [
                                    _cache[86] || (_cache[86] = _createElementVNode("th", null, "POST Limit", -1 /* CACHED */)),
                                    _createElementVNode("td", null, _toDisplayString(system.post_max_size), 1 /* TEXT */)
                                  ]),
                                  _createElementVNode("tr", null, [
                                    _cache[87] || (_cache[87] = _createElementVNode("th", null, "Disk Free", -1 /* CACHED */)),
                                    _createElementVNode("td", null, _toDisplayString(formatSize(system.disk_free)), 1 /* TEXT */)
                                  ]),
                                  _createElementVNode("tr", null, [
                                    _cache[88] || (_cache[88] = _createElementVNode("th", null, "Disk Total", -1 /* CACHED */)),
                                    _createElementVNode("td", null, _toDisplayString(formatSize(system.disk_total)), 1 /* TEXT */)
                                  ])
                                ])
                              ]),
                              _cache[89] || (_cache[89] = _createElementVNode("h6", null, "Loaded Extensions", -1 /* CACHED */)),
                              _createElementVNode("div", _hoisted_244, _toDisplayString(system.extensions), 1 /* TEXT */)
                            ]))
                      ]))
                    : _createCommentVNode("v-if", true)
                ], 64 /* STABLE_FRAGMENT */))
          ])
        ])
      ])
    ]))
  }
}
})(Vue),
    data() {
        return {
            tab: 'users',
            quickAdmin: false,
            adminConsoleUrl: (window.baseUrl || '/') + 'admin#users',
            users: [],
            groupsList: {},
            rolesList: {},
            logs: [],
            logsMeta: { total: 0, page: 1, pageSize: 50, totalPages: 1 },
            logFilters: { user: '', action: '', path_contains: '', date_from: '', date_to: '' },
            logPageSizeOptions: [25, 50, 100, 200],
            isLoadingLogs: false,
            permissionCatalog: [],
            effectivePermissions: { username: null, perms: [], loading: false },
            system: null,
            settings: null,
            settingsOriginal: null,
            settingsTab: 'email',
            emailValidation: { ok: null, message: '', timeoutId: null },
            error: null,
            isSavingSettings: false,
            isValidatingEmail: false,
            isTestingEmail: false,
            isSavingUser: false,
            isSavingGroup: false,
            isSavingRole: false,
            editingUser: null,
            editingGroup: null,
            editingRole: null,
            isNew: false,
            modal: null,
            currentUsername: username
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
    mounted() {
        const modalEl = document.getElementById('userAdminModal');
        const embedMode = Boolean(window.adminEmbed);
        this.quickAdmin = !embedMode && !window.adminPage;
        const modalOptions = embedMode ? { backdrop: false, keyboard: false } : {};

        this.modal = new bootstrap.Modal(modalEl, modalOptions);
        modalEl.addEventListener('show.bs.modal', this.initAdmin);

        if (embedMode) {
            modalEl.classList.add('admin-embed');
            modalEl.addEventListener('hidden.bs.modal', () => {
                window.location.href = window.baseUrl || '/';
            });
            this.initAdmin();
            this.modal.show();
        }
    },
    methods: {
        t(key, fallback = '', params = {}) {
            const value = i18n.t(key, params);
            if (value === key) {
                return fallback || key;
            }
            return value;
        },
        open() {
            this.modal.show();
        },
        consoleUrl(section, tab = null) {
            let url = (window.baseUrl || '/') + 'admin#' + section;
            if (tab) url += '/' + tab;
            return url;
        },
        async initAdmin() {
            this.tab = 'users';
            await this.loadUsers();
            await this.loadGroups();
            if (!this.quickAdmin) {
                await this.loadRoles();
                this.loadPermissionsCatalog();
                this.loadSystemInfo(false);
            } else {
                await this.loadRoles();
                this.loadSystemInfo(false);
                this.loadLogs();
            }
        },
        async loadUsers() {
            this.error = null;
            try { this.users = await Api.get('users'); } catch (e) { this.error = e.message; }
        },
        async loadGroups() {
            try { this.groupsList = await Api.get('groups'); } catch (e) {}
        },
        async loadRoles() {
            try { this.rolesList = await Api.get('roles'); } catch (e) {}
        },
        async loadLogs() {
            this.isLoadingLogs = true;
            try {
                if (this.quickAdmin) {
                    const res = await Api.get('logs/query', { page: 1, pageSize: 20 });
                    this.logs = res.items || [];
                    this.logsMeta = {
                        total: res.total ?? this.logs.length,
                        page: 1,
                        pageSize: 20,
                        totalPages: 1
                    };
                    return;
                }
                const params = {
                    ...this.logFilters,
                    page: this.logsMeta.page,
                    pageSize: this.logsMeta.pageSize
                };
                const res = await Api.get('logs/query', params);
                this.logs = res.items || [];
                this.logsMeta = {
                    total: res.total ?? 0,
                    page: res.page ?? 1,
                    pageSize: res.pageSize ?? this.logsMeta.pageSize,
                    totalPages: Math.max(1, res.totalPages ?? 1)
                };
            } catch (e) {
                this.error = e.message;
                this.toastError('Failed to load logs.');
            } finally {
                this.isLoadingLogs = false;
            }
        },
        async loadPermissionsCatalog() {
            try {
                const catalog = await Api.get('permissions/catalog');
                if (Array.isArray(catalog)) {
                    this.permissionCatalog = catalog;
                }
            } catch (e) {
                // Non-blocking: catalog is a helper, not required.
            }
        },
        async loadSystemInfo(switchTab = true) {
            if (switchTab) this.tab = 'system';
            if (this.system) return;
            try { this.system = await Api.get('system'); } catch (e) { this.error = e.message; }
        },
        async loadSettings(switchTab = true) {
            if (switchTab) this.tab = 'settings';
            try {
                this.settings = await Api.get('settings');
                if (!this.settings.mount_root_allowlist_text && Array.isArray(this.settings.mount_root_allowlist)) {
                    this.settings.mount_root_allowlist_text = this.settings.mount_root_allowlist.join('\n');
                }
                if (!this.settings.mount_remote_host_allowlist_text && Array.isArray(this.settings.mount_remote_host_allowlist)) {
                    this.settings.mount_remote_host_allowlist_text = this.settings.mount_remote_host_allowlist.join('\n');
                }
                if (!this.settings.email_protocol) {
                    this.settings.email_protocol = 'smtp';
                }
                if (!this.settings.sendmail_path) {
                    this.settings.sendmail_path = '/usr/sbin/sendmail';
                }
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
                if (typeof this.settings.session_idle_timeout_minutes !== 'number' || Number.isNaN(this.settings.session_idle_timeout_minutes)) {
                    this.settings.session_idle_timeout_minutes = 120;
                }
                if (typeof this.settings.upload_max_file_mb !== 'number' || Number.isNaN(this.settings.upload_max_file_mb)) {
                    this.settings.upload_max_file_mb = 0;
                }
                if (typeof this.settings.quota_per_user_mb !== 'number' || Number.isNaN(this.settings.quota_per_user_mb)) {
                    this.settings.quota_per_user_mb = 0;
                }
                this.settingsOriginal = JSON.parse(JSON.stringify(this.settings));
                this.clearEmailValidation();
            } catch (e) { this.error = e.message; }
        },
        async saveSettings() {
             if (!this.settingsDirty) return;
             this.isSavingSettings = true;
             try {
                 await Api.post('settings', this.settings);
                 this.settingsOriginal = JSON.parse(JSON.stringify(this.settings));
                 this.toastSuccess('Settings saved.');
             } catch(e) {
                 this.error = e.message;
                 this.toastError('Failed to save settings.');
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
                 await Api.post('settings/test-email', { ...this.settings, email });
                 this.setEmailValidation(true, 'Test email sent successfully.');
                 this.toastSuccess('Test email sent.');
             } catch(e) {
                 this.setEmailValidation(false, 'Test failed: ' + e.message);
                 this.toastError('Test email failed.');
             } finally {
                 this.isTestingEmail = false;
             }
        },
        async validateEmailSettings() {
             if (!this.settings) return;
             this.isValidatingEmail = true;
             try {
                 const res = await Api.post('settings/validate-email', this.settings);
                 this.setEmailValidation(true, res.message || 'Validation successful');
             } catch(e) {
                 this.setEmailValidation(false, 'Validation failed: ' + e.message);
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

        loadLogsTab() { this.tab = 'logs'; this.logsMeta.page = 1; this.loadLogs(); },
        loadGroupsTab() { this.tab = 'groups'; },
        loadRolesTab() { this.tab = 'roles'; },
        applyLogFilters() {
            this.logsMeta.page = 1;
            this.loadLogs();
        },
        resetLogFilters() {
            this.logFilters = { user: '', action: '', path_contains: '', date_from: '', date_to: '' };
            this.logsMeta.page = 1;
            this.loadLogs();
        },
        changeLogsPage(delta) {
            const nextPage = this.logsMeta.page + delta;
            if (nextPage < 1 || nextPage > this.logsMeta.totalPages) return;
            this.logsMeta.page = nextPage;
            this.loadLogs();
        },
        changeLogsPageSize() {
            this.logsMeta.page = 1;
            this.loadLogs();
        },
        exportLogs(format) {
            if (!this.logs || this.logs.length === 0) return;
            const stamp = new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-');
            if (format === 'json') {
                const json = JSON.stringify(this.logs, null, 2);
                this.downloadBlob(json, `audit-logs-${stamp}.json`, 'application/json');
                return;
            }

            const header = ['timestamp', 'date', 'user', 'action', 'path', 'ip'];
            const rows = this.logs.map((log) => {
                const ts = (log.timestamp ?? 0);
                const date = this.formatDate(ts);
                return [
                    ts,
                    date,
                    log.user ?? '',
                    log.action ?? '',
                    log.path ?? '',
                    log.ip ?? ''
                ];
            });
            const csvLines = [header.join(',')].concat(rows.map((row) => row.map(this.csvEscape).join(',')));
            this.downloadBlob(csvLines.join('\n'), `audit-logs-${stamp}.csv`, 'text/csv;charset=utf-8;');
        },
        csvEscape(value) {
            const str = String(value ?? '');
            if (/[",\n]/.test(str)) {
                return `"${str.replace(/"/g, '""')}"`;
            }
            return str;
        },
        downloadBlob(content, filename, mimeType) {
            const blob = new Blob([content], { type: mimeType });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
            this.toastSuccess('Export started.');
        },

        // User Methods
        showAddForm() {
            this.isNew = true;
            this.editingUser = { username: '', password: '', role: 'user', home_dir: '/', groups: [], allowed_extensions: '', blocked_extensions: '' };
            this.effectivePermissions = { username: null, perms: [], loading: false };
        },
        editUser(user) {
            this.isNew = false;
            this.editingUser = { ...user, password: '', groups: user.groups || [], allowed_extensions: user.allowed_extensions || '', blocked_extensions: user.blocked_extensions || '' };
            this.loadEffectivePermissions(user.username);
        },
        cancelEdit() {
            this.editingUser = null;
            this.effectivePermissions = { username: null, perms: [], loading: false };
        },
        async saveUser() {
            if (!this.editingUser) return;
            this.isSavingUser = true;
            try {
                const savedUsername = this.editingUser.username;
                if (this.isNew) await Api.post('users', this.editingUser);
                else await Api.put('users/' + this.editingUser.username, this.editingUser);
                await this.loadUsers();
                this.editingUser = null;
                this.toastSuccess('User saved.');
                if (!this.isNew && savedUsername) {
                    this.loadEffectivePermissions(savedUsername);
                }
            } catch (e) {
                this.error = e.message;
                this.toastError('Failed to save user.');
            } finally {
                this.isSavingUser = false;
            }
        },
        async deleteUser(user) {
            const confirmed = await this.confirmDanger('Delete user?', `This will delete ${user.username}.`);
            if (!confirmed) return;
            try {
                await Api.delete('users/' + user.username);
                await this.loadUsers();
                this.toastSuccess('User deleted.');
            } catch (e) {
                this.error = e.message;
                this.toastError('Failed to delete user.');
            }
        },
        async loadEffectivePermissions(username) {
            if (!username) return;
            const targetUsername = username;
            this.effectivePermissions = { username: targetUsername, perms: [], loading: true };
            try {
                const res = await Api.get(`users/${targetUsername}/permissions`);
                if (res && Array.isArray(res.permissions)) {
                    const isStillEditingSameUser = this.editingUser && this.editingUser.username === targetUsername;
                    if (isStillEditingSameUser) {
                        this.effectivePermissions = {
                            username: targetUsername,
                            perms: res.permissions,
                            loading: false
                        };
                    }
                    if (Array.isArray(res.catalog) && res.catalog.length) {
                        this.permissionCatalog = res.catalog;
                    }
                } else {
                    this.effectivePermissions = { username: targetUsername, perms: [], loading: false };
                }
            } catch (e) {
                this.effectivePermissions = { username: targetUsername, perms: [], loading: false };
                this.error = e.message;
            }
        },

        // Group Methods
        showAddGroupForm() { this.editingGroup = { name: '', roles: [], isEdit: false }; },
        editGroup(name, roles) { this.editingGroup = { name, roles: [...roles], isEdit: true }; },
        async saveGroup() {
            if (!this.editingGroup) return;
            this.isSavingGroup = true;
            try {
                await Api.post('groups', { name: this.editingGroup.name, roles: this.editingGroup.roles });
                await this.loadGroups();
                this.editingGroup = null;
                this.toastSuccess('Group saved.');
            } catch (e) {
                this.error = e.message;
                this.toastError('Failed to save group.');
            } finally {
                this.isSavingGroup = false;
            }
        },
        async deleteGroup(name) {
            const confirmed = await this.confirmDanger('Delete group?', `This will delete the group ${name}.`);
            if (!confirmed) return;
            try {
                await Api.delete('groups/' + name);
                await this.loadGroups();
                this.toastSuccess('Group deleted.');
            } catch (e) {
                this.error = e.message;
                this.toastError('Failed to delete group.');
            }
        },

        // Role Methods
        showAddRoleForm() { this.editingRole = { name: '', permsString: '', isEdit: false }; },
        editRole(name, perms) { this.editingRole = { name, permsString: perms.join(','), isEdit: true }; },
        async saveRole() {
            if (!this.editingRole) return;
            this.isSavingRole = true;
            try {
                const perms = this.editingRole.permsString.split(',').map(p => p.trim()).filter(p => p);
                await Api.post('roles', { name: this.editingRole.name, permissions: perms });
                await this.loadRoles();
                this.editingRole = null;
                this.toastSuccess('Role saved.');
            } catch (e) {
                this.error = e.message;
                this.toastError('Failed to save role.');
            } finally {
                this.isSavingRole = false;
            }
        },
        async deleteRole(name) {
            const confirmed = await this.confirmDanger('Delete role?', `This will delete the role ${name}.`);
            if (!confirmed) return;
            try {
                await Api.delete('roles/' + name);
                await this.loadRoles();
                this.toastSuccess('Role deleted.');
            } catch (e) {
                this.error = e.message;
                this.toastError('Failed to delete role.');
            }
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
        async confirmDanger(title, text) {
            const result = await Swal.fire({
                icon: 'warning',
                title,
                text,
                showCancelButton: true,
                confirmButtonText: 'Yes, continue',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc3545'
            });
            return Boolean(result.isConfirmed);
        },
        async promptForEmail() {
            const result = await Swal.fire({
                title: 'Send test email',
                input: 'email',
                inputLabel: 'Recipient email',
                inputPlaceholder: 'name@example.com',
                showCancelButton: true,
                confirmButtonText: 'Send',
                inputValidator: (value) => {
                    if (!value) return 'Email is required';
                    return null;
                }
            });
            if (!result.isConfirmed) return null;
            return result.value;
        },

        formatSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        formatDate(timestamp) {
            return new Date(timestamp * 1000).toLocaleString();
        }
    }
};
