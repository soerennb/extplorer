const AdminGroups = {
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
const _hoisted_13 = {
  key: 0,
  class: "small text-muted mt-1"
}
const _hoisted_14 = { class: "text-end" }
const _hoisted_15 = ["onClick", "title", "aria-label"]
const _hoisted_16 = ["onClick", "disabled", "title", "aria-label"]
const _hoisted_17 = { key: 0 }
const _hoisted_18 = {
  colspan: "4",
  class: "text-center text-muted py-4"
}
const _hoisted_19 = {
  key: 1,
  class: "card mt-3 bg-body-tertiary border"
}
const _hoisted_20 = { class: "card-body" }
const _hoisted_21 = ["aria-label", "placeholder", "onUpdate:modelValue"]
const _hoisted_22 = { class: "small d-block mb-1" }
const _hoisted_23 = { class: "d-flex flex-wrap gap-2" }
const _hoisted_24 = ["id", "value", "onUpdate:modelValue"]
const _hoisted_25 = ["for"]
const _hoisted_26 = { class: "mt-3 text-end" }
const _hoisted_27 = ["onClick", "disabled"]
const _hoisted_28 = ["onClick", "disabled"]

return function render(_ctx, _cache) {
  with (_ctx) {
    const { toDisplayString: _toDisplayString, createElementVNode: _createElementVNode, createTextVNode: _createTextVNode, openBlock: _openBlock, createElementBlock: _createElementBlock, createCommentVNode: _createCommentVNode, renderList: _renderList, Fragment: _Fragment, vModelText: _vModelText, withDirectives: _withDirectives, vModelCheckbox: _vModelCheckbox } = _Vue

    return (_openBlock(), _createElementBlock("div", null, [
      _createElementVNode("div", _hoisted_1, [
        _createElementVNode("div", null, [
          _createElementVNode("div", _hoisted_2, _toDisplayString(t('admin_groups_desc', 'Groups bundle roles for easier assignment.')), 1 /* TEXT */)
        ]),
        _createElementVNode("button", {
          class: "btn btn-success btn-sm",
          onClick: showAddGroupForm
        }, [
          _cache[0] || (_cache[0] = _createElementVNode("i", { class: "ri-add-line me-1" }, null, -1 /* CACHED */)),
          _createTextVNode(" " + _toDisplayString(t('admin_groups_add', 'Add Group')), 1 /* TEXT */)
        ], 8 /* PROPS */, _hoisted_3)
      ]),
      error
        ? (_openBlock(), _createElementBlock("div", _hoisted_4, _toDisplayString(error), 1 /* TEXT */))
        : _createCommentVNode("v-if", true),
      _createElementVNode("div", _hoisted_5, [
        _createElementVNode("table", _hoisted_6, [
          _createElementVNode("thead", _hoisted_7, [
            _createElementVNode("tr", null, [
              _createElementVNode("th", null, _toDisplayString(t('admin_groups_col_name', 'Group Name')), 1 /* TEXT */),
              _createElementVNode("th", null, _toDisplayString(t('admin_groups_col_usage', 'Usage')), 1 /* TEXT */),
              _createElementVNode("th", null, _toDisplayString(t('admin_groups_col_roles', 'Assigned Roles')), 1 /* TEXT */),
              _createElementVNode("th", _hoisted_8, _toDisplayString(t('admin_actions', 'Actions')), 1 /* TEXT */)
            ])
          ]),
          _createElementVNode("tbody", null, [
            (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(groupsList, (roles, name) => {
              return (_openBlock(), _createElementBlock("tr", { key: name }, [
                _createElementVNode("td", _hoisted_9, _toDisplayString(name), 1 /* TEXT */),
                _createElementVNode("td", null, [
                  _createElementVNode("div", _hoisted_10, [
                    _createElementVNode("span", {
                      class: "badge text-bg-secondary",
                      title: t('admin_groups_assigned_users_title', 'Assigned users: ') + groupUsage(name).assigned_users_count
                    }, _toDisplayString(t('admin_groups_users_badge', 'Users')) + " " + _toDisplayString(groupUsage(name).assigned_users_count), 9 /* TEXT, PROPS */, _hoisted_11),
                    _createElementVNode("span", {
                      class: "badge text-bg-info",
                      title: t('admin_groups_roles_granted_title', 'Roles granted: ') + groupUsage(name).roles_count
                    }, _toDisplayString(t('admin_groups_roles_badge', 'Roles')) + " " + _toDisplayString(groupUsage(name).roles_count), 9 /* TEXT, PROPS */, _hoisted_12)
                  ]),
                  (groupUsage(name).assigned_users_count)
                    ? (_openBlock(), _createElementBlock("div", _hoisted_13, _toDisplayString(t('admin_groups_unassign_hint', 'Unassign users before deleting.')), 1 /* TEXT */))
                    : _createCommentVNode("v-if", true)
                ]),
                _createElementVNode("td", null, [
                  (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(roles, (r) => {
                    return (_openBlock(), _createElementBlock("span", {
                      key: r,
                      class: "badge bg-info me-1"
                    }, _toDisplayString(r), 1 /* TEXT */))
                  }), 128 /* KEYED_FRAGMENT */))
                ]),
                _createElementVNode("td", _hoisted_14, [
                  _createElementVNode("button", {
                    class: "btn btn-sm btn-outline-primary me-1",
                    onClick: $event => (editGroup(name, roles)),
                    title: t('admin_groups_group_label', 'Group: ') + name,
                    "aria-label": t('admin_groups_group_label', 'Group: ') + name
                  }, [...(_cache[1] || (_cache[1] = [
                    _createElementVNode("i", {
                      class: "ri-edit-line",
                      "aria-hidden": "true"
                    }, null, -1 /* CACHED */)
                  ]))], 8 /* PROPS */, _hoisted_15),
                  _createElementVNode("button", {
                    class: "btn btn-sm btn-outline-danger",
                    onClick: $event => (deleteGroup(name)),
                    disabled: !canDeleteGroup(name),
                    title: deleteDisabledReason(name),
                    "aria-label": t('delete', 'Delete') + ': ' + name
                  }, [...(_cache[2] || (_cache[2] = [
                    _createElementVNode("i", {
                      class: "ri-delete-bin-line",
                      "aria-hidden": "true"
                    }, null, -1 /* CACHED */)
                  ]))], 8 /* PROPS */, _hoisted_16)
                ])
              ]))
            }), 128 /* KEYED_FRAGMENT */)),
            (Object.keys(groupsList).length === 0)
              ? (_openBlock(), _createElementBlock("tr", _hoisted_17, [
                  _createElementVNode("td", _hoisted_18, _toDisplayString(t('admin_groups_empty', 'No groups defined.')), 1 /* TEXT */)
                ]))
              : _createCommentVNode("v-if", true)
          ])
        ])
      ]),
      editingGroup
        ? (_openBlock(), _createElementBlock("div", _hoisted_19, [
            _createElementVNode("div", _hoisted_20, [
              _createElementVNode("h6", null, _toDisplayString(t('admin_groups_group_label', 'Group: ')) + _toDisplayString(editingGroup.name || t('admin_new', 'New')), 1 /* TEXT */),
              (!editingGroup.isEdit)
                ? _withDirectives((_openBlock(), _createElementBlock("input", {
                    key: 0,
                    type: "text",
                    id: "adminGroupName",
                    class: "form-control form-control-sm mb-2",
                    "aria-label": t('admin_groups_col_name', 'Group Name'),
                    placeholder: t('admin_groups_col_name', 'Group Name'),
                    "onUpdate:modelValue": $event => ((editingGroup.name) = $event)
                  }, null, 8 /* PROPS */, _hoisted_21)), [
                    [_vModelText, editingGroup.name]
                  ])
                : _createCommentVNode("v-if", true),
              _createElementVNode("label", _hoisted_22, _toDisplayString(t('admin_groups_roles_label', 'Roles')), 1 /* TEXT */),
              _createElementVNode("div", _hoisted_23, [
                (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(rolesList, (perms, rname) => {
                  return (_openBlock(), _createElementBlock("div", {
                    key: rname,
                    class: "form-check small"
                  }, [
                    _withDirectives(_createElementVNode("input", {
                      class: "form-check-input",
                      type: "checkbox",
                      id: 'admin_group_chk_r_'+rname,
                      value: rname,
                      "onUpdate:modelValue": $event => ((editingGroup.roles) = $event)
                    }, null, 8 /* PROPS */, _hoisted_24), [
                      [_vModelCheckbox, editingGroup.roles]
                    ]),
                    _createElementVNode("label", {
                      class: "form-check-label",
                      for: 'admin_group_chk_r_'+rname
                    }, _toDisplayString(rname), 9 /* TEXT, PROPS */, _hoisted_25)
                  ]))
                }), 128 /* KEYED_FRAGMENT */))
              ]),
              _createElementVNode("div", _hoisted_26, [
                _createElementVNode("button", {
                  class: "btn btn-secondary btn-sm me-2",
                  onClick: $event => (editingGroup = null),
                  disabled: isSavingGroup
                }, _toDisplayString(t('cancel', 'Cancel')), 9 /* TEXT, PROPS */, _hoisted_27),
                _createElementVNode("button", {
                  class: "btn btn-primary btn-sm",
                  onClick: saveGroup,
                  disabled: isSavingGroup
                }, _toDisplayString(isSavingGroup ? t('admin_saving', 'Saving…') : t('save', 'Save')), 9 /* TEXT, PROPS */, _hoisted_28)
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
            groupsList: {},
            rolesList: {},
            usageByGroup: {},
            editingGroup: null,
            isSavingGroup: false,
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
            await Promise.all([this.loadGroups(), this.loadRoles()]);
        },
        async loadGroups() {
            try {
                this.groupsList = await Api.get('groups');
                await this.loadUsageForGroups(Object.keys(this.groupsList));
            } catch (e) {
                this.error = e.message;
            }
        },
        async loadUsageForGroups(groupNames) {
            const usageEntries = await Promise.all(groupNames.map(async (groupName) => {
                try {
                    const usage = await Api.get(`groups/${encodeURIComponent(groupName)}/usage`);
                    return [groupName, usage];
                } catch (e) {
                    return [groupName, this.emptyUsage(groupName)];
                }
            }));

            this.usageByGroup = usageEntries.reduce((acc, [groupName, usage]) => {
                acc[groupName] = usage;
                return acc;
            }, {});
        },
        emptyUsage(groupName) {
            return {
                group: groupName,
                assigned_users: [],
                assigned_users_count: 0,
                roles: this.groupsList[groupName] || [],
                roles_count: Array.isArray(this.groupsList[groupName]) ? this.groupsList[groupName].length : 0
            };
        },
        groupUsage(groupName) {
            return this.usageByGroup[groupName] || this.emptyUsage(groupName);
        },
        canDeleteGroup(groupName) {
            const usage = this.groupUsage(groupName);
            return (usage.assigned_users_count ?? 0) === 0;
        },
        deleteDisabledReason(groupName) {
            const usage = this.groupUsage(groupName);
            if ((usage.assigned_users_count ?? 0) > 0) {
                return this.t('admin_groups_delete_blocked', 'Group is still assigned to users');
            }
            return this.t('admin_groups_delete', 'Delete group');
        },
        async loadRoles() {
            try {
                this.rolesList = await Api.get('roles');
            } catch (e) {
                // Non-blocking
            }
        },
        showAddGroupForm() {
            this.editingGroup = { name: '', roles: [], isEdit: false };
        },
        editGroup(name, roles) {
            this.editingGroup = { name, roles: [...roles], isEdit: true };
        },
        async saveGroup() {
            if (!this.editingGroup) return;
            this.isSavingGroup = true;
            try {
                await Api.post('groups', { name: this.editingGroup.name, roles: this.editingGroup.roles });
                await this.loadGroups();
                this.editingGroup = null;
                this.toastSuccess(this.t('admin_groups_saved', 'Group saved.'));
            } catch (e) {
                this.error = e.message;
                this.toastError(this.t('admin_groups_save_failed', 'Failed to save group.'));
            } finally {
                this.isSavingGroup = false;
            }
        },
        async deleteGroup(name) {
            try {
                const usage = await Api.get(`groups/${encodeURIComponent(name)}/usage`);
                this.usageByGroup[name] = usage;

                if ((usage.assigned_users_count ?? 0) > 0) {
                    const usersPreview = (usage.assigned_users || []).slice(0, 6).join(', ');
                    await Swal.fire({
                        icon: 'warning',
                        title: this.t('admin_groups_in_use_title', 'Group still assigned'),
                        text: `${this.t('admin_groups_users_badge', 'Users')} (${usage.assigned_users_count}): ${usersPreview}${usage.assigned_users_count > 6 ? ', …' : ''}`,
                        confirmButtonText: this.t('admin_ok', 'OK')
                    });
                    return;
                }

                const confirmed = await this.confirmDanger(
                    this.t('admin_groups_delete_title', 'Delete group?'),
                    this.t('admin_groups_delete_text_prefix', 'This will delete the group ') + name + '.'
                );
                if (!confirmed) return;

                await Api.delete('groups/' + name);
                await this.loadGroups();
                this.toastSuccess(this.t('admin_groups_deleted', 'Group deleted.'));
            } catch (e) {
                this.error = e.message;
                this.toastError(this.t('admin_groups_delete_failed', 'Failed to delete group.'));
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
