const AdminRoles = {
    render: ((Vue) => {
const _Vue = Vue
const { createElementVNode: _createElementVNode, createCommentVNode: _createCommentVNode, createTextVNode: _createTextVNode } = _Vue

const _hoisted_1 = { class: "d-flex align-items-end justify-content-between gap-2 mb-3" }
const _hoisted_2 = { class: "small text-muted" }
const _hoisted_3 = ["onClick"]
const _hoisted_4 = {
  key: 0,
  class: "alert alert-danger small"
}
const _hoisted_5 = { class: "table-responsive admin-table-scroll" }
const _hoisted_6 = { class: "table table-striped table-hover small align-middle" }
const _hoisted_7 = { class: "sticky-top bg-body" }
const _hoisted_8 = { class: "text-end" }
const _hoisted_9 = { class: "fw-semibold" }
const _hoisted_10 = { class: "d-flex flex-wrap gap-1 align-items-center" }
const _hoisted_11 = ["title"]
const _hoisted_12 = ["title"]
const _hoisted_13 = ["title"]
const _hoisted_14 = ["title"]
const _hoisted_15 = {
  key: 0,
  class: "small text-muted mt-1"
}
const _hoisted_16 = { class: "small" }
const _hoisted_17 = { class: "text-end" }
const _hoisted_18 = ["onClick", "title", "aria-label"]
const _hoisted_19 = ["onClick", "disabled", "title", "aria-label"]
const _hoisted_20 = { key: 0 }
const _hoisted_21 = {
  colspan: "4",
  class: "text-center text-muted py-4"
}
const _hoisted_22 = {
  key: 1,
  class: "card mt-3 bg-body-tertiary border"
}
const _hoisted_23 = { class: "card-body" }
const _hoisted_24 = ["aria-label", "placeholder", "onUpdate:modelValue"]
const _hoisted_25 = {
  class: "small d-block mb-1",
  for: "adminRolePermissions"
}
const _hoisted_26 = ["onUpdate:modelValue"]
const _hoisted_27 = {
  key: 1,
  class: "mt-2"
}
const _hoisted_28 = { class: "small text-muted d-block admin-note" }
const _hoisted_29 = { class: "mt-3 text-end" }
const _hoisted_30 = ["onClick", "disabled"]
const _hoisted_31 = ["onClick", "disabled"]

return function render(_ctx, _cache) {
  with (_ctx) {
    const { toDisplayString: _toDisplayString, createElementVNode: _createElementVNode, createTextVNode: _createTextVNode, openBlock: _openBlock, createElementBlock: _createElementBlock, createCommentVNode: _createCommentVNode, renderList: _renderList, Fragment: _Fragment, vModelText: _vModelText, withDirectives: _withDirectives } = _Vue

    return (_openBlock(), _createElementBlock("div", null, [
      _createElementVNode("div", _hoisted_1, [
        _createElementVNode("div", _hoisted_2, _toDisplayString(t('admin_roles_desc', 'Roles define permission sets that can be assigned directly or via groups.')), 1 /* TEXT */),
        _createElementVNode("button", {
          class: "btn btn-success btn-sm",
          onClick: showAddRoleForm
        }, [
          _cache[0] || (_cache[0] = _createElementVNode("i", { class: "ri-add-line me-1" }, null, -1 /* CACHED */)),
          _createTextVNode(" " + _toDisplayString(t('admin_roles_add', 'Add Role')), 1 /* TEXT */)
        ], 8 /* PROPS */, _hoisted_3)
      ]),
      error
        ? (_openBlock(), _createElementBlock("div", _hoisted_4, _toDisplayString(error), 1 /* TEXT */))
        : _createCommentVNode("v-if", true),
      _createElementVNode("div", _hoisted_5, [
        _createElementVNode("table", _hoisted_6, [
          _createElementVNode("thead", _hoisted_7, [
            _createElementVNode("tr", null, [
              _createElementVNode("th", null, _toDisplayString(t('admin_roles_col_name', 'Role Name')), 1 /* TEXT */),
              _createElementVNode("th", null, _toDisplayString(t('admin_roles_col_usage', 'Usage')), 1 /* TEXT */),
              _createElementVNode("th", null, _toDisplayString(t('admin_roles_col_permissions', 'Permissions')), 1 /* TEXT */),
              _createElementVNode("th", _hoisted_8, _toDisplayString(t('admin_actions', 'Actions')), 1 /* TEXT */)
            ])
          ]),
          _createElementVNode("tbody", null, [
            (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(rolesList, (perms, name) => {
              return (_openBlock(), _createElementBlock("tr", { key: name }, [
                _createElementVNode("td", _hoisted_9, _toDisplayString(name), 1 /* TEXT */),
                _createElementVNode("td", null, [
                  _createElementVNode("div", _hoisted_10, [
                    (roleUsage(name).protected)
                      ? (_openBlock(), _createElementBlock("span", {
                          key: 0,
                          class: "badge text-bg-warning",
                          title: t('admin_roles_system_role_title', 'System role')
                        }, _toDisplayString(t('admin_roles_system_role_badge', 'System')), 9 /* TEXT, PROPS */, _hoisted_11))
                      : _createCommentVNode("v-if", true),
                    _createElementVNode("span", {
                      class: "badge text-bg-secondary",
                      title: t('admin_roles_direct_users_title', 'Direct users: ') + roleUsage(name).direct_users_count
                    }, _toDisplayString(t('admin_roles_users_badge', 'Users')) + " " + _toDisplayString(roleUsage(name).direct_users_count), 9 /* TEXT, PROPS */, _hoisted_12),
                    _createElementVNode("span", {
                      class: "badge text-bg-info",
                      title: t('admin_roles_groups_title', 'Groups: ') + roleUsage(name).groups_count
                    }, _toDisplayString(t('admin_roles_groups_badge', 'Groups')) + " " + _toDisplayString(roleUsage(name).groups_count), 9 /* TEXT, PROPS */, _hoisted_13),
                    (roleUsage(name).users_via_groups_count)
                      ? (_openBlock(), _createElementBlock("span", {
                          key: 1,
                          class: "badge text-bg-light border",
                          title: t('admin_roles_via_groups_title', 'Users via groups: ') + roleUsage(name).users_via_groups_count
                        }, _toDisplayString(t('admin_roles_via_groups_badge', 'Via Groups')) + " " + _toDisplayString(roleUsage(name).users_via_groups_count), 9 /* TEXT, PROPS */, _hoisted_14))
                      : _createCommentVNode("v-if", true)
                  ]),
                  (roleUsage(name).direct_users_count || roleUsage(name).groups_count)
                    ? (_openBlock(), _createElementBlock("div", _hoisted_15, _toDisplayString(t('admin_roles_remove_assignments_hint', 'Remove assignments before deleting.')), 1 /* TEXT */))
                    : _createCommentVNode("v-if", true)
                ]),
                _createElementVNode("td", null, [
                  _createElementVNode("code", _hoisted_16, _toDisplayString(perms.join(', ')), 1 /* TEXT */)
                ]),
                _createElementVNode("td", _hoisted_17, [
                  _createElementVNode("button", {
                    class: "btn btn-sm btn-outline-primary me-1",
                    onClick: $event => (editRole(name, perms)),
                    title: t('admin_roles_role_label', 'Role: ') + name,
                    "aria-label": t('admin_roles_role_label', 'Role: ') + name
                  }, [...(_cache[1] || (_cache[1] = [
                    _createElementVNode("i", {
                      class: "ri-edit-line",
                      "aria-hidden": "true"
                    }, null, -1 /* CACHED */)
                  ]))], 8 /* PROPS */, _hoisted_18),
                  _createElementVNode("button", {
                    class: "btn btn-sm btn-outline-danger",
                    onClick: $event => (deleteRole(name)),
                    disabled: !canDeleteRole(name),
                    title: deleteDisabledReason(name),
                    "aria-label": t('delete', 'Delete') + ': ' + name
                  }, [...(_cache[2] || (_cache[2] = [
                    _createElementVNode("i", {
                      class: "ri-delete-bin-line",
                      "aria-hidden": "true"
                    }, null, -1 /* CACHED */)
                  ]))], 8 /* PROPS */, _hoisted_19)
                ])
              ]))
            }), 128 /* KEYED_FRAGMENT */)),
            (Object.keys(rolesList).length === 0)
              ? (_openBlock(), _createElementBlock("tr", _hoisted_20, [
                  _createElementVNode("td", _hoisted_21, _toDisplayString(t('admin_roles_empty', 'No roles defined.')), 1 /* TEXT */)
                ]))
              : _createCommentVNode("v-if", true)
          ])
        ])
      ]),
      editingRole
        ? (_openBlock(), _createElementBlock("div", _hoisted_22, [
            _createElementVNode("div", _hoisted_23, [
              _createElementVNode("h6", null, _toDisplayString(t('admin_roles_role_label', 'Role: ')) + _toDisplayString(editingRole.name || t('admin_new', 'New')), 1 /* TEXT */),
              (!editingRole.isEdit)
                ? _withDirectives((_openBlock(), _createElementBlock("input", {
                    key: 0,
                    type: "text",
                    id: "adminRoleName",
                    class: "form-control form-control-sm mb-2",
                    "aria-label": t('admin_roles_col_name', 'Role Name'),
                    placeholder: t('admin_roles_col_name', 'Role Name'),
                    "onUpdate:modelValue": $event => ((editingRole.name) = $event)
                  }, null, 8 /* PROPS */, _hoisted_24)), [
                    [_vModelText, editingRole.name]
                  ])
                : _createCommentVNode("v-if", true),
              _createElementVNode("label", _hoisted_25, _toDisplayString(t('admin_roles_permissions_label', 'Permissions (comma separated or *)')), 1 /* TEXT */),
              _withDirectives(_createElementVNode("input", {
                id: "adminRolePermissions",
                type: "text",
                class: "form-control form-control-sm",
                "onUpdate:modelValue": $event => ((editingRole.permsString) = $event)
              }, null, 8 /* PROPS */, _hoisted_26), [
                [_vModelText, editingRole.permsString]
              ]),
              (permissionCatalog.length)
                ? (_openBlock(), _createElementBlock("div", _hoisted_27, [
                    _createElementVNode("span", _hoisted_28, _toDisplayString(t('admin_roles_known_perms', 'Known permissions:')), 1 /* TEXT */),
                    (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(permissionCatalog, (perm) => {
                      return (_openBlock(), _createElementBlock("span", {
                        key: perm,
                        class: "badge bg-secondary-subtle text-secondary border border-secondary-subtle me-1 admin-badge"
                      }, _toDisplayString(perm), 1 /* TEXT */))
                    }), 128 /* KEYED_FRAGMENT */))
                  ]))
                : _createCommentVNode("v-if", true),
              _createElementVNode("div", _hoisted_29, [
                _createElementVNode("button", {
                  class: "btn btn-secondary btn-sm me-2",
                  onClick: $event => (editingRole = null),
                  disabled: isSavingRole
                }, _toDisplayString(t('cancel', 'Cancel')), 9 /* TEXT, PROPS */, _hoisted_30),
                _createElementVNode("button", {
                  class: "btn btn-primary btn-sm",
                  onClick: saveRole,
                  disabled: isSavingRole
                }, _toDisplayString(isSavingRole ? t('admin_saving', 'Saving…') : t('save', 'Save')), 9 /* TEXT, PROPS */, _hoisted_31)
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
            rolesList: {},
            permissionCatalog: [],
            usageByRole: {},
            protectedRoles: ['admin', 'user'],
            editingRole: null,
            isSavingRole: false,
            error: null
        };
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
            await Promise.all([this.loadRoles(), this.loadPermissionsCatalog()]);
        },
        async loadRoles() {
            try {
                this.rolesList = await Api.get('roles');
                await this.loadUsageForRoles(Object.keys(this.rolesList));
            } catch (e) {
                this.error = e.message;
            }
        },
        async loadUsageForRoles(roleNames) {
            const usageEntries = await Promise.all(roleNames.map(async (roleName) => {
                try {
                    const usage = await Api.get(`roles/${encodeURIComponent(roleName)}/usage`);
                    return [roleName, usage];
                } catch (e) {
                    return [roleName, this.emptyUsage(roleName)];
                }
            }));

            this.usageByRole = usageEntries.reduce((acc, [roleName, usage]) => {
                acc[roleName] = usage;
                return acc;
            }, {});
        },
        emptyUsage(roleName) {
            return {
                role: roleName,
                protected: this.protectedRoles.includes(roleName),
                direct_users: [],
                direct_users_count: 0,
                groups: [],
                groups_count: 0,
                users_via_groups: [],
                users_via_groups_count: 0
            };
        },
        roleUsage(roleName) {
            return this.usageByRole[roleName] || this.emptyUsage(roleName);
        },
        canDeleteRole(roleName) {
            const usage = this.roleUsage(roleName);
            if (usage.protected) return false;
            return usage.direct_users_count === 0 && usage.groups_count === 0;
        },
        deleteDisabledReason(roleName) {
            const usage = this.roleUsage(roleName);
            if (usage.protected) return this.t('admin_roles_delete_protected', 'System roles cannot be deleted');
            if (usage.direct_users_count > 0 || usage.groups_count > 0) {
                return this.t('admin_roles_delete_blocked', 'Role is still assigned to users or groups');
            }
            return this.t('admin_roles_delete', 'Delete role');
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
        showAddRoleForm() {
            this.editingRole = { name: '', permsString: '', isEdit: false };
        },
        editRole(name, perms) {
            this.editingRole = { name, permsString: perms.join(','), isEdit: true };
        },
        async saveRole() {
            if (!this.editingRole) return;
            this.isSavingRole = true;
            try {
                const perms = this.editingRole.permsString.split(',').map(p => p.trim()).filter(p => p);
                await Api.post('roles', { name: this.editingRole.name, permissions: perms });
                await this.loadRoles();
                this.editingRole = null;
                this.toastSuccess(this.t('admin_roles_saved', 'Role saved.'));
            } catch (e) {
                this.error = e.message;
                this.toastError(this.t('admin_roles_save_failed', 'Failed to save role.'));
            } finally {
                this.isSavingRole = false;
            }
        },
        async deleteRole(name) {
            try {
                const usage = await Api.get(`roles/${encodeURIComponent(name)}/usage`);
                this.usageByRole[name] = usage;

                if (usage.protected) {
                    await Swal.fire({
                        icon: 'info',
                        title: this.t('admin_roles_system_role_title', 'System role'),
                        text: name + ' ' + this.t('admin_roles_system_role_text', 'is a protected system role and cannot be deleted.'),
                        confirmButtonText: this.t('admin_ok', 'OK')
                    });
                    return;
                }

                if ((usage.direct_users_count ?? 0) > 0 || (usage.groups_count ?? 0) > 0) {
                    const directUsersPreview = (usage.direct_users || []).slice(0, 5).join(', ');
                    const groupsPreview = (usage.groups || []).slice(0, 5).join(', ');
                    const details = [
                        usage.direct_users_count
                            ? `${this.t('admin_roles_direct_users_label', 'Direct users')} (${usage.direct_users_count}): ${directUsersPreview}${usage.direct_users_count > 5 ? ', …' : ''}`
                            : null,
                        usage.groups_count
                            ? `${this.t('admin_roles_groups_badge', 'Groups')} (${usage.groups_count}): ${groupsPreview}${usage.groups_count > 5 ? ', …' : ''}`
                            : null,
                    ].filter(Boolean).join('\n');

                    await Swal.fire({
                        icon: 'warning',
                        title: this.t('admin_roles_in_use_title', 'Role still in use'),
                        text: details || this.t('admin_roles_in_use_text', 'This role is still assigned.'),
                        confirmButtonText: this.t('admin_ok', 'OK')
                    });
                    return;
                }

                const confirmed = await this.confirmDanger(
                    this.t('admin_roles_delete_title', 'Delete role?'),
                    this.t('admin_roles_delete_text_prefix', 'This will delete the role ') + name + '.'
                );
                if (!confirmed) return;

                await Api.delete('roles/' + name);
                await this.loadRoles();
                this.toastSuccess(this.t('admin_roles_deleted', 'Role deleted.'));
            } catch (e) {
                this.error = e.message;
                this.toastError(this.t('admin_roles_delete_failed', 'Failed to delete role.'));
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
