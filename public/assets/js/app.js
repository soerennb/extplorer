const { createApp, computed, onMounted, ref } = Vue;

const app = createApp({
    components: {
        FileTree,
        UserAdmin
    },
    setup() {
        const editorFile = ref(null);
        const userAdmin = ref(null); 
        const contextMenu = Vue.reactive({ visible: false, x: 0, y: 0, file: null });
        const imageViewer = Vue.reactive({ src: '', index: 0, list: [] });
        const theme = ref(localStorage.getItem('extplorer_theme') || 'light');
        let aceEditor = null;
        let editorModal = null;
        let imageModal = null;

        const isAdmin = computed(() => window.userRole === 'admin');

        // Helpers
        const isImage = (file) => {
            if (file.type === 'dir') return false;
            const ext = file.extension ? file.extension.toLowerCase() : '';
            return ['jpg','jpeg','png','gif','webp','svg'].includes(ext);
        };

        const getThumbUrl = (file) => {
            return baseUrl + 'api/thumb?path=' + encodeURIComponent(file.path);
        };

        const getIcon = (file) => {
            if (file.type === 'dir') return 'ri-folder-fill text-warning';
            
            const ext = file.extension ? file.extension.toLowerCase() : '';
            if (['jpg','jpeg','png','gif','svg','webp'].includes(ext)) return 'ri-image-fill text-success';
            if (['mp3','wav','ogg'].includes(ext)) return 'ri-music-fill text-info';
            if (['mp4','webm','mov'].includes(ext)) return 'ri-movie-fill text-info';
            if (['pdf'].includes(ext)) return 'ri-file-pdf-line text-danger';
            if (['zip','tar','gz','rar'].includes(ext)) return 'ri-file-zip-line text-warning';
            if (['php','js','css','html','json','sql'].includes(ext)) return 'ri-code-s-slash-line text-primary';
            if (['txt','md'].includes(ext)) return 'ri-file-text-line text-secondary';
            
            return 'ri-file-line text-secondary';
        };

        const formatSize = (bytes) => {
            if (bytes === 0) return ''; // Directories
            if (!bytes) return '-';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        };

        const formatDate = (timestamp) => {
            if (!timestamp) return '-';
            const date = new Date(timestamp * 1000);
            return date.toLocaleString();
        };

        const containerClass = computed(() => {
            return store.viewMode === 'grid' ? 'grid-view' : 'list-view';
        });

        const filteredFiles = computed(() => {
            let result = store.files;
            if (store.searchQuery) {
                const lower = store.searchQuery.toLowerCase();
                result = result.filter(f => f.name.toLowerCase().includes(lower));
            }
            const key = store.sortBy;
            const mult = store.sortDesc ? -1 : 1;
            return [...result].sort((a, b) => {
                if (a.type !== b.type) return a.type === 'dir' ? -1 : 1;
                let valA = a[key];
                let valB = b[key];
                if (key === 'name') return valA.localeCompare(valB) * mult;
                return (valA - valB) * mult;
            });
        });

        // Core Actions
        const goUp = () => {
            if (!store.cwd) return;
            const parts = store.cwd.split('/');
            parts.pop();
            store.loadPath(parts.join('/'));
        };

        const reload = () => store.loadPath(store.cwd);

        const open = async (file) => {
            if (file.type === 'dir') {
                store.loadPath(file.path);
            } else {
                const ext = file.extension ? file.extension.toLowerCase() : '';
                const textExts = ['php','js','css','html','json','xml','txt','md','sql','gitignore','env'];
                
                if (textExts.includes(ext)) {
                    try {
                        const res = await Api.get('content', { path: file.path });
                        editorFile.value = file;
                        editorModal.show();
                        const setContent = () => {
                            aceEditor.setValue(res.content, -1);
                            let mode = 'text';
                            if(ext === 'php') mode = 'php';
                            if(ext === 'js') mode = 'javascript';
                            if(ext === 'html') mode = 'html';
                            if(ext === 'css') mode = 'css';
                            if(ext === 'json') mode = 'json';
                            if(ext === 'xml') mode = 'xml';
                            if(ext === 'md') mode = 'markdown';
                            if(ext === 'sql') mode = 'sql';
                            aceEditor.session.setMode("ace/mode/" + mode);
                        };
                        if(aceEditor) setContent();
                        else document.getElementById('editorModal').addEventListener('shown.bs.modal', setContent, { once: true });
                    } catch(e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
                } else if (isImage(file)) {
                    imageViewer.list = filteredFiles.value.filter(f => isImage(f));
                    imageViewer.index = imageViewer.list.findIndex(f => f.name === file.name);
                    updateImageViewer();
                    imageModal.show();
                } else {
                    Swal.fire('Info', 'File type not supported for browser preview.', 'info');
                }
            }
        };

        const saveFile = async () => {
            if (!editorFile.value || !aceEditor) return;
            try {
                await Api.post('save', { path: editorFile.value.path, content: aceEditor.getValue() });
                editorModal.hide();
                Swal.fire(i18n.t('saved'), '', 'success');
                reload();
            } catch(e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
        };

        // UI & Modal Actions
        const openAdmin = () => { if (userAdmin.value) userAdmin.value.open(); };
        
        const toggleTheme = () => {
            theme.value = theme.value === 'light' ? 'dark' : 'light';
            localStorage.setItem('extplorer_theme', theme.value);
            document.documentElement.setAttribute('data-bs-theme', theme.value);
        };

        const changePassword = async () => {
            const { value: password } = await Swal.fire({
                title: i18n.t('change_password'),
                input: 'password',
                inputLabel: 'New Password',
                showCancelButton: true,
                inputValidator: (v) => !v ? 'Required!' : null
            });
            if (password) {
                try {
                    await Api.post('profile/password', { password }); // Note: I used Api.post for brevity, but ProfileController expected PUT. I'll use fetch to be safe.
                    // Wait, Api.post handles JSON. Let's stick to fetch if I want PUT.
                    await fetch(baseUrl + 'api/profile/password', {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ password })
                    }).then(res => { if(!res.ok) throw new Error('Failed'); });
                    Swal.fire(i18n.t('saved'), '', 'success');
                } catch (e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
            }
        };

        // File Operations
        const createFolder = async () => {
            const { value: name } = await Swal.fire({ title: i18n.t('new_folder'), input: 'text', showCancelButton: true });
            if (name) {
                try {
                    await Api.post('mkdir', { path: store.cwd ? store.cwd + '/' + name : name });
                    reload();
                } catch (e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
            }
        };

        const deleteSelected = async () => {
            if (!store.selectedItems.length) return;
            const res = await Swal.fire({ title: i18n.t('confirm_title'), icon: 'warning', showCancelButton: true });
            if (res.isConfirmed) {
                try {
                    for (const f of store.selectedItems) {
                        await Api.post('rm', { path: store.cwd ? store.cwd + '/' + f.name : f.name });
                    }
                    reload();
                    store.selectedItems = [];
                } catch (e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
            }
        };

        const renameSelected = async () => {
            if (store.selectedItems.length !== 1) return;
            const file = store.selectedItems[0];
            const { value: newName } = await Swal.fire({ title: i18n.t('rename'), input: 'text', inputValue: file.name, showCancelButton: true });
            if (newName && newName !== file.name) {
                try {
                    const oldPath = store.cwd ? store.cwd + '/' + file.name : file.name;
                    const newPath = store.cwd ? store.cwd + '/' + newName : newName;
                    await Api.post('mv', { from: oldPath, to: newPath });
                    reload();
                    store.selectedItems = [];
                } catch (e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
            }
        };

        const uploadFile = () => {
            const input = document.createElement('input');
            input.type = 'file';
            input.onchange = async e => {
                const file = e.target.files[0];
                if (!file) return;
                const fd = new FormData();
                fd.append('file', file);
                fd.append('path', store.cwd);
                try {
                    store.isLoading = true;
                    await fetch(baseUrl + 'api/upload', { method: 'POST', body: fd });
                    reload();
                } catch (e) { Swal.fire(i18n.t('error'), 'Upload failed', 'error'); }
                finally { store.isLoading = false; }
            };
            input.click();
        };

        const downloadSelected = () => {
            if (store.selectedItems.length !== 1) return;
            window.location.href = baseUrl + 'api/download?path=' + encodeURIComponent(store.selectedItems[0].path);
        };

        const createArchive = async () => {
            if (!store.selectedItems.length) return;
            const { value: name } = await Swal.fire({ title: i18n.t('archive'), input: 'text', inputValue: 'archive.zip', showCancelButton: true });
            if (name) {
                try {
                    const paths = store.selectedItems.map(f => f.path);
                    await Api.post('archive', { paths, name, cwd: store.cwd });
                    reload();
                } catch (e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
            }
        };

        const extractArchive = async () => {
            if (store.selectedItems.length !== 1) return;
            try {
                await Api.post('extract', { path: store.selectedItems[0].path, cwd: store.cwd });
                reload();
            } catch (e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
        };

        const chmodSelected = async () => {
            if (store.selectedItems.length !== 1) return;
            const file = store.selectedItems[0];
            const { value: mode } = await Swal.fire({ title: i18n.t('perms'), input: 'text', inputValue: file.perms || '755', showCancelButton: true });
            if (mode) {
                try {
                    await Api.post('chmod', { path: file.path, mode });
                    reload();
                } catch (e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
            }
        };

        // Image Viewer
        const updateImageViewer = () => {
            if (imageViewer.index < 0) return;
            const file = imageViewer.list[imageViewer.index];
            imageViewer.src = baseUrl + 'api/download?path=' + encodeURIComponent(file.path) + '&inline=1';
        };
        const nextImage = () => { if (imageViewer.index < imageViewer.list.length - 1) { imageViewer.index++; updateImageViewer(); } };
        const prevImage = () => { if (imageViewer.index > 0) { imageViewer.index--; updateImageViewer(); } };

        // Drag & Drop
        const onDragStart = (e, file) => { e.dataTransfer.setData('text/plain', JSON.stringify(file)); e.dataTransfer.effectAllowed = 'move'; };
        const onDragOver = (e) => { e.preventDefault(); e.dataTransfer.dropEffect = 'move'; };
        const onDrop = async (e, target) => {
            e.preventDefault();
            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                const fd = new FormData();
                fd.append('file', e.dataTransfer.files[0]);
                fd.append('path', target ? target.path : store.cwd);
                try {
                    store.isLoading = true;
                    await fetch(baseUrl + 'api/upload', { method: 'POST', body: fd });
                    reload();
                } finally { store.isLoading = false; }
                return;
            }
            try {
                const source = JSON.parse(e.dataTransfer.getData('text/plain'));
                if (!target || source.path === target.path) return;
                await Api.post('mv', { from: source.path, to: target.path + '/' + source.name });
                reload();
            } catch (e) {}
        };

        const setSort = (key) => {
            if (store.sortBy === key) store.sortDesc = !store.sortDesc;
            else { store.sortBy = key; store.sortDesc = false; }
        };

        // Context Menu
        const showContextMenu = (e, file) => {
            contextMenu.visible = true; contextMenu.x = e.clientX; contextMenu.y = e.clientY; contextMenu.file = file;
            store.selectedItems = [file];
        };
        const hideContextMenu = () => contextMenu.visible = false;
        const cmAction = (action) => {
            hideContextMenu();
            const file = contextMenu.file;
            if (!file) return;
            switch(action) {
                case 'open': open(file); break;
                case 'download': downloadSelected(); break;
                case 'rename': renameSelected(); break;
                case 'perms': chmodSelected(); break;
                case 'delete': deleteSelected(); break;
            }
        };

        // Initial Load
        onMounted(async () => {
            document.documentElement.setAttribute('data-bs-theme', theme.value);
            await i18n.load('en');
            store.loadPath('');
            ace.config.set('basePath', baseUrl + 'assets/vendor/ace');
            editorModal = new bootstrap.Modal(document.getElementById('editorModal'));
            imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
            document.getElementById('editorModal').addEventListener('shown.bs.modal', () => {
                if (!aceEditor) {
                    aceEditor = ace.edit("aceEditor");
                    aceEditor.setTheme("ace/theme/monokai");
                }
                aceEditor.resize();
            });
        });

        return {
            store, t: (k, p) => i18n.t(k, p),
            goUp, reload, open, saveFile, getIcon, formatSize, formatDate, containerClass, filteredFiles,
            isAdmin, openAdmin, changePassword, theme, toggleTheme, userAdmin,
            contextMenu, showContextMenu, hideContextMenu, cmAction,
            imageViewer, nextImage, prevImage,
            onDragStart, onDragOver, onDrop,
            setSort, isImage, getThumbUrl,
            createFolder, deleteSelected, renameSelected, uploadFile, downloadSelected, createArchive, extractArchive, chmodSelected,
            editorFile
        };
    }
});

app.mount('#app');