const appTemplateRender = ((Vue) => {
const _Vue = Vue
const { createVNode: _createVNode, createElementVNode: _createElementVNode, createCommentVNode: _createCommentVNode, createTextVNode: _createTextVNode } = _Vue

const _hoisted_1 = { class: "navbar navbar-expand-lg navbar-dark app-navbar" }
const _hoisted_2 = { class: "container-fluid" }
const _hoisted_3 = {
  class: "navbar-brand d-flex align-items-center",
  href: "#"
}
const _hoisted_4 = ["src"]
const _hoisted_5 = { class: "d-lg-none text-white mobile-current-path me-auto" }
const _hoisted_6 = { class: "mobile-current-path-text" }
const _hoisted_7 = {
  class: "d-flex align-items-center text-white me-3 desktop-path",
  "data-testid": "current-path"
}
const _hoisted_8 = { class: "me-2 text-white-50" }
const _hoisted_9 = {
  "aria-label": "breadcrumb",
  class: "path-breadcrumb"
}
const _hoisted_10 = { class: "breadcrumb mb-0" }
const _hoisted_11 = { class: "breadcrumb-item" }
const _hoisted_12 = ["onClick"]
const _hoisted_13 = ["onClick"]
const _hoisted_14 = { class: "me-3 navbar-search" }
const _hoisted_15 = { class: "input-group input-group-sm" }
const _hoisted_16 = ["placeholder", "onUpdate:modelValue", "onKeyup"]
const _hoisted_17 = ["onClick"]
const _hoisted_18 = { class: "d-flex gap-2 navbar-nav-tools" }
const _hoisted_19 = ["onClick", "disabled"]
const _hoisted_20 = ["onClick"]
const _hoisted_21 = ["onClick", "aria-label", "title"]
const _hoisted_22 = {
  class: "btn-group btn-group-sm d-none d-sm-flex",
  role: "group",
  "aria-label": "View mode"
}
const _hoisted_23 = ["onClick", "aria-pressed"]
const _hoisted_24 = ["onClick", "aria-pressed"]
const _hoisted_25 = { class: "dropdown" }
const _hoisted_26 = { class: "dropdown-menu dropdown-menu-end shadow" }
const _hoisted_27 = ["onClick"]
const _hoisted_28 = { key: 0 }
const _hoisted_29 = ["onClick"]
const _hoisted_30 = { key: 1 }
const _hoisted_31 = ["href"]
const _hoisted_32 = ["onClick"]
const _hoisted_33 = ["onClick"]
const _hoisted_34 = ["onClick"]
const _hoisted_35 = ["onClick"]
const _hoisted_36 = ["onMousedown"]
const _hoisted_37 = ["aria-label"]
const _hoisted_38 = { class: "command-palette-search input-group input-group-lg" }
const _hoisted_39 = ["onUpdate:modelValue", "placeholder", "aria-label", "onKeydown"]
const _hoisted_40 = ["onClick", "aria-label"]
const _hoisted_41 = { class: "command-palette-list py-1" }
const _hoisted_42 = ["disabled", "onMouseenter", "onClick"]
const _hoisted_43 = { class: "command-palette-icon" }
const _hoisted_44 = { class: "flex-grow-1" }
const _hoisted_45 = { class: "d-block fw-semibold" }
const _hoisted_46 = {
  key: 0,
  class: "d-block small text-muted"
}
const _hoisted_47 = {
  key: 0,
  class: "text-center text-muted small py-4"
}
const _hoisted_48 = { class: "bg-body-tertiary border-bottom p-2 d-flex gap-1 gap-md-2 align-items-center flex-wrap app-toolbar" }
const _hoisted_49 = ["onClick", "title"]
const _hoisted_50 = { class: "d-none d-md-inline" }
const _hoisted_51 = ["onClick", "title"]
const _hoisted_52 = ["onClick", "disabled", "title"]
const _hoisted_53 = { class: "d-none d-xl-inline" }
const _hoisted_54 = ["onClick", "disabled", "title"]
const _hoisted_55 = { class: "d-none d-xl-inline" }
const _hoisted_56 = ["onClick", "disabled", "title"]
const _hoisted_57 = { class: "d-none d-md-inline" }
const _hoisted_58 = {
  key: 0,
  class: "badge bg-success ms-1"
}
const _hoisted_59 = ["onClick", "disabled", "title"]
const _hoisted_60 = { class: "d-none d-md-inline" }
const _hoisted_61 = ["onClick", "aria-pressed", "title"]
const _hoisted_62 = { class: "d-none d-xl-inline" }
const _hoisted_63 = { class: "dropdown app-toolbar-overflow" }
const _hoisted_64 = {
  class: "btn btn-outline-secondary btn-sm dropdown-toggle",
  type: "button",
  "data-bs-toggle": "dropdown",
  "aria-label": "More actions"
}
const _hoisted_65 = { class: "d-sm-none" }
const _hoisted_66 = { class: "dropdown-menu dropdown-menu-end shadow" }
const _hoisted_67 = { class: "d-sm-none" }
const _hoisted_68 = ["onClick"]
const _hoisted_69 = { class: "d-sm-none" }
const _hoisted_70 = ["onClick"]
const _hoisted_71 = { class: "d-sm-none" }
const _hoisted_72 = ["onClick"]
const _hoisted_73 = { class: "d-sm-none" }
const _hoisted_74 = ["onClick"]
const _hoisted_75 = { class: "d-sm-none" }
const _hoisted_76 = ["onClick"]
const _hoisted_77 = ["onClick"]
const _hoisted_78 = ["onClick"]
const _hoisted_79 = ["onClick"]
const _hoisted_80 = ["onClick"]
const _hoisted_81 = ["onClick"]
const _hoisted_82 = ["onClick"]
const _hoisted_83 = ["onClick"]
const _hoisted_84 = ["onClick"]
const _hoisted_85 = { class: "d-flex align-items-center text-danger fw-bold me-auto" }
const _hoisted_86 = ["onClick", "disabled"]
const _hoisted_87 = ["onClick"]
const _hoisted_88 = ["aria-label"]
const _hoisted_89 = {
  class: "selection-action-bar-count",
  "aria-live": "polite"
}
const _hoisted_90 = { class: "selection-action-bar-actions" }
const _hoisted_91 = ["onClick", "aria-label"]
const _hoisted_92 = ["onClick"]
const _hoisted_93 = ["onClick"]
const _hoisted_94 = ["onClick"]
const _hoisted_95 = ["onClick"]
const _hoisted_96 = ["onClick"]
const _hoisted_97 = ["onClick"]
const _hoisted_98 = { class: "dropdown" }
const _hoisted_99 = ["aria-label"]
const _hoisted_100 = { class: "dropdown-menu dropdown-menu-end shadow" }
const _hoisted_101 = { key: 0 }
const _hoisted_102 = ["onClick"]
const _hoisted_103 = { key: 1 }
const _hoisted_104 = ["onClick"]
const _hoisted_105 = ["onClick"]
const _hoisted_106 = ["onClick"]
const _hoisted_107 = ["onClick"]
const _hoisted_108 = { key: 2 }
const _hoisted_109 = ["onClick"]
const _hoisted_110 = { key: 3 }
const _hoisted_111 = ["onClick"]
const _hoisted_112 = { key: 4 }
const _hoisted_113 = ["onClick"]
const _hoisted_114 = ["onClick", "onContextmenu", "onDragover", "onDragleave", "onDrop"]
const _hoisted_115 = {
  key: 0,
  class: "drag-operation-indicator drag-operation-indicator-copy"
}
const _hoisted_116 = {
  class: "sidebar offcanvas-lg offcanvas-start p-2",
  id: "sidebarOffcanvas",
  tabindex: "-1"
}
const _hoisted_117 = { class: "offcanvas-header d-lg-none" }
const _hoisted_118 = ["aria-label"]
const _hoisted_119 = { class: "offcanvas-body d-flex flex-column p-0" }
const _hoisted_120 = { class: "mb-4 px-2 d-flex gap-2" }
const _hoisted_121 = ["onClick"]
const _hoisted_122 = ["onClick"]
const _hoisted_123 = { class: "mb-4" }
const _hoisted_124 = { class: "d-flex justify-content-between align-items-center mb-2 px-2" }
const _hoisted_125 = { class: "small fw-bold text-uppercase text-muted mb-0" }
const _hoisted_126 = ["onClick", "aria-label", "title"]
const _hoisted_127 = {
  key: 0,
  class: "small text-muted px-2"
}
const _hoisted_128 = ["onClick"]
const _hoisted_129 = ["onClick", "aria-label", "title"]
const _hoisted_130 = {
  key: 0,
  class: "mb-4"
}
const _hoisted_131 = { class: "d-flex justify-content-between align-items-center mb-2 px-2" }
const _hoisted_132 = { class: "small fw-bold text-uppercase text-muted mb-0" }
const _hoisted_133 = ["onClick"]
const _hoisted_134 = ["onClick"]
const _hoisted_135 = { class: "text-truncate" }
const _hoisted_136 = { class: "mt-4 px-2" }
const _hoisted_137 = ["onClick"]
const _hoisted_138 = ["onClick"]
const _hoisted_139 = {
  key: 0,
  class: "mb-3"
}
const _hoisted_140 = { class: "d-flex justify-content-between small text-muted mb-1" }
const _hoisted_141 = { key: 0 }
const _hoisted_142 = { class: "progress progress-thin" }
const _hoisted_143 = {
  key: 1,
  class: "position-absolute top-50 start-50 translate-middle"
}
const _hoisted_144 = {
  key: 2,
  class: "alert alert-danger m-3"
}
const _hoisted_145 = { key: 3 }
const _hoisted_146 = {
  key: 0,
  class: "d-flex flex-column align-items-center justify-content-center w-100 text-muted py-5 my-5 empty-state"
}
const _hoisted_147 = { class: "bg-body-secondary rounded-circle d-flex align-items-center justify-content-center mb-4 empty-state-icon" }
const _hoisted_148 = { class: "fw-normal mb-3" }
const _hoisted_149 = { class: "mb-5 text-center px-4 empty-state-text" }
const _hoisted_150 = {
  key: 0,
  class: "d-flex gap-2"
}
const _hoisted_151 = ["onClick"]
const _hoisted_152 = ["onClick"]
const _hoisted_153 = ["onClick"]
const _hoisted_154 = ["onClick", "title", "aria-label"]
const _hoisted_155 = {
  key: 1,
  class: "list-view-header text-muted border-bottom px-3 py-2 small fw-bold user-select-none w-100"
}
const _hoisted_156 = ["onClick"]
const _hoisted_157 = ["onClick"]
const _hoisted_158 = ["onClick"]
const _hoisted_159 = ["data-file-name", "data-file-path", "onDragstart", "onDragend", "onDragover", "onDragleave", "onDrop", "onClick", "onTouchstart", "onTouchend", "onDblclick", "onContextmenu"]
const _hoisted_160 = { class: "file-icon position-relative" }
const _hoisted_161 = ["src"]
const _hoisted_162 = {
  key: 2,
  class: "position-absolute bottom-0 end-0 bg-white rounded-circle shadow-sm d-flex align-items-center justify-content-center shared-badge"
}
const _hoisted_163 = ["title"]
const _hoisted_164 = {
  key: 0,
  class: "badge bg-secondary-subtle text-secondary border border-secondary-subtle ms-1 mount-badge"
}
const _hoisted_165 = ["onClick"]
const _hoisted_166 = { class: "file-meta-col size-col" }
const _hoisted_167 = { class: "file-meta-col date-col" }
const _hoisted_168 = ["onClick"]
const _hoisted_169 = { class: "d-flex align-items-center justify-content-between gap-2 mb-3" }
const _hoisted_170 = {
  id: "detailsPaneTitle",
  class: "mb-0"
}
const _hoisted_171 = ["onClick", "aria-label"]
const _hoisted_172 = {
  key: 0,
  class: "text-center text-muted small py-4"
}
const _hoisted_173 = { key: 1 }
const _hoisted_174 = { class: "details-preview rounded d-flex align-items-center justify-content-center mb-3 overflow-hidden" }
const _hoisted_175 = ["src", "alt"]
const _hoisted_176 = { class: "d-flex align-items-start justify-content-between gap-2 mb-3" }
const _hoisted_177 = { class: "min-w-0" }
const _hoisted_178 = ["title"]
const _hoisted_179 = ["title"]
const _hoisted_180 = ["onClick", "aria-label", "title"]
const _hoisted_181 = { class: "row small mb-3" }
const _hoisted_182 = { class: "col-4" }
const _hoisted_183 = { class: "col-8 text-truncate" }
const _hoisted_184 = { class: "col-4" }
const _hoisted_185 = { class: "col-8" }
const _hoisted_186 = { class: "col-4" }
const _hoisted_187 = { class: "col-8" }
const _hoisted_188 = { class: "col-4" }
const _hoisted_189 = { class: "col-8" }
const _hoisted_190 = { class: "col-4" }
const _hoisted_191 = { class: "col-8" }
const _hoisted_192 = { class: "d-grid gap-2" }
const _hoisted_193 = ["onClick", "disabled"]
const _hoisted_194 = ["onClick", "disabled"]
const _hoisted_195 = { class: "bg-body-tertiary border-top px-3 py-2 small text-muted d-flex flex-wrap align-items-center gap-2 app-statusbar" }
const _hoisted_196 = { class: "d-flex align-items-center me-auto app-statusbar-summary" }
const _hoisted_197 = { class: "me-2 fw-bold text-primary version-label" }
const _hoisted_198 = { class: "ms-2 connection-label" }
const _hoisted_199 = {
  key: 0,
  class: "d-flex align-items-center gap-2 mx-auto order-2 order-md-1 app-statusbar-pagination"
}
const _hoisted_200 = { "aria-label": "File list pagination" }
const _hoisted_201 = { class: "pagination pagination-lg mb-0 flex-wrap gap-1" }
const _hoisted_202 = ["onClick"]
const _hoisted_203 = { class: "d-none d-sm-inline ms-1" }
const _hoisted_204 = {
  key: 0,
  class: "page-link"
}
const _hoisted_205 = ["onClick"]
const _hoisted_206 = ["onClick"]
const _hoisted_207 = { class: "d-none d-sm-inline me-1" }
const _hoisted_208 = { class: "text-muted small d-none d-md-inline" }
const _hoisted_209 = { class: "d-flex align-items-center gap-2 ms-auto order-1 order-md-2 app-statusbar-pagesize" }
const _hoisted_210 = {
  for: "pageSizeSelect",
  class: "small text-muted d-none d-sm-inline mb-0"
}
const _hoisted_211 = ["value", "onChange"]
const _hoisted_212 = {
  class: "modal fade",
  id: "editorModal",
  tabindex: "-1",
  "data-bs-backdrop": "static"
}
const _hoisted_213 = { class: "modal-dialog modal-fullscreen-lg-down modal-xl modal-dialog-centered h-90vh" }
const _hoisted_214 = { class: "modal-content h-100" }
const _hoisted_215 = { class: "modal-header py-2" }
const _hoisted_216 = { class: "modal-title fs-6" }
const _hoisted_217 = ["aria-label"]
const _hoisted_218 = { class: "modal-footer py-2" }
const _hoisted_219 = {
  type: "button",
  class: "btn btn-secondary btn-sm",
  "data-bs-dismiss": "modal"
}
const _hoisted_220 = ["onClick"]
const _hoisted_221 = {
  class: "modal fade",
  id: "diffModal",
  tabindex: "-1"
}
const _hoisted_222 = { class: "modal-dialog modal-fullscreen-lg-down modal-xl modal-dialog-centered h-90vh" }
const _hoisted_223 = { class: "modal-content h-100" }
const _hoisted_224 = { class: "modal-header py-2" }
const _hoisted_225 = ["aria-label"]
const _hoisted_226 = {
  class: "modal fade",
  id: "previewModal",
  tabindex: "-1"
}
const _hoisted_227 = { class: "modal-dialog modal-xl modal-dialog-centered" }
const _hoisted_228 = { class: "modal-content bg-dark border-0 shadow-lg" }
const _hoisted_229 = { class: "modal-header border-0 py-2" }
const _hoisted_230 = { class: "modal-title text-white" }
const _hoisted_231 = ["aria-label"]
const _hoisted_232 = { class: "modal-body p-0 text-center position-relative d-flex align-items-center justify-content-center preview-modal-body" }
const _hoisted_233 = ["src"]
const _hoisted_234 = ["src"]
const _hoisted_235 = {
  key: 2,
  class: "p-5 w-100"
}
const _hoisted_236 = ["src"]
const _hoisted_237 = ["src"]
const _hoisted_238 = ["onClick", "disabled", "aria-label"]
const _hoisted_239 = ["onClick", "disabled", "aria-label"]
const _hoisted_240 = {
  class: "modal fade",
  id: "propModal",
  tabindex: "-1"
}
const _hoisted_241 = { class: "modal-dialog modal-fullscreen-sm-down" }
const _hoisted_242 = { class: "modal-content" }
const _hoisted_243 = { class: "modal-header py-2" }
const _hoisted_244 = { class: "modal-title fs-6" }
const _hoisted_245 = ["aria-label"]
const _hoisted_246 = {
  key: 0,
  class: "modal-body"
}
const _hoisted_247 = { class: "text-center mb-3" }
const _hoisted_248 = { class: "mt-2" }
const _hoisted_249 = { "aria-labelledby": "propertiesMetadataHeading" }
const _hoisted_250 = {
  id: "propertiesMetadataHeading",
  class: "text-uppercase text-muted small fw-bold mb-2"
}
const _hoisted_251 = { class: "table table-sm small mb-0" }
const _hoisted_252 = ["onClick"]
const _hoisted_253 = {
  key: 0,
  class: "border border-warning-subtle rounded bg-warning-subtle p-3 mt-3",
  "aria-labelledby": "propertiesAdminHeading"
}
const _hoisted_254 = { class: "d-flex align-items-start gap-2 mb-3" }
const _hoisted_255 = {
  id: "propertiesAdminHeading",
  class: "mb-1"
}
const _hoisted_256 = { class: "small text-warning-emphasis" }
const _hoisted_257 = { class: "row g-2" }
const _hoisted_258 = { class: "col-sm-6" }
const _hoisted_259 = {
  class: "form-label small",
  for: "propOwner"
}
const _hoisted_260 = ["onUpdate:modelValue"]
const _hoisted_261 = { class: "col-sm-6" }
const _hoisted_262 = {
  class: "form-label small",
  for: "propGroup"
}
const _hoisted_263 = ["onUpdate:modelValue"]
const _hoisted_264 = { class: "form-check small mt-3" }
const _hoisted_265 = ["onUpdate:modelValue"]
const _hoisted_266 = {
  class: "form-check-label",
  for: "propRecursive"
}
const _hoisted_267 = {
  key: 0,
  class: "alert alert-warning small py-2 mt-2 mb-0"
}
const _hoisted_268 = { class: "d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3" }
const _hoisted_269 = { class: "small text-muted" }
const _hoisted_270 = ["onClick"]
const _hoisted_271 = {
  class: "modal fade",
  id: "webdavModal",
  tabindex: "-1"
}
const _hoisted_272 = { class: "modal-dialog modal-lg modal-dialog-centered" }
const _hoisted_273 = { class: "modal-content" }
const _hoisted_274 = { class: "modal-header py-2" }
const _hoisted_275 = { class: "modal-title fs-6" }
const _hoisted_276 = ["aria-label"]
const _hoisted_277 = { class: "modal-body" }
const _hoisted_278 = { class: "small text-muted" }
const _hoisted_279 = { class: "mb-3" }
const _hoisted_280 = {
  class: "form-label small fw-bold",
  for: "webdav_url_input"
}
const _hoisted_281 = { class: "input-group input-group-sm" }
const _hoisted_282 = ["value"]
const _hoisted_283 = ["onClick"]
const _hoisted_284 = { class: "row g-3 mb-3" }
const _hoisted_285 = { class: "col-md-4" }
const _hoisted_286 = { class: "border rounded p-3 h-100" }
const _hoisted_287 = { class: "fw-bold small mb-2" }
const _hoisted_288 = { class: "small text-muted" }
const _hoisted_289 = { class: "col-md-4" }
const _hoisted_290 = { class: "border rounded p-3 h-100" }
const _hoisted_291 = { class: "fw-bold small mb-2" }
const _hoisted_292 = { class: "small text-muted" }
const _hoisted_293 = { class: "col-md-4" }
const _hoisted_294 = { class: "border rounded p-3 h-100" }
const _hoisted_295 = { class: "fw-bold small mb-2" }
const _hoisted_296 = { class: "small text-muted" }
const _hoisted_297 = { class: "alert alert-info py-2 small mb-2" }
const _hoisted_298 = { class: "alert alert-warning py-2 small mb-2" }
const _hoisted_299 = { class: "alert alert-light border py-2 small mb-0" }
const _hoisted_300 = {
  key: 2,
  class: "dropdown-menu show context-menu"
}
const _hoisted_301 = ["onClick"]
const _hoisted_302 = ["onClick"]
const _hoisted_303 = ["onClick"]
const _hoisted_304 = ["onClick"]
const _hoisted_305 = ["onClick"]
const _hoisted_306 = ["onClick"]
const _hoisted_307 = ["onClick"]
const _hoisted_308 = ["onClick"]
const _hoisted_309 = ["onClick"]
const _hoisted_310 = ["onClick"]
const _hoisted_311 = ["onClick"]
const _hoisted_312 = ["onClick"]
const _hoisted_313 = ["onClick"]
const _hoisted_314 = ["onClick"]

return function render(_ctx, _cache) {
  with (_ctx) {
    const { createCommentVNode: _createCommentVNode, createElementVNode: _createElementVNode, toDisplayString: _toDisplayString, withModifiers: _withModifiers, renderList: _renderList, Fragment: _Fragment, openBlock: _openBlock, createElementBlock: _createElementBlock, vModelText: _vModelText, withKeys: _withKeys, withDirectives: _withDirectives, createTextVNode: _createTextVNode, normalizeClass: _normalizeClass, resolveComponent: _resolveComponent, createVNode: _createVNode, vModelCheckbox: _vModelCheckbox } = _Vue

    const _component_user_admin = _resolveComponent("user-admin")
    const _component_user_profile = _resolveComponent("user-profile")
    const _component_share_modal = _resolveComponent("share-modal")
    const _component_upload_modal = _resolveComponent("upload-modal")
    const _component_file_history_modal = _resolveComponent("file-history-modal")
    const _component_transfer_modal = _resolveComponent("transfer-modal")
    const _component_file_tree = _resolveComponent("file-tree")

    return (_openBlock(), _createElementBlock(_Fragment, null, [
      _createCommentVNode(" Navbar "),
      _createElementVNode("nav", _hoisted_1, [
        _createElementVNode("div", _hoisted_2, [
          _createCommentVNode(" Mobile Sidebar Toggle "),
          _cache[17] || (_cache[17] = _createElementVNode("button", {
            class: "btn btn-outline-light btn-sm d-lg-none me-2",
            type: "button",
            "data-bs-toggle": "offcanvas",
            "data-bs-target": "#sidebarOffcanvas",
            "aria-label": "Menu"
          }, [
            _createElementVNode("i", {
              class: "ri-menu-line",
              "aria-hidden": "true"
            })
          ], -1 /* CACHED */)),
          _createElementVNode("a", _hoisted_3, [
            _createElementVNode("img", {
              src: baseUrl + 'logo-dark.svg',
              alt: "Logo",
              class: "navbar-logo"
            }, null, 8 /* PROPS */, _hoisted_4)
          ]),
          _createElementVNode("div", _hoisted_5, [
            _createElementVNode("span", _hoisted_6, _toDisplayString(store.cwd || '/'), 1 /* TEXT */)
          ]),
          _createElementVNode("div", _hoisted_7, [
            _createElementVNode("span", _hoisted_8, _toDisplayString(t('path')), 1 /* TEXT */),
            _createElementVNode("nav", _hoisted_9, [
              _createElementVNode("ol", _hoisted_10, [
                _createElementVNode("li", _hoisted_11, [
                  _createElementVNode("a", {
                    href: "#",
                    class: "link-light text-decoration-none",
                    onClick: _withModifiers($event => (goToPath('')), ["prevent"])
                  }, "/", 8 /* PROPS */, _hoisted_12)
                ]),
                (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(breadcrumbs, (crumb, idx) => {
                  return (_openBlock(), _createElementBlock("li", {
                    key: crumb.path,
                    class: "breadcrumb-item"
                  }, [
                    _createElementVNode("a", {
                      href: "#",
                      class: "link-light text-decoration-none",
                      onClick: _withModifiers($event => (goToPath(crumb.path)), ["prevent"])
                    }, _toDisplayString(crumb.name), 9 /* TEXT, PROPS */, _hoisted_13)
                  ]))
                }), 128 /* KEYED_FRAGMENT */))
              ])
            ])
          ]),
          _createElementVNode("div", _hoisted_14, [
            _createElementVNode("div", _hoisted_15, [
              _withDirectives(_createElementVNode("input", {
                id: "navbarSearchInput",
                type: "text",
                class: "form-control bg-body-tertiary text-body border-secondary-subtle",
                placeholder: t('filter_placeholder'),
                "aria-label": "Search files",
                "onUpdate:modelValue": $event => ((store.searchQuery) = $event),
                onKeyup: _withKeys($event => (store.performSearch(store.searchQuery)), ["enter"])
              }, null, 40 /* PROPS, NEED_HYDRATION */, _hoisted_16), [
                [_vModelText, store.searchQuery]
              ]),
              _createElementVNode("button", {
                class: "btn btn-outline-secondary",
                type: "button",
                onClick: $event => (store.performSearch(store.searchQuery)),
                "aria-label": "Search"
              }, [...(_cache[0] || (_cache[0] = [
                _createElementVNode("i", {
                  class: "ri-search-line",
                  "aria-hidden": "true"
                }, null, -1 /* CACHED */)
              ]))], 8 /* PROPS */, _hoisted_17)
            ])
          ]),
          _createElementVNode("div", _hoisted_18, [
            _createElementVNode("button", {
              class: "btn btn-outline-light btn-sm d-none d-sm-inline-flex align-items-center",
              onClick: goUp,
              disabled: !store.cwd,
              "data-testid": "go-up"
            }, [
              _cache[1] || (_cache[1] = _createElementVNode("i", { class: "ri-arrow-up-line" }, null, -1 /* CACHED */)),
              _createTextVNode(" " + _toDisplayString(t('up')), 1 /* TEXT */)
            ], 8 /* PROPS */, _hoisted_19),
            _createElementVNode("button", {
              class: "btn btn-outline-light btn-sm d-none d-sm-inline-flex align-items-center",
              onClick: reload,
              "aria-label": "Refresh",
              title: "Refresh"
            }, [...(_cache[2] || (_cache[2] = [
              _createElementVNode("i", {
                class: "ri-refresh-line",
                "aria-hidden": "true"
              }, null, -1 /* CACHED */)
            ]))], 8 /* PROPS */, _hoisted_20),
            _createElementVNode("button", {
              class: "btn btn-outline-light btn-sm d-inline-flex align-items-center",
              onClick: openCommandPalette,
              "aria-label": t('command_palette'),
              title: t('command_palette')
            }, [...(_cache[3] || (_cache[3] = [
              _createElementVNode("i", {
                class: "ri-command-line",
                "aria-hidden": "true"
              }, null, -1 /* CACHED */)
            ]))], 8 /* PROPS */, _hoisted_21),
            _createElementVNode("div", _hoisted_22, [
              _createElementVNode("button", {
                class: _normalizeClass(["btn btn-outline-light", {active: store.viewMode === 'grid'}]),
                onClick: $event => (store.toggleViewMode('grid')),
                "aria-label": "Grid view",
                "aria-pressed": store.viewMode === 'grid'
              }, [...(_cache[4] || (_cache[4] = [
                _createElementVNode("i", {
                  class: "ri-grid-fill",
                  "aria-hidden": "true"
                }, null, -1 /* CACHED */)
              ]))], 10 /* CLASS, PROPS */, _hoisted_23),
              _createElementVNode("button", {
                class: _normalizeClass(["btn btn-outline-light", {active: store.viewMode === 'list'}]),
                onClick: $event => (store.toggleViewMode('list')),
                "aria-label": "List view",
                "aria-pressed": store.viewMode === 'list'
              }, [...(_cache[5] || (_cache[5] = [
                _createElementVNode("i", {
                  class: "ri-list-check",
                  "aria-hidden": "true"
                }, null, -1 /* CACHED */)
              ]))], 10 /* CLASS, PROPS */, _hoisted_24)
            ]),
            _createElementVNode("div", _hoisted_25, [
              _cache[16] || (_cache[16] = _createElementVNode("button", {
                class: "btn btn-outline-light btn-sm ms-2 dropdown-toggle",
                type: "button",
                "data-bs-toggle": "dropdown",
                "aria-label": "User menu",
                "data-testid": "user-menu"
              }, [
                _createElementVNode("i", {
                  class: "ri-user-line",
                  "aria-hidden": "true"
                })
              ], -1 /* CACHED */)),
              _createElementVNode("ul", _hoisted_26, [
                _createElementVNode("li", null, [
                  _createElementVNode("a", {
                    class: "dropdown-item",
                    href: "#",
                    onClick: _withModifiers(openProfile, ["prevent"])
                  }, [
                    _cache[6] || (_cache[6] = _createElementVNode("i", { class: "ri-user-settings-line me-2" }, null, -1 /* CACHED */)),
                    _createTextVNode(_toDisplayString(t('profile_settings') || 'Profile & Settings'), 1 /* TEXT */)
                  ], 8 /* PROPS */, _hoisted_27)
                ]),
                isAdmin
                  ? (_openBlock(), _createElementBlock("li", _hoisted_28, [
                      _createElementVNode("a", {
                        class: "dropdown-item",
                        href: "#",
                        onClick: _withModifiers(openAdmin, ["prevent"])
                      }, [...(_cache[7] || (_cache[7] = [
                        _createElementVNode("i", { class: "ri-flashlight-line me-2" }, null, -1 /* CACHED */),
                        _createTextVNode("Quick Admin", -1 /* CACHED */)
                      ]))], 8 /* PROPS */, _hoisted_29)
                    ]))
                  : _createCommentVNode("v-if", true),
                isAdmin
                  ? (_openBlock(), _createElementBlock("li", _hoisted_30, [
                      _createElementVNode("a", {
                        class: "dropdown-item",
                        href: baseUrl + 'admin'
                      }, [...(_cache[8] || (_cache[8] = [
                        _createElementVNode("i", { class: "ri-settings-3-line me-2" }, null, -1 /* CACHED */),
                        _createTextVNode(" Admin Console", -1 /* CACHED */)
                      ]))], 8 /* PROPS */, _hoisted_31)
                    ]))
                  : _createCommentVNode("v-if", true),
                _cache[13] || (_cache[13] = _createElementVNode("li", null, [
                  _createElementVNode("hr", { class: "dropdown-divider" })
                ], -1 /* CACHED */)),
                _cache[14] || (_cache[14] = _createElementVNode("li", null, [
                  _createElementVNode("h6", { class: "dropdown-header" }, "Appearance")
                ], -1 /* CACHED */)),
                _createElementVNode("li", null, [
                  _createElementVNode("a", {
                    class: _normalizeClass(["dropdown-item", {active: theme === 'light'}]),
                    href: "#",
                    onClick: _withModifiers($event => (setTheme('light')), ["prevent"])
                  }, [
                    _cache[9] || (_cache[9] = _createElementVNode("i", { class: "ri-sun-line me-2" }, null, -1 /* CACHED */)),
                    _createTextVNode(" " + _toDisplayString(t('theme_light')), 1 /* TEXT */)
                  ], 10 /* CLASS, PROPS */, _hoisted_32)
                ]),
                _createElementVNode("li", null, [
                  _createElementVNode("a", {
                    class: _normalizeClass(["dropdown-item", {active: theme === 'dark'}]),
                    href: "#",
                    onClick: _withModifiers($event => (setTheme('dark')), ["prevent"])
                  }, [
                    _cache[10] || (_cache[10] = _createElementVNode("i", { class: "ri-moon-line me-2" }, null, -1 /* CACHED */)),
                    _createTextVNode(" " + _toDisplayString(t('theme_dark')), 1 /* TEXT */)
                  ], 10 /* CLASS, PROPS */, _hoisted_33)
                ]),
                _createElementVNode("li", null, [
                  _createElementVNode("a", {
                    class: _normalizeClass(["dropdown-item", {active: theme === 'auto'}]),
                    href: "#",
                    onClick: _withModifiers($event => (setTheme('auto')), ["prevent"])
                  }, [
                    _cache[11] || (_cache[11] = _createElementVNode("i", { class: "ri-computer-line me-2" }, null, -1 /* CACHED */)),
                    _createTextVNode(" " + _toDisplayString(t('theme_auto')), 1 /* TEXT */)
                  ], 10 /* CLASS, PROPS */, _hoisted_34)
                ]),
                _cache[15] || (_cache[15] = _createElementVNode("li", null, [
                  _createElementVNode("hr", { class: "dropdown-divider" })
                ], -1 /* CACHED */)),
                _createElementVNode("li", null, [
                  _createElementVNode("a", {
                    class: "dropdown-item text-danger",
                    href: "#",
                    onClick: _withModifiers(logout, ["prevent"]),
                    "data-testid": "logout"
                  }, [
                    _cache[12] || (_cache[12] = _createElementVNode("i", { class: "ri-logout-box-r-line me-2" }, null, -1 /* CACHED */)),
                    _createTextVNode(_toDisplayString(t('logout') || 'Logout'), 1 /* TEXT */)
                  ], 8 /* PROPS */, _hoisted_35)
                ])
              ])
            ])
          ])
        ])
      ]),
      _createCommentVNode(" Components "),
      _createVNode(_component_user_admin, { ref: "userAdmin" }, null, 512 /* NEED_PATCH */),
      _createVNode(_component_user_profile, { ref: "userProfile" }, null, 512 /* NEED_PATCH */),
      _createVNode(_component_share_modal, {
        ref: "shareModal",
        onTransfer: openTransferWithFile
      }, null, 8 /* PROPS */, ["onTransfer"]),
      _createVNode(_component_upload_modal, { ref: "uploadModal" }, null, 512 /* NEED_PATCH */),
      _createVNode(_component_file_history_modal, { ref: "fileHistoryModal" }, null, 512 /* NEED_PATCH */),
      _createVNode(_component_transfer_modal, { ref: "transferModal" }, null, 512 /* NEED_PATCH */),
      commandPaletteOpen
        ? (_openBlock(), _createElementBlock("div", {
            key: 0,
            class: "command-palette-backdrop",
            onMousedown: _withModifiers(closeCommandPalette, ["self"])
          }, [
            _createElementVNode("section", {
              class: "command-palette",
              role: "dialog",
              "aria-modal": "true",
              "aria-label": t('command_palette')
            }, [
              _createElementVNode("div", _hoisted_38, [
                _cache[19] || (_cache[19] = _createElementVNode("span", { class: "input-group-text bg-transparent border-0" }, [
                  _createElementVNode("i", {
                    class: "ri-search-line",
                    "aria-hidden": "true"
                  })
                ], -1 /* CACHED */)),
                _withDirectives(_createElementVNode("input", {
                  ref: "commandPaletteInput",
                  id: "commandPaletteSearch",
                  name: "command_palette_search",
                  type: "text",
                  class: "form-control border-0 shadow-none",
                  "onUpdate:modelValue": $event => ((commandPaletteQuery) = $event),
                  autocomplete: "off",
                  placeholder: t('command_palette_placeholder'),
                  "aria-label": t('command_palette'),
                  onKeydown: [
                    _withKeys(_withModifiers($event => (moveCommandSelection(1)), ["prevent"]), ["down"]),
                    _withKeys(_withModifiers($event => (moveCommandSelection(-1)), ["prevent"]), ["up"]),
                    _withKeys(_withModifiers(runSelectedCommand, ["prevent"]), ["enter"]),
                    _withKeys(_withModifiers(closeCommandPalette, ["prevent"]), ["esc"])
                  ]
                }, null, 40 /* PROPS, NEED_HYDRATION */, _hoisted_39), [
                  [_vModelText, commandPaletteQuery]
                ]),
                _createElementVNode("button", {
                  type: "button",
                  class: "btn btn-link text-secondary text-decoration-none",
                  onClick: closeCommandPalette,
                  "aria-label": t('close') || 'Close'
                }, [...(_cache[18] || (_cache[18] = [
                  _createElementVNode("i", {
                    class: "ri-close-line",
                    "aria-hidden": "true"
                  }, null, -1 /* CACHED */)
                ]))], 8 /* PROPS */, _hoisted_40)
              ]),
              _createElementVNode("div", _hoisted_41, [
                (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(filteredCommandActions, (command, index) => {
                  return (_openBlock(), _createElementBlock("button", {
                    key: command.id,
                    type: "button",
                    class: _normalizeClass(["command-palette-item", { active: index === commandPaletteSelectedIndex }]),
                    disabled: !command.enabled,
                    onMouseenter: $event => (commandPaletteSelectedIndex = index),
                    onClick: $event => (runCommand(command))
                  }, [
                    _createElementVNode("span", _hoisted_43, [
                      _createElementVNode("i", {
                        class: _normalizeClass(command.icon),
                        "aria-hidden": "true"
                      }, null, 2 /* CLASS */)
                    ]),
                    _createElementVNode("span", _hoisted_44, [
                      _createElementVNode("span", _hoisted_45, _toDisplayString(command.label), 1 /* TEXT */),
                      (!command.enabled && command.reason)
                        ? (_openBlock(), _createElementBlock("span", _hoisted_46, _toDisplayString(command.reason), 1 /* TEXT */))
                        : _createCommentVNode("v-if", true)
                    ])
                  ], 42 /* CLASS, PROPS, NEED_HYDRATION */, _hoisted_42))
                }), 128 /* KEYED_FRAGMENT */)),
                (filteredCommandActions.length === 0)
                  ? (_openBlock(), _createElementBlock("div", _hoisted_47, _toDisplayString(t('command_no_results')), 1 /* TEXT */))
                  : _createCommentVNode("v-if", true)
              ])
            ], 8 /* PROPS */, _hoisted_37)
          ], 40 /* PROPS, NEED_HYDRATION */, _hoisted_36))
        : _createCommentVNode("v-if", true),
      _createCommentVNode(" Toolbar "),
      _createElementVNode("div", _hoisted_48, [
        (!store.isTrashMode)
          ? (_openBlock(), _createElementBlock(_Fragment, { key: 0 }, [
              _createElementVNode("button", {
                class: "btn btn-primary btn-sm d-none d-sm-inline-flex align-items-center",
                onClick: createFolder,
                title: t('new_folder'),
                "data-testid": "create-folder"
              }, [
                _cache[20] || (_cache[20] = _createElementVNode("i", { class: "ri-folder-add-line" }, null, -1 /* CACHED */)),
                _cache[21] || (_cache[21] = _createTextVNode()),
                _createElementVNode("span", _hoisted_50, _toDisplayString(t('new_folder')), 1 /* TEXT */)
              ], 8 /* PROPS */, _hoisted_49),
              _createElementVNode("button", {
                class: "btn btn-primary btn-sm app-toolbar-primary d-inline-flex align-items-center",
                onClick: uploadFile,
                title: t('upload'),
                "data-testid": "upload"
              }, [
                _cache[22] || (_cache[22] = _createElementVNode("i", { class: "ri-upload-cloud-2-line" }, null, -1 /* CACHED */)),
                _cache[23] || (_cache[23] = _createTextVNode()),
                _createElementVNode("span", null, _toDisplayString(t('upload')), 1 /* TEXT */)
              ], 8 /* PROPS */, _hoisted_51),
              _cache[49] || (_cache[49] = _createElementVNode("div", { class: "vr mx-1 d-none d-sm-block" }, null, -1 /* CACHED */)),
              _createElementVNode("button", {
                class: "btn btn-outline-secondary btn-sm d-none d-sm-inline-flex align-items-center",
                onClick: copySelected,
                disabled: store.selectedItems.length === 0,
                title: t('copy')
              }, [
                _cache[24] || (_cache[24] = _createElementVNode("i", { class: "ri-file-copy-line" }, null, -1 /* CACHED */)),
                _cache[25] || (_cache[25] = _createTextVNode()),
                _createElementVNode("span", _hoisted_53, _toDisplayString(t('copy')), 1 /* TEXT */)
              ], 8 /* PROPS */, _hoisted_52),
              _createElementVNode("button", {
                class: "btn btn-outline-secondary btn-sm d-none d-sm-inline-flex align-items-center",
                onClick: cutSelected,
                disabled: store.selectedItems.length === 0,
                title: t('cut')
              }, [
                _cache[26] || (_cache[26] = _createElementVNode("i", { class: "ri-scissors-cut-line" }, null, -1 /* CACHED */)),
                _cache[27] || (_cache[27] = _createTextVNode()),
                _createElementVNode("span", _hoisted_55, _toDisplayString(t('cut')), 1 /* TEXT */)
              ], 8 /* PROPS */, _hoisted_54),
              _createElementVNode("button", {
                class: "btn btn-outline-success btn-sm d-none d-sm-inline-flex align-items-center",
                onClick: paste,
                disabled: store.clipboard.items.length === 0,
                title: t('paste')
              }, [
                _cache[28] || (_cache[28] = _createElementVNode("i", { class: "ri-clipboard-line" }, null, -1 /* CACHED */)),
                _createElementVNode("span", _hoisted_57, _toDisplayString(t('paste')), 1 /* TEXT */),
                (store.clipboard.items.length > 0)
                  ? (_openBlock(), _createElementBlock("span", _hoisted_58, _toDisplayString(store.clipboard.items.length), 1 /* TEXT */))
                  : _createCommentVNode("v-if", true)
              ], 8 /* PROPS */, _hoisted_56),
              _cache[50] || (_cache[50] = _createElementVNode("div", { class: "vr mx-1 d-none d-sm-block" }, null, -1 /* CACHED */)),
              _createElementVNode("button", {
                class: "btn btn-outline-danger btn-sm d-none d-sm-inline-flex align-items-center",
                onClick: deleteSelected,
                disabled: store.selectedItems.length === 0,
                title: t('delete')
              }, [
                _cache[29] || (_cache[29] = _createElementVNode("i", { class: "ri-delete-bin-line" }, null, -1 /* CACHED */)),
                _cache[30] || (_cache[30] = _createTextVNode()),
                _createElementVNode("span", _hoisted_60, _toDisplayString(t('delete')), 1 /* TEXT */)
              ], 8 /* PROPS */, _hoisted_59),
              _createElementVNode("button", {
                class: _normalizeClass(["btn btn-outline-secondary btn-sm d-inline-flex align-items-center", {active: detailsPaneOpen}]),
                onClick: toggleDetailsPane,
                "aria-pressed": detailsPaneOpen,
                title: t('details_pane')
              }, [
                _cache[31] || (_cache[31] = _createElementVNode("i", {
                  class: "ri-sidebar-right-line",
                  "aria-hidden": "true"
                }, null, -1 /* CACHED */)),
                _cache[32] || (_cache[32] = _createTextVNode()),
                _createElementVNode("span", _hoisted_62, _toDisplayString(t('details')), 1 /* TEXT */)
              ], 10 /* CLASS, PROPS */, _hoisted_61),
              _createCommentVNode(" Overflow Menu "),
              _createElementVNode("div", _hoisted_63, [
                _createElementVNode("button", _hoisted_64, [
                  _cache[33] || (_cache[33] = _createElementVNode("i", {
                    class: "ri-more-2-fill",
                    "aria-hidden": "true"
                  }, null, -1 /* CACHED */)),
                  _createElementVNode("span", _hoisted_65, _toDisplayString(t('more_actions')), 1 /* TEXT */)
                ]),
                _createElementVNode("ul", _hoisted_66, [
                  _createElementVNode("li", _hoisted_67, [
                    _createElementVNode("a", {
                      class: "dropdown-item",
                      href: "#",
                      onClick: _withModifiers(createFolder, ["prevent"])
                    }, [
                      _cache[34] || (_cache[34] = _createElementVNode("i", { class: "ri-folder-add-line me-2" }, null, -1 /* CACHED */)),
                      _createTextVNode(" " + _toDisplayString(t('new_folder')), 1 /* TEXT */)
                    ], 8 /* PROPS */, _hoisted_68)
                  ]),
                  _createElementVNode("li", _hoisted_69, [
                    _createElementVNode("a", {
                      class: _normalizeClass(["dropdown-item", {disabled: store.selectedItems.length === 0}]),
                      href: "#",
                      onClick: _withModifiers(copySelected, ["prevent"])
                    }, [
                      _cache[35] || (_cache[35] = _createElementVNode("i", { class: "ri-file-copy-line me-2" }, null, -1 /* CACHED */)),
                      _createTextVNode(" " + _toDisplayString(t('copy')), 1 /* TEXT */)
                    ], 10 /* CLASS, PROPS */, _hoisted_70)
                  ]),
                  _createElementVNode("li", _hoisted_71, [
                    _createElementVNode("a", {
                      class: _normalizeClass(["dropdown-item", {disabled: store.selectedItems.length === 0}]),
                      href: "#",
                      onClick: _withModifiers(cutSelected, ["prevent"])
                    }, [
                      _cache[36] || (_cache[36] = _createElementVNode("i", { class: "ri-scissors-cut-line me-2" }, null, -1 /* CACHED */)),
                      _createTextVNode(" " + _toDisplayString(t('cut')), 1 /* TEXT */)
                    ], 10 /* CLASS, PROPS */, _hoisted_72)
                  ]),
                  _createElementVNode("li", _hoisted_73, [
                    _createElementVNode("a", {
                      class: _normalizeClass(["dropdown-item", {disabled: store.clipboard.items.length === 0}]),
                      href: "#",
                      onClick: _withModifiers(paste, ["prevent"])
                    }, [
                      _cache[37] || (_cache[37] = _createElementVNode("i", { class: "ri-clipboard-line me-2" }, null, -1 /* CACHED */)),
                      _createTextVNode(" " + _toDisplayString(t('paste')), 1 /* TEXT */)
                    ], 10 /* CLASS, PROPS */, _hoisted_74)
                  ]),
                  _createElementVNode("li", _hoisted_75, [
                    _createElementVNode("a", {
                      class: _normalizeClass(["dropdown-item text-danger", {disabled: store.selectedItems.length === 0}]),
                      href: "#",
                      onClick: _withModifiers(deleteSelected, ["prevent"])
                    }, [
                      _cache[38] || (_cache[38] = _createElementVNode("i", { class: "ri-delete-bin-line me-2" }, null, -1 /* CACHED */)),
                      _createTextVNode(" " + _toDisplayString(t('delete')), 1 /* TEXT */)
                    ], 10 /* CLASS, PROPS */, _hoisted_76)
                  ]),
                  _cache[46] || (_cache[46] = _createElementVNode("li", { class: "d-sm-none" }, [
                    _createElementVNode("hr", { class: "dropdown-divider" })
                  ], -1 /* CACHED */)),
                  _createElementVNode("li", null, [
                    _createElementVNode("a", {
                      class: _normalizeClass(["dropdown-item", {disabled: store.selectedItems.length !== 1}]),
                      href: "#",
                      onClick: _withModifiers(downloadSelected, ["prevent"])
                    }, [
                      _cache[39] || (_cache[39] = _createElementVNode("i", { class: "ri-download-line me-2" }, null, -1 /* CACHED */)),
                      _createTextVNode(" " + _toDisplayString(t('download')), 1 /* TEXT */)
                    ], 10 /* CLASS, PROPS */, _hoisted_77)
                  ]),
                  _createElementVNode("li", null, [
                    _createElementVNode("a", {
                      class: _normalizeClass(["dropdown-item", {disabled: store.selectedItems.length !== 1}]),
                      href: "#",
                      onClick: _withModifiers(renameSelected, ["prevent"])
                    }, [
                      _cache[40] || (_cache[40] = _createElementVNode("i", { class: "ri-edit-line me-2" }, null, -1 /* CACHED */)),
                      _createTextVNode(" " + _toDisplayString(t('rename')), 1 /* TEXT */)
                    ], 10 /* CLASS, PROPS */, _hoisted_78)
                  ]),
                  _createElementVNode("li", null, [
                    _createElementVNode("a", {
                      class: _normalizeClass(["dropdown-item", {disabled: store.selectedItems.length === 0}]),
                      href: "#",
                      onClick: _withModifiers(chmodSelected, ["prevent"])
                    }, [
                      _cache[41] || (_cache[41] = _createElementVNode("i", { class: "ri-lock-2-line me-2" }, null, -1 /* CACHED */)),
                      _createTextVNode(" " + _toDisplayString(t('perms')), 1 /* TEXT */)
                    ], 10 /* CLASS, PROPS */, _hoisted_79)
                  ]),
                  _createElementVNode("li", null, [
                    _createElementVNode("a", {
                      class: _normalizeClass(["dropdown-item", {disabled: store.selectedItems.length === 0}]),
                      href: "#",
                      onClick: _withModifiers(showProperties, ["prevent"])
                    }, [
                      _cache[42] || (_cache[42] = _createElementVNode("i", { class: "ri-information-line me-2" }, null, -1 /* CACHED */)),
                      _createTextVNode(" " + _toDisplayString(t('properties')), 1 /* TEXT */)
                    ], 10 /* CLASS, PROPS */, _hoisted_80)
                  ]),
                  _cache[47] || (_cache[47] = _createElementVNode("li", null, [
                    _createElementVNode("hr", { class: "dropdown-divider" })
                  ], -1 /* CACHED */)),
                  _createElementVNode("li", null, [
                    _createElementVNode("a", {
                      class: _normalizeClass(["dropdown-item", {disabled: store.selectedItems.length !== 2}]),
                      href: "#",
                      onClick: _withModifiers(diffSelected, ["prevent"])
                    }, [...(_cache[43] || (_cache[43] = [
                      _createElementVNode("i", { class: "ri-git-merge-line me-2" }, null, -1 /* CACHED */),
                      _createTextVNode(" Diff ", -1 /* CACHED */)
                    ]))], 10 /* CLASS, PROPS */, _hoisted_81)
                  ]),
                  _createElementVNode("li", null, [
                    _createElementVNode("a", {
                      class: _normalizeClass(["dropdown-item", {disabled: store.selectedItems.length === 0}]),
                      href: "#",
                      onClick: _withModifiers(createArchive, ["prevent"])
                    }, [
                      _cache[44] || (_cache[44] = _createElementVNode("i", { class: "ri-file-zip-line me-2" }, null, -1 /* CACHED */)),
                      _createTextVNode(" " + _toDisplayString(t('archive')), 1 /* TEXT */)
                    ], 10 /* CLASS, PROPS */, _hoisted_82)
                  ]),
                  _createElementVNode("li", null, [
                    _createElementVNode("a", {
                      class: _normalizeClass(["dropdown-item", {disabled: store.selectedItems.length !== 1 || !isArchive(store.selectedItems[0])}]),
                      href: "#",
                      onClick: _withModifiers(extractArchive, ["prevent"])
                    }, [
                      _cache[45] || (_cache[45] = _createElementVNode("i", { class: "ri-folder-zip-line me-2" }, null, -1 /* CACHED */)),
                      _createTextVNode(" " + _toDisplayString(t('extract')), 1 /* TEXT */)
                    ], 10 /* CLASS, PROPS */, _hoisted_83)
                  ]),
                  _cache[48] || (_cache[48] = _createElementVNode("li", null, [
                    _createElementVNode("hr", { class: "dropdown-divider" })
                  ], -1 /* CACHED */)),
                  _createElementVNode("li", null, [
                    _createElementVNode("a", {
                      class: "dropdown-item",
                      href: "#",
                      onClick: _withModifiers($event => (store.toggleHidden()), ["prevent"])
                    }, [
                      _createElementVNode("i", {
                        class: _normalizeClass(["me-2", store.showHidden ? 'ri-eye-line' : 'ri-eye-off-line'])
                      }, null, 2 /* CLASS */),
                      _createTextVNode(" " + _toDisplayString(t('show_hidden')), 1 /* TEXT */)
                    ], 8 /* PROPS */, _hoisted_84)
                  ])
                ])
              ])
            ], 64 /* STABLE_FRAGMENT */))
          : (_openBlock(), _createElementBlock(_Fragment, { key: 1 }, [
              _createElementVNode("div", _hoisted_85, [
                _cache[51] || (_cache[51] = _createElementVNode("i", { class: "ri-delete-bin-line me-2" }, null, -1 /* CACHED */)),
                _createTextVNode(" " + _toDisplayString(t('trash') || 'Recycle Bin'), 1 /* TEXT */)
              ]),
              _createElementVNode("button", {
                class: "btn btn-success btn-sm me-2",
                onClick: restoreSelected,
                disabled: store.selectedItems.length === 0,
                "data-testid": "trash-restore"
              }, [
                _cache[52] || (_cache[52] = _createElementVNode("i", { class: "ri-restart-line" }, null, -1 /* CACHED */)),
                _createTextVNode(" " + _toDisplayString(t('restore') || 'Restore'), 1 /* TEXT */)
              ], 8 /* PROPS */, _hoisted_86),
              _createElementVNode("button", {
                class: "btn btn-outline-danger btn-sm",
                onClick: emptyTrash,
                "data-testid": "empty-trash"
              }, [
                _cache[53] || (_cache[53] = _createElementVNode("i", { class: "ri-delete-bin-2-line" }, null, -1 /* CACHED */)),
                _createTextVNode(" " + _toDisplayString(t('empty_trash') || 'Empty Trash'), 1 /* TEXT */)
              ], 8 /* PROPS */, _hoisted_87)
            ], 64 /* STABLE_FRAGMENT */))
      ]),
      (store.selectedItems.length > 0)
        ? (_openBlock(), _createElementBlock("div", {
            key: 1,
            class: "selection-action-bar",
            role: "region",
            "aria-label": t('selection_bar_label')
          }, [
            _createElementVNode("div", _hoisted_89, [
              _cache[54] || (_cache[54] = _createElementVNode("i", {
                class: "ri-checkbox-multiple-line",
                "aria-hidden": "true"
              }, null, -1 /* CACHED */)),
              _createElementVNode("span", null, _toDisplayString(t('selected_count', {count: store.selectedItems.length})), 1 /* TEXT */)
            ]),
            _createElementVNode("div", _hoisted_90, [
              _createElementVNode("button", {
                type: "button",
                class: "btn btn-outline-secondary btn-sm",
                onClick: $event => (store.clearSelection()),
                "aria-label": t('clear_selection')
              }, [
                _cache[55] || (_cache[55] = _createElementVNode("i", {
                  class: "ri-close-line",
                  "aria-hidden": "true"
                }, null, -1 /* CACHED */)),
                _createElementVNode("span", null, _toDisplayString(t('clear_selection')), 1 /* TEXT */)
              ], 8 /* PROPS */, _hoisted_91),
              (store.isTrashMode)
                ? (_openBlock(), _createElementBlock(_Fragment, { key: 0 }, [
                    _createElementVNode("button", {
                      type: "button",
                      class: "btn btn-success btn-sm",
                      onClick: restoreSelected,
                      "data-testid": "selection-restore"
                    }, [
                      _cache[56] || (_cache[56] = _createElementVNode("i", {
                        class: "ri-restart-line",
                        "aria-hidden": "true"
                      }, null, -1 /* CACHED */)),
                      _createElementVNode("span", null, _toDisplayString(t('restore')), 1 /* TEXT */)
                    ], 8 /* PROPS */, _hoisted_92),
                    _createElementVNode("button", {
                      type: "button",
                      class: "btn btn-outline-danger btn-sm",
                      onClick: deletePermanent
                    }, [
                      _cache[57] || (_cache[57] = _createElementVNode("i", {
                        class: "ri-delete-bin-2-line",
                        "aria-hidden": "true"
                      }, null, -1 /* CACHED */)),
                      _createElementVNode("span", null, _toDisplayString(t('delete_perm')), 1 /* TEXT */)
                    ], 8 /* PROPS */, _hoisted_93)
                  ], 64 /* STABLE_FRAGMENT */))
                : (_openBlock(), _createElementBlock(_Fragment, { key: 1 }, [
                    (store.selectedItems.length === 1)
                      ? (_openBlock(), _createElementBlock("button", {
                          key: 0,
                          type: "button",
                          class: "btn btn-primary btn-sm",
                          onClick: downloadSelected,
                          "data-testid": "selection-download"
                        }, [
                          _cache[58] || (_cache[58] = _createElementVNode("i", {
                            class: "ri-download-line",
                            "aria-hidden": "true"
                          }, null, -1 /* CACHED */)),
                          _createElementVNode("span", null, _toDisplayString(t('download')), 1 /* TEXT */)
                        ], 8 /* PROPS */, _hoisted_94))
                      : _createCommentVNode("v-if", true),
                    _createElementVNode("button", {
                      type: "button",
                      class: "btn btn-outline-secondary btn-sm",
                      onClick: copySelected
                    }, [
                      _cache[59] || (_cache[59] = _createElementVNode("i", {
                        class: "ri-file-copy-line",
                        "aria-hidden": "true"
                      }, null, -1 /* CACHED */)),
                      _createElementVNode("span", null, _toDisplayString(t('copy')), 1 /* TEXT */)
                    ], 8 /* PROPS */, _hoisted_95),
                    _createElementVNode("button", {
                      type: "button",
                      class: "btn btn-outline-secondary btn-sm",
                      onClick: cutSelected
                    }, [
                      _cache[60] || (_cache[60] = _createElementVNode("i", {
                        class: "ri-scissors-cut-line",
                        "aria-hidden": "true"
                      }, null, -1 /* CACHED */)),
                      _createElementVNode("span", null, _toDisplayString(t('cut')), 1 /* TEXT */)
                    ], 8 /* PROPS */, _hoisted_96),
                    _createElementVNode("button", {
                      type: "button",
                      class: "btn btn-outline-danger btn-sm",
                      onClick: deleteSelected,
                      "data-testid": "selection-delete"
                    }, [
                      _cache[61] || (_cache[61] = _createElementVNode("i", {
                        class: "ri-delete-bin-line",
                        "aria-hidden": "true"
                      }, null, -1 /* CACHED */)),
                      _createElementVNode("span", null, _toDisplayString(t('delete')), 1 /* TEXT */)
                    ], 8 /* PROPS */, _hoisted_97),
                    _createElementVNode("div", _hoisted_98, [
                      _createElementVNode("button", {
                        class: "btn btn-outline-secondary btn-sm dropdown-toggle",
                        type: "button",
                        "data-bs-toggle": "dropdown",
                        "data-testid": "selection-more",
                        "aria-label": t('selection_more_actions')
                      }, [
                        _cache[62] || (_cache[62] = _createElementVNode("i", {
                          class: "ri-more-2-fill",
                          "aria-hidden": "true"
                        }, null, -1 /* CACHED */)),
                        _createElementVNode("span", null, _toDisplayString(t('selection_more_actions')), 1 /* TEXT */)
                      ], 8 /* PROPS */, _hoisted_99),
                      _createElementVNode("ul", _hoisted_100, [
                        (store.selectedItems.length === 1)
                          ? (_openBlock(), _createElementBlock("li", _hoisted_101, [
                              _createElementVNode("a", {
                                class: "dropdown-item",
                                href: "#",
                                onClick: _withModifiers(renameSelected, ["prevent"]),
                                "data-testid": "selection-rename"
                              }, [
                                _cache[63] || (_cache[63] = _createElementVNode("i", {
                                  class: "ri-edit-line me-2",
                                  "aria-hidden": "true"
                                }, null, -1 /* CACHED */)),
                                _createTextVNode(_toDisplayString(t('rename')), 1 /* TEXT */)
                              ], 8 /* PROPS */, _hoisted_102)
                            ]))
                          : _createCommentVNode("v-if", true),
                        (store.selectedItems.length === 1)
                          ? (_openBlock(), _createElementBlock("li", _hoisted_103, [
                              _createElementVNode("a", {
                                class: "dropdown-item",
                                href: "#",
                                onClick: _withModifiers(openShare, ["prevent"])
                              }, [
                                _cache[64] || (_cache[64] = _createElementVNode("i", {
                                  class: "ri-share-line me-2",
                                  "aria-hidden": "true"
                                }, null, -1 /* CACHED */)),
                                _createTextVNode(_toDisplayString(t('share_title') || 'Share'), 1 /* TEXT */)
                              ], 8 /* PROPS */, _hoisted_104)
                            ]))
                          : _createCommentVNode("v-if", true),
                        _createElementVNode("li", null, [
                          _createElementVNode("a", {
                            class: "dropdown-item",
                            href: "#",
                            onClick: _withModifiers(chmodSelected, ["prevent"])
                          }, [
                            _cache[65] || (_cache[65] = _createElementVNode("i", {
                              class: "ri-lock-2-line me-2",
                              "aria-hidden": "true"
                            }, null, -1 /* CACHED */)),
                            _createTextVNode(_toDisplayString(t('perms')), 1 /* TEXT */)
                          ], 8 /* PROPS */, _hoisted_105)
                        ]),
                        _createElementVNode("li", null, [
                          _createElementVNode("a", {
                            class: "dropdown-item",
                            href: "#",
                            onClick: _withModifiers(showProperties, ["prevent"])
                          }, [
                            _cache[66] || (_cache[66] = _createElementVNode("i", {
                              class: "ri-information-line me-2",
                              "aria-hidden": "true"
                            }, null, -1 /* CACHED */)),
                            _createTextVNode(_toDisplayString(t('properties')), 1 /* TEXT */)
                          ], 8 /* PROPS */, _hoisted_106)
                        ]),
                        _createElementVNode("li", null, [
                          _createElementVNode("a", {
                            class: "dropdown-item",
                            href: "#",
                            onClick: _withModifiers(createArchive, ["prevent"])
                          }, [
                            _cache[67] || (_cache[67] = _createElementVNode("i", {
                              class: "ri-file-zip-line me-2",
                              "aria-hidden": "true"
                            }, null, -1 /* CACHED */)),
                            _createTextVNode(_toDisplayString(t('archive')), 1 /* TEXT */)
                          ], 8 /* PROPS */, _hoisted_107)
                        ]),
                        (store.selectedItems.length === 1 && isArchive(store.selectedItems[0]))
                          ? (_openBlock(), _createElementBlock("li", _hoisted_108, [
                              _createElementVNode("a", {
                                class: "dropdown-item",
                                href: "#",
                                onClick: _withModifiers(extractArchive, ["prevent"])
                              }, [
                                _cache[68] || (_cache[68] = _createElementVNode("i", {
                                  class: "ri-folder-zip-line me-2",
                                  "aria-hidden": "true"
                                }, null, -1 /* CACHED */)),
                                _createTextVNode(_toDisplayString(t('extract')), 1 /* TEXT */)
                              ], 8 /* PROPS */, _hoisted_109)
                            ]))
                          : _createCommentVNode("v-if", true),
                        (store.selectedItems.length === 1 && store.selectedItems[0].type !== 'dir')
                          ? (_openBlock(), _createElementBlock("li", _hoisted_110, [
                              _createElementVNode("a", {
                                class: "dropdown-item",
                                href: "#",
                                onClick: _withModifiers(openHistory, ["prevent"])
                              }, [
                                _cache[69] || (_cache[69] = _createElementVNode("i", {
                                  class: "ri-history-line me-2",
                                  "aria-hidden": "true"
                                }, null, -1 /* CACHED */)),
                                _createTextVNode(_toDisplayString(t('version_history')), 1 /* TEXT */)
                              ], 8 /* PROPS */, _hoisted_111)
                            ]))
                          : _createCommentVNode("v-if", true),
                        (store.selectedItems.length === 2)
                          ? (_openBlock(), _createElementBlock("li", _hoisted_112, [
                              _createElementVNode("a", {
                                class: "dropdown-item",
                                href: "#",
                                onClick: _withModifiers(diffSelected, ["prevent"])
                              }, [...(_cache[70] || (_cache[70] = [
                                _createElementVNode("i", {
                                  class: "ri-git-merge-line me-2",
                                  "aria-hidden": "true"
                                }, null, -1 /* CACHED */),
                                _createTextVNode("Diff ", -1 /* CACHED */)
                              ]))], 8 /* PROPS */, _hoisted_113)
                            ]))
                          : _createCommentVNode("v-if", true)
                      ])
                    ])
                  ], 64 /* STABLE_FRAGMENT */))
            ])
          ], 8 /* PROPS */, _hoisted_88))
        : _createCommentVNode("v-if", true),
      _createCommentVNode(" Main "),
      _createElementVNode("div", {
        class: _normalizeClass(["main-container", {'drag-over': store.isDraggingOver}]),
        onClick: $event => {store.clearSelection(); hideContextMenu()},
        onContextmenu: _withModifiers($event => (hideContextMenu()), ["prevent"]),
        onDragover: _withModifiers($event => (onDragOver($event, null)), ["prevent"]),
        onDragleave: $event => (onDragLeave(null)),
        onDrop: _withModifiers($event => (onDrop($event, null)), ["prevent"])
      }, [
        (dragIndicatorMode === 'copy')
          ? (_openBlock(), _createElementBlock("div", _hoisted_115, [
              _cache[71] || (_cache[71] = _createElementVNode("span", { class: "drag-operation-indicator-icon" }, [
                _createElementVNode("i", { class: "ri-file-copy-line" })
              ], -1 /* CACHED */)),
              _createElementVNode("span", null, _toDisplayString(t('copy')), 1 /* TEXT */)
            ]))
          : _createCommentVNode("v-if", true),
        _createCommentVNode(" Sidebar "),
        _createElementVNode("div", _hoisted_116, [
          _createElementVNode("div", _hoisted_117, [
            _cache[72] || (_cache[72] = _createElementVNode("h5", { class: "offcanvas-title" }, "Menu", -1 /* CACHED */)),
            _createElementVNode("button", {
              type: "button",
              class: "btn-close",
              "data-bs-dismiss": "offcanvas",
              "data-bs-target": "#sidebarOffcanvas",
              "aria-label": t('close') || 'Close'
            }, null, 8 /* PROPS */, _hoisted_118)
          ]),
          _createElementVNode("div", _hoisted_119, [
            _createCommentVNode(" Connect "),
            _createElementVNode("div", _hoisted_120, [
              _createElementVNode("button", {
                class: "btn btn-primary btn-sm flex-fill",
                onClick: openTransfer
              }, [
                _cache[73] || (_cache[73] = _createElementVNode("i", { class: "ri-send-plane-fill me-1" }, null, -1 /* CACHED */)),
                _createTextVNode(" " + _toDisplayString(t('send_files') || 'Send Files'), 1 /* TEXT */)
              ], 8 /* PROPS */, _hoisted_121),
              webdavEnabled
                ? (_openBlock(), _createElementBlock("button", {
                    key: 0,
                    class: "btn btn-outline-primary btn-sm",
                    onClick: showWebDav,
                    title: "WebDAV Connect",
                    "aria-label": "WebDAV Connect"
                  }, [...(_cache[74] || (_cache[74] = [
                    _createElementVNode("i", {
                      class: "ri-link",
                      "aria-hidden": "true"
                    }, null, -1 /* CACHED */)
                  ]))], 8 /* PROPS */, _hoisted_122))
                : _createCommentVNode("v-if", true)
            ]),
            _createCommentVNode(" Bookmarks "),
            _createElementVNode("div", _hoisted_123, [
              _createElementVNode("div", _hoisted_124, [
                _createElementVNode("h6", _hoisted_125, _toDisplayString(t('bookmarks')), 1 /* TEXT */),
                _createElementVNode("button", {
                  class: "btn btn-link btn-sm p-0 text-decoration-none",
                  onClick: $event => (toggleBookmark(currentFolderBookmark)),
                  "aria-label": t('bookmark_current_folder'),
                  title: t('bookmark_current_folder')
                }, [
                  _createElementVNode("i", {
                    class: _normalizeClass(store.isBookmarked(currentFolderBookmark) ? 'ri-star-fill' : 'ri-star-line'),
                    "aria-hidden": "true"
                  }, null, 2 /* CLASS */)
                ], 8 /* PROPS */, _hoisted_126)
              ]),
              (store.bookmarks.length === 0)
                ? (_openBlock(), _createElementBlock("div", _hoisted_127, _toDisplayString(t('bookmarks_empty')), 1 /* TEXT */))
                : _createCommentVNode("v-if", true),
              (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(store.bookmarks, (bookmark) => {
                return (_openBlock(), _createElementBlock("div", {
                  key: bookmark.path,
                  class: "d-flex align-items-center py-1 px-2 rounded small file-item",
                  "data-bs-dismiss": "offcanvas",
                  "data-bs-target": "#sidebarOffcanvas"
                }, [
                  _createElementVNode("i", {
                    class: _normalizeClass([getIcon(bookmark), "me-2"])
                  }, null, 2 /* CLASS */),
                  _createElementVNode("span", {
                    class: "text-truncate flex-grow-1",
                    onClick: $event => (open(bookmark))
                  }, _toDisplayString(bookmark.name), 9 /* TEXT, PROPS */, _hoisted_128),
                  _createElementVNode("button", {
                    class: "btn btn-link btn-sm p-0 text-muted bookmark-item-action",
                    onClick: _withModifiers($event => (store.removeBookmark(bookmark.path)), ["stop"]),
                    "aria-label": t('remove_bookmark') + ': ' + bookmark.name,
                    title: t('remove_bookmark')
                  }, [...(_cache[75] || (_cache[75] = [
                    _createElementVNode("i", {
                      class: "ri-close-line",
                      "aria-hidden": "true"
                    }, null, -1 /* CACHED */)
                  ]))], 8 /* PROPS */, _hoisted_129)
                ]))
              }), 128 /* KEYED_FRAGMENT */))
            ]),
            _createCommentVNode(" Recent Files "),
            (store.recentFiles.length > 0)
              ? (_openBlock(), _createElementBlock("div", _hoisted_130, [
                  _createElementVNode("div", _hoisted_131, [
                    _createElementVNode("h6", _hoisted_132, _toDisplayString(t('recent_files')), 1 /* TEXT */),
                    _createElementVNode("button", {
                      class: "btn btn-link btn-sm p-0 text-decoration-none",
                      onClick: $event => (store.clearRecent()),
                      "aria-label": "Clear recent files",
                      title: "Clear recent files"
                    }, [...(_cache[76] || (_cache[76] = [
                      _createElementVNode("i", {
                        class: "ri-delete-bin-7-line",
                        "aria-hidden": "true"
                      }, null, -1 /* CACHED */)
                    ]))], 8 /* PROPS */, _hoisted_133)
                  ]),
                  (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(store.recentFiles, (file) => {
                    return (_openBlock(), _createElementBlock("div", {
                      key: file.path,
                      class: "d-flex align-items-center py-1 px-2 rounded small file-item",
                      onClick: $event => (open(file)),
                      "data-bs-dismiss": "offcanvas",
                      "data-bs-target": "#sidebarOffcanvas"
                    }, [
                      _createElementVNode("i", {
                        class: _normalizeClass([getIcon(file), "me-2"])
                      }, null, 2 /* CLASS */),
                      _createElementVNode("span", _hoisted_135, _toDisplayString(file.name), 1 /* TEXT */)
                    ], 8 /* PROPS */, _hoisted_134))
                  }), 128 /* KEYED_FRAGMENT */))
                ]))
              : _createCommentVNode("v-if", true),
            _cache[78] || (_cache[78] = _createElementVNode("h6", { class: "small fw-bold text-uppercase text-muted mb-2 px-2" }, "Explorer", -1 /* CACHED */)),
            _createVNode(_component_file_tree, {
              path: "",
              name: "Root",
              root: true,
              onClick: $event => (isMobile ? closeOffcanvas() : null)
            }, null, 8 /* PROPS */, ["root", "onClick"]),
            _createElementVNode("div", _hoisted_136, [
              _createElementVNode("div", {
                class: _normalizeClass(["d-flex align-items-center py-1 px-2 rounded small file-item", {'bg-danger-subtle text-danger': store.isTrashMode}]),
                onClick: toggleTrash,
                "data-testid": "trash-toggle",
                "data-bs-dismiss": "offcanvas",
                "data-bs-target": "#sidebarOffcanvas"
              }, [
                _cache[77] || (_cache[77] = _createElementVNode("i", { class: "ri-delete-bin-line me-2" }, null, -1 /* CACHED */)),
                _createElementVNode("span", null, _toDisplayString(t('trash') || 'Recycle Bin'), 1 /* TEXT */)
              ], 10 /* CLASS, PROPS */, _hoisted_137)
            ])
          ])
        ]),
        _createCommentVNode(" Content "),
        _createElementVNode("div", {
          id: "contentArea",
          class: "content-area position-relative",
          onClick: _withModifiers($event => {store.clearSelection(); hideContextMenu()}, ["stop"])
        }, [
          (store.uploadProgress > 0)
            ? (_openBlock(), _createElementBlock("div", _hoisted_139, [
                _createElementVNode("div", _hoisted_140, [
                  _createElementVNode("span", null, [
                    _cache[79] || (_cache[79] = _createTextVNode("Uploading: ", -1 /* CACHED */)),
                    _createElementVNode("strong", null, _toDisplayString(store.uploadFileName), 1 /* TEXT */)
                  ]),
                  (store.uploadTotal > 1)
                    ? (_openBlock(), _createElementBlock("span", _hoisted_141, _toDisplayString(store.uploadCurrent + 1) + " / " + _toDisplayString(store.uploadTotal), 1 /* TEXT */))
                    : _createCommentVNode("v-if", true)
                ]),
                _createElementVNode("div", _hoisted_142, [
                  _createElementVNode("div", {
                    class: _normalizeClass(["progress-bar progress-bar-striped progress-bar-animated", 'progress-w-' + Math.round(store.uploadProgress / 5) * 5]),
                    role: "progressbar"
                  }, null, 2 /* CLASS */)
                ])
              ]))
            : _createCommentVNode("v-if", true),
          (store.isLoading && store.uploadProgress === 0)
            ? (_openBlock(), _createElementBlock("div", _hoisted_143, [...(_cache[80] || (_cache[80] = [
                _createElementVNode("div", {
                  class: "spinner-border text-primary",
                  role: "status"
                }, null, -1 /* CACHED */)
              ]))]))
            : (store.error)
              ? (_openBlock(), _createElementBlock("div", _hoisted_144, [
                  _cache[81] || (_cache[81] = _createElementVNode("i", { class: "ri-error-warning-line me-2" }, null, -1 /* CACHED */)),
                  _createTextVNode(" " + _toDisplayString(store.error), 1 /* TEXT */)
                ]))
              : (_openBlock(), _createElementBlock("div", _hoisted_145, [
                  (filteredFiles.length === 0)
                    ? (_openBlock(), _createElementBlock("div", _hoisted_146, [
                        _createCommentVNode(" Icon with background circle "),
                        _createElementVNode("div", _hoisted_147, [
                          _createElementVNode("i", {
                            class: _normalizeClass([emptyStateIcon, "empty-state-icon-glyph"])
                          }, null, 2 /* CLASS */)
                        ]),
                        _createCommentVNode(" Title "),
                        _createElementVNode("h4", _hoisted_148, _toDisplayString(emptyStateTitle), 1 /* TEXT */),
                        _createCommentVNode(" Subtitle/Description "),
                        _createElementVNode("p", _hoisted_149, _toDisplayString(emptyStateDescription), 1 /* TEXT */),
                        _createCommentVNode(" CTAs (only for normal directory) "),
                        (!store.isTrashMode && !store.searchQuery)
                          ? (_openBlock(), _createElementBlock("div", _hoisted_150, [
                              _createElementVNode("button", {
                                class: "btn btn-primary btn-sm px-3",
                                onClick: createFolder
                              }, [
                                _cache[82] || (_cache[82] = _createElementVNode("i", { class: "ri-folder-add-line me-1" }, null, -1 /* CACHED */)),
                                _createTextVNode(" " + _toDisplayString(t('new_folder')), 1 /* TEXT */)
                              ], 8 /* PROPS */, _hoisted_151),
                              _createElementVNode("button", {
                                class: "btn btn-outline-secondary btn-sm px-3",
                                onClick: uploadFile
                              }, [
                                _cache[83] || (_cache[83] = _createElementVNode("i", { class: "ri-upload-cloud-2-line me-1" }, null, -1 /* CACHED */)),
                                _createTextVNode(" " + _toDisplayString(t('upload')), 1 /* TEXT */)
                              ], 8 /* PROPS */, _hoisted_152)
                            ]))
                          : _createCommentVNode("v-if", true),
                        _createCommentVNode(" CTA for Search (Clear) "),
                        (store.searchQuery)
                          ? (_openBlock(), _createElementBlock("button", {
                              key: 1,
                              class: "btn btn-outline-primary btn-sm px-3",
                              onClick: $event => {store.searchQuery = ''; store.performSearch('')}
                            }, [...(_cache[84] || (_cache[84] = [
                              _createElementVNode("i", { class: "ri-close-circle-line me-1" }, null, -1 /* CACHED */),
                              _createTextVNode(" Clear Search ", -1 /* CACHED */)
                            ]))], 8 /* PROPS */, _hoisted_153))
                          : _createCommentVNode("v-if", true)
                      ]))
                    : (_openBlock(), _createElementBlock("div", {
                        key: 1,
                        class: _normalizeClass([containerClass, "position-relative"])
                      }, [
                        (store.cwd)
                          ? (_openBlock(), _createElementBlock("button", {
                              key: 0,
                              class: "btn btn-light btn-sm position-absolute top-0 end-0 m-2 shadow-sm",
                              onClick: goUp,
                              title: t('up_one_level') || 'Up one level',
                              "aria-label": t('up_one_level') || 'Up one level'
                            }, [...(_cache[85] || (_cache[85] = [
                              _createElementVNode("i", {
                                class: "ri-arrow-up-line",
                                "aria-hidden": "true"
                              }, null, -1 /* CACHED */)
                            ]))], 8 /* PROPS */, _hoisted_154))
                          : _createCommentVNode("v-if", true),
                        _createCommentVNode(" Header for List View "),
                        (store.viewMode === 'list')
                          ? (_openBlock(), _createElementBlock("div", _hoisted_155, [
                              _cache[86] || (_cache[86] = _createElementVNode("div", { class: "list-view-header-spacer" }, null, -1 /* CACHED */)),
                              _createElementVNode("div", {
                                class: "list-view-name-col cursor-pointer",
                                onClick: $event => (setSort('name'))
                              }, [
                                _createTextVNode(_toDisplayString(t('name')) + " ", 1 /* TEXT */),
                                (store.sortBy === 'name')
                                  ? (_openBlock(), _createElementBlock("i", {
                                      key: 0,
                                      class: _normalizeClass(store.sortDesc ? 'ri-arrow-down-s-fill' : 'ri-arrow-up-s-fill')
                                    }, null, 2 /* CLASS */))
                                  : _createCommentVNode("v-if", true)
                              ], 8 /* PROPS */, _hoisted_156),
                              _createElementVNode("div", {
                                class: "file-meta-col size-col cursor-pointer",
                                onClick: $event => (setSort('size'))
                              }, [
                                _createTextVNode(_toDisplayString(t('size')) + " ", 1 /* TEXT */),
                                (store.sortBy === 'size')
                                  ? (_openBlock(), _createElementBlock("i", {
                                      key: 0,
                                      class: _normalizeClass(store.sortDesc ? 'ri-arrow-down-s-fill' : 'ri-arrow-up-s-fill')
                                    }, null, 2 /* CLASS */))
                                  : _createCommentVNode("v-if", true)
                              ], 8 /* PROPS */, _hoisted_157),
                              _createElementVNode("div", {
                                class: "file-meta-col date-col cursor-pointer",
                                onClick: $event => (setSort('mtime'))
                              }, [
                                _createTextVNode(_toDisplayString(t('date')) + " ", 1 /* TEXT */),
                                (store.sortBy === 'mtime')
                                  ? (_openBlock(), _createElementBlock("i", {
                                      key: 0,
                                      class: _normalizeClass(store.sortDesc ? 'ri-arrow-down-s-fill' : 'ri-arrow-up-s-fill')
                                    }, null, 2 /* CLASS */))
                                  : _createCommentVNode("v-if", true)
                              ], 8 /* PROPS */, _hoisted_158)
                            ]))
                          : _createCommentVNode("v-if", true),
                        _createCommentVNode(" File Loop "),
                        (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(filteredFiles, (file) => {
                          return (_openBlock(), _createElementBlock("div", {
                            key: file.name,
                            class: _normalizeClass(["file-item", {'selected': store.isSelected(file), 'drag-over': file.isDragOver}]),
                            "data-testid": "file-item",
                            "data-file-name": file.name,
                            "data-file-path": file.path || file.originalPath,
                            draggable: "true",
                            onDragstart: $event => (onDragStart($event, file)),
                            onDragend: onDragEnd,
                            onDragover: _withModifiers($event => (file.type === 'dir' ? onDragOver($event, file) : null), ["prevent"]),
                            onDragleave: $event => (file.type === 'dir' ? onDragLeave(file) : null),
                            onDrop: _withModifiers($event => (file.type === 'dir' ? onDrop($event, file) : null), ["prevent"]),
                            onClick: _withModifiers($event => (handleItemClick($event, file)), ["stop"]),
                            onTouchstart: $event => (handleTouchStart($event, file)),
                            onTouchend: handleTouchEnd,
                            onDblclick: _withModifiers($event => (open(file)), ["stop"]),
                            onContextmenu: _withModifiers($event => (showContextMenu($event, file)), ["prevent","stop"])
                          }, [
                            _createElementVNode("div", _hoisted_160, [
                              (store.viewMode === 'grid' && isImage(file))
                                ? (_openBlock(), _createElementBlock("img", {
                                    key: 0,
                                    src: getThumbUrl(file),
                                    class: "rounded shadow-sm thumb-grid",
                                    loading: "lazy",
                                    decoding: "async",
                                    draggable: "false"
                                  }, null, 8 /* PROPS */, _hoisted_161))
                                : (_openBlock(), _createElementBlock("i", {
                                    key: 1,
                                    class: _normalizeClass(getIcon(file))
                                  }, null, 2 /* CLASS */)),
                              (file.is_shared)
                                ? (_openBlock(), _createElementBlock("div", _hoisted_162, [...(_cache[87] || (_cache[87] = [
                                    _createElementVNode("i", { class: "ri-share-forward-line text-primary shared-badge-icon" }, null, -1 /* CACHED */)
                                  ]))]))
                                : _createCommentVNode("v-if", true)
                            ]),
                            _createElementVNode("div", {
                              class: "file-name",
                              title: file.name
                            }, [
                              _createTextVNode(_toDisplayString(file.name) + " ", 1 /* TEXT */),
                              (file.is_mount && file.is_external)
                                ? (_openBlock(), _createElementBlock("span", _hoisted_164, "Mount"))
                                : _createCommentVNode("v-if", true),
                              (file.type === 'dir')
                                ? (_openBlock(), _createElementBlock("i", {
                                    key: 1,
                                    class: "ri-arrow-right-line d-md-none ms-2 text-muted",
                                    onClick: _withModifiers($event => (open(file)), ["stop"])
                                  }, null, 8 /* PROPS */, _hoisted_165))
                                : _createCommentVNode("v-if", true)
                            ], 8 /* PROPS */, _hoisted_163),
                            _createCommentVNode(" List View Meta "),
                            (store.viewMode === 'list')
                              ? (_openBlock(), _createElementBlock(_Fragment, { key: 0 }, [
                                  _createElementVNode("div", _hoisted_166, _toDisplayString(formatSize(file.size)), 1 /* TEXT */),
                                  _createElementVNode("div", _hoisted_167, _toDisplayString(formatDate(file.mtime)), 1 /* TEXT */)
                                ], 64 /* STABLE_FRAGMENT */))
                              : _createCommentVNode("v-if", true)
                          ], 42 /* CLASS, PROPS, NEED_HYDRATION */, _hoisted_159))
                        }), 128 /* KEYED_FRAGMENT */))
                      ], 2 /* CLASS */))
                ]))
        ], 8 /* PROPS */, _hoisted_138),
        detailsPaneOpen
          ? (_openBlock(), _createElementBlock("aside", {
              key: 1,
              class: "details-pane p-3",
              "aria-labelledby": "detailsPaneTitle",
              onClick: _withModifiers(() => {}, ["stop"])
            }, [
              _createElementVNode("div", _hoisted_169, [
                _createElementVNode("h6", _hoisted_170, _toDisplayString(t('details_pane')), 1 /* TEXT */),
                _createElementVNode("button", {
                  class: "btn btn-link btn-sm text-muted p-0",
                  onClick: closeDetailsPane,
                  "aria-label": t('close') || 'Close'
                }, [...(_cache[88] || (_cache[88] = [
                  _createElementVNode("i", {
                    class: "ri-close-line",
                    "aria-hidden": "true"
                  }, null, -1 /* CACHED */)
                ]))], 8 /* PROPS */, _hoisted_171)
              ]),
              (!detailsItem)
                ? (_openBlock(), _createElementBlock("div", _hoisted_172, [
                    _cache[89] || (_cache[89] = _createElementVNode("i", {
                      class: "ri-information-line d-block fs-2 mb-2",
                      "aria-hidden": "true"
                    }, null, -1 /* CACHED */)),
                    _createTextVNode(" " + _toDisplayString(t('details_empty')), 1 /* TEXT */)
                  ]))
                : (_openBlock(), _createElementBlock("div", _hoisted_173, [
                    _createElementVNode("div", _hoisted_174, [
                      (!detailsItem.isBulk && isImage(detailsItem))
                        ? (_openBlock(), _createElementBlock("img", {
                            key: 0,
                            src: getThumbUrl(detailsItem),
                            class: "w-100 h-100 object-fit-cover",
                            alt: detailsItem.name
                          }, null, 8 /* PROPS */, _hoisted_175))
                        : (_openBlock(), _createElementBlock("i", {
                            key: 1,
                            class: _normalizeClass([getIcon(detailsItem), "details-preview-icon"]),
                            "aria-hidden": "true"
                          }, null, 2 /* CLASS */))
                    ]),
                    _createElementVNode("div", _hoisted_176, [
                      _createElementVNode("div", _hoisted_177, [
                        _createElementVNode("div", {
                          class: "fw-semibold text-truncate",
                          title: detailsItem.name
                        }, _toDisplayString(detailsItem.name), 9 /* TEXT, PROPS */, _hoisted_178),
                        _createElementVNode("div", {
                          class: "small text-muted text-truncate",
                          title: '/' + detailsItem.path
                        }, "/" + _toDisplayString(detailsItem.path), 9 /* TEXT, PROPS */, _hoisted_179)
                      ]),
                      (!detailsItem.isBulk)
                        ? (_openBlock(), _createElementBlock("button", {
                            key: 0,
                            class: "btn btn-outline-secondary btn-sm",
                            onClick: $event => (toggleBookmark(detailsItem)),
                            "aria-label": store.isBookmarked(detailsItem) ? t('remove_bookmark') : t('add_bookmark'),
                            title: store.isBookmarked(detailsItem) ? t('remove_bookmark') : t('add_bookmark')
                          }, [
                            _createElementVNode("i", {
                              class: _normalizeClass(store.isBookmarked(detailsItem) ? 'ri-star-fill' : 'ri-star-line'),
                              "aria-hidden": "true"
                            }, null, 2 /* CLASS */)
                          ], 8 /* PROPS */, _hoisted_180))
                        : _createCommentVNode("v-if", true)
                    ]),
                    _createElementVNode("dl", _hoisted_181, [
                      _createElementVNode("dt", _hoisted_182, _toDisplayString(t('type')), 1 /* TEXT */),
                      _createElementVNode("dd", _hoisted_183, _toDisplayString(detailsItem.mime || detailsItem.type), 1 /* TEXT */),
                      _createElementVNode("dt", _hoisted_184, _toDisplayString(t('size')), 1 /* TEXT */),
                      _createElementVNode("dd", _hoisted_185, _toDisplayString(formatSize(detailsItem.size || 0)), 1 /* TEXT */),
                      _createElementVNode("dt", _hoisted_186, _toDisplayString(t('date')), 1 /* TEXT */),
                      _createElementVNode("dd", _hoisted_187, _toDisplayString(detailsItem.mtime ? formatDate(detailsItem.mtime) : '-'), 1 /* TEXT */),
                      _createElementVNode("dt", _hoisted_188, _toDisplayString(t('perms')), 1 /* TEXT */),
                      _createElementVNode("dd", _hoisted_189, _toDisplayString(detailsItem.perms || '-'), 1 /* TEXT */),
                      (!detailsItem.isBulk)
                        ? (_openBlock(), _createElementBlock(_Fragment, { key: 0 }, [
                            _createElementVNode("dt", _hoisted_190, _toDisplayString(t('share')), 1 /* TEXT */),
                            _createElementVNode("dd", _hoisted_191, [
                              _createElementVNode("span", {
                                class: _normalizeClass(["badge", detailsItem.is_shared ? 'text-bg-primary' : 'text-bg-secondary'])
                              }, _toDisplayString(detailsShareLabel), 3 /* TEXT, CLASS */)
                            ])
                          ], 64 /* STABLE_FRAGMENT */))
                        : _createCommentVNode("v-if", true)
                    ]),
                    _createElementVNode("div", _hoisted_192, [
                      _createElementVNode("button", {
                        class: "btn btn-outline-primary btn-sm",
                        onClick: showProperties,
                        disabled: store.selectedItems.length === 0
                      }, [
                        _cache[90] || (_cache[90] = _createElementVNode("i", {
                          class: "ri-information-line me-1",
                          "aria-hidden": "true"
                        }, null, -1 /* CACHED */)),
                        _createTextVNode(_toDisplayString(t('properties')), 1 /* TEXT */)
                      ], 8 /* PROPS */, _hoisted_193),
                      (store.selectedItems.length === 1)
                        ? (_openBlock(), _createElementBlock("button", {
                            key: 0,
                            class: "btn btn-outline-primary btn-sm",
                            onClick: openShare,
                            disabled: store.isTrashMode
                          }, [
                            _cache[91] || (_cache[91] = _createElementVNode("i", {
                              class: "ri-share-line me-1",
                              "aria-hidden": "true"
                            }, null, -1 /* CACHED */)),
                            _createTextVNode(_toDisplayString(t('share')), 1 /* TEXT */)
                          ], 8 /* PROPS */, _hoisted_194))
                        : _createCommentVNode("v-if", true)
                    ])
                  ]))
            ], 8 /* PROPS */, _hoisted_168))
          : _createCommentVNode("v-if", true)
      ], 42 /* CLASS, PROPS, NEED_HYDRATION */, _hoisted_114),
      _createCommentVNode(" Status Bar "),
      _createElementVNode("div", _hoisted_195, [
        _createElementVNode("div", _hoisted_196, [
          _createElementVNode("span", _hoisted_197, "v" + _toDisplayString(appVersion), 1 /* TEXT */),
          _createElementVNode("span", null, _toDisplayString(t('items_count', {count: store.pagination.total})), 1 /* TEXT */),
          _createElementVNode("span", _hoisted_198, "(" + _toDisplayString(connectionMode === 'local' ? t('local_fs') : connectionMode.toUpperCase()) + ")", 1 /* TEXT */)
        ]),
        (store.pagination.total > store.pagination.pageSize)
          ? (_openBlock(), _createElementBlock("div", _hoisted_199, [
              _createElementVNode("nav", _hoisted_200, [
                _createElementVNode("ul", _hoisted_201, [
                  _createElementVNode("li", {
                    class: _normalizeClass(["page-item", {disabled: store.pagination.page <= 1}])
                  }, [
                    _createElementVNode("a", {
                      class: "page-link d-flex align-items-center",
                      href: "#",
                      onClick: _withModifiers($event => (changePage(-1)), ["prevent"]),
                      "aria-label": "Previous page"
                    }, [
                      _cache[92] || (_cache[92] = _createElementVNode("i", { class: "ri-arrow-left-s-line" }, null, -1 /* CACHED */)),
                      _createElementVNode("span", _hoisted_203, _toDisplayString(t('prev')), 1 /* TEXT */)
                    ], 8 /* PROPS */, _hoisted_202)
                  ], 2 /* CLASS */),
                  (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(paginationPages, (p) => {
                    return (_openBlock(), _createElementBlock("li", {
                      key: p.key,
                      class: _normalizeClass(["page-item", {active: p.type === 'page' && p.number === store.pagination.page, disabled: p.type === 'ellipsis'}])
                    }, [
                      (p.type === 'ellipsis')
                        ? (_openBlock(), _createElementBlock("span", _hoisted_204, "…"))
                        : (_openBlock(), _createElementBlock("a", {
                            key: 1,
                            class: "page-link",
                            href: "#",
                            onClick: _withModifiers($event => (goToPage(p.number)), ["prevent"])
                          }, _toDisplayString(p.number), 9 /* TEXT, PROPS */, _hoisted_205))
                    ], 2 /* CLASS */))
                  }), 128 /* KEYED_FRAGMENT */)),
                  _createElementVNode("li", {
                    class: _normalizeClass(["page-item", {disabled: store.pagination.page >= totalPages}])
                  }, [
                    _createElementVNode("a", {
                      class: "page-link d-flex align-items-center",
                      href: "#",
                      onClick: _withModifiers($event => (changePage(1)), ["prevent"]),
                      "aria-label": "Next page"
                    }, [
                      _createElementVNode("span", _hoisted_207, _toDisplayString(t('next')), 1 /* TEXT */),
                      _cache[93] || (_cache[93] = _createElementVNode("i", { class: "ri-arrow-right-s-line" }, null, -1 /* CACHED */))
                    ], 8 /* PROPS */, _hoisted_206)
                  ], 2 /* CLASS */)
                ])
              ]),
              _createElementVNode("span", _hoisted_208, _toDisplayString(t('page_info', {current: store.pagination.page, total: totalPages})), 1 /* TEXT */)
            ]))
          : _createCommentVNode("v-if", true),
        _createElementVNode("div", _hoisted_209, [
          _createElementVNode("label", _hoisted_210, _toDisplayString(t('rows_per_page') || 'Rows'), 1 /* TEXT */),
          _createElementVNode("select", {
            id: "pageSizeSelect",
            class: "form-select form-select-sm w-auto",
            value: store.pagination.pageSize,
            onChange: $event => (setPageSize($event))
          }, [...(_cache[94] || (_cache[94] = [
            _createElementVNode("option", { value: "25" }, "25", -1 /* CACHED */),
            _createElementVNode("option", { value: "50" }, "50", -1 /* CACHED */),
            _createElementVNode("option", { value: "100" }, "100", -1 /* CACHED */),
            _createElementVNode("option", { value: "200" }, "200", -1 /* CACHED */)
          ]))], 40 /* PROPS, NEED_HYDRATION */, _hoisted_211)
        ])
      ]),
      _createCommentVNode(" Modals "),
      _createCommentVNode(" Editor Modal "),
      _createElementVNode("div", _hoisted_212, [
        _createElementVNode("div", _hoisted_213, [
          _createElementVNode("div", _hoisted_214, [
            _createElementVNode("div", _hoisted_215, [
              _createElementVNode("h5", _hoisted_216, _toDisplayString(t('editing', {name: editorFile?.name})), 1 /* TEXT */),
              _createElementVNode("button", {
                type: "button",
                class: "btn-close",
                "data-bs-dismiss": "modal",
                "aria-label": t('close') || 'Close'
              }, null, 8 /* PROPS */, _hoisted_217)
            ]),
            _cache[95] || (_cache[95] = _createElementVNode("div", { class: "modal-body p-0 overflow-hidden" }, [
              _createElementVNode("div", {
                id: "aceEditor",
                class: "h-100 w-100"
              })
            ], -1 /* CACHED */)),
            _createElementVNode("div", _hoisted_218, [
              _createElementVNode("button", _hoisted_219, _toDisplayString(t('close')), 1 /* TEXT */),
              _createElementVNode("button", {
                type: "button",
                class: "btn btn-primary btn-sm",
                onClick: saveFile
              }, _toDisplayString(t('save')), 9 /* TEXT, PROPS */, _hoisted_220)
            ])
          ])
        ])
      ]),
      _createCommentVNode(" Diff Modal "),
      _createElementVNode("div", _hoisted_221, [
        _createElementVNode("div", _hoisted_222, [
          _createElementVNode("div", _hoisted_223, [
            _createElementVNode("div", _hoisted_224, [
              _cache[96] || (_cache[96] = _createElementVNode("h5", { class: "modal-title fs-6" }, "Compare Files", -1 /* CACHED */)),
              _createElementVNode("button", {
                type: "button",
                class: "btn-close",
                "data-bs-dismiss": "modal",
                "aria-label": t('close') || 'Close'
              }, null, 8 /* PROPS */, _hoisted_225)
            ]),
            _cache[97] || (_cache[97] = _createElementVNode("div", { class: "modal-body overflow-auto" }, [
              _createElementVNode("div", { id: "diffViewer" })
            ], -1 /* CACHED */))
          ])
        ])
      ]),
      _createCommentVNode(" Preview Modal "),
      _createElementVNode("div", _hoisted_226, [
        _createElementVNode("div", _hoisted_227, [
          _createElementVNode("div", _hoisted_228, [
            _createElementVNode("div", _hoisted_229, [
              _createElementVNode("h6", _hoisted_230, _toDisplayString(previewState.filename), 1 /* TEXT */),
              _createElementVNode("button", {
                type: "button",
                class: "btn-close btn-close-white",
                "data-bs-dismiss": "modal",
                "aria-label": t('close') || 'Close'
              }, null, 8 /* PROPS */, _hoisted_231)
            ]),
            _createElementVNode("div", _hoisted_232, [
              _createCommentVNode(" Image "),
              (previewState.type === 'image')
                ? (_openBlock(), _createElementBlock("img", {
                    key: 0,
                    src: previewState.src,
                    class: "img-fluid rounded max-h-90vh"
                  }, null, 8 /* PROPS */, _hoisted_233))
                : _createCommentVNode("v-if", true),
              _createCommentVNode(" Video "),
              (previewState.type === 'video')
                ? (_openBlock(), _createElementBlock("video", {
                    key: 1,
                    src: previewState.src,
                    controls: "",
                    autoplay: "",
                    class: "w-100 max-h-90vh"
                  }, null, 8 /* PROPS */, _hoisted_234))
                : _createCommentVNode("v-if", true),
              _createCommentVNode(" Audio "),
              (previewState.type === 'audio')
                ? (_openBlock(), _createElementBlock("div", _hoisted_235, [
                    _cache[98] || (_cache[98] = _createElementVNode("i", { class: "ri-music-2-line fs-1 text-white-50 d-block mb-3" }, null, -1 /* CACHED */)),
                    _createElementVNode("audio", {
                      src: previewState.src,
                      controls: "",
                      autoplay: "",
                      class: "w-100"
                    }, null, 8 /* PROPS */, _hoisted_236)
                  ]))
                : _createCommentVNode("v-if", true),
              _createCommentVNode(" PDF "),
              (previewState.type === 'pdf')
                ? (_openBlock(), _createElementBlock("iframe", {
                    key: 3,
                    src: previewState.src,
                    class: "w-100 preview-pdf-frame"
                  }, null, 8 /* PROPS */, _hoisted_237))
                : _createCommentVNode("v-if", true),
              _createCommentVNode(" Controls "),
              (previewState.list.length > 1)
                ? (_openBlock(), _createElementBlock("button", {
                    key: 4,
                    class: "btn btn-dark bg-opacity-50 position-absolute start-0 m-3 rounded-circle",
                    onClick: _withModifiers(prevPreview, ["stop"]),
                    disabled: previewState.index <= 0,
                    "aria-label": t('prev') || 'Previous'
                  }, [...(_cache[99] || (_cache[99] = [
                    _createElementVNode("i", {
                      class: "ri-arrow-left-s-line fs-4",
                      "aria-hidden": "true"
                    }, null, -1 /* CACHED */)
                  ]))], 8 /* PROPS */, _hoisted_238))
                : _createCommentVNode("v-if", true),
              (previewState.list.length > 1)
                ? (_openBlock(), _createElementBlock("button", {
                    key: 5,
                    class: "btn btn-dark bg-opacity-50 position-absolute end-0 m-3 rounded-circle",
                    onClick: _withModifiers(nextPreview, ["stop"]),
                    disabled: previewState.index >= previewState.list.length - 1,
                    "aria-label": t('next') || 'Next'
                  }, [...(_cache[100] || (_cache[100] = [
                    _createElementVNode("i", {
                      class: "ri-arrow-right-s-line fs-4",
                      "aria-hidden": "true"
                    }, null, -1 /* CACHED */)
                  ]))], 8 /* PROPS */, _hoisted_239))
                : _createCommentVNode("v-if", true)
            ])
          ])
        ])
      ]),
      _createCommentVNode(" Properties Modal "),
      _createElementVNode("div", _hoisted_240, [
        _createElementVNode("div", _hoisted_241, [
          _createElementVNode("div", _hoisted_242, [
            _createElementVNode("div", _hoisted_243, [
              _createElementVNode("h5", _hoisted_244, _toDisplayString(t('properties')), 1 /* TEXT */),
              _createElementVNode("button", {
                type: "button",
                class: "btn-close",
                "data-bs-dismiss": "modal",
                "aria-label": t('close') || 'Close'
              }, null, 8 /* PROPS */, _hoisted_245)
            ]),
            propFile
              ? (_openBlock(), _createElementBlock("div", _hoisted_246, [
                  _createElementVNode("div", _hoisted_247, [
                    _createElementVNode("i", {
                      class: _normalizeClass([getIcon(propFile), "icon-large"])
                    }, null, 2 /* CLASS */),
                    _createElementVNode("h6", _hoisted_248, _toDisplayString(propFile.name), 1 /* TEXT */)
                  ]),
                  _createElementVNode("section", _hoisted_249, [
                    _createElementVNode("h6", _hoisted_250, _toDisplayString(t('properties_metadata')), 1 /* TEXT */),
                    _createElementVNode("table", _hoisted_251, [
                      _createElementVNode("tbody", null, [
                        _createElementVNode("tr", null, [
                          _createElementVNode("th", null, _toDisplayString(t('name')), 1 /* TEXT */),
                          _createElementVNode("td", null, _toDisplayString(propFile.name), 1 /* TEXT */)
                        ]),
                        _createElementVNode("tr", null, [
                          _createElementVNode("th", null, _toDisplayString(t('location')), 1 /* TEXT */),
                          _createElementVNode("td", null, "/" + _toDisplayString(propFile.path), 1 /* TEXT */)
                        ]),
                        _createElementVNode("tr", null, [
                          _createElementVNode("th", null, _toDisplayString(t('size')), 1 /* TEXT */),
                          _createElementVNode("td", null, [
                            _createTextVNode(_toDisplayString(formatSize(propFile.size)) + " ", 1 /* TEXT */),
                            (propFile.type === 'dir')
                              ? (_openBlock(), _createElementBlock("button", {
                                  key: 0,
                                  class: "btn btn-link btn-sm p-0 ms-2 text-decoration-none",
                                  onClick: calcDirSize
                                }, [
                                  _cache[101] || (_cache[101] = _createElementVNode("i", {
                                    class: "ri-calculator-line",
                                    "aria-hidden": "true"
                                  }, null, -1 /* CACHED */)),
                                  _createTextVNode(" " + _toDisplayString(t('calculate')), 1 /* TEXT */)
                                ], 8 /* PROPS */, _hoisted_252))
                              : _createCommentVNode("v-if", true)
                          ])
                        ]),
                        _createElementVNode("tr", null, [
                          _createElementVNode("th", null, _toDisplayString(t('mime')), 1 /* TEXT */),
                          _createElementVNode("td", null, _toDisplayString(propFile.mime), 1 /* TEXT */)
                        ]),
                        _createElementVNode("tr", null, [
                          _createElementVNode("th", null, _toDisplayString(t('date')), 1 /* TEXT */),
                          _createElementVNode("td", null, _toDisplayString(formatDate(propFile.mtime)), 1 /* TEXT */)
                        ]),
                        _createElementVNode("tr", null, [
                          _createElementVNode("th", null, _toDisplayString(t('perms')), 1 /* TEXT */),
                          _createElementVNode("td", null, _toDisplayString(propFile.perms), 1 /* TEXT */)
                        ]),
                        _createElementVNode("tr", null, [
                          _createElementVNode("th", null, _toDisplayString(t('owner')), 1 /* TEXT */),
                          _createElementVNode("td", null, _toDisplayString(propFile.owner || '-'), 1 /* TEXT */)
                        ]),
                        _createElementVNode("tr", null, [
                          _createElementVNode("th", null, _toDisplayString(t('group')), 1 /* TEXT */),
                          _createElementVNode("td", null, _toDisplayString(propFile.group || '-'), 1 /* TEXT */)
                        ])
                      ])
                    ])
                  ]),
                  isAdmin
                    ? (_openBlock(), _createElementBlock("section", _hoisted_253, [
                        _createElementVNode("div", _hoisted_254, [
                          _cache[102] || (_cache[102] = _createElementVNode("i", {
                            class: "ri-shield-keyhole-line text-warning-emphasis fs-5",
                            "aria-hidden": "true"
                          }, null, -1 /* CACHED */)),
                          _createElementVNode("div", null, [
                            _createElementVNode("h6", _hoisted_255, _toDisplayString(t('properties_ownership_actions')), 1 /* TEXT */),
                            _createElementVNode("div", _hoisted_256, _toDisplayString(t('properties_ownership_desc')), 1 /* TEXT */)
                          ])
                        ]),
                        _createElementVNode("div", _hoisted_257, [
                          _createElementVNode("div", _hoisted_258, [
                            _createElementVNode("label", _hoisted_259, _toDisplayString(t('owner')), 1 /* TEXT */),
                            _withDirectives(_createElementVNode("input", {
                              id: "propOwner",
                              type: "text",
                              class: "form-control form-control-sm",
                              "onUpdate:modelValue": $event => ((propFile.owner) = $event),
                              autocomplete: "off"
                            }, null, 8 /* PROPS */, _hoisted_260), [
                              [_vModelText, propFile.owner]
                            ])
                          ]),
                          _createElementVNode("div", _hoisted_261, [
                            _createElementVNode("label", _hoisted_262, _toDisplayString(t('group')), 1 /* TEXT */),
                            _withDirectives(_createElementVNode("input", {
                              id: "propGroup",
                              type: "text",
                              class: "form-control form-control-sm",
                              "onUpdate:modelValue": $event => ((propFile.group) = $event),
                              autocomplete: "off"
                            }, null, 8 /* PROPS */, _hoisted_263), [
                              [_vModelText, propFile.group]
                            ])
                          ])
                        ]),
                        _createElementVNode("div", _hoisted_264, [
                          _withDirectives(_createElementVNode("input", {
                            class: "form-check-input",
                            type: "checkbox",
                            id: "propRecursive",
                            "onUpdate:modelValue": $event => ((propFile.recursive) = $event)
                          }, null, 8 /* PROPS */, _hoisted_265), [
                            [_vModelCheckbox, propFile.recursive]
                          ]),
                          _createElementVNode("label", _hoisted_266, _toDisplayString(t('apply_recursively')), 1 /* TEXT */)
                        ]),
                        (propFile.recursive)
                          ? (_openBlock(), _createElementBlock("div", _hoisted_267, _toDisplayString(t('recursive_owner_warning')), 1 /* TEXT */))
                          : _createCommentVNode("v-if", true),
                        _createElementVNode("div", _hoisted_268, [
                          _createElementVNode("div", _hoisted_269, _toDisplayString(propertiesTargetSummary), 1 /* TEXT */),
                          _createElementVNode("button", {
                            class: "btn btn-warning btn-sm",
                            type: "button",
                            onClick: saveChown
                          }, [
                            _cache[103] || (_cache[103] = _createElementVNode("i", {
                              class: "ri-shield-check-line me-1",
                              "aria-hidden": "true"
                            }, null, -1 /* CACHED */)),
                            _createTextVNode(_toDisplayString(t('apply_owner_group')), 1 /* TEXT */)
                          ], 8 /* PROPS */, _hoisted_270)
                        ])
                      ]))
                    : _createCommentVNode("v-if", true)
                ]))
              : _createCommentVNode("v-if", true)
          ])
        ])
      ]),
      _createCommentVNode(" WebDAV Modal "),
      _createElementVNode("div", _hoisted_271, [
        _createElementVNode("div", _hoisted_272, [
          _createElementVNode("div", _hoisted_273, [
            _createElementVNode("div", _hoisted_274, [
              _createElementVNode("h5", _hoisted_275, _toDisplayString(t('webdav_connect')), 1 /* TEXT */),
              _createElementVNode("button", {
                type: "button",
                class: "btn-close",
                "data-bs-dismiss": "modal",
                "aria-label": t('close') || 'Close'
              }, null, 8 /* PROPS */, _hoisted_276)
            ]),
            _createElementVNode("div", _hoisted_277, [
              _createElementVNode("p", _hoisted_278, _toDisplayString(t('webdav_help')), 1 /* TEXT */),
              _createElementVNode("div", _hoisted_279, [
                _createElementVNode("label", _hoisted_280, _toDisplayString(t('webdav_url')), 1 /* TEXT */),
                _createElementVNode("div", _hoisted_281, [
                  _createElementVNode("input", {
                    type: "text",
                    class: "form-control",
                    value: webDavUrl,
                    readonly: "",
                    id: "webdav_url_input"
                  }, null, 8 /* PROPS */, _hoisted_282),
                  _createElementVNode("button", {
                    class: "btn btn-outline-secondary",
                    onClick: copyWebDavUrl
                  }, [
                    _cache[104] || (_cache[104] = _createElementVNode("i", {
                      class: "ri-file-copy-line",
                      "aria-hidden": "true"
                    }, null, -1 /* CACHED */)),
                    _createElementVNode("span", null, _toDisplayString(t('webdav_copy_url')), 1 /* TEXT */)
                  ], 8 /* PROPS */, _hoisted_283)
                ])
              ]),
              _createElementVNode("div", _hoisted_284, [
                _createElementVNode("div", _hoisted_285, [
                  _createElementVNode("div", _hoisted_286, [
                    _createElementVNode("div", _hoisted_287, [
                      _cache[105] || (_cache[105] = _createElementVNode("i", {
                        class: "ri-windows-line me-1",
                        "aria-hidden": "true"
                      }, null, -1 /* CACHED */)),
                      _createTextVNode(_toDisplayString(t('webdav_windows_title')), 1 /* TEXT */)
                    ]),
                    _createElementVNode("div", _hoisted_288, _toDisplayString(t('webdav_windows_hint')), 1 /* TEXT */)
                  ])
                ]),
                _createElementVNode("div", _hoisted_289, [
                  _createElementVNode("div", _hoisted_290, [
                    _createElementVNode("div", _hoisted_291, [
                      _cache[106] || (_cache[106] = _createElementVNode("i", {
                        class: "ri-apple-line me-1",
                        "aria-hidden": "true"
                      }, null, -1 /* CACHED */)),
                      _createTextVNode(_toDisplayString(t('webdav_macos_title')), 1 /* TEXT */)
                    ]),
                    _createElementVNode("div", _hoisted_292, _toDisplayString(t('webdav_macos_hint')), 1 /* TEXT */)
                  ])
                ]),
                _createElementVNode("div", _hoisted_293, [
                  _createElementVNode("div", _hoisted_294, [
                    _createElementVNode("div", _hoisted_295, [
                      _cache[107] || (_cache[107] = _createElementVNode("i", {
                        class: "ri-terminal-box-line me-1",
                        "aria-hidden": "true"
                      }, null, -1 /* CACHED */)),
                      _createTextVNode(_toDisplayString(t('webdav_linux_title')), 1 /* TEXT */)
                    ]),
                    _createElementVNode("div", _hoisted_296, _toDisplayString(t('webdav_linux_hint')), 1 /* TEXT */)
                  ])
                ])
              ]),
              _createElementVNode("div", _hoisted_297, [
                _cache[108] || (_cache[108] = _createElementVNode("i", {
                  class: "ri-key-2-line me-1",
                  "aria-hidden": "true"
                }, null, -1 /* CACHED */)),
                _createTextVNode(" " + _toDisplayString(t('webdav_credentials_note', {username: username})), 1 /* TEXT */)
              ]),
              _createElementVNode("div", _hoisted_298, [
                _cache[109] || (_cache[109] = _createElementVNode("i", {
                  class: "ri-shield-check-line me-1",
                  "aria-hidden": "true"
                }, null, -1 /* CACHED */)),
                _createTextVNode(" " + _toDisplayString(t('webdav_https_note')), 1 /* TEXT */)
              ]),
              _createElementVNode("div", _hoisted_299, [
                _cache[110] || (_cache[110] = _createElementVNode("i", {
                  class: "ri-admin-line me-1",
                  "aria-hidden": "true"
                }, null, -1 /* CACHED */)),
                _createTextVNode(" " + _toDisplayString(t('webdav_policy_note')), 1 /* TEXT */)
              ])
            ])
          ])
        ])
      ]),
      _createCommentVNode(" Context Menu "),
      (contextMenu.visible)
        ? (_openBlock(), _createElementBlock("div", _hoisted_300, [
            (!store.isTrashMode)
              ? (_openBlock(), _createElementBlock(_Fragment, { key: 0 }, [
                  _createElementVNode("a", {
                    class: "dropdown-item",
                    href: "#",
                    onClick: _withModifiers($event => (cmAction('open')), ["prevent"])
                  }, [
                    _cache[111] || (_cache[111] = _createElementVNode("i", { class: "ri-folder-open-line me-2" }, null, -1 /* CACHED */)),
                    _createTextVNode(" " + _toDisplayString(t('open') || 'Open'), 1 /* TEXT */)
                  ], 8 /* PROPS */, _hoisted_301),
                  _createElementVNode("a", {
                    class: "dropdown-item",
                    href: "#",
                    onClick: _withModifiers($event => (cmAction('download')), ["prevent"])
                  }, [
                    _cache[112] || (_cache[112] = _createElementVNode("i", { class: "ri-download-line me-2" }, null, -1 /* CACHED */)),
                    _createTextVNode(" " + _toDisplayString(t('download')), 1 /* TEXT */)
                  ], 8 /* PROPS */, _hoisted_302),
                  _createElementVNode("a", {
                    class: "dropdown-item",
                    href: "#",
                    onClick: _withModifiers($event => (cmAction('share')), ["prevent"])
                  }, [
                    _cache[113] || (_cache[113] = _createElementVNode("i", { class: "ri-share-line me-2" }, null, -1 /* CACHED */)),
                    _createTextVNode(" " + _toDisplayString(t('share_title') || 'Share'), 1 /* TEXT */)
                  ], 8 /* PROPS */, _hoisted_303),
                  _cache[122] || (_cache[122] = _createElementVNode("div", { class: "dropdown-divider" }, null, -1 /* CACHED */)),
                  _createElementVNode("a", {
                    class: "dropdown-item",
                    href: "#",
                    onClick: _withModifiers($event => (cmAction('copy')), ["prevent"])
                  }, [
                    _cache[114] || (_cache[114] = _createElementVNode("i", { class: "ri-file-copy-line me-2" }, null, -1 /* CACHED */)),
                    _createTextVNode(" " + _toDisplayString(t('copy')), 1 /* TEXT */)
                  ], 8 /* PROPS */, _hoisted_304),
                  _createElementVNode("a", {
                    class: "dropdown-item",
                    href: "#",
                    onClick: _withModifiers($event => (cmAction('cut')), ["prevent"])
                  }, [
                    _cache[115] || (_cache[115] = _createElementVNode("i", { class: "ri-scissors-cut-line me-2" }, null, -1 /* CACHED */)),
                    _createTextVNode(" " + _toDisplayString(t('cut')), 1 /* TEXT */)
                  ], 8 /* PROPS */, _hoisted_305),
                  _createElementVNode("a", {
                    class: _normalizeClass(["dropdown-item", {disabled: store.clipboard.items.length === 0}]),
                    href: "#",
                    onClick: _withModifiers($event => (cmAction('paste')), ["prevent"])
                  }, [
                    _cache[116] || (_cache[116] = _createElementVNode("i", { class: "ri-clipboard-line me-2" }, null, -1 /* CACHED */)),
                    _createTextVNode(" " + _toDisplayString(t('paste')), 1 /* TEXT */)
                  ], 10 /* CLASS, PROPS */, _hoisted_306),
                  _cache[123] || (_cache[123] = _createElementVNode("div", { class: "dropdown-divider" }, null, -1 /* CACHED */)),
                  _createElementVNode("a", {
                    class: "dropdown-item",
                    href: "#",
                    onClick: _withModifiers($event => (cmAction('rename')), ["prevent"])
                  }, [
                    _cache[117] || (_cache[117] = _createElementVNode("i", { class: "ri-edit-line me-2" }, null, -1 /* CACHED */)),
                    _createTextVNode(" " + _toDisplayString(t('rename')), 1 /* TEXT */)
                  ], 8 /* PROPS */, _hoisted_307),
                  _createElementVNode("a", {
                    class: "dropdown-item",
                    href: "#",
                    onClick: _withModifiers($event => (cmAction('perms')), ["prevent"])
                  }, [
                    _cache[118] || (_cache[118] = _createElementVNode("i", { class: "ri-lock-2-line me-2" }, null, -1 /* CACHED */)),
                    _createTextVNode(" " + _toDisplayString(t('perms')), 1 /* TEXT */)
                  ], 8 /* PROPS */, _hoisted_308),
                  _createElementVNode("a", {
                    class: "dropdown-item",
                    href: "#",
                    onClick: _withModifiers($event => (cmAction('properties')), ["prevent"])
                  }, [
                    _cache[119] || (_cache[119] = _createElementVNode("i", { class: "ri-information-line me-2" }, null, -1 /* CACHED */)),
                    _createTextVNode(" " + _toDisplayString(t('properties')), 1 /* TEXT */)
                  ], 8 /* PROPS */, _hoisted_309),
                  (contextMenu.file && contextMenu.file.type !== 'dir')
                    ? (_openBlock(), _createElementBlock("a", {
                        key: 0,
                        class: "dropdown-item",
                        href: "#",
                        onClick: _withModifiers($event => (cmAction('history')), ["prevent"])
                      }, [
                        _cache[120] || (_cache[120] = _createElementVNode("i", { class: "ri-history-line me-2" }, null, -1 /* CACHED */)),
                        _createTextVNode(" " + _toDisplayString(t('version_history') || 'Version History'), 1 /* TEXT */)
                      ], 8 /* PROPS */, _hoisted_310))
                    : _createCommentVNode("v-if", true),
                  _cache[124] || (_cache[124] = _createElementVNode("div", { class: "dropdown-divider" }, null, -1 /* CACHED */)),
                  _createElementVNode("a", {
                    class: "dropdown-item text-danger",
                    href: "#",
                    onClick: _withModifiers($event => (cmAction('delete')), ["prevent"])
                  }, [
                    _cache[121] || (_cache[121] = _createElementVNode("i", { class: "ri-delete-bin-line me-2" }, null, -1 /* CACHED */)),
                    _createTextVNode(" " + _toDisplayString(t('delete')), 1 /* TEXT */)
                  ], 8 /* PROPS */, _hoisted_311)
                ], 64 /* STABLE_FRAGMENT */))
              : (_openBlock(), _createElementBlock(_Fragment, { key: 1 }, [
                  _createElementVNode("a", {
                    class: "dropdown-item text-success",
                    href: "#",
                    onClick: _withModifiers($event => (cmAction('restore')), ["prevent"])
                  }, [
                    _cache[125] || (_cache[125] = _createElementVNode("i", { class: "ri-restart-line me-2" }, null, -1 /* CACHED */)),
                    _createTextVNode(" " + _toDisplayString(t('restore') || 'Restore'), 1 /* TEXT */)
                  ], 8 /* PROPS */, _hoisted_312),
                  _createElementVNode("a", {
                    class: "dropdown-item",
                    href: "#",
                    onClick: _withModifiers($event => (cmAction('properties')), ["prevent"])
                  }, [
                    _cache[126] || (_cache[126] = _createElementVNode("i", { class: "ri-information-line me-2" }, null, -1 /* CACHED */)),
                    _createTextVNode(" " + _toDisplayString(t('properties')), 1 /* TEXT */)
                  ], 8 /* PROPS */, _hoisted_313),
                  _cache[128] || (_cache[128] = _createElementVNode("div", { class: "dropdown-divider" }, null, -1 /* CACHED */)),
                  _createElementVNode("a", {
                    class: "dropdown-item text-danger",
                    href: "#",
                    onClick: _withModifiers($event => (cmAction('delete_perm')), ["prevent"])
                  }, [
                    _cache[127] || (_cache[127] = _createElementVNode("i", { class: "ri-delete-bin-2-line me-2" }, null, -1 /* CACHED */)),
                    _createTextVNode(" " + _toDisplayString(t('delete_perm') || 'Delete Permanently'), 1 /* TEXT */)
                  ], 8 /* PROPS */, _hoisted_314)
                ], 64 /* STABLE_FRAGMENT */))
          ]))
        : _createCommentVNode("v-if", true)
    ], 64 /* STABLE_FRAGMENT */))
  }
}
})(Vue);
