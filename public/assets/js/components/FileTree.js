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
                if (item.mount_type === 'ftp' || item.mount_type === 'ssh2') return 'ri-cloud-fill text-info';
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
        'store.treeVersion'() {
            if (this.isOpen) {
                this.loadChildren();
            }
        }
    },
    watch: {
        'store.treeVersion'() {
            if (this.isOpen) {
                this.loadChildren();
            }
        }
    },
    template: `
        <div class="file-tree-item" :class="{'tree-indent': !root}">
            <div class="d-flex align-items-center py-1 px-2 rounded tree-item-interactive" 
                 :class="{'bg-primary text-white': isSelected, 'text-dark': !isSelected, 'bg-info-subtle': isDragOver}"
                 @click="select"
                 @dragover="onDragOver"
                 @dragleave="onDragLeave"
                 @drop.stop="onDrop">
                
                <i v-if="!root" 
                   class="ri-arrow-right-s-line me-1 tree-toggle" 
                   :class="{'rotate-90': isOpen}"
                   @click.stop="toggle"></i>
                
                <i class="me-2"
                   :class="[getIcon(item || {type: 'dir'}), isSelected ? 'text-white' : '']"></i>
                
                <span class="text-truncate">{{ name }}</span>
            </div>

            <div v-if="isOpen" class="ms-2 border-start border-secondary-subtle ps-1">
                <div v-if="isLoading" class="ps-3 py-1 small text-muted">Loading...</div>
                <file-tree 
                    v-for="child in children" 
                    :key="child.path" 
                    :path="child.path" 
                    :name="child.name"
                    :item="child"
                ></file-tree>
            </div>
        </div>
    `
};
