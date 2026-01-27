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
                    <div v-if="quickAdmin" class="alert alert-light border small d-flex align-items-center justify-content-between">
                        <div>
                            <strong>Quick Admin</strong>
                            <div class="text-muted">For full settings, logs, and system tools, use the admin console.</div>
                        </div>
                        <a class="btn btn-sm btn-primary" :href="adminConsoleUrl">
                            <i class="ri-external-link-line me-1"></i> Open Admin Console
                        </a>
                    </div>
                    <ul class="nav nav-tabs mb-3">
                        <li class="nav-item">
                            <a class="nav-link" :class="{active: tab === 'users'}" href="#" @click.prevent="tab = 'users'">Users</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" :class="{active: tab === 'logs'}" href="#" @click.prevent="loadLogsTab">Logs</a>
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
                                    <a class="nav-link" :class="{active: settingsTab === 'transfers'}" href="#" @click.prevent="settingsTab = 'transfers'">Transfers</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" :class="{active: settingsTab === 'sharing'}" href="#" @click.prevent="settingsTab = 'sharing'">Sharing</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" :class="{active: settingsTab === 'mounts'}" href="#" @click.prevent="settingsTab = 'mounts'">Mounts</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" :class="{active: settingsTab === 'logging'}" href="#" @click.prevent="settingsTab = 'logging'">Logging</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" :class="{active: settingsTab === 'governance'}" href="#" @click.prevent="settingsTab = 'governance'">Governance</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" :class="{active: settingsTab === 'security'}" href="#" @click.prevent="settingsTab = 'security'">Security</a>
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
                                </div>

                                <div v-if="settingsTab === 'transfers'">
                                    <h6 class="border-bottom pb-2 mb-3">Transfers</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Default Expiry (Days)</label>
                                            <input type="number" min="1" class="form-control form-control-sm" v-model.number="settings.default_transfer_expiry">
                                            <div class="form-text">Used when no expiry is provided.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Max Expiry (Days)</label>
                                            <input type="number" min="1" max="365" class="form-control form-control-sm" v-model.number="settings.transfer_max_expiry_days">
                                            <div class="form-text">Upper bound for all transfers.</div>
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <div class="form-check form-switch mb-1">
                                                <input class="form-check-input" type="checkbox" id="transferNotifyDownload" v-model="settings.transfer_default_notify_download">
                                                <label class="form-check-label small fw-bold" for="transferNotifyDownload">Notify On Download (Default)</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="settingsTab === 'sharing'">
                                    <h6 class="border-bottom pb-2 mb-3">Share Links</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Default Expiry (Days)</label>
                                            <input type="number" min="1" max="365" class="form-control form-control-sm" v-model.number="settings.share_default_expiry_days">
                                            <div class="form-text">Used when expiry is required but not provided.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Max Expiry (Days)</label>
                                            <input type="number" min="1" max="365" class="form-control form-control-sm" v-model.number="settings.share_max_expiry_days">
                                            <div class="form-text">Upper bound for all share links.</div>
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <div class="form-check form-switch mb-1">
                                                <input class="form-check-input" type="checkbox" id="shareRequireExpiry" v-model="settings.share_require_expiry">
                                                <label class="form-check-label small fw-bold" for="shareRequireExpiry">Require Expiry</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 d-flex align-items-end">
                                            <div class="form-check form-switch mb-1">
                                                <input class="form-check-input" type="checkbox" id="shareRequirePassword" v-model="settings.share_require_password">
                                                <label class="form-check-label small fw-bold" for="shareRequirePassword">Require Password</label>
                                            </div>
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

                                <div v-if="settingsTab === 'logging'">
                                    <h6 class="border-bottom pb-2 mb-3">Audit Logging</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Retention Count</label>
                                            <input type="number" min="100" max="20000" step="100" class="form-control form-control-sm" v-model.number="settings.log_retention_count">
                                            <div class="form-text">How many recent audit log entries to keep (100-20000).</div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="settingsTab === 'governance'">
                                    <h6 class="border-bottom pb-2 mb-3">Uploads & Quotas</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Max Upload Size (MB)</label>
                                            <input type="number" min="0" max="10240" class="form-control form-control-sm" v-model.number="settings.upload_max_file_mb">
                                            <div class="form-text">0 disables the limit. Applies to single and chunked uploads.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Per-User Quota (MB)</label>
                                            <input type="number" min="0" max="102400" class="form-control form-control-sm" v-model.number="settings.quota_per_user_mb">
                                            <div class="form-text">0 disables the quota. Applies to the user home directory.</div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="settingsTab === 'security'">
                                    <h6 class="border-bottom pb-2 mb-3">Session Security</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Idle Timeout (Minutes)</label>
                                            <input type="number" min="0" max="1440" class="form-control form-control-sm" v-model.number="settings.session_idle_timeout_minutes">
                                            <div class="form-text">0 disables idle timeout. Applies to API and UI sessions.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-top text-end">
                                    <div v-if="settingsDirty" class="text-warning small mb-2 text-start">
                                        You have unsaved settings changes.
                                    </div>
                                    <button v-if="settingsTab === 'email'" type="button" class="btn btn-outline-secondary btn-sm me-2" @click="validateEmailSettings" :disabled="!settings || isValidatingEmail">
                                        {{ isValidatingEmail ? 'Validating…' : 'Validate' }}
                                    </button>
                                    <button v-if="settingsTab === 'email'" type="button" class="btn btn-outline-secondary btn-sm me-2" @click="testEmail" :disabled="!settings || isTestingEmail">
                                        {{ isTestingEmail ? 'Sending…' : 'Send Test Email' }}
                                    </button>
                                    <button type="submit" class="btn btn-primary btn-sm" :disabled="!settingsDirty || isSavingSettings">
                                        {{ isSavingSettings ? 'Saving…' : 'Save Settings' }}
                                    </button>
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
                                    <div class="col-md-12" v-if="!isNew">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <label class="form-label small fw-bold mb-1">Effective Permissions</label>
                                            <button class="btn btn-outline-secondary btn-sm py-0 px-2" @click="loadEffectivePermissions(editingUser.username)" :disabled="effectivePermissions.loading">
                                                {{ effectivePermissions.loading ? '…' : 'Refresh' }}
                                            </button>
                                        </div>
                                        <div v-if="effectivePermissions.loading" class="text-muted small">Loading permissions…</div>
                                        <div v-else-if="effectivePermissions.perms.length === 0" class="text-muted small">No permissions resolved.</div>
                                        <div v-else class="d-flex flex-wrap gap-1">
                                            <span v-for="perm in effectivePermissions.perms" :key="perm" class="badge bg-primary-subtle text-primary border border-primary-subtle admin-badge">{{ perm }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 text-end">
                                    <button class="btn btn-secondary btn-sm me-2" @click="cancelEdit" :disabled="isSavingUser">Cancel</button>
                                    <button class="btn btn-primary btn-sm" @click="saveUser" :disabled="isSavingUser">
                                        {{ isSavingUser ? 'Saving…' : 'Save' }}
                                    </button>
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
                                    <button class="btn btn-secondary btn-sm me-2" @click="editingGroup = null" :disabled="isSavingGroup">Cancel</button>
                                    <button class="btn btn-primary btn-sm" @click="saveGroup" :disabled="isSavingGroup">
                                        {{ isSavingGroup ? 'Saving…' : 'Save' }}
                                    </button>
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
                                <div v-if="permissionCatalog.length" class="mt-2">
                                    <span class="small text-muted d-block admin-note">Known permissions:</span>
                                    <span v-for="perm in permissionCatalog" :key="perm" class="badge bg-secondary-subtle text-secondary border border-secondary-subtle me-1 admin-badge">{{ perm }}</span>
                                </div>
                                <div class="mt-3 text-end">
                                    <button class="btn btn-secondary btn-sm me-2" @click="editingRole = null" :disabled="isSavingRole">Cancel</button>
                                    <button class="btn btn-primary btn-sm" @click="saveRole" :disabled="isSavingRole">
                                        {{ isSavingRole ? 'Saving…' : 'Save' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Logs Tab -->
                    <div v-if="tab === 'logs'">
                        <div v-if="!quickAdmin" class="border rounded p-2 mb-2 bg-body-tertiary">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-2">
                                    <label class="form-label small mb-1">User</label>
                                    <input type="text" class="form-control form-control-sm" v-model.trim="logFilters.user" placeholder="username">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-1">Action</label>
                                    <input type="text" class="form-control form-control-sm" v-model.trim="logFilters.action" placeholder="action">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-1">Path contains</label>
                                    <input type="text" class="form-control form-control-sm" v-model.trim="logFilters.path_contains" placeholder="/path">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-1">From</label>
                                    <input type="date" class="form-control form-control-sm" v-model="logFilters.date_from">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-1">To</label>
                                    <input type="date" class="form-control form-control-sm" v-model="logFilters.date_to">
                                </div>
                                <div class="col-md-1 d-grid">
                                    <button class="btn btn-primary btn-sm" @click="applyLogFilters" :disabled="isLoadingLogs">
                                        {{ isLoadingLogs ? '…' : 'Apply' }}
                                    </button>
                                </div>
                            </div>
                            <div class="mt-2 d-flex flex-wrap gap-2">
                                <button class="btn btn-outline-secondary btn-sm" @click="resetLogFilters" :disabled="isLoadingLogs">Reset</button>
                                <button class="btn btn-outline-secondary btn-sm" @click="exportLogs('json')" :disabled="isLoadingLogs || logs.length === 0">Export JSON</button>
                                <button class="btn btn-outline-secondary btn-sm" @click="exportLogs('csv')" :disabled="isLoadingLogs || logs.length === 0">Export CSV</button>
                            </div>
                        </div>
                        <div v-else class="text-muted small mb-2">
                            Showing the most recent activity. Use the admin console for full audit tools.
                        </div>
                        <div v-if="isLoadingLogs" class="text-muted small mb-2">Loading logs…</div>
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
                        <div v-if="!quickAdmin" class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2 small">
                            <div class="text-muted">
                                Showing page {{ logsMeta.page }} of {{ logsMeta.totalPages }} ({{ logsMeta.total }} total)
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <label class="mb-0">Page size</label>
                                <select class="form-select form-select-sm select-auto-width" v-model.number="logsMeta.pageSize" @change="changeLogsPageSize">
                                    <option v-for="size in logPageSizeOptions" :key="size" :value="size">{{ size }}</option>
                                </select>
                                <button class="btn btn-outline-secondary btn-sm" @click="changeLogsPage(-1)" :disabled="isLoadingLogs || logsMeta.page <= 1">Prev</button>
                                <button class="btn btn-outline-secondary btn-sm" @click="changeLogsPage(1)" :disabled="isLoadingLogs || logsMeta.page >= logsMeta.totalPages">Next</button>
                            </div>
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
        open() {
            this.modal.show();
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
                this.loadPermissionsCatalog();
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
                    totalPages: res.totalPages ?? 1
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
