const AdminApp = {
    template: `
    <div class="admin-page">
        <aside class="admin-sidebar border-end">
            <div class="admin-sidebar-header">
                <div class="fw-semibold">{{ t('admin_console', 'Admin Console') }}</div>
                <div class="small text-muted">{{ t('app_name', 'eXtplorer 3') }}</div>
            </div>
            <nav class="nav flex-column admin-sidebar-nav">
                <a v-for="item in navItems"
                   :key="item.section"
                   href="#"
                   class="nav-link d-flex align-items-center gap-2"
                   :class="{ active: activeSection === item.section }"
                   @click.prevent="navigate(item.section, item.section === 'settings' ? activeSettingsTab : null)">
                    <i :class="item.icon"></i>
                    <span>{{ t(item.labelKey, item.labelFallback) }}</span>
                </a>
            </nav>
            <div class="admin-sidebar-footer small text-muted">
                <div>{{ t('admin_signed_in_as', 'Signed in as') }}</div>
                <div class="fw-semibold text-body">{{ currentUsername }}</div>
            </div>
        </aside>

        <main class="admin-main">
            <div class="admin-main-header border-bottom">
                <div>
                    <h1 class="h5 mb-1">{{ activeLabel }}</h1>
                    <div class="small text-muted">{{ activeDescription }}</div>
                </div>
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-secondary btn-sm" :href="baseUrl">
                        <i class="ri-folder-line me-1"></i> {{ t('admin_files', 'Files') }}
                    </a>
                    <a class="btn btn-outline-danger btn-sm" :href="baseUrl + 'logout'">
                        <i class="ri-logout-box-r-line me-1"></i> {{ t('logout', 'Logout') }}
                    </a>
                </div>
            </div>

            <div class="admin-main-body">
                <admin-users v-if="activeSection === 'users'" ref="users"></admin-users>
                <admin-groups v-else-if="activeSection === 'groups'" ref="groups"></admin-groups>
                <admin-roles v-else-if="activeSection === 'roles'" ref="roles"></admin-roles>
                <admin-logs v-else-if="activeSection === 'logs'" ref="logs"></admin-logs>
                <admin-settings v-else-if="activeSection === 'settings'"
                                ref="settings"
                                :initial-tab="activeSettingsTab"
                                :on-tab-change="handleSettingsTabChange">
                </admin-settings>
                <admin-system v-else-if="activeSection === 'system'" ref="system"></admin-system>
            </div>
        </main>
    </div>
    `,
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
