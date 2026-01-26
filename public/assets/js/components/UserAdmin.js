const UserAdmin = {
    template: `
    <div class="modal fade" id="userAdminModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Admin Panel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs mb-3">
                        <li class="nav-item">
                            <a class="nav-link" :class="{active: tab === 'users'}" href="#" @click.prevent="tab = 'users'">Users</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" :class="{active: tab === 'groups'}" href="#" @click.prevent="tab = 'groups'">Groups</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" :class="{active: tab === 'roles'}" href="#" @click.prevent="tab = 'roles'">Roles</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" :class="{active: tab === 'logs'}" href="#" @click.prevent="loadLogsTab">Logs</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" :class="{active: tab === 'system'}" href="#" @click.prevent="loadSystemInfo">System Info</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" :class="{active: tab === 'settings'}" href="#" @click.prevent="loadSettings">Settings</a>
                        </li>
                    </ul>

                    <div v-if="error" class="alert alert-danger">{{ error }}</div>
                    
                    <!-- Settings Tab -->
                    <div v-if="tab === 'settings'">
                        <div v-if="!settings" class="text-center text-muted">Loading...</div>
                        <div v-else>
                            <ul class="nav nav-pills mb-3">
                                <li class="nav-item">
                                    <a class="nav-link" :class="{active: settingsTab === 'email'}" href="#" @click.prevent="settingsTab = 'email'">Email</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" :class="{active: settingsTab === 'mounts'}" href="#" @click.prevent="settingsTab = 'mounts'">Mounts</a>
                                </li>
                            </ul>

                            <form @submit.prevent="saveSettings">
                                <div v-if="settingsTab === 'email'">
                                    <h6 class="border-bottom pb-2 mb-3">Email Configuration</h6>
                                    <div v-if="emailValidation.message" class="alert small" :class="emailValidation.ok ? 'alert-success' : 'alert-danger'">
                                        {{ emailValidation.message }}
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Protocol</label>
                                            <select class="form-select form-select-sm" v-model="settings.email_protocol">
                                                <option value="smtp">SMTP</option>
                                                <option value="sendmail">Sendmail</option>
                                                <option value="mail">PHP mail()</option>
                                            </select>
                                        </div>
                                        <div class="col-md-8" v-if="settings.email_protocol === 'sendmail'">
                                            <label class="form-label small fw-bold">Sendmail Path</label>
                                            <input type="text" class="form-control form-control-sm" v-model="settings.sendmail_path" placeholder="/usr/sbin/sendmail">
                                        </div>
                                    </div>

                                    <div v-if="settings.email_protocol === 'smtp'" class="row g-3 mt-1">
                                        <div class="col-md-8">
                                            <label class="form-label small fw-bold">SMTP Host</label>
                                            <input type="text" class="form-control form-control-sm" v-model="settings.smtp_host" placeholder="smtp.example.com">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Port</label>
                                            <input type="number" class="form-control form-control-sm" v-model="settings.smtp_port" placeholder="587">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Username</label>
                                            <input type="text" class="form-control form-control-sm" v-model="settings.smtp_user">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Password</label>
                                            <input type="password" class="form-control form-control-sm" v-model="settings.smtp_pass" placeholder="********">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Encryption</label>
                                            <select class="form-select form-select-sm" v-model="settings.smtp_crypto">
                                                <option value="tls">TLS</option>
                                                <option value="ssl">SSL</option>
                                                <option value="">None</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">From Email</label>
                                            <input type="email" class="form-control form-control-sm" v-model="settings.email_from">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">From Name</label>
                                            <input type="text" class="form-control form-control-sm" v-model="settings.email_from_name">
                                        </div>
                                    </div>

                                    <div v-if="settings.email_protocol !== 'smtp'" class="row g-3 mt-1">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">From Email</label>
                                            <input type="email" class="form-control form-control-sm" v-model="settings.email_from">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">From Name</label>
                                            <input type="text" class="form-control form-control-sm" v-model="settings.email_from_name">
                                        </div>
                                    </div>

                                    <h6 class="border-bottom pb-2 mb-3 mt-4">Transfers</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Default Expiry (Days)</label>
                                            <input type="number" class="form-control form-control-sm" v-model="settings.default_transfer_expiry">
                                        </div>
                                    </div>
                                </div>

                                <div v-if="settingsTab === 'mounts'">
                                    <h6 class="border-bottom pb-2 mb-3">External Mounts</h6>
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <label class="form-label small fw-bold">Allowlist (one path per line)</label>
                                            <textarea class="form-control form-control-sm" rows="4" v-model="settings.mount_root_allowlist_text" placeholder="/srv/data&#10;/mnt/storage"></textarea>
                                            <div class="form-text">Only paths under these roots can be mounted. Leave empty to disable external mounts.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-top text-end">
                                    <button type="button" class="btn btn-outline-secondary btn-sm me-2" @click="validateEmailSettings">Validate</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm me-2" @click="testEmail">Send Test Email</button>
                                    <button type="submit" class="btn btn-primary btn-sm">Save Settings</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Users Tab -->
                    <div v-if="tab === 'users'">
                        <table class="table table-striped table-hover small">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Groups</th>
                                    <th>Home Dir</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="user in users" :key="user.username">
                                    <td>{{ user.username }}</td>
                                    <td>
                                        <span v-for="g in user.groups" class="badge bg-secondary me-1">{{ g }}</span>
                                    </td>
                                    <td>{{ user.home_dir }}</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary me-1" @click="editUser(user)">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" @click="deleteUser(user)" :disabled="user.username === currentUsername">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <button class="btn btn-success btn-sm" @click="showAddForm">
                            <i class="ri-user-add-line"></i> Add User
                        </button>

                        <div v-if="editingUser" class="card mt-3 bg-body-tertiary">
                            <div class="card-body">
                                <h6>{{ isNew ? 'Add User' : 'Edit User: ' + editingUser.username }}</h6>
                                <div class="row g-2">
                                    <div class="col-md-6" v-if="isNew">
                                        <label class="form-label small">Username</label>
                                        <input type="text" class="form-control form-control-sm" v-model="editingUser.username">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Password {{ !isNew ? '(Leave blank to keep)' : '' }}</label>
                                        <input type="password" class="form-control form-control-sm" v-model="editingUser.password">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Home Dir</label>
                                        <input type="text" class="form-control form-control-sm" v-model="editingUser.home_dir">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Allowed Extensions (csv)</label>
                                        <input type="text" class="form-control form-control-sm" v-model="editingUser.allowed_extensions" placeholder="e.g. jpg,png,pdf">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Blocked Extensions (csv)</label>
                                        <input type="text" class="form-control form-control-sm" v-model="editingUser.blocked_extensions" placeholder="e.g. php,exe">
                                        <div v-if="system && system.system_blocklist" class="mt-1">
                                            <span class="small text-muted d-block admin-note">System Blocklist (Always Applied):</span>
                                            <span v-for="ext in system.system_blocklist" :key="ext" class="badge bg-secondary-subtle text-secondary border border-secondary-subtle me-1 admin-badge">{{ ext }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label small d-block">Groups</label>
                                        <div class="d-flex flex-wrap gap-2">
                                            <div v-for="(roles, gname) in groupsList" :key="gname" class="form-check small">
                                                <input class="form-check-input" type="checkbox" :id="'chk_g_'+gname" :value="gname" v-model="editingUser.groups">
                                                <label class="form-check-label" :for="'chk_g_'+gname">{{ gname }}</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 text-end">
                                    <button class="btn btn-secondary btn-sm me-2" @click="cancelEdit">Cancel</button>
                                    <button class="btn btn-primary btn-sm" @click="saveUser">Save</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Groups Tab -->
                    <div v-if="tab === 'groups'">
                        <table class="table table-striped table-hover small">
                            <thead>
                                <tr>
                                    <th>Group Name</th>
                                    <th>Assigned Roles</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(roles, name) in groupsList" :key="name">
                                    <td>{{ name }}</td>
                                    <td>
                                        <span v-for="r in roles" class="badge bg-info me-1">{{ r }}</span>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary me-1" @click="editGroup(name, roles)">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" @click="deleteGroup(name)">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <button class="btn btn-success btn-sm" @click="showAddGroupForm">
                            <i class="ri-add-line"></i> Add Group
                        </button>

                        <div v-if="editingGroup" class="card mt-3 bg-body-tertiary">
                            <div class="card-body">
                                <h6>Group: {{ editingGroup.name || 'New' }}</h6>
                                <input v-if="!editingGroup.isEdit" type="text" class="form-control form-control-sm mb-2" placeholder="Group Name" v-model="editingGroup.name">
                                <label class="small d-block mb-1">Roles</label>
                                <div class="d-flex flex-wrap gap-2">
                                    <div v-for="(perms, rname) in rolesList" :key="rname" class="form-check small">
                                        <input class="form-check-input" type="checkbox" :id="'chk_r_'+rname" :value="rname" v-model="editingGroup.roles">
                                        <label class="form-check-label" :for="'chk_r_'+rname">{{ rname }}</label>
                                    </div>
                                </div>
                                <div class="mt-3 text-end">
                                    <button class="btn btn-secondary btn-sm me-2" @click="editingGroup = null">Cancel</button>
                                    <button class="btn btn-primary btn-sm" @click="saveGroup">Save</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Roles Tab -->
                    <div v-if="tab === 'roles'">
                        <table class="table table-striped table-hover small">
                            <thead>
                                <tr>
                                    <th>Role Name</th>
                                    <th>Permissions</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(perms, name) in rolesList" :key="name">
                                    <td>{{ name }}</td>
                                    <td>
                                        <code class="small">{{ perms.join(', ') }}</code>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary me-1" @click="editRole(name, perms)">
                                            <i class="ri-edit-line"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" @click="deleteRole(name)">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <button class="btn btn-success btn-sm" @click="showAddRoleForm">
                            <i class="ri-add-line"></i> Add Role
                        </button>

                        <div v-if="editingRole" class="card mt-3 bg-body-tertiary">
                            <div class="card-body">
                                <h6>Role: {{ editingRole.name || 'New' }}</h6>
                                <input v-if="!editingRole.isEdit" type="text" class="form-control form-control-sm mb-2" placeholder="Role Name" v-model="editingRole.name">
                                <label class="small d-block mb-1">Permissions (comma separated or *)</label>
                                <input type="text" class="form-control form-control-sm" v-model="editingRole.permsString">
                                <div class="mt-3 text-end">
                                    <button class="btn btn-secondary btn-sm me-2" @click="editingRole = null">Cancel</button>
                                    <button class="btn btn-primary btn-sm" @click="saveRole">Save</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Logs Tab -->
                    <div v-if="tab === 'logs'">
                        <div class="table-responsive admin-table-scroll">
                            <table class="table table-sm table-striped table-hover small">
                                <thead class="sticky-top bg-body">
                                    <tr>
                                        <th>Date</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Path</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="log in logs" :key="log.timestamp + log.action">
                                        <td class="text-nowrap">{{ formatDate(log.timestamp) }}</td>
                                        <td>{{ log.user }}</td>
                                        <td><strong>{{ log.action }}</strong></td>
                                        <td class="text-truncate admin-log-path" :title="log.path">{{ log.path }}</td>
                                        <td>{{ log.ip }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- System Info Tab -->
                    <div v-if="tab === 'system'">
                        <div v-if="!system" class="text-center text-muted">Loading...</div>
                        <div v-else>
                            <table class="table table-sm table-bordered">
                                <tbody>
                                    <tr><th class="admin-meta-label">eXtplorer Version</th><td><span class="badge bg-success">{{ system.app_version }}</span></td></tr>
                                    <tr><th>PHP Version</th><td>{{ system.php_version }}</td></tr>
                                    <tr><th>OS</th><td>{{ system.server_os }}</td></tr>
                                    <tr><th>Server Software</th><td>{{ system.server_software }}</td></tr>
                                    <tr><th>Memory Limit</th><td>{{ system.memory_limit }}</td></tr>
                                    <tr><th>Upload Limit</th><td>{{ system.upload_max_filesize }}</td></tr>
                                    <tr><th>POST Limit</th><td>{{ system.post_max_size }}</td></tr>
                                    <tr><th>Disk Free</th><td>{{ formatSize(system.disk_free) }}</td></tr>
                                    <tr><th>Disk Total</th><td>{{ formatSize(system.disk_total) }}</td></tr>
                                </tbody>
                            </table>
                            <h6>Loaded Extensions</h6>
                            <div class="small text-muted p-2 bg-light border rounded admin-config-box">
                                {{ system.extensions }}
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    `,
    data() {
        return {
            tab: 'users',
            users: [],
            groupsList: {},
            rolesList: {},
            logs: [],
            system: null,
            settings: null,
            settingsTab: 'email',
            emailValidation: { ok: null, message: '', timeoutId: null },
            error: null,
            editingUser: null,
            editingGroup: null,
            editingRole: null,
            isNew: false,
            modal: null,
            currentUsername: username
        };
    },
    mounted() {
        this.modal = new bootstrap.Modal(document.getElementById('userAdminModal'));
        document.getElementById('userAdminModal').addEventListener('show.bs.modal', this.initAdmin);
    },
    methods: {
        open() {
            this.modal.show();
        },
        async initAdmin() {
            this.tab = 'users';
            await this.loadUsers();
            await this.loadGroups();
            await this.loadRoles();
            this.loadSystemInfo(false);
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
            try { this.logs = await Api.get('logs'); } catch (e) {}
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
                if (!this.settings.email_protocol) {
                    this.settings.email_protocol = 'smtp';
                }
                if (!this.settings.sendmail_path) {
                    this.settings.sendmail_path = '/usr/sbin/sendmail';
                }
                this.clearEmailValidation();
            } catch (e) { this.error = e.message; }
        },
        async saveSettings() {
             try {
                 await Api.post('settings', this.settings);
                 alert('Settings Saved');
             } catch(e) { this.error = e.message; }
        },
        async testEmail() {
             const email = prompt("Enter email to send test to:");
             if(email) {
                 try {
                     await Api.post('settings/test-email', {...this.settings, email});
                     this.setEmailValidation(true, 'Test email sent successfully.');
                 } catch(e) {
                     this.setEmailValidation(false, 'Test failed: ' + e.message);
                 }
             }
        },
        async validateEmailSettings() {
             try {
                 const res = await Api.post('settings/validate-email', this.settings);
                 this.setEmailValidation(true, res.message || 'Validation successful');
             } catch(e) {
                 this.setEmailValidation(false, 'Validation failed: ' + e.message);
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
        
        loadLogsTab() { this.tab = 'logs'; this.loadLogs(); },
        loadGroupsTab() { this.tab = 'groups'; },
        loadRolesTab() { this.tab = 'roles'; },

        // User Methods
        showAddForm() {
            this.isNew = true;
            this.editingUser = { username: '', password: '', role: 'user', home_dir: '/', groups: [], allowed_extensions: '', blocked_extensions: '' };
        },
        editUser(user) {
            this.isNew = false;
            this.editingUser = { ...user, password: '', groups: user.groups || [], allowed_extensions: user.allowed_extensions || '', blocked_extensions: user.blocked_extensions || '' };
        },
        cancelEdit() { this.editingUser = null; },
        async saveUser() {
            try {
                if (this.isNew) await Api.post('users', this.editingUser);
                else await Api.put('users/' + this.editingUser.username, this.editingUser);
                await this.loadUsers();
                this.editingUser = null;
            } catch (e) { this.error = e.message; }
        },
        async deleteUser(user) {
            if (!confirm('Are you sure?')) return;
            try { await Api.delete('users/' + user.username); await this.loadUsers(); } catch (e) { this.error = e.message; }
        },

        // Group Methods
        showAddGroupForm() { this.editingGroup = { name: '', roles: [], isEdit: false }; },
        editGroup(name, roles) { this.editingGroup = { name, roles: [...roles], isEdit: true }; },
        async saveGroup() {
            try {
                await Api.post('groups', { name: this.editingGroup.name, roles: this.editingGroup.roles });
                await this.loadGroups();
                this.editingGroup = null;
            } catch (e) { this.error = e.message; }
        },
        async deleteGroup(name) {
            if (!confirm('Are you sure?')) return;
            try { await Api.delete('groups/' + name); await this.loadGroups(); } catch (e) { this.error = e.message; }
        },

        // Role Methods
        showAddRoleForm() { this.editingRole = { name: '', permsString: '', isEdit: false }; },
        editRole(name, perms) { this.editingRole = { name, permsString: perms.join(','), isEdit: true }; },
        async saveRole() {
            try {
                const perms = this.editingRole.permsString.split(',').map(p => p.trim()).filter(p => p);
                await Api.post('roles', { name: this.editingRole.name, permissions: perms });
                await this.loadRoles();
                this.editingRole = null;
            } catch (e) { this.error = e.message; }
        },
        async deleteRole(name) {
            if (!confirm('Are you sure?')) return;
            try { await Api.delete('roles/' + name); await this.loadRoles(); } catch (e) { this.error = e.message; }
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
