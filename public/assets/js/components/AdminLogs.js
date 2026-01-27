const AdminLogs = {
    template: `
    <div>
        <div class="border rounded p-2 mb-2 bg-body-tertiary">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small mb-1">{{ t('admin_logs_filter_user', 'User') }}</label>
                    <input type="text" class="form-control form-control-sm" v-model.trim="logFilters.user" :placeholder="t('admin_logs_filter_user_placeholder', 'username')">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">{{ t('admin_logs_filter_action', 'Action') }}</label>
                    <input type="text" class="form-control form-control-sm" v-model.trim="logFilters.action" :placeholder="t('admin_logs_filter_action_placeholder', 'action')">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">{{ t('admin_logs_filter_path', 'Path contains') }}</label>
                    <input type="text" class="form-control form-control-sm" v-model.trim="logFilters.path_contains" :placeholder="t('admin_logs_filter_path_placeholder', '/path')">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">{{ t('admin_logs_filter_from', 'From') }}</label>
                    <input type="date" class="form-control form-control-sm" v-model="logFilters.date_from">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">{{ t('admin_logs_filter_to', 'To') }}</label>
                    <input type="date" class="form-control form-control-sm" v-model="logFilters.date_to">
                </div>
                <div class="col-md-1 d-grid">
                    <button class="btn btn-primary btn-sm" @click="applyLogFilters" :disabled="isLoadingLogs">
                        {{ isLoadingLogs ? '…' : t('admin_logs_apply', 'Apply') }}
                    </button>
                </div>
            </div>
            <div class="mt-2 d-flex flex-wrap gap-2">
                <button class="btn btn-outline-secondary btn-sm" @click="resetLogFilters" :disabled="isLoadingLogs">{{ t('admin_logs_reset', 'Reset') }}</button>
                <button class="btn btn-outline-secondary btn-sm" @click="exportLogs('json')" :disabled="isLoadingLogs || logs.length === 0">{{ t('admin_logs_export_json', 'Export JSON') }}</button>
                <button class="btn btn-outline-secondary btn-sm" @click="exportLogs('csv')" :disabled="isLoadingLogs || logs.length === 0">{{ t('admin_logs_export_csv', 'Export CSV') }}</button>
            </div>
        </div>

        <div v-if="error" class="alert alert-danger small">{{ error }}</div>
        <div v-if="isLoadingLogs" class="text-muted small mb-2">{{ t('admin_logs_loading', 'Loading logs…') }}</div>

        <div class="table-responsive admin-table-scroll">
            <table class="table table-sm table-striped table-hover small align-middle">
                <thead class="sticky-top bg-body">
                    <tr>
                        <th>{{ t('admin_logs_col_date', 'Date') }}</th>
                        <th>{{ t('admin_logs_col_user', 'User') }}</th>
                        <th>{{ t('admin_logs_col_action', 'Action') }}</th>
                        <th>{{ t('admin_logs_col_path', 'Path') }}</th>
                        <th>{{ t('admin_logs_col_ip', 'IP') }}</th>
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
                        <td colspan="5" class="text-center text-muted py-4">{{ t('admin_logs_empty', 'No log entries match your filters.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2 small">
            <div class="text-muted">
                {{ t('admin_logs_paging', 'Showing page {page} of {totalPages} ({total} total)', { page: logsMeta.page, totalPages: logsMeta.totalPages, total: logsMeta.total }) }}
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="mb-0">{{ t('admin_logs_page_size', 'Page size') }}</label>
                <select class="form-select form-select-sm select-auto-width" v-model.number="logsMeta.pageSize" @change="changeLogsPageSize">
                    <option v-for="size in logPageSizeOptions" :key="size" :value="size">{{ size }}</option>
                </select>
                <button class="btn btn-outline-secondary btn-sm" @click="changeLogsPage(-1)" :disabled="isLoadingLogs || logsMeta.page <= 1">{{ t('prev', 'Prev') }}</button>
                <button class="btn btn-outline-secondary btn-sm" @click="changeLogsPage(1)" :disabled="isLoadingLogs || logsMeta.page >= logsMeta.totalPages">{{ t('next', 'Next') }}</button>
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
        t(key, fallback = '', params = {}) {
            const value = i18n.t(key, params);
            if (value === key) {
                if (fallback && Object.keys(params).length) {
                    return this.interpolate(fallback, params);
                }
                return fallback || key;
            }
            return value;
        },
        interpolate(template, params) {
            let str = template;
            Object.keys(params).forEach((k) => {
                str = str.replace(`{${k}}`, params[k]);
            });
            return str;
        },
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
            this.toastSuccess(this.t('admin_logs_export_started', 'Export started.'));
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
