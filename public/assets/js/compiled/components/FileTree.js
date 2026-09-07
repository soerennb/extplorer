const FileTree = {
    name: 'FileTree',
    props: {
        path: String,
        name: String,
        root: Boolean,
        item: Object
    },
    data() {
        return {
            store,
            isOpen: this.root,
            isLoading: false,
            isDragOver: false,
            children: []
        };
    },
    computed: {
        isSelected() {
            return this.store.cwd === this.path;
        }
    },
    methods: {
        async toggle() {
            if (this.root) return;
            this.isOpen = !this.isOpen;
            if (this.isOpen && this.children.length === 0) {
                await this.loadChildren();
            }
        },
        async loadChildren() {
            this.isLoading = true;
            this.children = []; // Clear current children to ensure we reflect deletions
            try {
                const res = await Api.get('ls', { path: this.path });
                this.children = res.items.filter(item => item.type === 'dir');
                this.children.sort((a, b) => a.name.localeCompare(b.name));
            } catch (e) {
                console.error("Tree load error:", e);
            } finally {
                this.isLoading = false;
            }
        },
        getIcon(item) {
            if (item.is_mount && item.is_external) {
                if (item.mount_type === 'ftp' || item.mount_type === 'ftps' || item.mount_type === 'ssh2' || item.mount_type === 'sftp') return 'ri-cloud-fill text-info';
                return 'ri-hard-drive-2-fill text-primary';
            }
            return this.isOpen ? 'ri-folder-open-fill tree-folder' : 'ri-folder-fill tree-folder';
        },
        select() {
            this.store.loadPath(this.path);
        },
        onDragOver(e) {
            e.preventDefault();
            this.isDragOver = true;
        },
        onDragLeave() {
            this.isDragOver = false;
        },
        onDrop(e) {
            this.isDragOver = false;
            // Use the global onDrop handler from the parent app instance
            this.$root.onDrop(e, { path: this.path, type: 'dir' });
        }
    },
    mounted() {
        if (this.root) {
             this.loadChildren();
        }
    },
    watch: {
        'store.cwd'(val) {
            if (!this.path) return;
            const normalized = String(val || '').replace(/^\/+|\/+$/g, '');
            const myPath = String(this.path || '').replace(/^\/+|\/+$/g, '');
            if (!normalized || !myPath) return;
            if (normalized === myPath || normalized.startsWith(myPath + '/')) {
                if (!this.isOpen) this.isOpen = true;
                if (this.children.length === 0) {
                    this.loadChildren();
                }
            }
        },
        'store.treeVersion'() {
            if (this.isOpen) {
                this.loadChildren();
            }
        }
    },
    render: ((Vue) => {
const _Vue = Vue
const { createElementVNode: _createElementVNode, createCommentVNode: _createCommentVNode } = _Vue

const _hoisted_1 = ["onClick", "onDragover", "onDragleave", "onDrop"]
const _hoisted_2 = ["onClick"]
const _hoisted_3 = { class: "text-truncate" }
const _hoisted_4 = {
  key: 0,
  class: "ms-2 border-start border-secondary-subtle ps-1"
}
const _hoisted_5 = {
  key: 0,
  class: "ps-3 py-1 small text-muted"
}

return function render(_ctx, _cache) {
  with (_ctx) {
    const { withModifiers: _withModifiers, normalizeClass: _normalizeClass, openBlock: _openBlock, createElementBlock: _createElementBlock, createCommentVNode: _createCommentVNode, createElementVNode: _createElementVNode, toDisplayString: _toDisplayString, renderList: _renderList, Fragment: _Fragment, resolveComponent: _resolveComponent, createBlock: _createBlock } = _Vue

    const _component_file_tree = _resolveComponent("file-tree", true)

    return (_openBlock(), _createElementBlock("div", {
      class: _normalizeClass(["file-tree-item", {'tree-indent': !root}])
    }, [
      _createElementVNode("div", {
        class: _normalizeClass(["d-flex align-items-center py-1 px-2 rounded tree-item-interactive", {'bg-primary text-white': isSelected, 'bg-info-subtle': isDragOver}]),
        onClick: select,
        onDragover: onDragOver,
        onDragleave: onDragLeave,
        onDrop: _withModifiers(onDrop, ["stop"])
      }, [
        (!root)
          ? (_openBlock(), _createElementBlock("i", {
              key: 0,
              class: _normalizeClass(["ri-arrow-right-s-line me-1 tree-toggle", {'rotate-90': isOpen}]),
              onClick: _withModifiers(toggle, ["stop"])
            }, null, 10 /* CLASS, PROPS */, _hoisted_2))
          : _createCommentVNode("v-if", true),
        _createElementVNode("i", {
          class: _normalizeClass(["me-2", [getIcon(item || {type: 'dir'}), isSelected ? 'text-white' : '']])
        }, null, 2 /* CLASS */),
        _createElementVNode("span", _hoisted_3, _toDisplayString(name), 1 /* TEXT */)
      ], 42 /* CLASS, PROPS, NEED_HYDRATION */, _hoisted_1),
      isOpen
        ? (_openBlock(), _createElementBlock("div", _hoisted_4, [
            isLoading
              ? (_openBlock(), _createElementBlock("div", _hoisted_5, "Loading..."))
              : _createCommentVNode("v-if", true),
            (_openBlock(true), _createElementBlock(_Fragment, null, _renderList(children, (child) => {
              return (_openBlock(), _createBlock(_component_file_tree, {
                key: child.path,
                path: child.path,
                name: child.name,
                item: child
              }, null, 8 /* PROPS */, ["path", "name", "item"]))
            }), 128 /* KEYED_FRAGMENT */))
          ]))
        : _createCommentVNode("v-if", true)
    ], 2 /* CLASS */))
  }
}
})(Vue)
};
