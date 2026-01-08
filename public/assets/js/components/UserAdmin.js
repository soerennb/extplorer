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
                            <a class="nav-link" :class="{active: tab === 'system'}" href="#" @click.prevent="loadSystemInfo">System Info</a>
                        </li>
                    </ul>

                    <div v-if="error" class="alert alert-danger">{{ error }}</div>
                    
                    <!-- Users Tab -->
                    <div v-if="tab === 'users'">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Home Dir</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="user in users" :key="user.username">
                                    <td>{{ user.username }}</td>
                                    <td><span class="badge" :class="user.role === 'admin' ? 'bg-danger' : 'bg-primary'">{{ user.role }}</span></td>
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

                        <div v-if="editingUser" class="card mt-3 bg-light">
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
                                        <label class="form-label small">Role</label>
                                        <select class="form-select form-select-sm" v-model="editingUser.role">
                                            <option value="user">User</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">Home Dir</label>
                                        <input type="text" class="form-control form-control-sm" v-model="editingUser.home_dir">
                                    </div>
                                </div>
                                <div class="mt-3 text-end">
                                    <button class="btn btn-secondary btn-sm me-2" @click="cancelEdit">Cancel</button>
                                    <button class="btn btn-primary btn-sm" @click="saveUser">Save</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Info Tab -->
                    <div v-if="tab === 'system'">
                        <div v-if="!system" class="text-center text-muted">Loading...</div>
                        <div v-else>
                            <table class="table table-sm table-bordered">
                                <tbody>
                                    <tr><th style="width: 200px;">PHP Version</th><td>{{ system.php_version }}</td></tr>
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
                            <div class="small text-muted p-2 bg-light border rounded" style="max-height: 150px; overflow-y: auto;">
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
            system: null,
            error: null,
            editingUser: null,
            isNew: false,
            modal: null,
            currentUsername: username
        };
    },
    mounted() {
        this.modal = new bootstrap.Modal(document.getElementById('userAdminModal'));
        document.getElementById('userAdminModal').addEventListener('show.bs.modal', this.loadUsers);
    },
    methods: {
        open() {
            this.modal.show();
        },
        async loadUsers() {
            this.error = null;
            try {
                this.users = await Api.get('users');
            } catch (e) {
                this.error = e.message;
            }
        },
        async loadSystemInfo() {
            this.tab = 'system';
            if (this.system) return;
            try {
                this.system = await Api.get('system');
            } catch (e) {
                this.error = e.message;
            }
        },
        showAddForm() {
            this.isNew = true;
            this.editingUser = { username: '', password: '', role: 'user', home_dir: '/' };
        },
        editUser(user) {
            this.isNew = false;
            this.editingUser = { ...user, password: '' };
        },
        cancelEdit() {
            this.editingUser = null;
        },
        async saveUser() {
            try {
                if (this.isNew) {
                    await Api.post('users', this.editingUser);
                } else {
                    await fetch(baseUrl + 'api/users/' + this.editingUser.username, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(this.editingUser)
                    }).then(res => { if(!res.ok) throw new Error('Failed'); });
                }
                await this.loadUsers();
                this.editingUser = null;
            } catch (e) {
                this.error = e.message || 'Save failed';
            }
        },
        async deleteUser(user) {
            if (!confirm('Are you sure?')) return;
            try {
                await fetch(baseUrl + 'api/users/' + user.username, {
                    method: 'DELETE'
                }).then(res => { if(!res.ok) throw new Error('Failed'); });
                await this.loadUsers();
            } catch (e) {
                this.error = e.message || 'Delete failed';
            }
        },
        formatSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    }
};