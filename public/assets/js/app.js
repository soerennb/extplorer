const { createApp, computed, onMounted, ref } = Vue;

const app = createApp({
    components: {
        FileTree,
        UserAdmin
    },
    setup() {
        const editorFile = ref(null);
        const propFile = ref(null);
        const userAdmin = ref(null); 
        const contextMenu = Vue.reactive({ visible: false, x: 0, y: 0, file: null });
        const imageViewer = Vue.reactive({ src: '', index: 0, list: [] });
        const theme = ref(localStorage.getItem('extplorer_theme') || 'auto');
        let aceEditor = null;
        let editorModal = null;
        let imageModal = null;
        let diffModal = null;
        let propModal = null;
        let webdavModal = null;
// ... (applyTheme method)
        const applyTheme = () => {
            let t = theme.value;
            if (t === 'auto') {
                t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-bs-theme', t);
            if (aceEditor) {
                aceEditor.setTheme(t === 'dark' ? "ace/theme/monokai" : "ace/theme/chrome");
            }
        };

        const setTheme = (t) => {
            theme.value = t;
            localStorage.setItem('extplorer_theme', t);
            applyTheme();
        };

        const toggleTheme = () => {
            const next = { 'auto': 'light', 'light': 'dark', 'dark': 'auto' };
            setTheme(next[theme.value]);
        };

        const isAdmin = computed(() => {
            const role = String(window.userRole || '').toLowerCase();
            const user = String(window.username || '').toLowerCase();
            const perms = window.userPermissions || [];
            
            return role === 'admin' || 
                   user === 'admin' || 
                   perms.includes('*') || 
                   perms.includes('admin_users');
        });

        const webDavUrl = computed(() => {
            return window.baseUrl.replace(/\/$/, '') + '/dav';
        });

        // Helpers
        const isImage = (file) => {
            if (file.type === 'dir') return false;
            const ext = file.extension ? file.extension.toLowerCase() : '';
            return ['jpg','jpeg','png','gif','webp','svg'].includes(ext);
        };

        const isArchive = (file) => {
            if (file.type === 'dir') return false;
            const ext = file.extension ? file.extension.toLowerCase() : '';
            return ['zip','tar','gz'].includes(ext);
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

        const changePage = (delta) => {
            const newPage = store.pagination.page + delta;
            if (newPage > 0 && newPage <= Math.ceil(store.pagination.total / store.pagination.pageSize)) {
                store.loadPath(store.cwd, newPage);
            }
        };

        const open = async (file) => {
            if (file.type === 'dir') {
                store.loadPath(file.path);
            } else {
                const ext = file.extension ? file.extension.toLowerCase() : '';
                const textExts = ['php','js','css','html','json','xml','txt','md','sql','gitignore','env'];
                
                if (textExts.includes(ext)) {
                    try {
                        const res = await Api.get('content', { path: file.path });
                        store.addToRecent(file);
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
                    store.addToRecent(file);
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
                    await Api.put('profile/password', { password });
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
                        await Api.post('rm', { path: f.path });
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
                    const oldPath = file.path;
                    const newPath = (store.cwd ? store.cwd + '/' : '') + newName;
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

                const CHUNK_SIZE = 1024 * 1024; // 1MB
                const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
                
                try {
                    store.isLoading = true;
                    
                    if (file.size <= CHUNK_SIZE) {
                        // Standard upload for small files
                        const fd = new FormData();
                        fd.append('file', file);
                        fd.append('path', store.cwd);
                        const headers = { 'X-Requested-With': 'XMLHttpRequest' };
                        if (window.csrfHash) headers['X-CSRF-TOKEN'] = window.csrfHash;
                        await fetch(window.baseUrl + 'api/upload', { method: 'POST', headers: headers, body: fd });
                    } else {
                        // Chunked upload
                        for (let i = 0; i < totalChunks; i++) {
                            const chunk = file.slice(i * CHUNK_SIZE, (i + 1) * CHUNK_SIZE);
                            const fd = new FormData();
                            fd.append('file', chunk);
                            fd.append('filename', file.name);
                            fd.append('chunkIndex', i);
                            fd.append('totalChunks', totalChunks);
                            fd.append('path', store.cwd);
                            
                            const headers = { 'X-Requested-With': 'XMLHttpRequest' };
                            if (window.csrfHash) headers['X-CSRF-TOKEN'] = window.csrfHash;
                            
                            await fetch(window.baseUrl + 'api/upload_chunk', { method: 'POST', headers: headers, body: fd });
                        }
                    }
                    reload();
                    Swal.fire(i18n.t('uploaded'), '', 'success');
                } catch (e) { 
                    Swal.fire(i18n.t('error'), 'Upload failed', 'error'); 
                } finally { 
                    store.isLoading = false; 
                }
            };
            input.click();
        };

        const copySelected = () => {
            if (!store.selectedItems.length) return;
            store.clipboard.items = [...store.selectedItems];
            store.clipboard.mode = 'copy';
        };

        const cutSelected = () => {
            if (!store.selectedItems.length) return;
            store.clipboard.items = [...store.selectedItems];
            store.clipboard.mode = 'cut';
        };

        const paste = async () => {
            if (!store.clipboard.items.length) return;
            try {
                store.isLoading = true;
                for (const file of store.clipboard.items) {
                    const to = (store.cwd ? store.cwd + '/' : '') + file.name;
                    const endpoint = store.clipboard.mode === 'copy' ? 'cp' : 'mv';
                    await Api.post(endpoint, { from: file.path, to: to });
                }
                if (store.clipboard.mode === 'cut') {
                    store.clipboard.items = [];
                    store.clipboard.mode = null;
                }
                reload();
                Swal.fire(i18n.t('success'), '', 'success');
            } catch (e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
            finally { store.isLoading = false; }
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
            if (!store.selectedItems.length) return;
            const firstFile = store.selectedItems[0];

            const { value: formValues } = await Swal.fire({
                title: i18n.t('perms'),
                html:
                    `<input id="swal-input1" class="swal2-input" placeholder="Octal Mode" value="${firstFile.perms || '755'}">` +
                    '<div class="form-check mt-3 text-start ms-5">' +
                    '  <input class="form-check-input" type="checkbox" id="swal-input2">' +
                    '  <label class="form-check-label" for="swal-input2">Apply Recursively</label>' +
                    '</div>',
                focusConfirm: false,
                preConfirm: () => {
                    return [
                        document.getElementById('swal-input1').value,
                        document.getElementById('swal-input2').checked
                    ]
                },
                showCancelButton: true
            });

            if (formValues) {
                const [mode, recursive] = formValues;
                if (!mode || !/^[0-7]{3,4}$/.test(mode)) {
                    Swal.fire(i18n.t('error'), 'Invalid octal mode!', 'error');
                    return;
                }
                try {
                    const paths = store.selectedItems.map(f => f.path);
                    await Api.post('chmod', { paths, mode, recursive });
                    reload();
                    store.selectedItems = [];
                    Swal.fire(i18n.t('saved'), '', 'success');
                } catch (e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
            }
        };

        const showProperties = () => {
            if (!store.selectedItems.length) return;
            if (store.selectedItems.length === 1) {
                propFile.value = JSON.parse(JSON.stringify(store.selectedItems[0])); // Clone
                propFile.value.isBulk = false;
            } else {
                propFile.value = {
                    name: store.selectedItems.length + ' items selected',
                    path: store.cwd,
                    size: store.selectedItems.reduce((acc, f) => acc + f.size, 0),
                    mime: 'multiple',
                    owner: '',
                    group: '',
                    isBulk: true,
                    paths: store.selectedItems.map(f => f.path)
                };
            }
            propFile.value.recursive = false;
            propModal.show();
        };

        const saveChown = async () => {
            if (!propFile.value) return;
            try {
                const data = {
                    user: propFile.value.owner,
                    group: propFile.value.group,
                    recursive: propFile.value.recursive
                };
                if (propFile.value.isBulk) {
                    data.paths = propFile.value.paths;
                } else {
                    data.path = propFile.value.path;
                }
                await Api.post('chown', data);
                reload();
                Swal.fire(i18n.t('saved'), '', 'success');
            } catch (e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
        };

        const calcDirSize = async () => {
            if (!propFile.value || propFile.value.type !== 'dir') return;
            try {
                const res = await Api.get('dirsize', { path: propFile.value.path });
                propFile.value.size = res.size;
            } catch (e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
        };

        const showWebDav = () => {
            webdavModal.show();
        };

        const copyWebDavUrl = () => {
            const input = document.getElementById('webdav_url_input');
            input.select();
            document.execCommand('copy');
            Swal.fire({ title: 'Copied!', icon: 'success', toast: true, position: 'top-end', timer: 3000, showConfirmButton: false });
        };

        const diffSelected = async () => {
            if (store.selectedItems.length !== 2) return;
            const [file1, file2] = store.selectedItems;
            try {
                store.isLoading = true;
                const [res1, res2] = await Promise.all([
                    Api.get('content', { path: file1.path }),
                    Api.get('content', { path: file2.path })
                ]);
                const udiff = Diff.createPatch(file1.name, res1.content, res2.content);
                const diffViewer = document.getElementById('diffViewer');
                const diff2htmlUi = new Diff2HtmlUI(diffViewer, udiff, {
                    drawFileList: true,
                    matching: 'lines',
                    outputFormat: 'side-by-side',
                });
                diff2htmlUi.draw();
                diffModal.show();
            } catch (e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
            finally { store.isLoading = false; }
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
                case 'copy': copySelected(); break;
                case 'cut': cutSelected(); break;
                case 'paste': paste(); break;
                case 'rename': renameSelected(); break;
                case 'perms': chmodSelected(); break;
                case 'properties': showProperties(); break;
                case 'delete': deleteSelected(); break;
            }
        };

        // Initial Load
        onMounted(async () => {
            applyTheme();
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                if (theme.value === 'auto') applyTheme();
            });
            await i18n.load('en');
            store.loadPath('');
            ace.config.set('basePath', window.baseUrl + 'assets/vendor/ace');
            editorModal = new bootstrap.Modal(document.getElementById('editorModal'));
            imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
            diffModal = new bootstrap.Modal(document.getElementById('diffModal'));
            propModal = new bootstrap.Modal(document.getElementById('propModal'));
            webdavModal = new bootstrap.Modal(document.getElementById('webdavModal'));
            
            // Shortcuts
            window.addEventListener('keydown', (e) => {
                const target = e.target;
                const isInput = target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable;
                
                // Editor specific
                if (editorFile.value && !isInput) {
                    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                        e.preventDefault();
                        saveFile();
                    }
                    return;
                }

                if (isInput) return;

                // Global shortcuts
                if (e.key === 'Delete') {
                    e.preventDefault();
                    deleteSelected();
                } else if (e.key === 'F2') {
                    e.preventDefault();
                    renameSelected();
                } else if (e.key === 'Enter') {
                    if (store.selectedItems.length === 1) {
                        e.preventDefault();
                        open(store.selectedItems[0]);
                    }
                } else if (e.key === 'Escape') {
                    store.clearSelection();
                    hideContextMenu();
                } else if ((e.ctrlKey || e.metaKey) && e.key === 'a') {
                    e.preventDefault();
                    store.selectedItems = [...filteredFiles.value];
                }
            });

            document.getElementById('editorModal').addEventListener('shown.bs.modal', () => {
                if (!aceEditor) {
                    aceEditor = ace.edit("aceEditor");
                    aceEditor.setTheme("ace/theme/monokai");
                }
                aceEditor.resize();
            });
        });

        return {
            store, i18n, t: (k, p) => i18n.t(k, p),
            goUp, reload, changePage, open, saveFile, getIcon, formatSize, formatDate, containerClass, filteredFiles,
            isAdmin, openAdmin, changePassword, theme, setTheme, toggleTheme, userAdmin,
            contextMenu, showContextMenu, hideContextMenu, cmAction,
            imageViewer, nextImage, prevImage,
            showWebDav, copyWebDavUrl, webDavUrl,
            onDragStart, onDragOver, onDrop,
            setSort, isImage, isArchive, getThumbUrl,
            createFolder, deleteSelected, renameSelected, uploadFile, downloadSelected, createArchive, extractArchive, chmodSelected,
            diffSelected, copySelected, cutSelected, paste,
            editorFile, propFile, saveChown, calcDirSize,
            username: window.username
        };
    }
});

app.mount('#app');
