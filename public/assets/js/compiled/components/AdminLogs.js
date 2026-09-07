const AdminLogs = {
    render: ((Vue) => {
const _Vue = Vue
const { createElementVNode: _createElementVNode, createCommentVNode: _createCommentVNode } = _Vue

const _hoisted_1 = { class: "border rounded p-2 mb-2 bg-body-tertiary" }
const _hoisted_2 = { class: "row g-2 align-items-end" }
const _hoisted_3 = { class: "col-md-2" }
const _hoisted_4 = {
  class: "form-label small mb-1",
  for: "adminLogsUser"
}
const _hoisted_5 = ["onUpdate:modelValue", "placeholder"]
const _hoisted_6 = { class: "col-md-2" }
const _hoisted_7 = {
  class: "form-label small mb-1",
  for: "adminLogsAction"
}
const _hoisted_8 = ["onUpdate:modelValue", "placeholder"]
const _hoisted_9 = { class: "col-md-3" }
const _hoisted_10 = {
  class: "form-label small mb-1",
  for: "adminLogsPath"
}
const _hoisted_11 = ["onUpdate:modelValue", "placeholder"]
const _hoisted_12 = { class: "col-md-2" }
const _hoisted_13 = {
  class: "form-label small mb-1",
  for: "adminLogsFrom"
}
const _hoisted_14 = ["onUpdate:modelValue"]
const _hoisted_15 = { class: "col-md-2" }
const _hoisted_16 = {
  class: "form-label small mb-1",
  for: "adminLogsTo"
}
const _hoisted_17 = ["onUpdate:modelValue"]
const _hoisted_18 = { class: "col-md-1 d-grid" }
const _hoisted_19 = ["onClick", "disabled"]
const _hoisted_20 = { class: "mt-2 d-flex flex-wrap gap-2" }
const _hoisted_21 = ["onClick", "disabled"]
const _hoisted_22 = ["onClick", "disabled"]
const _hoisted_23 = ["onClick", "disabled"]
const _hoisted_24 = {
  key: 0,
  class: "alert alert-danger small"
}
const _hoisted_25 = {
  key: 1,
  class: "text-muted small mb-2"
}
const _hoisted_26 = { class: "table-responsive admin-table-scroll" }
const _hoisted_27 = { class: "table table-sm table-striped table-hover small align-middle" }
const _hoisted_28 = { class: "sticky-top bg-body" }
const _hoisted_29 = { class: "text-nowrap" }
const _hoisted_30 = ["title"]
const _hoisted_31 = { key: 0 }
const _hoisted_32 = {
  colspan: "5",
  class: "text-center text-muted py-5"
}
const _hoisted_33 = { class: "fw-semibold text-body" }
const _hoisted_34 = { class: "small" }
const _hoisted_35 = { class: "d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2 small" }
const _hoisted_36 = { class: "text-muted" }
const _hoisted_37 = { class: "d-flex align-items-center gap-2" }
const _hoisted_38 = {
  class: "mb-0",
  for: "adminLogsPageSize"
}
const _hoisted_39 = ["onUpdate:modelValue", "onChange"]
const _hoisted_40 = ["value"]
const _hoisted_41 = ["onClick", "disabled"]
const _hoisted_42 = ["onClick", "disabled"]

return function render(_ctx, _cache) {
  with (_ctx) {
    const { toDisplayString: _toDisplayString, createElementVNode: _createElementVNode, vModelText: _vModelText, withDirectives: _withDirectives, openBlock: _openBlock, createElementBlock: _createElementBlock, createCommentVNode: _createCommentVNode, renderList: _renderList, Fragment: _Fragment, vModelSelect: _vModelSelect } = _Vue

    return (_openBlock(), _createElementBlock("div", null, [
      _createElementVNode("div", _hoisted_1, [
        _createElementVNode("div", _hoisted_2, [
          _createElementVNode("div", _hoisted_3, [
            _createElementVNode("label", _hoisted_4, _toDisplayString(t('admin_logs_filter_user', 'User')), 1 /* TEXT */),
            _withDirectives(_createElementVNode("input", {
              id: "adminLogsUser",
              type: "text",
              class: "form-control form-control-sm",
              "onUpdate:modelValue": $event => ((logFilters.user) = $event),
              placeholder: t('admin_logs_filter_user_placeholder', 'username')
            }, null, 8 /* PROPS */, _hoisted_5), [
              [
                _vModelText,
                logFilters.user,
                void 0,
                { trim: true }
              ]
            ])
          ]),
          _createElementVNode("div", _hoisted_6, [
            _createElementVNode("label", _hoisted_7, _toDisplayString(t('admin_logs_filter_action', 'Action')), 1 /* TEXT */),
            _withDirectives(_createElementVNode("input", {
              id: "adminLogsAction",
              type: "text",
              class: "form-control form-control-sm",
              "onUpdate:modelValue": $event => ((logFilters.action) = $event),
              placeholder: t('admin_logs_filter_action_placeholder', 'action')
            }, null, 8 /* PROPS */, _hoisted_8), [
              [
                _vModelText,
                logFilters.action,
                void 0,
                { trim: true }
              ]
            ])
          ]),
          _createElementVNode("div", _hoisted_9, [
            _createElementVNode("label", _hoisted_10, _toDisplayString(t('admin_logs_filter_path', 'Path contains')), 1 /* TEXT */),
            _withDirectives(_createElementVNode("input", {
              id: "adminLogsPath",
              type: "text",
              class: "form-control form-control-sm",
              "onUpdate:modelValue": $event => ((logFilters.path_contains) = $event),
              placeholder: t('admin_logs_filter_path_placeholder', '/path')
            }, null, 8 /* PROPS */, _hoisted_11), [
              [
                _vModelText,
                logFilters.path_contains,
                void 0,
                { trim: true }
              ]
            ])
          ]),
          _createElementVNode("div", _hoisted_12, [
            _createElementVNode("label", _hoisted_13, _toDisplayString(t('admin_logs_filter_from', 'From')), 1 /* TEXT */),
            _withDirectives(_createElementVNode("input", {
              id: "adminLogsFrom",
              type: "date",
              class: "form-control form-control-sm",
              "onUpdate:modelValue": $event => ((logFilters.date_from) = $event)
            }, null, 8 /* PROPS */, _hoisted_14), [
              [_vModelText, logFilters.date_from]
            ])
          ]),
          _createElementVNode("div", _hoisted_15, [
            _createElementVNode("label", _hoisted_16, _toDisplayString(t('admin_logs_filter_to', 'To')), 1 /* TEXT */),
            _withDirectives(_createElementVNode("input", {
              id: "adminLogsTo",
              type: "date",
              class: "form-control form-control-sm",
              "onUpdate:modelValue": $event => ((logFilters.date_to) = $event)
            }, null, 8 /* PROPS */, _hoisted_17), [
              [_vModelText, logFilters.date_to]
            ])
          ]),
          _createElementVNode("div", _hoisted_18, [
            _createElementVNode("button", {
              class: "btn btn-primary btn-sm",
              onClick: applyLogFilters,
              disabled: isLoadingLogs
            }, _toDisplayString(isLoadingLogs ? '…' : t('admin_logs_apply', 'Apply')), 9 /* TEXT, PROPS */, _hoisted_19)
          ])
        ]),
        _createElementVNode("div", _hoisted_20, [
          _createElementVNode("button", {
            class: "btn btn-outline-secondary btn-sm",
            onClick: resetLogFilters,
            disabled: isLoadingLogs
          }, _toDisplayString(t('admin_logs_reset', 'Reset')), 9 /* TEXT, PROPS */, _hoisted_21),
          _createElementVNode("button", {
            class: "btn btn-outline-secondary btn-sm",
            onClick: $event => (exportLogs('json')),
            disabled: isLoadingLogs || logs.length === 0
          }, _toDisplayString(t('admin_logs_export_json', 'Export JSON')), 9 /* TEXT, PROPS */, _hoisted_22),
          _createElementVNode("button", {
            class: "btn btn-outline-secondary btn-sm",
            onClick: $event => (exportLogs('csv')),
            disabled: isLoadingLogs || logs.length === 0
          }, _toDisplayString(t('admin_logs_export_csv', 'Export CSV')), 9 /* TEXT, PROPS */, _hoisted_23)
        ])
      ]),
      error
        ? (_openBlock(), _createElementBlock("div", _hoisted_24, _toDisplayString(error), 1 /* TEXT */))
        : _createCommentVNode("v-if", true),
      isLoadingLogs
        ? (_openBlock(), _createElementBlock("div", _hoisted_25, _toDisplayString(t('admin_logs_loading', 'Loading logs…')), 1 /* TEXT */))
        : _createCommentVNode("v-if", true),
      _createElementVNode("div", _hoisted_26, [
        _createElementVNode("table", _hoisted_27, [
          _createElementVNode("thead", _hoisted_28, [
            _createElementVNode("tr", null, [
              _createElementVNode("th", null, _toDisplayString(t('admin_logs_col_date', 'Date')), 1 /* TEXT */),
              _createElementVNode("th", null, _toDisplayString(t('admin_logs_col_user', 'User')), 1 /* TEXT */),
              _createElementVNode("th", null, _toDisplayString(t('admin_logs_col_action', 'Action')), 1 /* TEXT */),
              _createElementVNode("th", null, _toDisplayString(t('admin_logs_col_path', 'Path')), 1 /* TEXT */),
              _createElementVNode("th", null, _toDisplayString(t('admin_logs_col_ip', 'IP')), 1 /* TEXT */)
            ])
          ]),
          _createElementVNode("tbody", null, [
            (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(logs, (log) => {
              return (_openBlock(), _createElementBlock("tr", { key: log.timestamp + log.action }, [
                _createElementVNode("td", _hoisted_29, _toDisplayString(formatDate(log.timestamp)), 1 /* TEXT */),
                _createElementVNode("td", null, _toDisplayString(log.user), 1 /* TEXT */),
                _createElementVNode("td", null, [
                  _createElementVNode("strong", null, _toDisplayString(log.action), 1 /* TEXT */)
                ]),
                _createElementVNode("td", {
                  class: "text-truncate admin-log-path",
                  title: log.path
                }, _toDisplayString(log.path), 9 /* TEXT, PROPS */, _hoisted_30),
                _createElementVNode("td", null, _toDisplayString(log.ip), 1 /* TEXT */)
              ]))
            }), 128 /* KEYED_FRAGMENT */)),
            (logs.length === 0 && !isLoadingLogs)
              ? (_openBlock(), _createElementBlock("tr", _hoisted_31, [
                  _createElementVNode("td", _hoisted_32, [
                    _cache[0] || (_cache[0] = _createElementVNode("i", {
                      class: "ri-inbox-line d-block fs-3 mb-2",
                      "aria-hidden": "true"
                    }, null, -1 /* CACHED */)),
                    _createElementVNode("div", _hoisted_33, _toDisplayString(hasActiveFilters ? t('admin_logs_empty', 'No log entries match your filters.') : t('admin_logs_empty_unfiltered', 'No audit activity has been recorded yet.')), 1 /* TEXT */),
                    _createElementVNode("div", _hoisted_34, _toDisplayString(hasActiveFilters ? t('admin_logs_empty_filtered_hint', 'Adjust or reset the filters to broaden the audit search.') : t('admin_logs_empty_unfiltered_hint', 'Activity will appear here after users sign in, change files, share items, or update settings.')), 1 /* TEXT */)
                  ])
                ]))
              : _createCommentVNode("v-if", true)
          ])
        ])
      ]),
      _createElementVNode("div", _hoisted_35, [
        _createElementVNode("div", _hoisted_36, _toDisplayString(t('admin_logs_paging', 'Showing page {page} of {totalPages} ({total} total)', { page: logsMeta.page, totalPages: logsMeta.totalPages, total: logsMeta.total })), 1 /* TEXT */),
        _createElementVNode("div", _hoisted_37, [
          _createElementVNode("label", _hoisted_38, _toDisplayString(t('admin_logs_page_size', 'Page size')), 1 /* TEXT */),
          _withDirectives(_createElementVNode("select", {
            id: "adminLogsPageSize",
            class: "form-select form-select-sm select-auto-width",
            "onUpdate:modelValue": $event => ((logsMeta.pageSize) = $event),
            onChange: changeLogsPageSize
          }, [
            (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(logPageSizeOptions, (size) => {
              return (_openBlock(), _createElementBlock("option", {
                key: size,
                value: size
              }, _toDisplayString(size), 9 /* TEXT, PROPS */, _hoisted_40))
            }), 128 /* KEYED_FRAGMENT */))
          ], 40 /* PROPS, NEED_HYDRATION */, _hoisted_39), [
            [
              _vModelSelect,
              logsMeta.pageSize,
              void 0,
              { number: true }
            ]
          ]),
          _createElementVNode("button", {
            class: "btn btn-outline-secondary btn-sm",
            onClick: $event => (changeLogsPage(-1)),
            disabled: isLoadingLogs || logsMeta.page <= 1
          }, _toDisplayString(t('prev', 'Prev')), 9 /* TEXT, PROPS */, _hoisted_41),
          _createElementVNode("button", {
            class: "btn btn-outline-secondary btn-sm",
            onClick: $event => (changeLogsPage(1)),
            disabled: isLoadingLogs || logsMeta.page >= logsMeta.totalPages
          }, _toDisplayString(t('next', 'Next')), 9 /* TEXT, PROPS */, _hoisted_42)
        ])
      ])
    ]))
  }
}
})(Vue),
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
    computed: {
        hasActiveFilters() {
            return Object.values(this.logFilters).some((value) => String(value || '').trim() !== '');
        }
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
                    totalPages: Math.max(1, res.totalPages ?? 1)
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
