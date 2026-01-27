const AdminUsers = {
    template: `
    <div>
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
            <div class="flex-grow-1">
                <label class="form-label small fw-bold mb-1">Search Users</label>
                <input type="text" class="form-control form-control-sm" v-model.trim="searchQuery" placeholder="Filter by username or home directory">
            </div>
            <div>
                <button class="btn btn-success btn-sm" @click="showAddForm">
                    <i class="ri-user-add-line me-1"></i> Add User
                </button>
            </div>
        </div>

        <div v-if="error" class="alert alert-danger small">{{ error }}</div>

        <div class="table-responsive admin-table-scroll">
            <table class="table table-striped table-hover small align-middle">
                <thead class="sticky-top bg-body">
                    <tr>
                        <th>Username</th>
                        <th>Groups</th>
                        <th>Home Dir</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in filteredUsers" :key="user.username">
                        <td class="fw-semibold">{{ user.username }}</td>
                        <td>
                            <span v-for="g in (user.groups || [])" :key="g" class="badge bg-secondary me-1">{{ g }}</span>
                        </td>
                        <td class="text-muted small">{{ user.home_dir }}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary me-1" @click="editUser(user)">
                                <i class="ri-edit-line"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" @click="deleteUser(user)" :disabled="user.username === currentUsername">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="filteredUsers.length === 0">
                        <td colspan="4" class="text-center text-muted py-4">No users match your filter.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="editingUser" class="card mt-3 bg-body-tertiary border">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="mb-0">{{ isNew ? 'Add User' : 'Edit User: ' + editingUser.username }}</h6>
                    <div class="small text-muted">Changes apply immediately on save.</div>
                </div>
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
                        <div v-if="systemBlocklist.length" class="mt-1">
                            <span class="small text-muted d-block admin-note">System Blocklist (Always Applied):</span>
                            <span v-for="ext in systemBlocklist" :key="ext" class="badge bg-secondary-subtle text-secondary border border-secondary-subtle me-1 admin-badge">{{ ext }}</span>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small d-block">Groups</label>
                        <div class="d-flex flex-wrap gap-2">
                            <div v-for="(roles, gname) in groupsList" :key="gname" class="form-check small">
                                <input class="form-check-input" type="checkbox" :id="'admin_chk_g_'+gname" :value="gname" v-model="editingUser.groups">
                                <label class="form-check-label" :for="'admin_chk_g_'+gname">{{ gname }}</label>
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
    `,
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
                this.toastSuccess('User saved.');
                this.editingUser = null;
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
        }
    }
};

