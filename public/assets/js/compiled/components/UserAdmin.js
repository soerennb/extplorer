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
const _hoisted_7 = { class: "alert alert-light border small d-flex align-items-center justify-content-between" }
const _hoisted_8 = { class: "text-muted" }
const _hoisted_9 = ["href"]
const _hoisted_10 = { class: "row g-2 mb-3" }
const _hoisted_11 = { class: "col-sm-4" }
const _hoisted_12 = { class: "border rounded p-3 h-100 bg-body-tertiary" }
const _hoisted_13 = { class: "small text-muted" }
const _hoisted_14 = { class: "fs-4 fw-semibold" }
const _hoisted_15 = ["href"]
const _hoisted_16 = { class: "col-sm-4" }
const _hoisted_17 = { class: "border rounded p-3 h-100 bg-body-tertiary" }
const _hoisted_18 = { class: "small text-muted" }
const _hoisted_19 = { class: "fs-4 fw-semibold" }
const _hoisted_20 = ["href"]
const _hoisted_21 = { class: "col-sm-4" }
const _hoisted_22 = { class: "border rounded p-3 h-100 bg-body-tertiary" }
const _hoisted_23 = { class: "small text-muted" }
const _hoisted_24 = { class: "fs-4 fw-semibold" }
const _hoisted_25 = ["href"]
const _hoisted_26 = { class: "d-flex flex-wrap gap-2 mb-3" }
const _hoisted_27 = ["href"]
const _hoisted_28 = ["href"]
const _hoisted_29 = ["href"]
const _hoisted_30 = ["href"]
const _hoisted_31 = { class: "border rounded" }
const _hoisted_32 = { class: "d-flex align-items-center justify-content-between gap-2 px-3 py-2 border-bottom bg-body-tertiary" }
const _hoisted_33 = { class: "fw-semibold" }
const _hoisted_34 = ["onClick", "disabled"]
const _hoisted_35 = {
  key: 0,
  class: "text-muted small p-3"
}
const _hoisted_36 = {
  key: 1,
  class: "text-center text-muted small p-4"
}
const _hoisted_37 = {
  key: 2,
  class: "table-responsive"
}
const _hoisted_38 = { class: "table table-sm table-hover small mb-0" }
const _hoisted_39 = { class: "table-light" }
const _hoisted_40 = { class: "text-nowrap" }
const _hoisted_41 = ["title"]
const _hoisted_42 = {
  key: 0,
  class: "alert alert-danger"
}

return function render(_ctx, _cache) {
  with (_ctx) {
    const { toDisplayString: _toDisplayString, createElementVNode: _createElementVNode, createTextVNode: _createTextVNode, openBlock: _openBlock, createElementBlock: _createElementBlock, createCommentVNode: _createCommentVNode, renderList: _renderList, Fragment: _Fragment } = _Vue

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
            _createElementVNode("div", _hoisted_7, [
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
            ]),
            _createElementVNode("div", null, [
              _createElementVNode("div", _hoisted_10, [
                _createElementVNode("div", _hoisted_11, [
                  _createElementVNode("div", _hoisted_12, [
                    _createElementVNode("div", _hoisted_13, _toDisplayString(t('admin_nav_users', 'Users')), 1 /* TEXT */),
                    _createElementVNode("div", _hoisted_14, _toDisplayString(users.length), 1 /* TEXT */),
                    _createElementVNode("a", {
                      class: "small",
                      href: consoleUrl('users')
                    }, _toDisplayString(t('quick_admin_manage_users', 'Manage users')), 9 /* TEXT, PROPS */, _hoisted_15)
                  ])
                ]),
                _createElementVNode("div", _hoisted_16, [
                  _createElementVNode("div", _hoisted_17, [
                    _createElementVNode("div", _hoisted_18, _toDisplayString(t('admin_nav_groups', 'Groups')), 1 /* TEXT */),
                    _createElementVNode("div", _hoisted_19, _toDisplayString(Object.keys(groupsList).length), 1 /* TEXT */),
                    _createElementVNode("a", {
                      class: "small",
                      href: consoleUrl('groups')
                    }, _toDisplayString(t('quick_admin_manage_groups', 'Manage groups')), 9 /* TEXT, PROPS */, _hoisted_20)
                  ])
                ]),
                _createElementVNode("div", _hoisted_21, [
                  _createElementVNode("div", _hoisted_22, [
                    _createElementVNode("div", _hoisted_23, _toDisplayString(t('admin_nav_roles', 'Roles')), 1 /* TEXT */),
                    _createElementVNode("div", _hoisted_24, _toDisplayString(Object.keys(rolesList).length), 1 /* TEXT */),
                    _createElementVNode("a", {
                      class: "small",
                      href: consoleUrl('roles')
                    }, _toDisplayString(t('quick_admin_manage_roles', 'Manage roles')), 9 /* TEXT, PROPS */, _hoisted_25)
                  ])
                ])
              ]),
              _createElementVNode("div", _hoisted_26, [
                _createElementVNode("a", {
                  class: "btn btn-outline-primary btn-sm",
                  href: consoleUrl('users')
                }, [
                  _cache[2] || (_cache[2] = _createElementVNode("i", {
                    class: "ri-user-settings-line me-1",
                    "aria-hidden": "true"
                  }, null, -1 /* CACHED */)),
                  _createTextVNode(_toDisplayString(t('quick_admin_open_users', 'Open user administration')), 1 /* TEXT */)
                ], 8 /* PROPS */, _hoisted_27),
                _createElementVNode("a", {
                  class: "btn btn-outline-primary btn-sm",
                  href: consoleUrl('settings', 'sharing')
                }, [
                  _cache[3] || (_cache[3] = _createElementVNode("i", {
                    class: "ri-share-line me-1",
                    "aria-hidden": "true"
                  }, null, -1 /* CACHED */)),
                  _createTextVNode(_toDisplayString(t('quick_admin_open_sharing', 'Open sharing settings')), 1 /* TEXT */)
                ], 8 /* PROPS */, _hoisted_28),
                _createElementVNode("a", {
                  class: "btn btn-outline-primary btn-sm",
                  href: consoleUrl('settings', 'mounts')
                }, [
                  _cache[4] || (_cache[4] = _createElementVNode("i", {
                    class: "ri-hard-drive-2-line me-1",
                    "aria-hidden": "true"
                  }, null, -1 /* CACHED */)),
                  _createTextVNode(_toDisplayString(t('quick_admin_open_mounts', 'Open mount settings')), 1 /* TEXT */)
                ], 8 /* PROPS */, _hoisted_29),
                _createElementVNode("a", {
                  class: "btn btn-outline-secondary btn-sm",
                  href: consoleUrl('logs')
                }, [
                  _cache[5] || (_cache[5] = _createElementVNode("i", {
                    class: "ri-file-list-3-line me-1",
                    "aria-hidden": "true"
                  }, null, -1 /* CACHED */)),
                  _createTextVNode(_toDisplayString(t('quick_admin_view_all_logs', 'View all audit logs')), 1 /* TEXT */)
                ], 8 /* PROPS */, _hoisted_30)
              ]),
              _createElementVNode("div", _hoisted_31, [
                _createElementVNode("div", _hoisted_32, [
                  _createElementVNode("div", _hoisted_33, _toDisplayString(t('quick_admin_recent_activity', 'Recent activity')), 1 /* TEXT */),
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
                  ], 8 /* PROPS */, _hoisted_34)
                ]),
                isLoadingLogs
                  ? (_openBlock(), _createElementBlock("div", _hoisted_35, _toDisplayString(t('admin_logs_loading', 'Loading logs…')), 1 /* TEXT */))
                  : (logs.length === 0)
                    ? (_openBlock(), _createElementBlock("div", _hoisted_36, [
                        _cache[7] || (_cache[7] = _createElementVNode("i", {
                          class: "ri-inbox-line d-block fs-3 mb-2",
                          "aria-hidden": "true"
                        }, null, -1 /* CACHED */)),
                        _createTextVNode(" " + _toDisplayString(t('admin_logs_empty_unfiltered', 'No audit activity has been recorded yet.')), 1 /* TEXT */)
                      ]))
                    : (_openBlock(), _createElementBlock("div", _hoisted_37, [
                        _createElementVNode("table", _hoisted_38, [
                          _createElementVNode("thead", _hoisted_39, [
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
                                _createElementVNode("td", _hoisted_40, _toDisplayString(formatDate(log.timestamp)), 1 /* TEXT */),
                                _createElementVNode("td", null, _toDisplayString(log.user), 1 /* TEXT */),
                                _createElementVNode("td", null, [
                                  _createElementVNode("strong", null, _toDisplayString(log.action), 1 /* TEXT */)
                                ]),
                                _createElementVNode("td", {
                                  class: "text-truncate admin-log-path",
                                  title: log.path
                                }, _toDisplayString(log.path), 9 /* TEXT, PROPS */, _hoisted_41)
                              ]))
                            }), 128 /* KEYED_FRAGMENT */))
                          ])
                        ])
                      ]))
              ])
            ]),
            error
              ? (_openBlock(), _createElementBlock("div", _hoisted_42, _toDisplayString(error), 1 /* TEXT */))
              : _createCommentVNode("v-if", true)
          ])
        ])
      ])
    ]))
  }
}
})(Vue),
    data() {
        return { adminConsoleUrl: Api.route('admin') + '#users', users: [], groupsList: {}, rolesList: {},
            logs: [], isLoadingLogs: false, error: null, modal: null };
    },
    mounted() {
        const element = document.getElementById('userAdminModal');
        this.modal = new bootstrap.Modal(element);
        element.addEventListener('show.bs.modal', () => this.initAdmin());
    },
    methods: {
        t(key, fallback = '', params = {}) {
            const value = i18n.t(key, params);
            return value === key ? (fallback || key) : value;
        },
        open() { this.modal.show(); },
        consoleUrl(section, tab = null) {
            return Api.route('admin') + '#' + section + (tab ? '/' + tab : '');
        },
        async initAdmin() {
            this.error = null;
            try {
                [this.users, this.groupsList, this.rolesList] = await Promise.all([
                    Api.get('users'), Api.get('groups'), Api.get('roles')
                ]);
            } catch (error) { this.error = error.message; }
            await this.loadLogs();
        },
        async loadLogs() {
            this.isLoadingLogs = true;
            try { this.logs = (await Api.get('logs/query', { page: 1, pageSize: 20 })).items || []; }
            catch (error) { this.error = error.message; }
            finally { this.isLoadingLogs = false; }
        },
        formatDate(timestamp) { return new Date(Number(timestamp) * 1000).toLocaleString(); }
    }
};
