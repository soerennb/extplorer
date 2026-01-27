const AdminRoles = {
    template: `
    <div>
        <div class="d-flex align-items-end justify-content-between gap-2 mb-3">
            <div class="small text-muted">Roles define permission sets that can be assigned directly or via groups.</div>
            <button class="btn btn-success btn-sm" @click="showAddRoleForm">
                <i class="ri-add-line me-1"></i> Add Role
            </button>
        </div>

        <div v-if="error" class="alert alert-danger small">{{ error }}</div>

        <div class="table-responsive admin-table-scroll">
            <table class="table table-striped table-hover small align-middle">
                <thead class="sticky-top bg-body">
                    <tr>
                        <th>Role Name</th>
                        <th>Permissions</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(perms, name) in rolesList" :key="name">
                        <td class="fw-semibold">{{ name }}</td>
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
                    <tr v-if="Object.keys(rolesList).length === 0">
                        <td colspan="3" class="text-center text-muted py-4">No roles defined.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="editingRole" class="card mt-3 bg-body-tertiary border">
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
    `,
    data() {
        return {
            rolesList: {},
            permissionCatalog: [],
            editingRole: null,
            isSavingRole: false,
            error: null
        };
    },
    mounted() {
        this.init();
    },
    methods: {
        async init() {
            await Promise.all([this.loadRoles(), this.loadPermissionsCatalog()]);
        },
        async loadRoles() {
            try {
                this.rolesList = await Api.get('roles');
            } catch (e) {
                this.error = e.message;
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
        }
    }
};

