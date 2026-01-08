const store = Vue.reactive({
    cwd: '',
    files: [],
    selectedItems: [],
    searchQuery: '',
    sortBy: 'name',
    sortDesc: false,
    clipboard: { items: [], mode: null },
    recentFiles: JSON.parse(localStorage.getItem('extplorer_recent_files') || '[]'),
    showHidden: localStorage.getItem('extplorer_show_hidden') === 'true',
    viewMode: localStorage.getItem('extplorer_view_mode') || 'grid',
    isLoading: false,
    error: null,
    pagination: {
        page: 1,
        pageSize: 100,
        total: 0
    },

    toggleHidden() {
        this.showHidden = !this.showHidden;
        localStorage.setItem('extplorer_show_hidden', this.showHidden);
        this.loadPath(this.cwd);
    },

    addToRecent(file) {
        if (file.type === 'dir') return;
        // Remove if already exists to move to top
        this.recentFiles = this.recentFiles.filter(f => f.path !== file.path);
        this.recentFiles.unshift(file);
        // Keep only last 10
        if (this.recentFiles.length > 10) this.recentFiles.pop();
        localStorage.setItem('extplorer_recent_files', JSON.stringify(this.recentFiles));
    },

    clearRecent() {
        this.recentFiles = [];
        localStorage.removeItem('extplorer_recent_files');
    },

    async loadPath(path, page = 1) {
        this.isLoading = true;
        this.error = null;
        this.selectedItems = []; // Clear selection on navigate
        this.searchQuery = ''; // Clear search query when navigating
        
        try {
            const offset = (page - 1) * this.pagination.pageSize;
            const res = await Api.get('ls', { 
                path, 
                showHidden: this.showHidden,
                limit: this.pagination.pageSize,
                offset: offset
            });
            
            const items = res.items;
            this.pagination.total = res.total;
            this.pagination.page = page;

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

    async performSearch(query) {
        if (!query) {
            return this.loadPath(this.cwd);
        }
        this.isLoading = true;
        this.error = null;
        this.selectedItems = [];
        try {
            const res = await Api.get('search', { q: query });
            this.files = res.items;
            this.pagination.total = res.total;
            this.pagination.page = 1; // Search results usually a single page for now
        } catch (e) {
            this.error = e.message;
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
