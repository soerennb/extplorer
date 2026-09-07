const AdminSystem = {
    render: ((Vue) => {
const _Vue = Vue
const { createElementVNode: _createElementVNode, createCommentVNode: _createCommentVNode } = _Vue

const _hoisted_1 = {
  key: 0,
  class: "alert alert-danger small"
}
const _hoisted_2 = {
  key: 1,
  class: "text-center text-muted py-5"
}
const _hoisted_3 = { key: 2 }
const _hoisted_4 = { class: "table table-sm table-bordered align-middle" }
const _hoisted_5 = { class: "admin-meta-label" }
const _hoisted_6 = { class: "badge bg-success" }
const _hoisted_7 = { class: "small text-muted p-2 bg-light border rounded admin-config-box" }

return function render(_ctx, _cache) {
  with (_ctx) {
    const { toDisplayString: _toDisplayString, openBlock: _openBlock, createElementBlock: _createElementBlock, createCommentVNode: _createCommentVNode, createElementVNode: _createElementVNode } = _Vue

    return (_openBlock(), _createElementBlock("div", null, [
      error
        ? (_openBlock(), _createElementBlock("div", _hoisted_1, _toDisplayString(error), 1 /* TEXT */))
        : _createCommentVNode("v-if", true),
      (!system)
        ? (_openBlock(), _createElementBlock("div", _hoisted_2, _toDisplayString(t('admin_system_loading', 'Loading system info…')), 1 /* TEXT */))
        : (_openBlock(), _createElementBlock("div", _hoisted_3, [
            _createElementVNode("table", _hoisted_4, [
              _createElementVNode("tbody", null, [
                _createElementVNode("tr", null, [
                  _createElementVNode("th", _hoisted_5, _toDisplayString(t('admin_system_version', 'eXtplorer Version')), 1 /* TEXT */),
                  _createElementVNode("td", null, [
                    _createElementVNode("span", _hoisted_6, _toDisplayString(system.app_version), 1 /* TEXT */)
                  ])
                ]),
                _createElementVNode("tr", null, [
                  _createElementVNode("th", null, _toDisplayString(t('admin_system_php', 'PHP Version')), 1 /* TEXT */),
                  _createElementVNode("td", null, _toDisplayString(system.php_version), 1 /* TEXT */)
                ]),
                _createElementVNode("tr", null, [
                  _createElementVNode("th", null, _toDisplayString(t('admin_system_os', 'OS')), 1 /* TEXT */),
                  _createElementVNode("td", null, _toDisplayString(system.server_os), 1 /* TEXT */)
                ]),
                _createElementVNode("tr", null, [
                  _createElementVNode("th", null, _toDisplayString(t('admin_system_server_software', 'Server Software')), 1 /* TEXT */),
                  _createElementVNode("td", null, _toDisplayString(system.server_software), 1 /* TEXT */)
                ]),
                _createElementVNode("tr", null, [
                  _createElementVNode("th", null, _toDisplayString(t('admin_system_memory', 'Memory Limit')), 1 /* TEXT */),
                  _createElementVNode("td", null, _toDisplayString(system.memory_limit), 1 /* TEXT */)
                ]),
                _createElementVNode("tr", null, [
                  _createElementVNode("th", null, _toDisplayString(t('admin_system_upload', 'Upload Limit')), 1 /* TEXT */),
                  _createElementVNode("td", null, _toDisplayString(system.upload_max_filesize), 1 /* TEXT */)
                ]),
                _createElementVNode("tr", null, [
                  _createElementVNode("th", null, _toDisplayString(t('admin_system_post', 'POST Limit')), 1 /* TEXT */),
                  _createElementVNode("td", null, _toDisplayString(system.post_max_size), 1 /* TEXT */)
                ]),
                _createElementVNode("tr", null, [
                  _createElementVNode("th", null, _toDisplayString(t('admin_system_disk_free', 'Disk Free')), 1 /* TEXT */),
                  _createElementVNode("td", null, _toDisplayString(formatSize(system.disk_free)), 1 /* TEXT */)
                ]),
                _createElementVNode("tr", null, [
                  _createElementVNode("th", null, _toDisplayString(t('admin_system_disk_total', 'Disk Total')), 1 /* TEXT */),
                  _createElementVNode("td", null, _toDisplayString(formatSize(system.disk_total)), 1 /* TEXT */)
                ])
              ])
            ]),
            _createElementVNode("h6", null, _toDisplayString(t('admin_system_extensions_heading', 'Loaded Extensions')), 1 /* TEXT */),
            _createElementVNode("div", _hoisted_7, _toDisplayString(system.extensions), 1 /* TEXT */)
          ]))
    ]))
  }
}
})(Vue),
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
