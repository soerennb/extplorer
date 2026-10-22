const { createApp, computed, onMounted, ref, watch, nextTick } = Vue;

const app = createApp({
    render: appTemplateRender,
    components: {
        FileTree,
        UserAdmin,
        UserProfile,
        ShareModal,
        UploadModal,
        FileHistoryModal,
        TransferModal
    },
    setup() {
        const editorFile = ref(null);
        const propFile = ref(null);
        const userAdmin = ref(null);
        const userProfile = ref(null);
        const shareModal = ref(null);
        const uploadModal = ref(null);
        const fileHistoryModal = ref(null);
        const transferModal = ref(null);
        const transferCapability = ref(null);
        const transferCapabilityLoading = ref(true);
        const commandPaletteOpen = ref(false);
        const commandPaletteQuery = ref('');
        const commandPaletteInput = ref(null);
        const commandPaletteSelectedIndex = ref(0);
        const detailsPaneOpen = ref(localStorage.getItem('extplorer_details_pane') === 'true');
        const contextMenu = Vue.reactive({ visible: false, x: 0, y: 0, file: null });
        const theme = ref(localStorage.getItem('extplorer_theme') || 'auto');

        let aceEditor = null;
        let editorModal = null;
        let diffModal = null;
        let propModal = null;
        let webdavModal = null;

        // --- Watchers ---
        watch(() => store.cwd, (newCwd) => {
            document.title = 'eXtplorer' + (newCwd ? ' - /' + newCwd : '');
        }, { immediate: true });

        // --- Computed ---
        const supports = (file, capability) => Boolean(file) && file.capabilities?.[capability] !== false;
        const isAdmin = computed(() => {
            const role = String(window.userRole || '').toLowerCase();
            const user = String(window.username || '').toLowerCase();
            const perms = window.userPermissions || [];
            return role === 'admin' || user === 'admin' || perms.includes('*') || perms.includes('admin_users') || perms.includes('admin_settings');
        });

        const webDavUrl = computed(() => Api.route('dav'));
        const propertiesTargetSummary = computed(() => {
            if (!propFile.value) return '';
            if (propFile.value.isBulk) {
                const count = Array.isArray(propFile.value.paths) ? propFile.value.paths.length : 0;
                return i18n.t('target_selected_items', { count });
            }
            return '/' + (propFile.value.path || '');
        });

        const filteredFiles = computed(() => {
            const sortItems = (items) => {
                const key = store.sortBy;
                const mult = store.sortDesc ? -1 : 1;
                return [...items].sort((a, b) => {
                    if (a.type !== b.type) return a.type === 'dir' ? -1 : 1;
                    let valA = a[key];
                    let valB = b[key];
                    if (key === 'name') return valA.localeCompare(valB) * mult;
                    return (valA - valB) * mult;
                });
            };

            if (store.isTrashMode) {
                return sortItems(store.trashItems);
            }

            if (store.searchQuery) {
                const lower = store.searchQuery.toLowerCase();
                const result = store.files.filter(f => f.name.toLowerCase().includes(lower));
                return sortItems(result);
            }

            return store.files;
        });

        const { previewState, openPreview, nextPreview, prevPreview, isPreviewable } = useFilePreview(() => filteredFiles.value);

        const totalPages = computed(() => Math.max(1, Math.ceil(store.pagination.total / store.pagination.pageSize)));
        const commandActions = computed(() => {
            const hasSelection = store.selectedItems.length > 0;
            const hasSingleSelection = store.selectedItems.length === 1;
            const hasClipboard = store.clipboard && store.clipboard.items && store.clipboard.items.length > 0;
            const unavailable = i18n.t('command_unavailable');
            return [
                { id: 'search', label: i18n.t('command_open_search'), icon: 'ri-search-line', enabled: true, action: focusSearch },
                { id: 'upload', label: i18n.t('upload'), icon: 'ri-upload-cloud-2-line', enabled: !store.isLoading && store.capabilities.upload !== false, action: uploadFile },
                { id: 'new-folder', label: i18n.t('new_folder'), icon: 'ri-folder-add-line', enabled: !store.isLoading && !store.isTrashMode, reason: unavailable, action: createFolder },
                { id: 'send-files', label: i18n.t('send_files'), icon: 'ri-send-plane-line', enabled: transferAvailable.value === true, reason: i18n.t('transfer_email_test_required'), action: openTransfer },
                { id: 'share', label: i18n.t('share'), icon: 'ri-share-line', enabled: hasSingleSelection && !store.isTrashMode && supports(store.selectedItems[0], 'share'), reason: i18n.t('command_requires_one_selection'), action: openShare },
                { id: 'properties', label: i18n.t('properties'), icon: 'ri-information-line', enabled: hasSelection, reason: i18n.t('command_requires_selection'), action: showProperties },
                { id: 'perms', label: i18n.t('perms'), icon: 'ri-lock-2-line', enabled: hasSelection && !store.isTrashMode, reason: i18n.t('command_requires_selection'), action: chmodSelected },
                { id: 'download', label: i18n.t('download'), icon: 'ri-download-line', enabled: hasSingleSelection && !store.isTrashMode, reason: i18n.t('command_requires_one_selection'), action: downloadSelected },
                { id: 'copy', label: i18n.t('copy'), icon: 'ri-file-copy-line', enabled: hasSelection && !store.isTrashMode, reason: i18n.t('command_requires_selection'), action: copySelected },
                { id: 'paste', label: i18n.t('paste'), icon: 'ri-clipboard-line', enabled: hasClipboard && !store.isLoading && !store.isTrashMode, reason: unavailable, action: paste },
                { id: 'go-up', label: i18n.t('up_one_level'), icon: 'ri-arrow-up-line', enabled: Boolean(store.cwd), reason: unavailable, action: goUp },
                { id: 'refresh', label: i18n.t('refresh'), icon: 'ri-refresh-line', enabled: true, action: reload },
                { id: 'details', label: i18n.t('command_toggle_details'), icon: 'ri-sidebar-right-line', enabled: true, action: toggleDetailsPane },
                { id: 'trash', label: i18n.t('command_toggle_trash'), icon: 'ri-delete-bin-7-line', enabled: true, action: toggleTrash },
                { id: 'grid', label: i18n.t('command_grid_view'), icon: 'ri-grid-fill', enabled: store.viewMode !== 'grid', reason: unavailable, action: () => store.toggleViewMode('grid') },
                { id: 'list', label: i18n.t('command_list_view'), icon: 'ri-list-check', enabled: store.viewMode !== 'list', reason: unavailable, action: () => store.toggleViewMode('list') },
                { id: 'theme', label: i18n.t('command_toggle_theme'), icon: 'ri-contrast-2-line', enabled: true, action: toggleTheme },
                { id: 'profile', label: i18n.t('command_open_profile'), icon: 'ri-user-settings-line', enabled: true, action: openProfile },
                { id: 'quick-admin', label: i18n.t('command_open_quick_admin'), icon: 'ri-flashlight-line', enabled: isAdmin.value, reason: unavailable, action: openAdmin },
                { id: 'admin-console', label: i18n.t('command_open_admin_console'), icon: 'ri-settings-3-line', enabled: isAdmin.value, reason: unavailable, action: () => { window.location.href = Api.route('admin'); } }
            ];
        });
        const filteredCommandActions = computed(() => {
            const q = commandPaletteQuery.value.trim().toLowerCase();
            const commands = q
                ? commandActions.value.filter((command) => command.label.toLowerCase().includes(q))
                : commandActions.value;
            return commands;
        });
        const paginationPages = computed(() => {
            const total = totalPages.value;
            const current = store.pagination.page;
            if (total <= 1) return [];

            const isMobile = store.isMobile;
            const windowSize = isMobile ? 0 : 1;
            const pages = [];
            const addPage = (n) => pages.push({ type: 'page', number: n, key: `p${n}` });
            const addEllipsis = () => pages.push({ type: 'ellipsis', key: `e${pages.length}` });

            if (total <= (isMobile ? 3 : 5)) {
                for (let i = 1; i <= total; i++) addPage(i);
                return pages;
            }

            addPage(1);
            const start = Math.max(2, current - windowSize);
            const end = Math.min(total - 1, current + windowSize);
            if (start > 2) addEllipsis();
            for (let i = start; i <= end; i++) addPage(i);
            if (end < total - 1) addEllipsis();
            addPage(total);

            return pages;
        });

        const containerClass = computed(() => store.viewMode === 'grid' ? 'grid-view' : 'list-view');
        const breadcrumbs = computed(() => {
            if (!store.cwd) return [];
            const parts = store.cwd.split('/').filter(Boolean);
            const crumbs = [];
            let current = '';
            for (const part of parts) {
                current = current ? `${current}/${part}` : part;
                crumbs.push({ name: part, path: current });
            }
            return crumbs;
        });
        const currentFolderBookmark = computed(() => {
            const path = store.cwd || '';
            const name = path ? path.split('/').filter(Boolean).pop() : '/';
            return { name, path, type: 'dir', mime: 'directory' };
        });
        const detailsItem = computed(() => {
            if (store.selectedItems.length === 1) return store.selectedItems[0];
            if (store.selectedItems.length > 1) {
                return {
                    name: i18n.t('target_selected_items', { count: store.selectedItems.length }),
                    path: store.cwd || '',
                    type: 'multiple',
                    mime: i18n.t('multiple_items'),
                    size: store.selectedItems.reduce((sum, item) => sum + (Number(item.size) || 0), 0),
                    mtime: null,
                    isBulk: true
                };
            }
            return null;
        });
        const detailsShareLabel = computed(() => {
            if (!detailsItem.value || detailsItem.value.isBulk) return '';
            return detailsItem.value.is_shared ? i18n.t('share_status_shared') : i18n.t('share_status_private');
        });

        const emptyStateIcon = computed(() => {
            if (store.isTrashMode) return 'ri-delete-bin-7-line';
            if (store.searchQuery) return 'ri-search-2-line';
            return 'ri-folder-add-line';
        });

        const emptyStateTitle = computed(() => {
            const getT = (k, def) => { const v = i18n.t(k); return v === k ? def : v; };
            if (store.isTrashMode) return getT('trash_empty', 'Trash is Empty');
            if (store.searchQuery) return getT('no_results', 'No Results Found');
            return getT('empty_dir', 'Empty Directory');
        });

        const emptyStateDescription = computed(() => {
            const getT = (k, def) => { const v = i18n.t(k); return v === k ? def : v; };
            if (store.isTrashMode) return getT('trash_empty_desc', 'Items moved to the trash will be deleted permanently after 30 days.');
            if (store.searchQuery) return getT('no_results_desc', 'Try adjusting your search terms to find what you are looking for.');
            return getT('empty_dir_desc', 'This folder is empty. Start by adding new content.');
        });

        // --- Helpers ---
        const isImage = (f) => supports(f, 'thumbnail') && f.type !== 'dir' && ['jpg','jpeg','png','gif','webp','svg'].includes(f.extension?.toLowerCase());
        const isArchive = (f) => f.type !== 'dir' && ['zip','tar','gz'].includes(f.extension?.toLowerCase());
        const getThumbUrl = (f) => (window.routeBaseUrl || window.baseUrl) + 'api/thumb?path=' + encodeURIComponent(f.path);
        const formatSize = (b) => {
            if (b === 0 || !b) return b === 0 ? '' : '-';
            const k = 1024, sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(b) / Math.log(k));
            return parseFloat((b / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        };
        const formatDate = (t) => t ? new Date(t * 1000).toLocaleString() : '-';
        const getIcon = (f) => {
            if (f.is_mount && f.is_external) {
                if (f.mount_type === 'ftp' || f.mount_type === 'ssh2') return 'ri-cloud-fill text-info';
                return 'ri-hard-drive-2-fill text-primary';
            }
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
        const reload = () => store.reload();
        const goUp = () => { if (!store.isLoading && store.cwd) { const p = store.cwd.split('/'); p.pop(); store.loadPath(p.join('/')); } };
        const toggleDetailsPane = () => {
            detailsPaneOpen.value = !detailsPaneOpen.value;
            localStorage.setItem('extplorer_details_pane', detailsPaneOpen.value ? 'true' : 'false');
        };
        const closeDetailsPane = () => {
            detailsPaneOpen.value = false;
            localStorage.setItem('extplorer_details_pane', 'false');
        };
        const toggleBookmark = (file) => {
            store.toggleBookmark(file);
        };
        const focusSearch = () => {
            const input = document.getElementById('navbarSearchInput');
            if (input) {
                input.focus();
                input.select();
            }
        };
        const openCommandPalette = () => {
            commandPaletteOpen.value = true;
            commandPaletteQuery.value = '';
            commandPaletteSelectedIndex.value = 0;
            nextTick(() => commandPaletteInput.value && commandPaletteInput.value.focus());
        };
        const closeCommandPalette = () => {
            commandPaletteOpen.value = false;
        };
        const moveCommandSelection = (delta) => {
            const total = filteredCommandActions.value.length;
            if (!total) return;
            commandPaletteSelectedIndex.value = (commandPaletteSelectedIndex.value + delta + total) % total;
        };
        const runCommand = (command) => {
            if (!command || !command.enabled) return;
            closeCommandPalette();
            command.action();
        };
        const runSelectedCommand = () => {
            const commands = filteredCommandActions.value;
            if (!commands.length) return;
            const preferred = commands[commandPaletteSelectedIndex.value];
            const command = preferred && preferred.enabled ? preferred : commands.find((item) => item.enabled);
            runCommand(command);
        };
        const goToPath = (path) => {
            store.loadPath(path);
        };
        
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

        const changePage = (d) => { const n = store.pagination.page + d; if (n > 0 && n <= totalPages.value) store.loadPath(store.cwd, n); };
        const goToPage = (n) => { if (n > 0 && n <= totalPages.value) store.loadPath(store.cwd, n); };
        const setPageSize = (event) => {
            const next = Number(event.target.value) || store.pagination.pageSize;
            if (next === store.pagination.pageSize) return;
            store.setPageSize(next);
        };

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
                } else if (isPreviewable(file)) {
                    openPreview(file);
                    closeOffcanvas();
                } else Swal.fire('Info', 'Preview not supported for this file type.', 'info');
            }
        };

        const saveFile = async () => {
            if (!supports(editorFile.value, 'edit')) return;
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
            if (store.isLoading) return;
            const target = store.cwd;
            const { value: name } = await Swal.fire({ title: i18n.t('new_folder'), input: 'text', showCancelButton: true });
            if (name) {
                try { await Api.post('mkdir', { path: target ? target + '/' + name : name }); reload(); store.refreshTree(); }
                catch (e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
            }
        };

        const deleteSelected = async () => {
            if (!store.selectedItems.length || !store.selectedItems.every(f => supports(f, 'trash'))) return;
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
            if (!store.isLoading && uploadModal.value) uploadModal.value.open();
        };

        const performUpload = async (files, targetPath = null, silent = false) => {
            const path = targetPath !== null ? targetPath : store.cwd;
            try {
                if (!silent) {
                    store.isLoading = true;
                    store.uploadTotal = files.length;
                    store.uploadCurrent = 0;
                }
                for (let file of files) {
                    store.uploadFileName = file.name;
                    store.uploadProgress = 0;
                    await FileUploadClient.upload(file, {
                        path, relativePath: file.webkitRelativePath || '',
                        onProgress: (fraction) => { store.uploadProgress = Math.round(fraction * 100); }
                    });
                    store.uploadCurrent++;
                }
                if (!silent) {
                    reload();
                    store.refreshTree();
                    Swal.fire(i18n.t('uploaded'), '', 'success');
                }
            } catch (e) { 
                if (!silent) Swal.fire(i18n.t('error'), e.message, 'error');
                throw e; // Rethrow to let caller know
            }
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
            if (store.isLoading || !store.clipboard.items.length) return;
            const target = store.cwd;
            const generation = store.navigationGeneration;
            try {
                store.isLoading = true;
                for (const file of store.clipboard.items) {
                    const to = (target ? target + '/' : '') + file.name;
                    await Api.post(store.clipboard.mode === 'copy' ? 'cp' : 'mv', { from: file.path, to });
                }
                if (store.clipboard.mode === 'cut') { store.clipboard.items = []; store.clipboard.mode = null; }
                await reload();
                Swal.fire(i18n.t('success'), '', 'success');
            } catch (e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
            finally { if (store.navigationGeneration === generation) store.isLoading = false; }
        };

        const downloadSelected = () => {
            if (store.selectedItems.length === 1) window.location.href = (window.routeBaseUrl || window.baseUrl) + 'api/download?path=' + encodeURIComponent(store.selectedItems[0].path);
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
                html: `<input id="swal-i1" class="swal2-input" placeholder="${i18n.t('octal_mode')}">` +
                      `<div class="form-check mt-3 text-start ms-5"><input class="form-check-input" type="checkbox" id="swal-i2"><label class="form-check-label" for="swal-i2">${i18n.t('apply_recursively')}</label></div>`,
                // Remote listings supply perms; assign it as a value, never as markup.
                didOpen: () => { document.getElementById('swal-i1').value = String(store.selectedItems[0].perms || '755'); },
                focusConfirm: false, preConfirm: () => [document.getElementById('swal-i1').value, document.getElementById('swal-i2').checked], showCancelButton: true
            });
            if (form) {
                const [mode, recursive] = form;
                if (!mode || !/^[0-7]{3,4}$/.test(mode)) return Swal.fire(i18n.t('error'), i18n.t('invalid_octal_mode'), 'error');
                if (recursive) {
                    const confirmed = await confirmRecursiveChange(
                        i18n.t('confirm_recursive_perms_title'),
                        i18n.t('confirm_recursive_perms_text', { target: selectedTargetSummary(), mode })
                    );
                    if (!confirmed) return;
                }
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
                if (data.recursive) {
                    const confirmed = await confirmRecursiveChange(
                        i18n.t('confirm_recursive_owner_title'),
                        i18n.t('confirm_recursive_owner_text', {
                            target: propertiesTargetSummary.value,
                            owner: data.user || '-',
                            group: data.group || '-'
                        })
                    );
                    if (!confirmed) return;
                }
                await Api.post('chown', data); reload(); Swal.fire(i18n.t('saved'), '', 'success');
            } catch (e) { Swal.fire(i18n.t('error'), e.message, 'error'); }
        };

        const selectedTargetSummary = () => {
            if (store.selectedItems.length === 1) {
                return '/' + (store.selectedItems[0].path || '');
            }
            return i18n.t('target_selected_items', { count: store.selectedItems.length });
        };

        const confirmRecursiveChange = async (title, text) => {
            const result = await Swal.fire({
                icon: 'warning',
                title,
                text,
                showCancelButton: true,
                confirmButtonText: i18n.t('confirm_recursive_apply'),
                cancelButtonText: i18n.t('cancel'),
                confirmButtonColor: '#d97706'
            });
            return Boolean(result.isConfirmed);
        };

        const openShare = () => {
            if (store.selectedItems.length === 1 && supports(store.selectedItems[0], 'share') && shareModal.value) {
                shareModal.value.open(store.selectedItems[0]);
            }
        };

        const openHistory = () => {
            if (supports(store.selectedItems[0], 'versions') && store.selectedItems.length === 1 && fileHistoryModal.value) {
                fileHistoryModal.value.open(store.selectedItems[0]);
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
        const openProfile = (tab = null) => { if (userProfile.value) userProfile.value.open(tab); };
        const transferAvailable = computed(() => transferCapability.value?.available === true);
        const transferSettingsUrl = computed(() => {
            if (transferCapability.value?.settings_url) return transferCapability.value.settings_url;
            return isAdmin.value ? `${window.routeBaseUrl || window.baseUrl}admin#settings/email` : '';
        });

        const loadTransferCapability = async () => {
            transferCapabilityLoading.value = true;
            try {
                transferCapability.value = await Api.get('transfer/capability');
            } catch (error) {
                transferCapability.value = {
                    available: false,
                    reason: 'unavailable',
                    settings_url: isAdmin.value ? `${window.routeBaseUrl || window.baseUrl}admin#settings/email` : null
                };
            } finally {
                transferCapabilityLoading.value = false;
            }
            return transferAvailable.value;
        };

        const redirectToTransferSettings = async () => {
            if (transferSettingsUrl.value) {
                window.location.href = transferSettingsUrl.value;
                return false;
            }

            if (window.Swal) {
                await Swal.fire(
                    i18n.t('transfer_email_unavailable'),
                    i18n.t('transfer_email_admin_required'),
                    'info'
                );
            }
            return false;
        };

        const ensureTransferAvailable = async () => {
            const available = await loadTransferCapability();
            return available || redirectToTransferSettings();
        };

        const openTransfer = async () => {
            if (!(await ensureTransferAvailable())) return;
            if (transferModal.value) transferModal.value.open();
        };

        const openTransferWithFile = async (file) => {
            if (!(await ensureTransferAvailable())) return;
            if (transferModal.value) transferModal.value.openWithFiles([file]);
        };

        watch(commandPaletteQuery, () => {
            commandPaletteSelectedIndex.value = 0;
        });
        const showWebDav = () => webdavModal.show();
        const copyWebDavUrl = () => {
            const i = document.getElementById('webdav_url_input'); i.select(); document.execCommand('copy');
            Swal.fire({ title: 'Copied!', icon: 'success', toast: true, position: 'top-end', timer: 3000, showConfirmButton: false });
        };
        const changePassword = () => openProfile('security');

        const { dragIndicatorMode, onDragStart, onDragEnd, onDragOver, onDragLeave, onDrop } =
            useFileDragDrop(performUpload, resetUploadProgress, reload);

        const setSort = (k) => {
            if (store.sortBy === k) store.sortDesc = !store.sortDesc;
            else { store.sortBy = k; store.sortDesc = false; }
            if (!store.isTrashMode && !store.searchQuery) {
                store.loadPath(store.cwd, 1);
            }
        };

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

                const updateContextMenuStyle = () => {
                    const styleEl = document.getElementById('context-menu-style');
                    if (!styleEl) return;
                    if (!contextMenu.visible) {
                        styleEl.textContent = '';
                        return;
                    }
                    styleEl.textContent = `.context-menu{top:${contextMenu.y}px;left:${contextMenu.x}px;}`;
                };

        const showContextMenu = (e, f) => { 
            contextMenu.visible = true; 
            contextMenu.x = e.clientX; 
            contextMenu.y = e.clientY; 
            contextMenu.file = f; 
            store.selectedItems = [f]; 
            
            // Positioning
            let styleEl = document.getElementById('context-menu-style');
            if (styleEl) {
                styleEl.textContent = `.context-menu{top:${contextMenu.y}px;left:${contextMenu.x}px;}`;
            }
        };
        const hideContextMenu = () => contextMenu.visible = false;
        const logout = () => {
            const form = document.createElement('form');
            form.method = 'post';
            form.action = Api.route('logout');
            const token = document.createElement('input');
            token.type = 'hidden';
            token.name = window.csrfTokenName;
            token.value = window.csrfHash;
            form.appendChild(token);
            document.body.appendChild(form);
            form.submit();
        };
        const cmAction = (a) => {
            hideContextMenu(); const f = contextMenu.file; if (!f) return;
            const actions = { 
                'open':()=>open(f), 'download':downloadSelected, 'copy':copySelected, 'cut':cutSelected, 'paste':paste, 
                'rename':renameSelected, 'perms':chmodSelected, 'properties':showProperties, 'delete':deleteSelected,
                'restore': restoreSelected, 'delete_perm': deletePermanent,
                'share': openShare, 'history': openHistory
            };
            if (actions[a]) actions[a]();
        };

        // Initial Load
        onMounted(async () => {
            applyTheme();
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => { if (theme.value === 'auto') applyTheme(); });
            const lastPath = localStorage.getItem('extplorer_last_path') || '';
            store.loadPath(lastPath);
            loadTransferCapability();
            
            ace.config.set('basePath', window.baseUrl + 'assets/vendor/ace');
            editorModal = new bootstrap.Modal(document.getElementById('editorModal'));
            diffModal = new bootstrap.Modal(document.getElementById('diffModal'));
            propModal = new bootstrap.Modal(document.getElementById('propModal'));
            webdavModal = new bootstrap.Modal(document.getElementById('webdavModal'));

            const contentArea = document.getElementById('contentArea');
            if (contentArea) {
                const updateHeaderShadow = () => {
                    contentArea.classList.toggle('scrolled', contentArea.scrollTop > 0);
                };
                updateHeaderShadow();
                contentArea.addEventListener('scroll', updateHeaderShadow);
            }

            nextTick(() => {
                if (window.forcePasswordChange) {
                    openProfile('security');
                }
            });
            
            window.addEventListener('keydown', (e) => {
                const t = e.target, isI = t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.isContentEditable;
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                    e.preventDefault();
                    openCommandPalette();
                    return;
                }
                if (commandPaletteOpen.value && e.key === 'Escape') {
                    e.preventDefault();
                    closeCommandPalette();
                    return;
                }
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
            commandPaletteOpen, commandPaletteQuery, commandPaletteInput, commandPaletteSelectedIndex, filteredCommandActions,
            detailsPaneOpen, detailsItem, detailsShareLabel, currentFolderBookmark,
            goUp, goToPath, reload, closeOffcanvas, handleItemClick, handleTouchStart, handleTouchEnd, changePage, goToPage, setPageSize, open, saveFile, getIcon, formatSize, formatDate, containerClass, filteredFiles, breadcrumbs,
            openCommandPalette, closeCommandPalette, moveCommandSelection, runCommand, runSelectedCommand,
            toggleDetailsPane, closeDetailsPane, toggleBookmark,
            emptyStateIcon, emptyStateTitle, emptyStateDescription,
            supports, isAdmin, openAdmin, changePassword, theme, setTheme, toggleTheme, userAdmin, userProfile, openProfile, shareModal, uploadModal, fileHistoryModal, transferModal, openTransfer, openTransferWithFile, openShare, openHistory,
            transferCapability, transferCapabilityLoading, transferAvailable, transferSettingsUrl,
            contextMenu, showContextMenu, hideContextMenu, logout, cmAction,
            previewState, nextPreview, prevPreview, showWebDav, copyWebDavUrl, webDavUrl,
            dragIndicatorMode, onDragStart, onDragEnd, onDragOver, onDragLeave, onDrop,
            setSort, isImage, isArchive, getThumbUrl,
            createFolder, deleteSelected, renameSelected, uploadFile, downloadSelected, createArchive, extractArchive, chmodSelected,
            showProperties, diffSelected, copySelected, cutSelected, paste,
            editorFile, propFile, propertiesTargetSummary, saveChown, calcDirSize,
            toggleTrash, restoreSelected, deletePermanent, emptyTrash,
            username: window.username,
            baseUrl: window.baseUrl,
            routeBaseUrl: window.routeBaseUrl || window.baseUrl,
            connectionMode: window.connectionMode,
            appVersion: window.appVersion,
            webdavEnabled: window.webdavEnabled,
            paginationPages, totalPages
        };
    }
});

(async () => {
    if (typeof i18n !== 'undefined' && typeof i18n.init === 'function') {
        await i18n.init();
    }
    app.mount('#app');
})();
