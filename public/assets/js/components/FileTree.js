const FileTree = {
    name: 'FileTree',
    props: {
        path: String,
        name: String,
        root: Boolean
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
        },
        indent() {
            return { paddingLeft: this.root ? '0px' : '15px' };
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
        <div class="file-tree-item" :style="indent">
            <div class="d-flex align-items-center py-1 px-2 rounded" 
                 :class="{'bg-primary text-white': isSelected, 'text-dark': !isSelected, 'bg-info-subtle': isDragOver}"
                 style="cursor: pointer; user-select: none;"
                 @click="select"
                 @dragover="onDragOver"
                 @dragleave="onDragLeave"
                 @drop.stop="onDrop">
                
                <i v-if="!root" 
                   class="ri-arrow-right-s-line me-1" 
                   :class="{'rotate-90': isOpen}"
                   @click.stop="toggle"
                   style="transition: transform 0.2s;"></i>
                
                <i class="me-2" :class="isOpen ? 'ri-folder-open-fill' : 'ri-folder-fill'" :style="{color: isSelected ? 'white' : '#ffc107'}"></i>
                
                <span class="text-truncate">{{ name }}</span>
            </div>

            <div v-if="isOpen" class="ms-2 border-start border-secondary-subtle ps-1">
                <div v-if="isLoading" class="ps-3 py-1 small text-muted">Loading...</div>
                <file-tree 
                    v-for="child in children" 
                    :key="child.path" 
                    :path="child.path" 
                    :name="child.name"
                ></file-tree>
            </div>
        </div>
    `
};