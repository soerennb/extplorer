const AdminApp = {
    render: ((Vue) => {
const _Vue = Vue
const { createElementVNode: _createElementVNode, createCommentVNode: _createCommentVNode, createTextVNode: _createTextVNode } = _Vue

const _hoisted_1 = { class: "admin-page" }
const _hoisted_2 = { class: "admin-sidebar border-end" }
const _hoisted_3 = { class: "admin-sidebar-header" }
const _hoisted_4 = { class: "fw-semibold" }
const _hoisted_5 = { class: "small text-muted" }
const _hoisted_6 = { class: "nav flex-column admin-sidebar-nav" }
const _hoisted_7 = ["onClick"]
const _hoisted_8 = { class: "admin-sidebar-footer small text-muted" }
const _hoisted_9 = { class: "fw-semibold text-body" }
const _hoisted_10 = { class: "admin-main" }
const _hoisted_11 = { class: "admin-main-header border-bottom" }
const _hoisted_12 = { class: "h5 mb-1" }
const _hoisted_13 = { class: "small text-muted" }
const _hoisted_14 = { class: "d-flex gap-2" }
const _hoisted_15 = ["href"]
const _hoisted_16 = { class: "admin-main-body" }

return function render(_ctx, _cache) {
  with (_ctx) {
    const { toDisplayString: _toDisplayString, createElementVNode: _createElementVNode, renderList: _renderList, Fragment: _Fragment, openBlock: _openBlock, createElementBlock: _createElementBlock, normalizeClass: _normalizeClass, withModifiers: _withModifiers, createTextVNode: _createTextVNode, resolveComponent: _resolveComponent, createBlock: _createBlock, createCommentVNode: _createCommentVNode } = _Vue

    const _component_admin_users = _resolveComponent("admin-users")
    const _component_admin_groups = _resolveComponent("admin-groups")
    const _component_admin_roles = _resolveComponent("admin-roles")
    const _component_admin_logs = _resolveComponent("admin-logs")
    const _component_admin_settings = _resolveComponent("admin-settings")
    const _component_admin_system = _resolveComponent("admin-system")

    return (_openBlock(), _createElementBlock("div", _hoisted_1, [
      _createElementVNode("aside", _hoisted_2, [
        _createElementVNode("div", _hoisted_3, [
          _createElementVNode("div", _hoisted_4, _toDisplayString(t('admin_console', 'Admin Console')), 1 /* TEXT */),
          _createElementVNode("div", _hoisted_5, _toDisplayString(t('app_name', 'eXtplorer 3')), 1 /* TEXT */)
        ]),
        _createElementVNode("nav", _hoisted_6, [
          (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(navItems, (item) => {
            return (_openBlock(), _createElementBlock("a", {
              key: item.section,
              href: "#",
              class: _normalizeClass(["nav-link d-flex align-items-center gap-2", { active: activeSection === item.section }]),
              onClick: _withModifiers($event => (navigate(item.section, item.section === 'settings' ? activeSettingsTab : null)), ["prevent"])
            }, [
              _createElementVNode("i", {
                class: _normalizeClass(item.icon)
              }, null, 2 /* CLASS */),
              _createElementVNode("span", null, _toDisplayString(t(item.labelKey, item.labelFallback)), 1 /* TEXT */)
            ], 10 /* CLASS, PROPS */, _hoisted_7))
          }), 128 /* KEYED_FRAGMENT */))
        ]),
        _createElementVNode("div", _hoisted_8, [
          _createElementVNode("div", null, _toDisplayString(t('admin_signed_in_as', 'Signed in as')), 1 /* TEXT */),
          _createElementVNode("div", _hoisted_9, _toDisplayString(currentUsername), 1 /* TEXT */)
        ])
      ]),
      _createElementVNode("main", _hoisted_10, [
        _createElementVNode("div", _hoisted_11, [
          _createElementVNode("div", null, [
            _createElementVNode("h1", _hoisted_12, _toDisplayString(activeLabel), 1 /* TEXT */),
            _createElementVNode("div", _hoisted_13, _toDisplayString(activeDescription), 1 /* TEXT */)
          ]),
          _createElementVNode("div", _hoisted_14, [
            _createElementVNode("a", {
              class: "btn btn-primary admin-back-button",
              href: baseUrl
            }, [
              _cache[0] || (_cache[0] = _createElementVNode("i", { class: "ri-arrow-left-line me-2" }, null, -1 /* CACHED */)),
              _createTextVNode(" " + _toDisplayString(t('admin_back_to_file_manager', 'Back to File Manager')), 1 /* TEXT */)
            ], 8 /* PROPS */, _hoisted_15)
          ])
        ]),
        _createElementVNode("div", _hoisted_16, [
          (activeSection === 'users')
            ? (_openBlock(), _createBlock(_component_admin_users, {
                key: 0,
                ref: "users"
              }, null, 512 /* NEED_PATCH */))
            : (activeSection === 'groups')
              ? (_openBlock(), _createBlock(_component_admin_groups, {
                  key: 1,
                  ref: "groups"
                }, null, 512 /* NEED_PATCH */))
              : (activeSection === 'roles')
                ? (_openBlock(), _createBlock(_component_admin_roles, {
                    key: 2,
                    ref: "roles"
                  }, null, 512 /* NEED_PATCH */))
                : (activeSection === 'logs')
                  ? (_openBlock(), _createBlock(_component_admin_logs, {
                      key: 3,
                      ref: "logs"
                    }, null, 512 /* NEED_PATCH */))
                  : (activeSection === 'settings')
                    ? (_openBlock(), _createBlock(_component_admin_settings, {
                        key: 4,
                        ref: "settings",
                        "initial-tab": activeSettingsTab,
                        "on-tab-change": handleSettingsTabChange
                      }, null, 8 /* PROPS */, ["initial-tab", "on-tab-change"]))
                    : (activeSection === 'system')
                      ? (_openBlock(), _createBlock(_component_admin_system, {
                          key: 5,
                          ref: "system"
                        }, null, 512 /* NEED_PATCH */))
                      : _createCommentVNode("v-if", true)
        ])
      ])
    ]))
  }
}
})(Vue),
    data() {
        return {
            baseUrl: window.baseUrl || '/',
            currentUsername: window.username || '',
            activeSection: 'users',
            activeSettingsTab: 'email',
            navItems: [
                { section: 'users', labelKey: 'admin_nav_users', labelFallback: 'Users', icon: 'ri-user-line' },
                { section: 'groups', labelKey: 'admin_nav_groups', labelFallback: 'Groups', icon: 'ri-team-line' },
                { section: 'roles', labelKey: 'admin_nav_roles', labelFallback: 'Roles', icon: 'ri-shield-user-line' },
                { section: 'logs', labelKey: 'admin_nav_logs', labelFallback: 'Logs', icon: 'ri-file-list-3-line' },
                { section: 'settings', labelKey: 'admin_nav_settings', labelFallback: 'Settings', icon: 'ri-settings-3-line' },
                { section: 'system', labelKey: 'admin_nav_system', labelFallback: 'System', icon: 'ri-information-line' }
            ],
            sectionMeta: {
                users: {
                    labelKey: 'admin_nav_users',
                    labelFallback: 'Users',
                    descriptionKey: 'admin_desc_users',
                    descriptionFallback: 'Create users, assign groups, and inspect effective permissions.'
                },
                groups: {
                    labelKey: 'admin_nav_groups',
                    labelFallback: 'Groups',
                    descriptionKey: 'admin_desc_groups',
                    descriptionFallback: 'Bundle roles into reusable groups.'
                },
                roles: {
                    labelKey: 'admin_nav_roles',
                    labelFallback: 'Roles',
                    descriptionKey: 'admin_desc_roles',
                    descriptionFallback: 'Define role permission sets used across the system.'
                },
                logs: {
                    labelKey: 'admin_nav_logs',
                    labelFallback: 'Audit Logs',
                    descriptionKey: 'admin_desc_logs',
                    descriptionFallback: 'Filter, paginate, and export activity logs.'
                },
                settings: {
                    labelKey: 'admin_nav_settings',
                    labelFallback: 'Settings',
                    descriptionKey: 'admin_desc_settings',
                    descriptionFallback: 'Configure email, sharing, governance, and security controls.'
                },
                system: {
                    labelKey: 'admin_nav_system',
                    labelFallback: 'System Info',
                    descriptionKey: 'admin_desc_system',
                    descriptionFallback: 'Inspect runtime and environment details.'
                }
            }
        };
    },
    computed: {
        activeLabel() {
            const meta = this.sectionMeta[this.activeSection];
            if (!meta) return this.t('admin_console', 'Admin');
            return this.t(meta.labelKey, meta.labelFallback);
        },
        activeDescription() {
            const meta = this.sectionMeta[this.activeSection];
            if (!meta) return '';
            return this.t(meta.descriptionKey, meta.descriptionFallback);
        }
    },
    mounted() {
        this.applyHash();
        window.addEventListener('hashchange', this.applyHash);
    },
    beforeUnmount() {
        window.removeEventListener('hashchange', this.applyHash);
    },
    methods: {
        t(key, fallback = '') {
            const value = i18n.t(key);
            if (value === key) {
                return fallback || key;
            }
            return value;
        },
        applyHash() {
            const raw = (window.location.hash || '').replace(/^#/, '');
            if (!raw) {
                this.navigate('users');
                return;
            }

            const parts = raw.split('/').filter(Boolean);
            const section = parts[0];
            const tab = parts[1] || null;

            const validSections = this.navItems.map((i) => i.section);
            const nextSection = validSections.includes(section) ? section : 'users';

            if (nextSection === 'settings' && tab) {
                this.activeSettingsTab = tab;
            }

            this.activeSection = nextSection;
        },
        navigate(section, tab = null) {
            this.activeSection = section;
            if (section === 'settings' && tab) {
                this.activeSettingsTab = tab;
            }
            this.updateHash();
        },
        handleSettingsTabChange(tab) {
            this.activeSettingsTab = tab || 'email';
            this.updateHash();
        },
        updateHash() {
            let nextHash = `#${this.activeSection}`;
            if (this.activeSection === 'settings' && this.activeSettingsTab) {
                nextHash += `/${this.activeSettingsTab}`;
            }
            if (window.location.hash !== nextHash) {
                window.location.hash = nextHash;
            }
        }
    }
};
