const store = Vue.reactive({
    cwd: '',
    files: [],
    selectedItems: [],
    searchQuery: '',
    sortBy: 'name',
    sortDesc: false,
    viewMode: localStorage.getItem('extplorer_view_mode') || 'grid',
    isLoading: false,
    error: null,

    async loadPath(path) {
        this.isLoading = true;
        this.error = null;
        this.selectedItems = []; // Clear selection on navigate
        try {
            const items = await Api.get('ls', { path });
            // Sort: folders first, then files
            items.sort((a, b) => {
                if (a.type === b.type) return a.name.localeCompare(b.name);
                return a.type === 'dir' ? -1 : 1;
            });
            this.files = items;
            this.cwd = path;
        } catch (e) {
            this.error = e.message;
            console.error(e);
        } finally {
            this.isLoading = false;
        }
    },

    toggleSelection(file) {
        const index = this.selectedItems.findIndex(f => f.name === file.name);
        if (index === -1) {
            this.selectedItems.push(file);
        } else {
            this.selectedItems.splice(index, 1);
        }
    },

    isSelected(file) {
        return this.selectedItems.some(f => f.name === file.name);
    },

    clearSelection() {
        this.selectedItems = [];
    },

    toggleViewMode() {
        this.viewMode = this.viewMode === 'grid' ? 'list' : 'grid';
        localStorage.setItem('extplorer_view_mode', this.viewMode);
    }
});
