const AdminSystem = {
    template: `
    <div>
        <div v-if="error" class="alert alert-danger small">{{ error }}</div>
        <div v-if="!system" class="text-center text-muted py-5">{{ t('admin_system_loading', 'Loading system info…') }}</div>
        <div v-else>
            <table class="table table-sm table-bordered align-middle">
                <tbody>
                    <tr><th class="admin-meta-label">{{ t('admin_system_version', 'eXtplorer Version') }}</th><td><span class="badge bg-success">{{ system.app_version }}</span></td></tr>
                    <tr><th>{{ t('admin_system_php', 'PHP Version') }}</th><td>{{ system.php_version }}</td></tr>
                    <tr><th>{{ t('admin_system_os', 'OS') }}</th><td>{{ system.server_os }}</td></tr>
                    <tr><th>{{ t('admin_system_server_software', 'Server Software') }}</th><td>{{ system.server_software }}</td></tr>
                    <tr><th>{{ t('admin_system_memory', 'Memory Limit') }}</th><td>{{ system.memory_limit }}</td></tr>
                    <tr><th>{{ t('admin_system_upload', 'Upload Limit') }}</th><td>{{ system.upload_max_filesize }}</td></tr>
                    <tr><th>{{ t('admin_system_post', 'POST Limit') }}</th><td>{{ system.post_max_size }}</td></tr>
                    <tr><th>{{ t('admin_system_disk_free', 'Disk Free') }}</th><td>{{ formatSize(system.disk_free) }}</td></tr>
                    <tr><th>{{ t('admin_system_disk_total', 'Disk Total') }}</th><td>{{ formatSize(system.disk_total) }}</td></tr>
                </tbody>
            </table>

            <h6>{{ t('admin_system_extensions_heading', 'Loaded Extensions') }}</h6>
            <div class="small text-muted p-2 bg-light border rounded admin-config-box">
                {{ system.extensions }}
            </div>
        </div>
    </div>
    `,
    data() {
        return {
            system: null,
            error: null
        };
    },
    mounted() {
        this.loadSystemInfo();
    },
    methods: {
        t(key, fallback = '') {
            const value = i18n.t(key);
            if (value === key) {
                return fallback || key;
            }
            return value;
        },
        async loadSystemInfo() {
            this.error = null;
            try {
                this.system = await Api.get('system');
            } catch (e) {
                this.error = e.message;
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
