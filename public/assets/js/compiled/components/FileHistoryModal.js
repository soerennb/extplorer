const FileHistoryModal = {
    render: ((Vue) => {
const _Vue = Vue
const { createElementVNode: _createElementVNode, createCommentVNode: _createCommentVNode, createTextVNode: _createTextVNode } = _Vue

const _hoisted_1 = {
  class: "modal fade",
  id: "fileHistoryModal",
  tabindex: "-1"
}
const _hoisted_2 = { class: "modal-dialog modal-dialog-centered" }
const _hoisted_3 = { class: "modal-content" }
const _hoisted_4 = { class: "modal-header" }
const _hoisted_5 = { class: "modal-title" }
const _hoisted_6 = ["aria-label"]
const _hoisted_7 = { class: "modal-body" }
const _hoisted_8 = {
  key: 0,
  class: "text-center py-4"
}
const _hoisted_9 = {
  key: 1,
  class: "text-center py-4 text-muted"
}
const _hoisted_10 = { key: 2 }
const _hoisted_11 = { class: "small text-muted mb-3" }
const _hoisted_12 = { class: "table table-sm table-hover small" }
const _hoisted_13 = { class: "text-end" }
const _hoisted_14 = ["onClick"]

return function render(_ctx, _cache) {
  with (_ctx) {
    const { createElementVNode: _createElementVNode, toDisplayString: _toDisplayString, createTextVNode: _createTextVNode, openBlock: _openBlock, createElementBlock: _createElementBlock, createCommentVNode: _createCommentVNode, renderList: _renderList, Fragment: _Fragment } = _Vue

    return (_openBlock(), _createElementBlock("div", _hoisted_1, [
      _createElementVNode("div", _hoisted_2, [
        _createElementVNode("div", _hoisted_3, [
          _createElementVNode("div", _hoisted_4, [
            _createElementVNode("h5", _hoisted_5, [
              _cache[0] || (_cache[0] = _createElementVNode("i", { class: "ri-history-line me-2" }, null, -1 /* CACHED */)),
              _createTextVNode(" " + _toDisplayString(t('version_history') || 'Version History'), 1 /* TEXT */)
            ]),
            _createElementVNode("button", {
              type: "button",
              class: "btn-close",
              "data-bs-dismiss": "modal",
              "aria-label": t('close') || 'Close'
            }, null, 8 /* PROPS */, _hoisted_6)
          ]),
          _createElementVNode("div", _hoisted_7, [
            loading
              ? (_openBlock(), _createElementBlock("div", _hoisted_8, [...(_cache[1] || (_cache[1] = [
                  _createElementVNode("div", { class: "spinner-border" }, null, -1 /* CACHED */)
                ]))]))
              : (versions.length === 0)
                ? (_openBlock(), _createElementBlock("div", _hoisted_9, [
                    _cache[2] || (_cache[2] = _createElementVNode("i", { class: "ri-information-line fs-2 d-block mb-2" }, null, -1 /* CACHED */)),
                    _createTextVNode(" " + _toDisplayString(t('no_versions') || 'No previous versions found for this file.'), 1 /* TEXT */)
                  ]))
                : (_openBlock(), _createElementBlock("div", _hoisted_10, [
                    _createElementVNode("p", _hoisted_11, _toDisplayString(t('history_desc') || 'Restoring a version will overwrite the current file.'), 1 /* TEXT */),
                    _createElementVNode("table", _hoisted_12, [
                      _createElementVNode("thead", null, [
                        _createElementVNode("tr", null, [
                          _createElementVNode("th", null, _toDisplayString(t('date')), 1 /* TEXT */),
                          _createElementVNode("th", null, _toDisplayString(t('size')), 1 /* TEXT */),
                          _cache[3] || (_cache[3] = _createElementVNode("th", { class: "text-end" }, "Action", -1 /* CACHED */))
                        ])
                      ]),
                      _createElementVNode("tbody", null, [
                        (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(versions, (v) => {
                          return (_openBlock(), _createElementBlock("tr", { key: v.id }, [
                            _createElementVNode("td", null, _toDisplayString(v.date), 1 /* TEXT */),
                            _createElementVNode("td", null, _toDisplayString(formatSize(v.size)), 1 /* TEXT */),
                            _createElementVNode("td", _hoisted_13, [
                              _createElementVNode("button", {
                                class: "btn btn-outline-success btn-sm py-0",
                                onClick: $event => (restore(v.id))
                              }, _toDisplayString(t('restore') || 'Restore'), 9 /* TEXT, PROPS */, _hoisted_14)
                            ])
                          ]))
                        }), 128 /* KEYED_FRAGMENT */))
                      ])
                    ])
                  ]))
          ])
        ])
      ])
    ]))
  }
}
})(Vue),
    setup() {
        const { ref } = Vue;
        const versions = ref([]);
        const loading = ref(false);
        const targetPath = ref('');
        let modalInstance = null;

        const loadVersions = async (path) => {
            loading.value = true;
            try {
                const res = await Api.get('versions/list', { path });
                versions.value = res.versions;
            } catch(e) { console.error(e); }
            finally { loading.value = false; }
        };

        const open = (file) => {
            targetPath.value = file.path;
            loadVersions(file.path);
            if (!modalInstance) modalInstance = new bootstrap.Modal(document.getElementById('fileHistoryModal'));
            modalInstance.show();
        };

        const restore = async (versionId) => {
            const res = await Swal.fire({
                title: i18n.t('confirm_restore') || 'Restore this version?',
                text: i18n.t('restore_text') || 'The current file content will be replaced.',
                icon: 'warning',
                showCancelButton: true
            });

            if (res.isConfirmed) {
                loading.value = true;
                try {
                    await Api.post('versions/restore', { path: targetPath.value, version_id: versionId });
                    modalInstance.hide();
                    Swal.fire(i18n.t('restored'), '', 'success');
                    store.loadPath(store.cwd); // Refresh
                } catch(e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
                finally { loading.value = false; }
            }
        };

        const formatSize = (b) => {
            const k=1024, s=['B','KB','MB','GB'];
            const i=Math.floor(Math.log(b)/Math.log(k));
            return parseFloat((b/Math.pow(k,i)).toFixed(2))+' '+s[i];
        };

        return { open, versions, loading, restore, formatSize, t: (k) => i18n.t(k) };
    }
};
