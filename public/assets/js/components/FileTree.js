const FileTree = {
    name: 'FileTree',
    props: {
        path: String,
        name: String,
        root: Boolean
    },
    data() {
        return {
            isOpen: this.root, // Root is open by default
            isLoading: false,
            children: []
        };
    },
    computed: {
        isSelected() {
            // Check if this folder is the current working directory
            return store.cwd === this.path;
        },
        indent() {
            return { paddingLeft: this.root ? '0px' : '15px' };
        }
    },
    methods: {
        async toggle() {
            if (this.root) return; // Root is always "open" conceptually, though we might want to toggle it too. Let's say root is just a container.
            
            this.isOpen = !this.isOpen;
            if (this.isOpen && this.children.length === 0) {
                await this.loadChildren();
            }
        },
        async loadChildren() {
            this.isLoading = true;
            try {
                const res = await Api.get('ls', { path: this.path });
                // Filter only directories
                this.children = res.items.filter(item => item.type === 'dir');
                // Sort by name
                this.children.sort((a, b) => a.name.localeCompare(b.name));
            } catch (e) {
                console.error("Tree load error:", e);
            } finally {
                this.isLoading = false;
            }
        },
        select() {
            store.loadPath(this.path);
        }
    },
    mounted() {
        if (this.root) {
             this.loadChildren();
        }
    },
    template: `
        <div class="file-tree-item" :style="indent">
            <div class="d-flex align-items-center py-1 px-2 rounded" 
                 :class="{'bg-primary text-white': isSelected, 'text-dark': !isSelected}"
                 style="cursor: pointer; user-select: none;"
                 @click="select">
                
                <!-- Toggle Icon -->
                <i v-if="!root" 
                   class="ri-arrow-right-s-line me-1" 
                   :class="{'rotate-90': isOpen, 'invisible': false}"
                   @click.stop="toggle"
                   style="transition: transform 0.2s;"></i>
                
                <!-- Folder Icon -->
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
