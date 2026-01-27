const AdminGroups = {
    template: `
    <div>
        <div class="d-flex align-items-end justify-content-between gap-2 mb-3">
            <div>
                <div class="small text-muted">Groups bundle roles for easier assignment.</div>
            </div>
            <button class="btn btn-success btn-sm" @click="showAddGroupForm">
                <i class="ri-add-line me-1"></i> Add Group
            </button>
        </div>

        <div v-if="error" class="alert alert-danger small">{{ error }}</div>

        <div class="table-responsive admin-table-scroll">
            <table class="table table-striped table-hover small align-middle">
                <thead class="sticky-top bg-body">
                    <tr>
                        <th>Group Name</th>
                        <th>Usage</th>
                        <th>Assigned Roles</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(roles, name) in groupsList" :key="name">
                        <td class="fw-semibold">{{ name }}</td>
                        <td>
                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                <span class="badge text-bg-secondary" :title="'Assigned users: ' + groupUsage(name).assigned_users_count">
                                    Users {{ groupUsage(name).assigned_users_count }}
                                </span>
                                <span class="badge text-bg-info" :title="'Roles granted: ' + groupUsage(name).roles_count">
                                    Roles {{ groupUsage(name).roles_count }}
                                </span>
                            </div>
                            <div v-if="groupUsage(name).assigned_users_count" class="small text-muted mt-1">
                                Unassign users before deleting.
                            </div>
                        </td>
                        <td>
                            <span v-for="r in roles" :key="r" class="badge bg-info me-1">{{ r }}</span>
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary me-1" @click="editGroup(name, roles)">
                                <i class="ri-edit-line"></i>
                            </button>
                            <button
                                class="btn btn-sm btn-outline-danger"
                                @click="deleteGroup(name)"
                                :disabled="!canDeleteGroup(name)"
                                :title="deleteDisabledReason(name)"
                            >
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="Object.keys(groupsList).length === 0">
                        <td colspan="4" class="text-center text-muted py-4">No groups defined.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="editingGroup" class="card mt-3 bg-body-tertiary border">
            <div class="card-body">
                <h6>Group: {{ editingGroup.name || 'New' }}</h6>
                <input v-if="!editingGroup.isEdit" type="text" class="form-control form-control-sm mb-2" placeholder="Group Name" v-model="editingGroup.name">
                <label class="small d-block mb-1">Roles</label>
                <div class="d-flex flex-wrap gap-2">
                    <div v-for="(perms, rname) in rolesList" :key="rname" class="form-check small">
                        <input class="form-check-input" type="checkbox" :id="'admin_group_chk_r_'+rname" :value="rname" v-model="editingGroup.roles">
                        <label class="form-check-label" :for="'admin_group_chk_r_'+rname">{{ rname }}</label>
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
    `,
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
                return 'Group is still assigned to users';
            }
            return 'Delete group';
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
                this.toastSuccess('Group saved.');
            } catch (e) {
                this.error = e.message;
                this.toastError('Failed to save group.');
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
                        title: 'Group still assigned',
                        text: `Users (${usage.assigned_users_count}): ${usersPreview}${usage.assigned_users_count > 6 ? ', …' : ''}`,
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                const confirmed = await this.confirmDanger('Delete group?', `This will delete the group ${name}.`);
                if (!confirmed) return;

                await Api.delete('groups/' + name);
                await this.loadGroups();
                this.toastSuccess('Group deleted.');
            } catch (e) {
                this.error = e.message;
                this.toastError('Failed to delete group.');
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
