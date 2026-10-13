const UserAdmin = {
    template: `
    <div class="modal fade" id="userAdminModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ t('admin_panel', 'Admin Panel') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border small d-flex align-items-center justify-content-between">
                        <div>
                            <strong>{{ t('quick_admin', 'Quick Admin') }}</strong>
                            <div class="text-muted">{{ t('quick_admin_desc', 'For full settings, logs, and system tools, use the admin console.') }}</div>
                        </div>
                        <a class="btn btn-sm btn-primary" :href="adminConsoleUrl">
                            <i class="ri-external-link-line me-1"></i> {{ t('admin_open_console', 'Open Admin Console') }}
                        </a>
                    </div>
                    <div v-if="quickAdmin">
                        <div class="row g-2 mb-3">
                            <div class="col-sm-4">
                                <div class="border rounded p-3 h-100 bg-body-tertiary">
                                    <div class="small text-muted">{{ t('admin_nav_users', 'Users') }}</div>
                                    <div class="fs-4 fw-semibold">{{ users.length }}</div>
                                    <a class="small" :href="consoleUrl('users')">{{ t('quick_admin_manage_users', 'Manage users') }}</a>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="border rounded p-3 h-100 bg-body-tertiary">
                                    <div class="small text-muted">{{ t('admin_nav_groups', 'Groups') }}</div>
                                    <div class="fs-4 fw-semibold">{{ Object.keys(groupsList).length }}</div>
                                    <a class="small" :href="consoleUrl('groups')">{{ t('quick_admin_manage_groups', 'Manage groups') }}</a>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="border rounded p-3 h-100 bg-body-tertiary">
                                    <div class="small text-muted">{{ t('admin_nav_roles', 'Roles') }}</div>
                                    <div class="fs-4 fw-semibold">{{ Object.keys(rolesList).length }}</div>
                                    <a class="small" :href="consoleUrl('roles')">{{ t('quick_admin_manage_roles', 'Manage roles') }}</a>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <a class="btn btn-outline-primary btn-sm" :href="consoleUrl('users')">
                                <i class="ri-user-settings-line me-1" aria-hidden="true"></i>{{ t('quick_admin_open_users', 'Open user administration') }}
                            </a>
                            <a class="btn btn-outline-primary btn-sm" :href="consoleUrl('settings', 'sharing')">
                                <i class="ri-share-line me-1" aria-hidden="true"></i>{{ t('quick_admin_open_sharing', 'Open sharing settings') }}
                            </a>
                            <a class="btn btn-outline-primary btn-sm" :href="consoleUrl('settings', 'mounts')">
                                <i class="ri-hard-drive-2-line me-1" aria-hidden="true"></i>{{ t('quick_admin_open_mounts', 'Open mount settings') }}
                            </a>
                            <a class="btn btn-outline-secondary btn-sm" :href="consoleUrl('logs')">
                                <i class="ri-file-list-3-line me-1" aria-hidden="true"></i>{{ t('quick_admin_view_all_logs', 'View all audit logs') }}
                            </a>
                        </div>

                        <div class="border rounded">
                            <div class="d-flex align-items-center justify-content-between gap-2 px-3 py-2 border-bottom bg-body-tertiary">
                                <div class="fw-semibold">{{ t('quick_admin_recent_activity', 'Recent activity') }}</div>
                                <button class="btn btn-outline-secondary btn-sm" type="button" @click="loadLogs" :disabled="isLoadingLogs">
                                    <i class="ri-refresh-line me-1" aria-hidden="true"></i>{{ t('refresh', 'Refresh') }}
                                </button>
                            </div>
                            <div v-if="isLoadingLogs" class="text-muted small p-3">{{ t('admin_logs_loading', 'Loading logs…') }}</div>
                            <div v-else-if="logs.length === 0" class="text-center text-muted small p-4">
                                <i class="ri-inbox-line d-block fs-3 mb-2" aria-hidden="true"></i>
                                {{ t('admin_logs_empty_unfiltered', 'No audit activity has been recorded yet.') }}
                            </div>
                            <div v-else class="table-responsive">
                                <table class="table table-sm table-hover small mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ t('admin_logs_col_date', 'Date') }}</th>
                                            <th>{{ t('admin_logs_col_user', 'User') }}</th>
                                            <th>{{ t('admin_logs_col_action', 'Action') }}</th>
                                            <th>{{ t('admin_logs_col_path', 'Path') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="log in logs.slice(0, 8)" :key="log.timestamp + log.action + log.path">
                                            <td class="text-nowrap">{{ formatDate(log.timestamp) }}</td>
                                            <td>{{ log.user }}</td>
                                            <td><strong>{{ log.action }}</strong></td>
                                            <td class="text-truncate admin-log-path" :title="log.path">{{ log.path }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div v-if="error" class="alert alert-danger">{{ error }}</div>
                </div>
            </div>
        </div>
    </div>
    `,
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
