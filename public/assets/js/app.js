const { createApp, computed, onMounted, ref, watch } = Vue;

const app = createApp({
    components: {
        FileTree,
        UserAdmin,
        UserProfile,
        ShareModal
    },
    setup() {
        const editorFile = ref(null);
        const propFile = ref(null);
        const userAdmin = ref(null);
        const userProfile = ref(null);
        const shareModal = ref(null);
        const contextMenu = Vue.reactive({ visible: false, x: 0, y: 0, file: null });
        const imageViewer = Vue.reactive({ src: '', index: 0, list: [] });
        const theme = ref(localStorage.getItem('extplorer_theme') || 'auto');
        let aceEditor = null;
        let editorModal = null;
        let imageModal = null;
        let diffModal = null;
        let propModal = null;
        let webdavModal = null;

        // --- Watchers ---
        watch(() => store.cwd, (newCwd) => {
            document.title = 'eXtplorer' + (newCwd ? ' - /' + newCwd : '');
        }, { immediate: true });

        // --- Computed ---
        const isAdmin = computed(() => {
            const role = String(window.userRole || '').toLowerCase();
            const user = String(window.username || '').toLowerCase();
            const perms = window.userPermissions || [];
            return role === 'admin' || user === 'admin' || perms.includes('*') || perms.includes('admin_users');
        });

        const webDavUrl = computed(() => window.baseUrl.replace(/\/$/, '') + '/dav');

        const filteredFiles = computed(() => {
            let result = store.isTrashMode ? store.trashItems : store.files;
            
            if (store.searchQuery && !store.isTrashMode) {
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

        const containerClass = computed(() => store.viewMode === 'grid' ? 'grid-view' : 'list-view');

        // --- Helpers ---
        const isImage = (f) => f.type !== 'dir' && ['jpg','jpeg','png','gif','webp','svg'].includes(f.extension?.toLowerCase());
        const isArchive = (f) => f.type !== 'dir' && ['zip','tar','gz'].includes(f.extension?.toLowerCase());
        const getThumbUrl = (f) => window.baseUrl + 'api/thumb?path=' + encodeURIComponent(f.path);
        const formatSize = (b) => {
            if (b === 0 || !b) return b === 0 ? '' : '-';
            const k = 1024, sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(b) / Math.log(k));
            return parseFloat((b / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        };
        const formatDate = (t) => t ? new Date(t * 1000).toLocaleString() : '-';
        const getIcon = (f) => {
            if (f.type === 'dir') return 'ri-folder-fill text-warning';
            const ext = f.extension?.toLowerCase();
            if (['jpg','jpeg','png','gif','svg','webp'].includes(ext)) return 'ri-image-fill text-success';
            if (['mp3','wav','ogg'].includes(ext)) return 'ri-music-fill text-info';
            if (['mp4','webm','mov'].includes(ext)) return 'ri-movie-fill text-info';
            if (['pdf'].includes(ext)) return 'ri-file-pdf-line text-danger';
            if (['zip','tar','gz','rar'].includes(ext)) return 'ri-file-zip-line text-warning';
            if (['php','js','css','html','json','sql'].includes(ext)) return 'ri-code-s-slash-line text-primary';
            return 'ri-file-line text-secondary';
        };

        // --- UI & Theme ---
        const applyTheme = () => {
            let t = theme.value;
            if (t === 'auto') t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-bs-theme', t);
            if (aceEditor) aceEditor.setTheme(t === 'dark' ? "ace/theme/monokai" : "ace/theme/chrome");
        };
        const setTheme = (t) => { theme.value = t; localStorage.setItem('extplorer_theme', t); applyTheme(); };
        const toggleTheme = () => { const next = { 'auto': 'light', 'light': 'dark', 'dark': 'auto' }; setTheme(next[theme.value]); };

        // --- Trash Actions ---
        const toggleTrash = () => {
            if (store.isTrashMode) {
                store.exitTrash();
            } else {
                store.loadTrash();
                closeOffcanvas();
            }
        };

        const restoreSelected = async () => {
            if (!store.selectedItems.length) return;
            try {
                store.isLoading = true;
                for (const item of store.selectedItems) {
                    await Api.post('trash/restore', { id: item.id });
                }
                store.loadTrash();
                Swal.fire(i18n.t('restored') || 'Restored', '', 'success');
            } catch(e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
            finally { store.isLoading = false; }
        };

        const deletePermanent = async () => {
            if (!store.selectedItems.length) return;
            const res = await Swal.fire({ 
                title: i18n.t('confirm_permanent_delete') || 'Delete Permanently?', 
                text: i18n.t('cannot_undo') || 'This cannot be undone!', 
                icon: 'warning', 
                showCancelButton: true,
                confirmButtonColor: '#d33'
            });
            if (res.isConfirmed) {
                try {
                    store.isLoading = true;
                    for (const item of store.selectedItems) {
                        await Api.post('trash/delete', { id: item.id });
                    }
                    store.loadTrash();
                } catch(e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
                finally { store.isLoading = false; }
            }
        };

        const emptyTrash = async () => {
            const res = await Swal.fire({ 
                title: 'Empty Trash?', 
                text: 'All items will be permanently deleted.', 
                icon: 'warning', 
                showCancelButton: true,
                confirmButtonColor: '#d33'
            });
            if (res.isConfirmed) {
                try {
                    await Api.post('trash/empty', {});
                    store.loadTrash();
                } catch(e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
            }
        };

        // --- Core Actions ---
        const reload = () => { store.loadPath(store.cwd); store.refreshTree(); };
        const goUp = () => { if (store.cwd) { const p = store.cwd.split('/'); p.pop(); store.loadPath(p.join('/')); } };
        
        const closeOffcanvas = () => {
            const el = document.getElementById('sidebarOffcanvas');
            const instance = bootstrap.Offcanvas.getInstance(el);
            if (instance) instance.hide();
        };

        const handleItemClick = (e, file) => {
            if (e.ctrlKey || e.metaKey) {
                store.toggleSelection(file);
            } else {
                store.selectedItems = [file];
            }
            hideContextMenu();
        };

        const changePage = (d) => { const n = store.pagination.page + d; if (n > 0 && n <= Math.ceil(store.pagination.total / store.pagination.pageSize)) store.loadPath(store.cwd, n); };

        const open = async (file) => {
            if (file.type === 'dir') {
                store.loadPath(file.path);
                closeOffcanvas();
            } else {
                const ext = file.extension?.toLowerCase();
                const textExts = ['php','js','css','html','json','xml','txt','md','sql','gitignore','env'];
                if (textExts.includes(ext)) {
                    try {
                        const res = await Api.get('content', { path: file.path });
                        store.addToRecent(file);
                        editorFile.value = file;
                        editorModal.show();
                        const setContent = () => {
                            aceEditor.setValue(res.content, -1);
                            const modeMap = { 'php':'php', 'js':'javascript', 'html':'html', 'css':'css', 'json':'json', 'xml':'xml', 'md':'markdown', 'sql':'sql' };
                            aceEditor.session.setMode("ace/mode/" + (modeMap[ext] || 'text'));
                        };
                        if (aceEditor) setContent();
                        else document.getElementById('editorModal').addEventListener('shown.bs.modal', setContent, { once: true });
                    } catch(e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
                } else if (isImage(file)) {
                    store.addToRecent(file);
                    imageViewer.list = filteredFiles.value.filter(f => isImage(f));
                    imageViewer.index = imageViewer.list.findIndex(f => f.name === file.name);
                    updateImageViewer();
                    imageModal.show();
                    closeOffcanvas();
                } else Swal.fire('Info', 'Preview not supported for this file type.', 'info');
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

        // --- File Operations ---
        const createFolder = async () => {
            const { value: name } = await Swal.fire({ title: i18n.t('new_folder'), input: 'text', showCancelButton: true });
            if (name) {
                try { await Api.post('mkdir', { path: store.cwd ? store.cwd + '/' + name : name }); reload(); store.refreshTree(); }
                catch (e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
            }
        };

        const deleteSelected = async () => {
            if (!store.selectedItems.length) return;
            const res = await Swal.fire({ title: i18n.t('confirm_title'), text: i18n.t('confirm_text'), icon: 'warning', showCancelButton: true });
            if (res.isConfirmed) {
                try {
                    for (const f of store.selectedItems) await Api.post('rm', { path: f.path });
                    reload();
                    store.refreshTree();
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
                    await Api.post('mv', { from: file.path, to: (store.cwd ? store.cwd + '/' : '') + newName });
                    reload();
                    store.refreshTree();
                    store.selectedItems = [];
                } catch (e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
            }
        };

        const uploadFile = () => {
            const input = document.createElement('input'); input.type = 'file'; input.multiple = true;
            input.onchange = e => { if (e.target.files.length) performUpload(e.target.files); };
            input.click();
        };

        const performUpload = async (files, targetPath = null, silent = false) => {
            const path = targetPath !== null ? targetPath : store.cwd;
            const CHUNK_SIZE = 1024 * 1024;
            try {
                if (!silent) {
                    store.isLoading = true;
                    store.uploadTotal = files.length;
                    store.uploadCurrent = 0;
                }
                for (let file of files) {
                    store.uploadFileName = file.name;
                    store.uploadProgress = 0;
                    if (file.size <= CHUNK_SIZE) {
                        const fd = new FormData(); fd.append('file', file); fd.append('path', path);
                        const headers = { 'X-Requested-With': 'XMLHttpRequest' };
                        if (window.csrfHash) headers['X-CSRF-TOKEN'] = window.csrfHash;
                        const res = await fetch(window.baseUrl + 'api/upload', { method: 'POST', headers, body: fd });
                        if (!res.ok) {
                            const json = await res.json();
                            throw new Error(json.messages?.error || json.message || 'Upload failed');
                        }
                        store.uploadProgress = 100;
                    } else {
                        const total = Math.ceil(file.size / CHUNK_SIZE);
                        for (let i = 0; i < total; i++) {
                            const chunk = file.slice(i * CHUNK_SIZE, (i + 1) * CHUNK_SIZE);
                            const fd = new FormData(); fd.append('file', chunk); fd.append('filename', file.name);
                            fd.append('chunkIndex', i); fd.append('totalChunks', total); fd.append('path', path);
                            const headers = { 'X-Requested-With': 'XMLHttpRequest' };
                            if (window.csrfHash) headers['X-CSRF-TOKEN'] = window.csrfHash;
                            const res = await fetch(window.baseUrl + 'api/upload_chunk', { method: 'POST', headers, body: fd });
                            if (!res.ok) {
                                const json = await res.json();
                                throw new Error(json.messages?.error || json.message || 'Upload chunk failed');
                            }
                            store.uploadProgress = Math.round(((i + 1) / total) * 100);
                        }
                    }
                    store.uploadCurrent++;
                }
                if (!silent) {
                    reload();
                    store.refreshTree();
                    Swal.fire(i18n.t('uploaded'), '', 'success');
                }
            } catch (e) { if (!silent) Swal.fire(i18n.t('error'), e.message, 'error'); }
            finally { 
                if (!silent) {
                    store.isLoading = false; 
                    resetUploadProgress();
                }
            }
        };

        const resetUploadProgress = () => {
            setTimeout(() => {
                store.uploadProgress = 0;
                store.uploadCurrent = 0;
                store.uploadTotal = 0;
                store.uploadFileName = '';
            }, 1000);
        };

        const copySelected = () => { if (store.selectedItems.length) { store.clipboard.items = [...store.selectedItems]; store.clipboard.mode = 'copy'; } };
        const cutSelected = () => { if (store.selectedItems.length) { store.clipboard.items = [...store.selectedItems]; store.clipboard.mode = 'cut'; } };
        const paste = async () => {
            if (!store.clipboard.items.length) return;
            try {
                store.isLoading = true;
                for (const file of store.clipboard.items) {
                    const to = (store.cwd ? store.cwd + '/' : '') + file.name;
                    await Api.post(store.clipboard.mode === 'copy' ? 'cp' : 'mv', { from: file.path, to });
                }
                if (store.clipboard.mode === 'cut') { store.clipboard.items = []; store.clipboard.mode = null; }
                reload();
                store.refreshTree();
                Swal.fire(i18n.t('success'), '', 'success');
            } catch (e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
            finally { store.isLoading = false; }
        };

        const downloadSelected = () => {
            if (store.selectedItems.length === 1) window.location.href = window.baseUrl + 'api/download?path=' + encodeURIComponent(store.selectedItems[0].path);
        };

        const createArchive = async () => {
            if (!store.selectedItems.length) return;
            const { value: name } = await Swal.fire({ title: i18n.t('archive'), input: 'text', inputValue: 'archive.zip', showCancelButton: true });
            if (name) {
                try { await Api.post('archive', { paths: store.selectedItems.map(f => f.path), name, cwd: store.cwd }); reload(); store.refreshTree(); }
                catch (e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
            }
        };

        const extractArchive = async () => {
            if (store.selectedItems.length === 1) {
                try { await Api.post('extract', { path: store.selectedItems[0].path, cwd: store.cwd }); reload(); store.refreshTree(); }
                catch (e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
            }
        };

        const chmodSelected = async () => {
            if (!store.selectedItems.length) return;
            const { value: form } = await Swal.fire({
                title: i18n.t('perms'),
                html: `<input id="swal-i1" class="swal2-input" placeholder="Octal Mode" value="${store.selectedItems[0].perms || '755'}">` +
                      '<div class="form-check mt-3 text-start ms-5"><input class="form-check-input" type="checkbox" id="swal-i2"><label class="form-check-label" for="swal-i2">Apply Recursively</label></div>',
                focusConfirm: false, preConfirm: () => [document.getElementById('swal-i1').value, document.getElementById('swal-i2').checked], showCancelButton: true
            });
            if (form) {
                const [mode, recursive] = form;
                if (!mode || !/^[0-7]{3,4}$/.test(mode)) return Swal.fire(i18n.t('error'), 'Invalid octal mode!', 'error');
                try { await Api.post('chmod', { paths: store.selectedItems.map(f => f.path), mode, recursive }); reload(); store.selectedItems = []; Swal.fire(i18n.t('saved'), '', 'success'); }
                catch (e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
            }
        };

        const showProperties = () => {
            if (!store.selectedItems.length) return;
            if (store.selectedItems.length === 1) { propFile.value = { ...store.selectedItems[0], isBulk: false }; }
            else { propFile.value = { name: store.selectedItems.length + ' items selected', path: store.cwd, size: store.selectedItems.reduce((a, f) => a + f.size, 0), mime: 'multiple', owner: '', group: '', isBulk: true, paths: store.selectedItems.map(f => f.path) }; }
            propFile.value.recursive = false;
            propModal.show();
        };

        const saveChown = async () => {
            if (!propFile.value) return;
            try {
                const data = { user: propFile.value.owner, group: propFile.value.group, recursive: propFile.value.recursive };
                if (propFile.value.isBulk) data.paths = propFile.value.paths; else data.path = propFile.value.path;
                await Api.post('chown', data); reload(); Swal.fire(i18n.t('saved'), '', 'success');
            } catch (e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
        };

        const openShare = () => {
            if (store.selectedItems.length === 1 && shareModal.value) {
                shareModal.value.open(store.selectedItems[0]);
            }
        };

        const calcDirSize = async () => {
            if (!propFile.value || propFile.value.type !== 'dir') return;
            try { const res = await Api.get('dirsize', { path: propFile.value.path }); propFile.value.size = res.size; }
            catch (e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
        };

        const diffSelected = async () => {
            if (store.selectedItems.length !== 2) return;
            try {
                store.isLoading = true;
                const [r1, r2] = await Promise.all([Api.get('content', { path: store.selectedItems[0].path }), Api.get('content', { path: store.selectedItems[1].path })]);
                const udiff = Diff.createPatch(store.selectedItems[0].name, r1.content, r2.content);
                new Diff2HtmlUI(document.getElementById('diffViewer'), udiff, { drawFileList: true, matching: 'lines', outputFormat: 'side-by-side' }).draw();
                diffModal.show();
            } catch (e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
            finally { store.isLoading = false; }
        };

        // --- Remote/Auth ---
        const openAdmin = () => { if (userAdmin.value) userAdmin.value.open(); };
        const openProfile = () => { if (userProfile.value) userProfile.value.open(); };
        const showWebDav = () => webdavModal.show();
        const copyWebDavUrl = () => {
            const i = document.getElementById('webdav_url_input'); i.select(); document.execCommand('copy');
            Swal.fire({ title: 'Copied!', icon: 'success', toast: true, position: 'top-end', timer: 3000, showConfirmButton: false });
        };
        const changePassword = () => openProfile();

        // --- Image Viewer ---
        const updateImageViewer = () => {
            if (imageViewer.index >= 0) imageViewer.src = window.baseUrl + 'api/download?path=' + encodeURIComponent(imageViewer.list[imageViewer.index].path) + '&inline=1';
        };
        const nextImage = () => { if (imageViewer.index < imageViewer.list.length - 1) { imageViewer.index++; updateImageViewer(); } };
        const prevImage = () => { if (imageViewer.index > 0) { imageViewer.index--; updateImageViewer(); } };

        // Drag & Drop
        const onDragStart = (e, f) => {
            const items = store.isSelected(f) ? store.selectedItems : [f];
            e.dataTransfer.setData('text/plain', JSON.stringify(items));
            e.dataTransfer.effectAllowed = 'move';
        };

        const onDragOver = (e, file = null) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            if (file && file.type === 'dir') {
                file.isDragOver = true;
            } else if (!file) {
                store.isDraggingOver = true;
            }
        };

        const onDragLeave = (file = null) => {
            if (file) file.isDragOver = false;
            else store.isDraggingOver = false;
        };

        const traverseFileTree = async (item, path) => {
            if (item.isFile) {
                const file = await new Promise(resolve => item.file(resolve));
                await performUpload([file], path, true);
            } else if (item.isDirectory) {
                const newPath = (path ? path + '/' : '') + item.name;
                try { await Api.post('mkdir', { path: newPath }); } catch(e) {} // Ignore if exists
                const dirReader = item.createReader();
                const entries = await new Promise(resolve => dirReader.readEntries(resolve));
                for (let entry of entries) {
                    await traverseFileTree(entry, newPath);
                }
            }
        };

        const countEntries = async (item) => {
            if (item.isFile) return 1;
            if (item.isDirectory) {
                let count = 0;
                const dirReader = item.createReader();
                const entries = await new Promise(resolve => dirReader.readEntries(resolve));
                for (let entry of entries) count += await countEntries(entry);
                return count;
            }
            return 0;
        };

        const onDrop = async (e, target) => {
            e.preventDefault();
            store.isDraggingOver = false;
            if (target) target.isDragOver = false;

            // 1. External Files/Folders (Upload)
            const items = e.dataTransfer.items;
            if (items && items.length > 0 && items[0].kind === 'file') {
                const targetPath = target ? target.path : store.cwd;
                store.isLoading = true;
                store.uploadProgress = 0;
                store.uploadCurrent = 0;
                store.uploadTotal = 0;

                // Scan for total items first
                for (let i = 0; i < items.length; i++) {
                    const entry = items[i].webkitGetAsEntry();
                    if (entry) store.uploadTotal += await countEntries(entry);
                }

                for (let i = 0; i < items.length; i++) {
                    const entry = items[i].webkitGetAsEntry();
                    if (entry) await traverseFileTree(entry, targetPath);
                }
                store.isLoading = false;
                resetUploadProgress();
                reload();
                store.refreshTree();
                Swal.fire(i18n.t('uploaded'), '', 'success');
                return;
            }

            // 2. Internal Move
            try {
                const s = e.dataTransfer.getData('text/plain');
                if (!s) return;
                const items = JSON.parse(s);
                const list = Array.isArray(items) ? items : [items];
                if (!target) return;

                for (let item of list) {
                    if (item.path === target.path) continue;
                    await Api.post('mv', { from: item.path, to: target.path + '/' + item.name });
                }
                reload();
                store.selectedItems = [];
            } catch (e) { console.error("Drop error", e); }
        };

        const setSort = (k) => { if (store.sortBy === k) store.sortDesc = !store.sortDesc; else { store.sortBy = k; store.sortDesc = false; } };

                

                // Touch Support

                let touchTimer = null;

                const handleTouchStart = (e, file) => {

                    touchTimer = setTimeout(() => {

                        const touch = e.touches[0];

                        showContextMenu({ clientX: touch.clientX, clientY: touch.clientY }, file);

                        touchTimer = null;

                    }, 600); // 600ms for long press

                };

                const handleTouchEnd = () => {

                    if (touchTimer) {

                        clearTimeout(touchTimer);

                        touchTimer = null;

                    }

                };

        

                const showContextMenu = (e, f) => { contextMenu.visible = true; contextMenu.x = e.clientX; contextMenu.y = e.clientY; contextMenu.file = f; store.selectedItems = [f]; };
        const hideContextMenu = () => contextMenu.visible = false;
        const cmAction = (a) => {
            hideContextMenu(); const f = contextMenu.file; if (!f) return;
            const actions = { 
                'open':()=>open(f), 'download':downloadSelected, 'copy':copySelected, 'cut':cutSelected, 'paste':paste, 
                'rename':renameSelected, 'perms':chmodSelected, 'properties':showProperties, 'delete':deleteSelected,
                'restore': restoreSelected, 'delete_perm': deletePermanent,
                'share': openShare
            };
            if (actions[a]) actions[a]();
        };

        // Initial Load
        onMounted(async () => {
            applyTheme();
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => { if (theme.value === 'auto') applyTheme(); });
            await i18n.load('en');
            
            const lastPath = localStorage.getItem('extplorer_last_path') || '';
            store.loadPath(lastPath);
            
            ace.config.set('basePath', window.baseUrl + 'assets/vendor/ace');
            editorModal = new bootstrap.Modal(document.getElementById('editorModal'));
            imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
            diffModal = new bootstrap.Modal(document.getElementById('diffModal'));
            propModal = new bootstrap.Modal(document.getElementById('propModal'));
            webdavModal = new bootstrap.Modal(document.getElementById('webdavModal'));
            
            window.addEventListener('keydown', (e) => {
                const t = e.target, isI = t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.isContentEditable;
                if (editorFile.value && !isI) { if ((e.ctrlKey || e.metaKey) && e.key === 's') { e.preventDefault(); saveFile(); } return; }
                if (isI) return;
                if (e.key === 'Delete') { e.preventDefault(); deleteSelected(); }
                else if (e.key === 'F2') { e.preventDefault(); renameSelected(); }
                else if (e.key === 'Enter' && store.selectedItems.length === 1) { e.preventDefault(); open(store.selectedItems[0]); }
                else if (e.key === 'Escape') { store.clearSelection(); hideContextMenu(); }
                else if ((e.ctrlKey || e.metaKey) && e.key === 'a') { e.preventDefault(); store.selectedItems = [...filteredFiles.value]; }
            });

            document.getElementById('editorModal').addEventListener('shown.bs.modal', () => {
                if (!aceEditor) { aceEditor = ace.edit("aceEditor"); aceEditor.setTheme(document.documentElement.getAttribute('data-bs-theme') === 'dark' ? "ace/theme/monokai" : "ace/theme/chrome"); }
                aceEditor.resize();
            });
        });

        return {
            store, i18n, t: (k, p) => i18n.t(k, p),
            goUp, reload, closeOffcanvas, handleItemClick, handleTouchStart, handleTouchEnd, changePage, open, saveFile, getIcon, formatSize, formatDate, containerClass, filteredFiles,
            isAdmin, openAdmin, changePassword, theme, setTheme, toggleTheme, userAdmin, userProfile, openProfile, shareModal,
            contextMenu, showContextMenu, hideContextMenu, cmAction,
            imageViewer, nextImage, prevImage, showWebDav, copyWebDavUrl, webDavUrl,
            onDragStart, onDragOver, onDragLeave, onDrop,
            setSort, isImage, isArchive, getThumbUrl,
            createFolder, deleteSelected, renameSelected, uploadFile, downloadSelected, createArchive, extractArchive, chmodSelected,
            showProperties, diffSelected, copySelected, cutSelected, paste,
            editorFile, propFile, saveChown, calcDirSize, 
            toggleTrash, restoreSelected, deletePermanent, emptyTrash,
            username: window.username,
            connectionMode: window.connectionMode,
            appVersion: window.appVersion
        };
    }
});

app.mount('#app');