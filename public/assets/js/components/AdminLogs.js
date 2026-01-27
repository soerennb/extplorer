const AdminLogs = {
    template: `
    <div>
        <div class="border rounded p-2 mb-2 bg-body-tertiary">
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

        <div v-if="error" class="alert alert-danger small">{{ error }}</div>
        <div v-if="isLoadingLogs" class="text-muted small mb-2">Loading logs…</div>

        <div class="table-responsive admin-table-scroll">
            <table class="table table-sm table-striped table-hover small align-middle">
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
                    <tr v-if="logs.length === 0 && !isLoadingLogs">
                        <td colspan="5" class="text-center text-muted py-4">No log entries match your filters.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2 small">
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
    `,
    data() {
        return {
            logs: [],
            logsMeta: { total: 0, page: 1, pageSize: 50, totalPages: 1 },
            logFilters: { user: '', action: '', path_contains: '', date_from: '', date_to: '' },
            logPageSizeOptions: [25, 50, 100, 200],
            isLoadingLogs: false,
            error: null
        };
    },
    mounted() {
        this.loadLogs();
    },
    methods: {
        async loadLogs() {
            this.isLoadingLogs = true;
            this.error = null;
            try {
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
            } finally {
                this.isLoadingLogs = false;
            }
        },
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
        formatDate(timestamp) {
            return new Date(timestamp * 1000).toLocaleString();
        }
    }
};

