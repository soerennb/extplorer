const AdminUsers = {
    render: ((Vue) => {
const _Vue = Vue
const { createElementVNode: _createElementVNode, createCommentVNode: _createCommentVNode, createTextVNode: _createTextVNode } = _Vue

const _hoisted_1 = { class: "d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3" }
const _hoisted_2 = { class: "flex-grow-1" }
const _hoisted_3 = {
  class: "form-label small fw-bold mb-1",
  for: "adminUsersSearch"
}
const _hoisted_4 = ["onUpdate:modelValue", "placeholder"]
const _hoisted_5 = ["onClick"]
const _hoisted_6 = {
  key: 0,
  class: "alert alert-danger small"
}
const _hoisted_7 = { class: "table-responsive admin-table-scroll" }
const _hoisted_8 = { class: "table table-striped table-hover small align-middle" }
const _hoisted_9 = { class: "sticky-top bg-body" }
const _hoisted_10 = { class: "text-end" }
const _hoisted_11 = { class: "fw-semibold" }
const _hoisted_12 = { class: "text-muted small" }
const _hoisted_13 = { class: "text-end" }
const _hoisted_14 = ["onClick", "title", "aria-label"]
const _hoisted_15 = ["onClick", "disabled", "title", "aria-label"]
const _hoisted_16 = { key: 0 }
const _hoisted_17 = {
  colspan: "4",
  class: "text-center text-muted py-4"
}
const _hoisted_18 = {
  key: 1,
  class: "card mt-3 bg-body-tertiary border"
}
const _hoisted_19 = { class: "card-body" }
const _hoisted_20 = { class: "d-flex align-items-center justify-content-between mb-2" }
const _hoisted_21 = { class: "mb-0" }
const _hoisted_22 = { class: "small text-muted" }
const _hoisted_23 = { class: "row g-2" }
const _hoisted_24 = {
  key: 0,
  class: "col-md-6"
}
const _hoisted_25 = {
  class: "form-label small",
  for: "adminUserUsername"
}
const _hoisted_26 = ["onUpdate:modelValue"]
const _hoisted_27 = { class: "col-md-6" }
const _hoisted_28 = {
  class: "form-label small",
  for: "adminUserPassword"
}
const _hoisted_29 = ["onUpdate:modelValue"]
const _hoisted_30 = { class: "col-md-6" }
const _hoisted_31 = {
  class: "form-label small",
  for: "adminUserHome"
}
const _hoisted_32 = ["onUpdate:modelValue"]
const _hoisted_33 = { class: "col-md-6" }
const _hoisted_34 = {
  class: "form-label small",
  for: "adminUserAllowedExt"
}
const _hoisted_35 = ["onUpdate:modelValue", "placeholder"]
const _hoisted_36 = { class: "col-md-6" }
const _hoisted_37 = {
  class: "form-label small",
  for: "adminUserBlockedExt"
}
const _hoisted_38 = ["onUpdate:modelValue", "placeholder"]
const _hoisted_39 = {
  key: 0,
  class: "mt-1"
}
const _hoisted_40 = { class: "small text-muted d-block admin-note" }
const _hoisted_41 = { class: "col-md-12" }
const _hoisted_42 = { class: "form-label small d-block" }
const _hoisted_43 = { class: "d-flex flex-wrap gap-2" }
const _hoisted_44 = ["id", "value", "onUpdate:modelValue"]
const _hoisted_45 = ["for"]
const _hoisted_46 = {
  key: 1,
  class: "col-md-12"
}
const _hoisted_47 = { class: "d-flex align-items-center justify-content-between" }
const _hoisted_48 = { class: "form-label small fw-bold mb-1" }
const _hoisted_49 = ["onClick", "disabled"]
const _hoisted_50 = {
  key: 0,
  class: "text-muted small"
}
const _hoisted_51 = {
  key: 1,
  class: "text-muted small"
}
const _hoisted_52 = {
  key: 2,
  class: "d-flex flex-wrap gap-1"
}
const _hoisted_53 = { class: "mt-3 text-end" }
const _hoisted_54 = ["onClick", "disabled"]
const _hoisted_55 = ["onClick", "disabled"]

return function render(_ctx, _cache) {
  with (_ctx) {
    const { toDisplayString: _toDisplayString, createElementVNode: _createElementVNode, vModelText: _vModelText, withDirectives: _withDirectives, createTextVNode: _createTextVNode, openBlock: _openBlock, createElementBlock: _createElementBlock, createCommentVNode: _createCommentVNode, renderList: _renderList, Fragment: _Fragment, vModelCheckbox: _vModelCheckbox } = _Vue

    return (_openBlock(), _createElementBlock("div", null, [
      _createElementVNode("div", _hoisted_1, [
        _createElementVNode("div", _hoisted_2, [
          _createElementVNode("label", _hoisted_3, _toDisplayString(t('admin_users_search_label', 'Search Users')), 1 /* TEXT */),
          _withDirectives(_createElementVNode("input", {
            type: "text",
            id: "adminUsersSearch",
            class: "form-control form-control-sm",
            "onUpdate:modelValue": $event => ((searchQuery) = $event),
            placeholder: t('admin_users_search_placeholder', 'Filter by username or home directory')
          }, null, 8 /* PROPS */, _hoisted_4), [
            [
              _vModelText,
              searchQuery,
              void 0,
              { trim: true }
            ]
          ])
        ]),
        _createElementVNode("div", null, [
          _createElementVNode("button", {
            class: "btn btn-success btn-sm",
            onClick: showAddForm
          }, [
            _cache[0] || (_cache[0] = _createElementVNode("i", { class: "ri-user-add-line me-1" }, null, -1 /* CACHED */)),
            _createTextVNode(" " + _toDisplayString(t('admin_users_add', 'Add User')), 1 /* TEXT */)
          ], 8 /* PROPS */, _hoisted_5)
        ])
      ]),
      error
        ? (_openBlock(), _createElementBlock("div", _hoisted_6, _toDisplayString(error), 1 /* TEXT */))
        : _createCommentVNode("v-if", true),
      _createElementVNode("div", _hoisted_7, [
        _createElementVNode("table", _hoisted_8, [
          _createElementVNode("thead", _hoisted_9, [
            _createElementVNode("tr", null, [
              _createElementVNode("th", null, _toDisplayString(t('admin_users_col_username', 'Username')), 1 /* TEXT */),
              _createElementVNode("th", null, _toDisplayString(t('admin_users_col_groups', 'Groups')), 1 /* TEXT */),
              _createElementVNode("th", null, _toDisplayString(t('admin_users_col_home', 'Home Dir')), 1 /* TEXT */),
              _createElementVNode("th", _hoisted_10, _toDisplayString(t('admin_actions', 'Actions')), 1 /* TEXT */)
            ])
          ]),
          _createElementVNode("tbody", null, [
            (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(filteredUsers, (user) => {
              return (_openBlock(), _createElementBlock("tr", { key: user.username }, [
                _createElementVNode("td", _hoisted_11, _toDisplayString(user.username), 1 /* TEXT */),
                _createElementVNode("td", null, [
                  (_openBlock(true), _createElementBlock(_Fragment, null, _renderList((user.groups || []), (g) => {
                    return (_openBlock(), _createElementBlock("span", {
                      key: g,
                      class: "badge bg-secondary me-1"
                    }, _toDisplayString(g), 1 /* TEXT */))
                  }), 128 /* KEYED_FRAGMENT */))
                ]),
                _createElementVNode("td", _hoisted_12, _toDisplayString(user.home_dir), 1 /* TEXT */),
                _createElementVNode("td", _hoisted_13, [
                  _createElementVNode("button", {
                    class: "btn btn-sm btn-outline-primary me-1",
                    onClick: $event => (editUser(user)),
                    title: t('admin_users_edit_prefix', 'Edit User: ') + user.username,
                    "aria-label": t('admin_users_edit_prefix', 'Edit User: ') + user.username
                  }, [...(_cache[1] || (_cache[1] = [
                    _createElementVNode("i", {
                      class: "ri-edit-line",
                      "aria-hidden": "true"
                    }, null, -1 /* CACHED */)
                  ]))], 8 /* PROPS */, _hoisted_14),
                  _createElementVNode("button", {
                    class: "btn btn-sm btn-outline-danger",
                    onClick: $event => (deleteUser(user)),
                    disabled: user.username === currentUsername,
                    title: t('delete', 'Delete') + ': ' + user.username,
                    "aria-label": t('delete', 'Delete') + ': ' + user.username
                  }, [...(_cache[2] || (_cache[2] = [
                    _createElementVNode("i", {
                      class: "ri-delete-bin-line",
                      "aria-hidden": "true"
                    }, null, -1 /* CACHED */)
                  ]))], 8 /* PROPS */, _hoisted_15)
                ])
              ]))
            }), 128 /* KEYED_FRAGMENT */)),
            (filteredUsers.length === 0)
              ? (_openBlock(), _createElementBlock("tr", _hoisted_16, [
                  _createElementVNode("td", _hoisted_17, _toDisplayString(t('admin_users_empty', 'No users match your filter.')), 1 /* TEXT */)
                ]))
              : _createCommentVNode("v-if", true)
          ])
        ])
      ]),
      editingUser
        ? (_openBlock(), _createElementBlock("div", _hoisted_18, [
            _createElementVNode("div", _hoisted_19, [
              _createElementVNode("div", _hoisted_20, [
                _createElementVNode("h6", _hoisted_21, _toDisplayString(isNew ? t('admin_users_add', 'Add User') : t('admin_users_edit_prefix', 'Edit User: ') + editingUser.username), 1 /* TEXT */),
                _createElementVNode("div", _hoisted_22, _toDisplayString(t('admin_users_changes_apply', 'Changes apply immediately on save.')), 1 /* TEXT */)
              ]),
              _createElementVNode("div", _hoisted_23, [
                isNew
                  ? (_openBlock(), _createElementBlock("div", _hoisted_24, [
                      _createElementVNode("label", _hoisted_25, _toDisplayString(t('admin_users_field_username', 'Username')), 1 /* TEXT */),
                      _withDirectives(_createElementVNode("input", {
                        id: "adminUserUsername",
                        type: "text",
                        class: "form-control form-control-sm",
                        "onUpdate:modelValue": $event => ((editingUser.username) = $event),
                        autocomplete: "username"
                      }, null, 8 /* PROPS */, _hoisted_26), [
                        [_vModelText, editingUser.username]
                      ])
                    ]))
                  : _createCommentVNode("v-if", true),
                _createElementVNode("div", _hoisted_27, [
                  _createElementVNode("label", _hoisted_28, _toDisplayString(t('admin_users_field_password', 'Password')) + " " + _toDisplayString(!isNew ? t('admin_users_leave_blank', '(Leave blank to keep)') : ''), 1 /* TEXT */),
                  _withDirectives(_createElementVNode("input", {
                    id: "adminUserPassword",
                    type: "password",
                    class: "form-control form-control-sm",
                    "onUpdate:modelValue": $event => ((editingUser.password) = $event),
                    autocomplete: "new-password"
                  }, null, 8 /* PROPS */, _hoisted_29), [
                    [_vModelText, editingUser.password]
                  ])
                ]),
                _createElementVNode("div", _hoisted_30, [
                  _createElementVNode("label", _hoisted_31, _toDisplayString(t('admin_users_field_home', 'Home Dir')), 1 /* TEXT */),
                  _withDirectives(_createElementVNode("input", {
                    id: "adminUserHome",
                    type: "text",
                    class: "form-control form-control-sm",
                    "onUpdate:modelValue": $event => ((editingUser.home_dir) = $event)
                  }, null, 8 /* PROPS */, _hoisted_32), [
                    [_vModelText, editingUser.home_dir]
                  ])
                ]),
                _createElementVNode("div", _hoisted_33, [
                  _createElementVNode("label", _hoisted_34, _toDisplayString(t('admin_users_field_allowed_ext', 'Allowed Extensions (csv)')), 1 /* TEXT */),
                  _withDirectives(_createElementVNode("input", {
                    type: "text",
                    id: "adminUserAllowedExt",
                    class: "form-control form-control-sm",
                    "onUpdate:modelValue": $event => ((editingUser.allowed_extensions) = $event),
                    placeholder: t('admin_users_allowed_ext_placeholder', 'e.g. jpg,png,pdf')
                  }, null, 8 /* PROPS */, _hoisted_35), [
                    [_vModelText, editingUser.allowed_extensions]
                  ])
                ]),
                _createElementVNode("div", _hoisted_36, [
                  _createElementVNode("label", _hoisted_37, _toDisplayString(t('admin_users_field_blocked_ext', 'Blocked Extensions (csv)')), 1 /* TEXT */),
                  _withDirectives(_createElementVNode("input", {
                    type: "text",
                    id: "adminUserBlockedExt",
                    class: "form-control form-control-sm",
                    "onUpdate:modelValue": $event => ((editingUser.blocked_extensions) = $event),
                    placeholder: t('admin_users_blocked_ext_placeholder', 'e.g. php,exe')
                  }, null, 8 /* PROPS */, _hoisted_38), [
                    [_vModelText, editingUser.blocked_extensions]
                  ]),
                  (systemBlocklist.length)
                    ? (_openBlock(), _createElementBlock("div", _hoisted_39, [
                        _createElementVNode("span", _hoisted_40, _toDisplayString(t('admin_users_system_blocklist', 'System Blocklist (Always Applied):')), 1 /* TEXT */),
                        (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(systemBlocklist, (ext) => {
                          return (_openBlock(), _createElementBlock("span", {
                            key: ext,
                            class: "badge bg-secondary-subtle text-secondary border border-secondary-subtle me-1 admin-badge"
                          }, _toDisplayString(ext), 1 /* TEXT */))
                        }), 128 /* KEYED_FRAGMENT */))
                      ]))
                    : _createCommentVNode("v-if", true)
                ]),
                _createElementVNode("div", _hoisted_41, [
                  _createElementVNode("label", _hoisted_42, _toDisplayString(t('admin_users_field_groups', 'Groups')), 1 /* TEXT */),
                  _createElementVNode("div", _hoisted_43, [
                    (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(groupsList, (roles, gname) => {
                      return (_openBlock(), _createElementBlock("div", {
                        key: gname,
                        class: "form-check small"
                      }, [
                        _withDirectives(_createElementVNode("input", {
                          class: "form-check-input",
                          type: "checkbox",
                          id: 'admin_chk_g_'+gname,
                          value: gname,
                          "onUpdate:modelValue": $event => ((editingUser.groups) = $event)
                        }, null, 8 /* PROPS */, _hoisted_44), [
                          [_vModelCheckbox, editingUser.groups]
                        ]),
                        _createElementVNode("label", {
                          class: "form-check-label",
                          for: 'admin_chk_g_'+gname
                        }, _toDisplayString(gname), 9 /* TEXT, PROPS */, _hoisted_45)
                      ]))
                    }), 128 /* KEYED_FRAGMENT */))
                  ])
                ]),
                (!isNew)
                  ? (_openBlock(), _createElementBlock("div", _hoisted_46, [
                      _createElementVNode("div", _hoisted_47, [
                        _createElementVNode("label", _hoisted_48, _toDisplayString(t('admin_users_effective_perms', 'Effective Permissions')), 1 /* TEXT */),
                        _createElementVNode("button", {
                          class: "btn btn-outline-secondary btn-sm py-0 px-2",
                          onClick: $event => (loadEffectivePermissions(editingUser.username)),
                          disabled: effectivePermissions.loading
                        }, _toDisplayString(effectivePermissions.loading ? '…' : t('admin_users_refresh', 'Refresh')), 9 /* TEXT, PROPS */, _hoisted_49)
                      ]),
                      (effectivePermissions.loading)
                        ? (_openBlock(), _createElementBlock("div", _hoisted_50, _toDisplayString(t('admin_users_loading_perms', 'Loading permissions…')), 1 /* TEXT */))
                        : (effectivePermissions.perms.length === 0)
                          ? (_openBlock(), _createElementBlock("div", _hoisted_51, _toDisplayString(t('admin_users_no_perms', 'No permissions resolved.')), 1 /* TEXT */))
                          : (_openBlock(), _createElementBlock("div", _hoisted_52, [
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
              _createElementVNode("div", _hoisted_53, [
                _createElementVNode("button", {
                  class: "btn btn-secondary btn-sm me-2",
                  onClick: cancelEdit,
                  disabled: isSavingUser
                }, _toDisplayString(t('cancel', 'Cancel')), 9 /* TEXT, PROPS */, _hoisted_54),
                _createElementVNode("button", {
                  class: "btn btn-primary btn-sm",
                  onClick: saveUser,
                  disabled: isSavingUser
                }, _toDisplayString(isSavingUser ? t('admin_saving', 'Saving…') : t('save', 'Save')), 9 /* TEXT, PROPS */, _hoisted_55)
              ])
            ])
          ]))
        : _createCommentVNode("v-if", true)
    ]))
  }
}
})(Vue),
    data() {
        return {
            users: [],
            groupsList: {},
            permissionCatalog: [],
            systemBlocklist: [],
            searchQuery: '',
            error: null,
            editingUser: null,
            isNew: false,
            isSavingUser: false,
            effectivePermissions: { username: null, perms: [], loading: false },
            currentUsername: window.username
        };
    },
    computed: {
        filteredUsers() {
            const q = this.searchQuery.toLowerCase();
            if (!q) return this.users;
            return this.users.filter((u) => {
                const username = String(u.username || '').toLowerCase();
                const home = String(u.home_dir || '').toLowerCase();
                return username.includes(q) || home.includes(q);
            });
        }
    },
    mounted() {
        this.init();
    },
    methods: {
        t(key, fallback = '') {
            const value = i18n.t(key);
            if (value === key) {
                return fallback || key;
            }
            return value;
        },
        async init() {
            await Promise.all([this.loadUsers(), this.loadGroups(), this.loadSystemInfo(), this.loadPermissionsCatalog()]);
        },
        async loadUsers() {
            this.error = null;
            try {
                this.users = await Api.get('users');
            } catch (e) {
                this.error = e.message;
            }
        },
        async loadGroups() {
            try {
                this.groupsList = await Api.get('groups');
            } catch (e) {
                // Non-blocking
            }
        },
        async loadSystemInfo() {
            try {
                const system = await Api.get('system');
                this.systemBlocklist = Array.isArray(system.system_blocklist) ? system.system_blocklist : [];
            } catch (e) {
                // Non-blocking
            }
        },
        async loadPermissionsCatalog() {
            try {
                const catalog = await Api.get('permissions/catalog');
                if (Array.isArray(catalog)) {
                    this.permissionCatalog = catalog;
                }
            } catch (e) {
                // Non-blocking
            }
        },
        showAddForm() {
            this.isNew = true;
            this.editingUser = {
                username: '',
                password: '',
                role: 'user',
                home_dir: '/',
                groups: [],
                allowed_extensions: '',
                blocked_extensions: ''
            };
            this.effectivePermissions = { username: null, perms: [], loading: false };
        },
        editUser(user) {
            this.isNew = false;
            this.editingUser = {
                ...user,
                password: '',
                groups: user.groups || [],
                allowed_extensions: user.allowed_extensions || '',
                blocked_extensions: user.blocked_extensions || ''
            };
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
                if (this.isNew) {
                    await Api.post('users', this.editingUser);
                } else {
                    await Api.put('users/' + this.editingUser.username, this.editingUser);
                }
                await this.loadUsers();
                this.toastSuccess(this.t('admin_users_saved', 'User saved.'));
                this.editingUser = null;
            } catch (e) {
                this.error = e.message;
                this.toastError(this.t('admin_users_save_failed', 'Failed to save user.'));
            } finally {
                this.isSavingUser = false;
            }
        },
        async deleteUser(user) {
            const confirmed = await this.confirmDanger(
                this.t('admin_users_delete_title', 'Delete user?'),
                this.t('admin_users_delete_text_prefix', 'This will delete ') + user.username + '.'
            );
            if (!confirmed) return;
            try {
                await Api.delete('users/' + user.username);
                await this.loadUsers();
                this.toastSuccess(this.t('admin_users_deleted', 'User deleted.'));
            } catch (e) {
                this.error = e.message;
                this.toastError(this.t('admin_users_delete_failed', 'Failed to delete user.'));
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
                confirmButtonText: this.t('admin_confirm_yes', 'Yes, continue'),
                cancelButtonText: this.t('cancel', 'Cancel'),
                confirmButtonColor: '#dc3545'
            });
            return Boolean(result.isConfirmed);
        }
    }
};
