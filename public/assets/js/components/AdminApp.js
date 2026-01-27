const AdminApp = {
    template: `
    <div class="admin-page">
        <aside class="admin-sidebar border-end">
            <div class="admin-sidebar-header">
                <div class="fw-semibold">Admin Console</div>
                <div class="small text-muted">eXtplorer</div>
            </div>
            <nav class="nav flex-column admin-sidebar-nav">
                <a v-for="item in navItems"
                   :key="item.section"
                   href="#"
                   class="nav-link d-flex align-items-center gap-2"
                   :class="{ active: activeSection === item.section }"
                   @click.prevent="navigate(item.section, item.section === 'settings' ? activeSettingsTab : null)">
                    <i :class="item.icon"></i>
                    <span>{{ item.label }}</span>
                </a>
            </nav>
            <div class="admin-sidebar-footer small text-muted">
                <div>Signed in as</div>
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
                        <i class="ri-folder-line me-1"></i> Files
                    </a>
                    <a class="btn btn-outline-danger btn-sm" :href="baseUrl + 'logout'">
                        <i class="ri-logout-box-r-line me-1"></i> Logout
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
                { section: 'users', label: 'Users', icon: 'ri-user-line' },
                { section: 'groups', label: 'Groups', icon: 'ri-team-line' },
                { section: 'roles', label: 'Roles', icon: 'ri-shield-user-line' },
                { section: 'logs', label: 'Logs', icon: 'ri-file-list-3-line' },
                { section: 'settings', label: 'Settings', icon: 'ri-settings-3-line' },
                { section: 'system', label: 'System', icon: 'ri-information-line' }
            ],
            sectionMeta: {
                users: {
                    label: 'Users',
                    description: 'Create users, assign groups, and inspect effective permissions.'
                },
                groups: {
                    label: 'Groups',
                    description: 'Bundle roles into reusable groups.'
                },
                roles: {
                    label: 'Roles',
                    description: 'Define role permission sets used across the system.'
                },
                logs: {
                    label: 'Audit Logs',
                    description: 'Filter, paginate, and export activity logs.'
                },
                settings: {
                    label: 'Settings',
                    description: 'Configure email, sharing, governance, and security controls.'
                },
                system: {
                    label: 'System Info',
                    description: 'Inspect runtime and environment details.'
                }
            }
        };
    },
    computed: {
        activeLabel() {
            return this.sectionMeta[this.activeSection]?.label || 'Admin';
        },
        activeDescription() {
            return this.sectionMeta[this.activeSection]?.description || '';
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

