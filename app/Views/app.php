<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eXtplorer</title>
    <link rel="icon" type="image/svg+xml" href="<?= base_url('favicon.svg') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/remixicon.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/sweetalert2.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/diff2html.min.css') ?>">
    <style <?= csp_style_nonce() ?>>
        body, html { height: 100%; overflow: hidden; }
        #app { display: flex; flex-direction: column; height: 100%; }
        .main-container { flex: 1; display: flex; overflow: hidden; position: relative; }
        .content-area { flex: 1; overflow: auto; min-width: 0; }
        
        /* Sidebar */
        .sidebar { 
            width: 250px; 
            border-right: 1px solid var(--bs-border-color); 
            overflow-y: auto; 
            background-color: var(--bs-body-bg); 
            flex-shrink: 0;
        }
        
        @media (max-width: 991.98px) {
            .sidebar { width: auto; border-right: none; }
        }
        
        /* Main Container Drag Over */
        .main-container.drag-over { background-color: var(--bs-primary-bg-subtle); border: 2px dashed var(--bs-primary); }
        
        .navbar-logo { height: 48px; width: auto; margin-right: 10px; }
        
        .file-item { 
            cursor: pointer; 
            border-radius: 4px;
            transition: background-color 0.2s;
            color: var(--bs-body-color);
        }
        .file-item:hover { background-color: var(--bs-tertiary-bg); }
        .file-item.selected { background-color: var(--bs-primary) !important; color: white !important; }
        .file-item.selected .file-meta { color: rgba(255,255,255,0.8) !important; }
        .file-item.drag-over { background-color: var(--bs-info-bg-subtle) !important; border: 1px dashed var(--bs-info) !important; }
        .mount-badge { font-size: 0.65rem; }
        
        /* Grid View */
        .grid-view {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            align-content: start;
            width: 100%;
        }
        .grid-view .file-item { 
            width: auto; 
            padding: 10px; 
            margin: 0;
            text-align: center; 
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        @media (min-width: 768px) {
            .grid-view { grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 15px; }
            .grid-view .file-item { padding: 15px; }
        }

        .grid-view .file-icon { font-size: 2.5rem; line-height: 1; margin-bottom: 5px; }
        @media (min-width: 768px) { .grid-view .file-icon { font-size: 3rem; } }

        .grid-view .file-name { 
            width: 100%; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis; 
            font-size: 0.8rem;
        }
        @media (min-width: 768px) { .grid-view .file-name { font-size: 0.9rem; } }

        /* List View */
        .list-view {
            width: 100%;
        }
        .list-view,
        .list-view-header {
            --list-name-min: clamp(200px, 30vw, 420px);
            --list-size: clamp(90px, 10vw, 120px);
            --list-date: clamp(140px, 16vw, 210px);
            --list-cols: 32px minmax(var(--list-name-min), 1fr);
        }
        @media (min-width: 768px) {
            .list-view,
            .list-view-header {
                --list-cols: 32px minmax(var(--list-name-min), 1fr) var(--list-size) var(--list-date);
            }
        }
        .list-view-header {
            display: grid;
            grid-template-columns: var(--list-cols);
            position: sticky;
            top: 0;
            z-index: 2;
            background: var(--bs-body-bg);
            column-gap: 8px;
        }
        .content-area.scrolled .list-view-header {
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }
        .list-view .file-item {
            display: grid;
            grid-template-columns: var(--list-cols);
            align-items: center;
            padding: 8px 15px; /* Increased for touch */
            border-bottom: 1px solid var(--bs-border-color);
            width: 100%;
            white-space: nowrap;
            overflow: hidden;
            min-height: 44px; /* Touch target minimum */
            column-gap: 8px;
        }
        @media (min-width: 768px) {
            .list-view .file-item { padding: 2px 15px; min-height: 32px; }
        }

        .list-view .file-icon { 
            font-size: 1.2rem; 
            width: 24px; 
            min-width: 24px;
            display: flex; 
            align-items: center; 
            justify-content: center; 
            justify-self: center;
        }
        .list-view .file-name { 
            flex: 1; 
            text-overflow: ellipsis; 
            overflow: hidden;
            line-height: 1.2;
            min-width: 0;
        }
        
        .list-view .file-meta-col {
            display: none; /* Hide on mobile */
            font-size: 0.8rem; 
            color: var(--bs-secondary-color); 
            margin-left: 15px; 
            text-align: right;
        }
        .list-view .file-meta-col,
        .list-view-header .file-meta-col {
            margin-left: 0;
            justify-self: end;
        }
        .list-view-name-col {
            min-width: 0;
        }
        @media (min-width: 768px) {
            .list-view .file-meta-col { display: block; }
        }
        .rotate-90 { transform: rotate(90deg); }
        
        [v-cloak] { display: none; }
        
        [data-bs-theme="dark"] .navbar { background-color: var(--bs-body-bg) !important; border-bottom: 1px solid var(--bs-border-color); }
        [data-bs-theme="light"] .navbar { background-color: var(--bs-dark) !important; }
        
        .dropdown-menu { box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); }
        .icon-large { font-size: 4rem; }
        .thumb-grid { width: 100%; height: 100%; object-fit: cover; }

        .list-view-header-spacer { width: auto; }
        .cursor-pointer { cursor: pointer; }
        .h-90vh { height: 90vh; }
        .h-100 { height: 100%; }
        .w-100 { width: 100%; }
        .max-h-90vh { max-height: 90vh; }
        .z-1060 { z-index: 1060; }
        .progress-thin { height: 5px; }
        .empty-state { min-height: 50vh; }
        .empty-state-icon { width: 120px; height: 120px; }
        .empty-state-icon-glyph { font-size: 3.5rem; opacity: 0.5; }
        .empty-state-text { max-width: 450px; line-height: 1.6; }
        .shared-badge { width: 18px; height: 18px; transform: translate(20%, 20%); z-index: 1; }
        .shared-badge-icon { font-size: 12px; }
        .preview-modal-body { min-height: 400px; background: #000; }
        .preview-pdf-frame { height: 80vh; border: none; }
        .tree-indent { padding-left: 15px; }
        .tree-item-interactive { cursor: pointer; user-select: none; color: var(--bs-body-color); }
        .tree-item-interactive:hover { background-color: var(--bs-tertiary-bg); }
        .tree-toggle { transition: transform 0.2s; }
        .tree-folder { color: #ffc107; }
        .tree-folder-selected { color: #fff; }
        .transfer-dropzone { min-height: 200px; display: flex; flex-direction: column; justify-content: center; }
        .transfer-files-list { max-height: 250px; }
        .transfer-recipient { max-width: 150px; }
        .upload-list { max-height: 300px; overflow-y: auto; }
        .upload-status { width: 150px; }
        .progress-compact { height: 6px; }
        .qr-image { width: 200px; height: 200px; }
        .qr-input-group { max-width: 300px; margin: 0 auto; }
        .admin-note { font-size: 0.75rem; }
        .admin-badge { font-size: 0.7rem; }
        .admin-table-scroll { max-height: 400px; }
        .admin-log-path { max-width: 200px; }
        .admin-meta-label { width: 200px; }
        .admin-config-box { max-height: 150px; overflow-y: auto; }
        .select-auto-width { width: auto; }
        .context-menu { position: fixed; z-index: 1050; }
        .path-breadcrumb .breadcrumb-item + .breadcrumb-item::before { color: rgba(255, 255, 255, 0.6); }
        [data-bs-theme="dark"] .file-tree-item .border-secondary-subtle { border-color: var(--bs-secondary-color) !important; }
        .progress-w-0 { width: 0%; }
        .progress-w-5 { width: 5%; }
        .progress-w-10 { width: 10%; }
        .progress-w-15 { width: 15%; }
        .progress-w-20 { width: 20%; }
        .progress-w-25 { width: 25%; }
        .progress-w-30 { width: 30%; }
        .progress-w-35 { width: 35%; }
        .progress-w-40 { width: 40%; }
        .progress-w-45 { width: 45%; }
        .progress-w-50 { width: 50%; }
        .progress-w-55 { width: 55%; }
        .progress-w-60 { width: 60%; }
        .progress-w-65 { width: 65%; }
        .progress-w-70 { width: 70%; }
        .progress-w-75 { width: 75%; }
        .progress-w-80 { width: 80%; }
        .progress-w-85 { width: 85%; }
        .progress-w-90 { width: 90%; }
        .progress-w-95 { width: 95%; }
        .progress-w-100 { width: 100%; }

        /* Profile Modal Enhancements */
        .profile-password-hints {
            padding-left: 1rem;
            font-size: 0.82rem;
        }
        .profile-strength-bar {
            height: 6px;
        }
        .strength-score-0 { width: 0%; }
        .strength-score-1 { width: 25%; }
        .strength-score-2 { width: 50%; }
        .strength-score-3 { width: 75%; }
        .strength-score-4 { width: 100%; }

        .profile-stepper {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .profile-step {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            color: var(--bs-secondary-color);
        }
        .profile-step-index {
            width: 26px;
            height: 26px;
            border-radius: 999px;
            border: 1px solid var(--bs-border-color);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            background: var(--bs-body-bg);
        }
        .profile-step.is-active .profile-step-index {
            border-color: var(--bs-primary);
            background: var(--bs-primary);
            color: #fff;
        }
        .profile-step.is-complete .profile-step-index {
            border-color: var(--bs-success);
            background: var(--bs-success);
            color: #fff;
        }
        .profile-step.is-active .profile-step-label {
            color: var(--bs-primary-text-emphasis);
            font-weight: 600;
        }
        .profile-step.is-complete .profile-step-label {
            color: var(--bs-success-text-emphasis);
            font-weight: 600;
        }

        .profile-qr-wrap {
            display: inline-block;
            padding: 0.6rem;
            border-radius: 0.5rem;
            border: 1px solid var(--bs-border-color);
            background: #fff;
        }
        [data-bs-theme="dark"] .profile-qr-wrap {
            background: #fff;
        }
        .qr-image {
            max-width: 200px;
            height: auto;
        }
        .profile-secret-block {
            padding: 0.6rem;
            border: 1px dashed var(--bs-border-color);
            border-radius: 0.5rem;
            background: var(--bs-tertiary-bg);
        }
        .profile-recovery-list {
            padding: 0.75rem;
            border: 1px solid var(--bs-border-color);
            border-radius: 0.5rem;
            background: #fff;
            max-height: 180px;
            overflow: auto;
        }
        [data-bs-theme="dark"] .profile-recovery-list {
            background: var(--bs-body-bg);
        }
    </style>
    <style <?= csp_style_nonce() ?> id="context-menu-style"></style>
</head>
<body>
    <div id="app" v-cloak>
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container-fluid">
                <!-- Mobile Sidebar Toggle -->
                <button class="btn btn-outline-light btn-sm d-lg-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
                    <i class="ri-menu-line"></i>
                </button>

                <a class="navbar-brand d-flex align-items-center" href="#">
                    <img src="<?= base_url('logo-dark.svg') ?>" alt="Logo" class="navbar-logo">
                </a>
                
                <div class="d-flex align-items-center text-white me-3">
                    <span class="me-2 text-white-50">{{ t('path') }}</span>
                    <nav aria-label="breadcrumb" class="path-breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="#" class="link-light text-decoration-none" @click.prevent="goToPath('')">/</a>
                            </li>
                            <li v-for="(crumb, idx) in breadcrumbs" :key="crumb.path" class="breadcrumb-item">
                                <a href="#" class="link-light text-decoration-none" @click.prevent="goToPath(crumb.path)">{{ crumb.name }}</a>
                            </li>
                        </ol>
                    </nav>
                </div>

                <div class="me-3">
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control bg-body-tertiary text-body border-secondary-subtle" 
                               :placeholder="t('filter_placeholder')" 
                               v-model="store.searchQuery"
                               @keyup.enter="store.performSearch(store.searchQuery)">
                        <button class="btn btn-outline-secondary" type="button" @click="store.performSearch(store.searchQuery)">
                            <i class="ri-search-line"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-outline-light btn-sm" @click="goUp" :disabled="!store.cwd">
                        <i class="ri-arrow-up-line"></i> {{ t('up') }}
                    </button>
                    <button class="btn btn-outline-light btn-sm" @click="reload">
                        <i class="ri-refresh-line"></i>
                    </button>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-light" :class="{active: store.viewMode === 'grid'}" @click="store.toggleViewMode('grid')">
                            <i class="ri-grid-fill"></i>
                        </button>
                        <button class="btn btn-outline-light" :class="{active: store.viewMode === 'list'}" @click="store.toggleViewMode('list')">
                            <i class="ri-list-check"></i>
                        </button>
                    </div>
                    
                    <div class="dropdown">
                        <button class="btn btn-outline-light btn-sm ms-2 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="ri-user-line"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><a class="dropdown-item" href="#" @click.prevent="openProfile">{{ t('profile_settings') || 'Profile & Settings' }}</a></li>
                            <li v-if="isAdmin"><a class="dropdown-item" href="<?= base_url('admin') ?>"><i class="ri-settings-3-line me-2"></i> Admin Console</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Appearance</h6></li>
                            <li><a class="dropdown-item" :class="{active: theme === 'light'}" href="#" @click.prevent="setTheme('light')">
                                <i class="ri-sun-line me-2"></i> {{ t('theme_light') }}
                            </a></li>
                            <li><a class="dropdown-item" :class="{active: theme === 'dark'}" href="#" @click.prevent="setTheme('dark')">
                                <i class="ri-moon-line me-2"></i> {{ t('theme_dark') }}
                            </a></li>
                            <li><a class="dropdown-item" :class="{active: theme === 'auto'}" href="#" @click.prevent="setTheme('auto')">
                                <i class="ri-computer-line me-2"></i> {{ t('theme_auto') }}
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Language</h6></li>
                            <li><a class="dropdown-item" :class="{active: i18n.locale === 'en'}" href="#" @click.prevent="i18n.load('en')">English</a></li>
                            <li><a class="dropdown-item" :class="{active: i18n.locale === 'de'}" href="#" @click.prevent="i18n.load('de')">Deutsch</a></li>
                            <li><a class="dropdown-item" :class="{active: i18n.locale === 'fr'}" href="#" @click.prevent="i18n.load('fr')">Français</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout"><i class="ri-logout-box-r-line me-2"></i> Logout</a></li>
                        </ul>
                    </div>

                    <button v-if="isAdmin" class="btn btn-outline-warning btn-sm ms-2" @click="openAdmin">
                        <i class="ri-settings-3-line"></i>
                    </button>
                </div>
            </div>
        </nav>

        <!-- Components -->
        <user-admin ref="userAdmin"></user-admin>
        <user-profile ref="userProfile"></user-profile>
        <share-modal ref="shareModal"></share-modal>
        <upload-modal ref="uploadModal"></upload-modal>
        <file-history-modal ref="fileHistoryModal"></file-history-modal>
        <transfer-modal ref="transferModal"></transfer-modal>

        <!-- Toolbar -->
        <div class="bg-body-tertiary border-bottom p-2 d-flex gap-1 gap-md-2 align-items-center flex-wrap">
            <template v-if="!store.isTrashMode">
                <button class="btn btn-primary btn-sm" @click="createFolder" :title="t('new_folder')">
                    <i class="ri-folder-add-line"></i> <span class="d-none d-md-inline">{{ t('new_folder') }}</span>
                </button>
                <button class="btn btn-outline-secondary btn-sm" @click="uploadFile" :title="t('upload')">
                    <i class="ri-upload-cloud-2-line"></i> <span class="d-none d-md-inline">{{ t('upload') }}</span>
                </button>
                
                <div class="vr mx-1"></div>

                <button class="btn btn-outline-secondary btn-sm" @click="copySelected" :disabled="store.selectedItems.length === 0" :title="t('copy')">
                    <i class="ri-file-copy-line"></i> <span class="d-none d-xl-inline">{{ t('copy') }}</span>
                </button>
                <button class="btn btn-outline-secondary btn-sm" @click="cutSelected" :disabled="store.selectedItems.length === 0" :title="t('cut')">
                    <i class="ri-scissors-cut-line"></i> <span class="d-none d-xl-inline">{{ t('cut') }}</span>
                </button>
                <button class="btn btn-outline-success btn-sm" @click="paste" :disabled="store.clipboard.items.length === 0" :title="t('paste')">
                    <i class="ri-clipboard-line"></i> 
                    <span class="d-none d-md-inline">{{ t('paste') }}</span>
                    <span v-if="store.clipboard.items.length > 0" class="badge bg-success ms-1">{{ store.clipboard.items.length }}</span>
                </button>

                <div class="vr mx-1"></div>

                <button class="btn btn-outline-danger btn-sm" @click="deleteSelected" :disabled="store.selectedItems.length === 0" :title="t('delete')">
                    <i class="ri-delete-bin-line"></i> <span class="d-none d-md-inline">{{ t('delete') }}</span>
                </button>

                <!-- Overflow Menu -->
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="ri-more-2-fill"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><a class="dropdown-item" :class="{disabled: store.selectedItems.length !== 1}" href="#" @click.prevent="downloadSelected">
                            <i class="ri-download-line me-2"></i> {{ t('download') }}
                        </a></li>
                        <li><a class="dropdown-item" :class="{disabled: store.selectedItems.length !== 1}" href="#" @click.prevent="renameSelected">
                            <i class="ri-edit-line me-2"></i> {{ t('rename') }}
                        </a></li>
                        <li><a class="dropdown-item" :class="{disabled: store.selectedItems.length === 0}" href="#" @click.prevent="chmodSelected">
                            <i class="ri-lock-2-line me-2"></i> {{ t('perms') }}
                        </a></li>
                        <li><a class="dropdown-item" :class="{disabled: store.selectedItems.length === 0}" href="#" @click.prevent="showProperties">
                            <i class="ri-information-line me-2"></i> {{ t('properties') }}
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" :class="{disabled: store.selectedItems.length !== 2}" href="#" @click.prevent="diffSelected">
                            <i class="ri-diff-line me-2"></i> Diff
                        </a></li>
                        <li><a class="dropdown-item" :class="{disabled: store.selectedItems.length === 0}" href="#" @click.prevent="createArchive">
                            <i class="ri-file-zip-line me-2"></i> {{ t('archive') }}
                        </a></li>
                        <li><a class="dropdown-item" :class="{disabled: store.selectedItems.length !== 1 || !isArchive(store.selectedItems[0])}" href="#" @click.prevent="extractArchive">
                            <i class="ri-folder-zip-line me-2"></i> {{ t('extract') }}
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" @click.prevent="store.toggleHidden()">
                            <i class="me-2" :class="store.showHidden ? 'ri-eye-line' : 'ri-eye-off-line'"></i>
                            {{ t('show_hidden') }}
                        </a></li>
                    </ul>
                </div>
            </template>
            <template v-else>
                <div class="d-flex align-items-center text-danger fw-bold me-auto">
                    <i class="ri-delete-bin-line me-2"></i> {{ t('trash') || 'Recycle Bin' }}
                </div>
                <button class="btn btn-success btn-sm me-2" @click="restoreSelected" :disabled="store.selectedItems.length === 0">
                    <i class="ri-restart-line"></i> {{ t('restore') || 'Restore' }}
                </button>
                <button class="btn btn-outline-danger btn-sm" @click="emptyTrash">
                    <i class="ri-delete-bin-2-line"></i> {{ t('empty_trash') || 'Empty Trash' }}
                </button>
            </template>
        </div>

        <!-- Main -->
        <div class="main-container" 
             :class="{'drag-over': store.isDraggingOver}"
             @click="store.clearSelection(); hideContextMenu()" 
             @contextmenu.prevent="hideContextMenu()"
             @dragover.prevent="onDragOver($event, null)" 
             @dragleave="onDragLeave(null)"
             @drop.prevent="onDrop($event, null)">
            
            <!-- Sidebar -->
            <div class="sidebar offcanvas-lg offcanvas-start p-2" id="sidebarOffcanvas" tabindex="-1">
                 <div class="offcanvas-header d-lg-none">
                     <h5 class="offcanvas-title">Menu</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebarOffcanvas"></button>
                 </div>
                 <div class="offcanvas-body d-flex flex-column p-0">
                     <!-- Connect -->
                     <div class="mb-4 px-2 d-flex gap-2">
                         <button class="btn btn-primary btn-sm flex-fill" @click="openTransfer">
                             <i class="ri-send-plane-fill me-1"></i> {{ t('send_files') || 'Send Files' }}
                         </button>
                         <button class="btn btn-outline-primary btn-sm" @click="showWebDav" title="WebDAV Connect">
                             <i class="ri-link"></i>
                         </button>
                     </div>

                     <!-- Recent Files -->
                     <div v-if="store.recentFiles.length > 0" class="mb-4">
                         <div class="d-flex justify-content-between align-items-center mb-2 px-2">
                             <h6 class="small fw-bold text-uppercase text-muted mb-0">{{ t('recent_files') }}</h6>
                             <button class="btn btn-link btn-sm p-0 text-decoration-none" @click="store.clearRecent()">
                                 <i class="ri-delete-bin-7-line"></i>
                             </button>
                         </div>
                         <div v-for="file in store.recentFiles" :key="file.path" 
                              class="d-flex align-items-center py-1 px-2 rounded small file-item"
                              @click="open(file)"
                              data-bs-dismiss="offcanvas" data-bs-target="#sidebarOffcanvas">
                             <i :class="getIcon(file)" class="me-2"></i>
                             <span class="text-truncate">{{ file.name }}</span>
                         </div>
                     </div>

                     <h6 class="small fw-bold text-uppercase text-muted mb-2 px-2">Explorer</h6>
                     <file-tree path="" name="Root" :root="true" @click="isMobile ? closeOffcanvas() : null"></file-tree>
                     
                     <div class="mt-4 px-2">
                         <div class="d-flex align-items-center py-1 px-2 rounded small file-item" 
                              :class="{'bg-danger-subtle text-danger': store.isTrashMode}"
                              @click="toggleTrash"
                              data-bs-dismiss="offcanvas" data-bs-target="#sidebarOffcanvas">
                             <i class="ri-delete-bin-line me-2"></i>
                             <span>{{ t('trash') || 'Recycle Bin' }}</span>
                         </div>
                     </div>
                 </div>
            </div>

            <!-- Content -->
            <div id="contentArea" class="content-area position-relative" @click.stop="store.clearSelection(); hideContextMenu()">
                <div v-if="store.uploadProgress > 0" class="mb-3">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Uploading: <strong>{{ store.uploadFileName }}</strong></span>
                        <span v-if="store.uploadTotal > 1">{{ store.uploadCurrent + 1 }} / {{ store.uploadTotal }}</span>
                    </div>
                    <div class="progress progress-thin">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" :class="'progress-w-' + Math.round(store.uploadProgress / 5) * 5"></div>
                    </div>
                </div>

                <div v-if="store.isLoading && store.uploadProgress === 0" class="position-absolute top-50 start-50 translate-middle">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
                
                <div v-else-if="store.error" class="alert alert-danger m-3">
                    <i class="ri-error-warning-line me-2"></i> {{ store.error }}
                </div>

                <div v-else>
                    <div v-if="filteredFiles.length === 0" class="d-flex flex-column align-items-center justify-content-center w-100 text-muted py-5 my-5 empty-state">
                        <!-- Icon with background circle -->
                        <div class="bg-body-secondary rounded-circle d-flex align-items-center justify-content-center mb-4 empty-state-icon">
                            <i :class="emptyStateIcon" class="empty-state-icon-glyph"></i>
                        </div>
                        
                        <!-- Title -->
                        <h4 class="fw-normal mb-3">{{ emptyStateTitle }}</h4>
                        
                        <!-- Subtitle/Description -->
                        <p class="mb-5 text-center px-4 empty-state-text">
                            {{ emptyStateDescription }}
                        </p>

                        <!-- CTAs (only for normal directory) -->
                        <div v-if="!store.isTrashMode && !store.searchQuery" class="d-flex gap-2">
                            <button class="btn btn-primary btn-sm px-3" @click="createFolder">
                                <i class="ri-folder-add-line me-1"></i> {{ t('new_folder') }}
                            </button>
                            <button class="btn btn-outline-secondary btn-sm px-3" @click="uploadFile">
                                <i class="ri-upload-cloud-2-line me-1"></i> {{ t('upload') }}
                            </button>
                        </div>
                        
                        <!-- CTA for Search (Clear) -->
                        <button v-if="store.searchQuery" class="btn btn-outline-primary btn-sm px-3" @click="store.searchQuery = ''; store.performSearch('')">
                            <i class="ri-close-circle-line me-1"></i> Clear Search
                        </button>
                    </div>

                    <div v-else :class="containerClass" class="position-relative">
                        <button v-if="store.cwd" class="btn btn-light btn-sm position-absolute top-0 end-0 m-2 shadow-sm" @click="goUp" :title="t('up_one_level') || 'Up one level'">
                            <i class="ri-arrow-up-line"></i>
                        </button>
                        <!-- Header for List View -->
                        <div v-if="store.viewMode === 'list'" class="list-view-header text-muted border-bottom px-3 py-2 small fw-bold user-select-none w-100">
                            <div class="list-view-header-spacer"></div>
                            <div class="list-view-name-col cursor-pointer" @click="setSort('name')">
                                {{ t('name') }}
                                <i v-if="store.sortBy === 'name'" :class="store.sortDesc ? 'ri-arrow-down-s-fill' : 'ri-arrow-up-s-fill'"></i>
                            </div>
                            <div class="file-meta-col size-col cursor-pointer" @click="setSort('size')">
                                {{ t('size') }}
                                <i v-if="store.sortBy === 'size'" :class="store.sortDesc ? 'ri-arrow-down-s-fill' : 'ri-arrow-up-s-fill'"></i>
                            </div>
                            <div class="file-meta-col date-col cursor-pointer" @click="setSort('mtime')">
                                {{ t('date') }}
                                <i v-if="store.sortBy === 'mtime'" :class="store.sortDesc ? 'ri-arrow-down-s-fill' : 'ri-arrow-up-s-fill'"></i>
                            </div>
                        </div>

                        <!-- File Loop -->
                        <div v-for="file in filteredFiles" :key="file.name" class="file-item" 
                             :class="{'selected': store.isSelected(file), 'drag-over': file.isDragOver}"
                             draggable="true"
                             @dragstart="onDragStart($event, file)"
                             @dragover.prevent="file.type === 'dir' ? onDragOver($event, file) : null"
                             @dragleave="file.type === 'dir' ? onDragLeave(file) : null"
                             @drop.prevent="file.type === 'dir' ? onDrop($event, file) : null"
                             @click.stop="handleItemClick($event, file)"
                             @touchstart="handleTouchStart($event, file)"
                             @touchend="handleTouchEnd"
                             @dblclick.stop="open(file)"
                             @contextmenu.prevent.stop="showContextMenu($event, file)">
                            
                            <div class="file-icon position-relative">
                                <img v-if="store.viewMode === 'grid' && isImage(file)" 
                                     :src="getThumbUrl(file)" 
                                     class="rounded shadow-sm thumb-grid"
                                     loading="lazy"
                                     decoding="async"
                                     draggable="false">
                                <i v-else :class="getIcon(file)"></i>
                                
                                <div v-if="file.is_shared" class="position-absolute bottom-0 end-0 bg-white rounded-circle shadow-sm d-flex align-items-center justify-content-center shared-badge">
                                    <i class="ri-share-forward-line text-primary shared-badge-icon"></i>
                                </div>
                            </div>
                            
                            <div class="file-name" :title="file.name">
                                {{ file.name }}
                                <span v-if="file.is_mount && file.is_external" class="badge bg-secondary-subtle text-secondary border border-secondary-subtle ms-1 mount-badge">Mount</span>
                                <i v-if="file.type === 'dir'" class="ri-arrow-right-line d-md-none ms-2 text-muted" @click.stop="open(file)"></i>
                            </div>
                            
                            <!-- List View Meta -->
                            <template v-if="store.viewMode === 'list'">
                                <div class="file-meta-col size-col">{{ formatSize(file.size) }}</div>
                                <div class="file-meta-col date-col">{{ formatDate(file.mtime) }}</div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Status Bar -->
        <div class="bg-body-tertiary border-top px-3 py-2 small text-muted d-flex flex-wrap align-items-center gap-2">
            <div class="d-flex align-items-center me-auto">
                <span class="me-2 fw-bold text-primary">v{{ appVersion }}</span>
                <span>{{ t('items_count', {count: store.pagination.total}) }}</span>
                <span class="ms-2">({{ connectionMode === 'local' ? t('local_fs') : connectionMode.toUpperCase() }})</span>
            </div>
            
            <div v-if="store.pagination.total > store.pagination.pageSize" class="d-flex align-items-center gap-2 mx-auto order-2 order-md-1">
                <nav aria-label="File list pagination">
                    <ul class="pagination pagination-lg mb-0 flex-wrap gap-1">
                        <li class="page-item" :class="{disabled: store.pagination.page <= 1}">
                            <a class="page-link d-flex align-items-center" href="#" @click.prevent="changePage(-1)" aria-label="Previous page">
                                <i class="ri-arrow-left-s-line"></i>
                                <span class="d-none d-sm-inline ms-1">{{ t('prev') }}</span>
                            </a>
                        </li>
                        <li v-for="p in paginationPages" :key="p.key" class="page-item" :class="{active: p.type === 'page' && p.number === store.pagination.page, disabled: p.type === 'ellipsis'}">
                            <span v-if="p.type === 'ellipsis'" class="page-link">…</span>
                            <a v-else class="page-link" href="#" @click.prevent="goToPage(p.number)">{{ p.number }}</a>
                        </li>
                        <li class="page-item" :class="{disabled: store.pagination.page >= totalPages}">
                            <a class="page-link d-flex align-items-center" href="#" @click.prevent="changePage(1)" aria-label="Next page">
                                <span class="d-none d-sm-inline me-1">{{ t('next') }}</span>
                                <i class="ri-arrow-right-s-line"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
                <span class="text-muted small d-none d-md-inline">{{ t('page_info', {current: store.pagination.page, total: totalPages}) }}</span>
            </div>

            <div class="d-flex align-items-center gap-2 ms-auto order-1 order-md-2">
                <label for="pageSizeSelect" class="small text-muted d-none d-sm-inline mb-0">{{ t('rows_per_page') || 'Rows' }}</label>
                <select id="pageSizeSelect" class="form-select form-select-sm w-auto" :value="store.pagination.pageSize" @change="setPageSize($event)">
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                </select>
            </div>
        </div>

        <!-- Modals -->
        
        <!-- Editor Modal -->
        <div class="modal fade" id="editorModal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-fullscreen-lg-down modal-xl modal-dialog-centered h-90vh">
                <div class="modal-content h-100">
                    <div class="modal-header py-2">
                        <h5 class="modal-title fs-6">{{ t('editing', {name: editorFile?.name}) }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-0 overflow-hidden">
                        <div id="aceEditor" class="h-100 w-100"></div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ t('close') }}</button>
                        <button type="button" class="btn btn-primary btn-sm" @click="saveFile">{{ t('save') }}</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Diff Modal -->
        <div class="modal fade" id="diffModal" tabindex="-1">
            <div class="modal-dialog modal-fullscreen-lg-down modal-xl modal-dialog-centered h-90vh">
                <div class="modal-content h-100">
                    <div class="modal-header py-2">
                        <h5 class="modal-title fs-6">Compare Files</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body overflow-auto">
                        <div id="diffViewer"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <div class="modal fade" id="previewModal" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content bg-dark border-0 shadow-lg">
                    <div class="modal-header border-0 py-2">
                        <h6 class="modal-title text-white">{{ previewState.filename }}</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-0 text-center position-relative d-flex align-items-center justify-content-center preview-modal-body">
                        
                        <!-- Image -->
                        <img v-if="previewState.type === 'image'" :src="previewState.src" class="img-fluid rounded max-h-90vh">
                        
                        <!-- Video -->
                        <video v-if="previewState.type === 'video'" :src="previewState.src" controls autoplay class="w-100 max-h-90vh"></video>
                        
                        <!-- Audio -->
                        <div v-if="previewState.type === 'audio'" class="p-5 w-100">
                            <i class="ri-music-2-line fs-1 text-white-50 d-block mb-3"></i>
                            <audio :src="previewState.src" controls autoplay class="w-100"></audio>
                        </div>

                        <!-- PDF -->
                        <iframe v-if="previewState.type === 'pdf'" :src="previewState.src" class="w-100 preview-pdf-frame"></iframe>

                        <!-- Controls -->
                        <button v-if="previewState.list.length > 1" class="btn btn-dark bg-opacity-50 position-absolute start-0 m-3 rounded-circle" @click.stop="prevPreview" :disabled="previewState.index <= 0">
                            <i class="ri-arrow-left-s-line fs-4"></i>
                        </button>
                        <button v-if="previewState.list.length > 1" class="btn btn-dark bg-opacity-50 position-absolute end-0 m-3 rounded-circle" @click.stop="nextPreview" :disabled="previewState.index >= previewState.list.length - 1">
                            <i class="ri-arrow-right-s-line fs-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Properties Modal -->
        <div class="modal fade" id="propModal" tabindex="-1">
            <div class="modal-dialog modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h5 class="modal-title fs-6">{{ t('properties') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div v-if="propFile" class="modal-body">
                        <div class="text-center mb-3">
                            <i :class="getIcon(propFile)" class="icon-large"></i>
                            <h6 class="mt-2">{{ propFile.name }}</h6>
                        </div>
                        <table class="table table-sm small">
                            <tbody>
                                <tr><th>{{ t('name') }}</th><td>{{ propFile.name }}</td></tr>
                                <tr><th>{{ t('location') }}</th><td>/{{ propFile.path }}</td></tr>
                                <tr><th>{{ t('size') }}</th><td>
                                    {{ formatSize(propFile.size) }}
                                    <button v-if="propFile.type === 'dir'" class="btn btn-link btn-sm p-0 ms-2 text-decoration-none" @click="calcDirSize">
                                        <i class="ri-calculator-line"></i> {{ t('calculate') }}
                                    </button>
                                </td></tr>
                                <tr><th>{{ t('mime') }}</th><td>{{ propFile.mime }}</td></tr>
                                <tr><th>{{ t('date') }}</th><td>{{ formatDate(propFile.mtime) }}</td></tr>
                                <tr><th>{{ t('perms') }}</th><td>{{ propFile.perms }}</td></tr>
                                <tr><th>{{ t('owner') }}</th><td>
                                    <div v-if="isAdmin" class="input-group input-group-sm">
                                        <input type="text" class="form-control" v-model="propFile.owner">
                                        <button class="btn btn-outline-secondary" @click="saveChown">Set</button>
                                    </div>
                                    <span v-else>{{ propFile.owner }}</span>
                                </td></tr>
                                <tr><th>{{ t('group') }}</th><td>
                                    <div v-if="isAdmin" class="input-group input-group-sm">
                                        <input type="text" class="form-control" v-model="propFile.group">
                                        <button class="btn btn-outline-secondary" @click="saveChown">Set</button>
                                    </div>
                                    <span v-else>{{ propFile.group }}</span>
                                </td></tr>
                                <tr v-if="isAdmin">
                                    <th></th>
                                    <td>
                                        <div class="form-check form-check-sm small">
                                            <input class="form-check-input" type="checkbox" id="propRecursive" v-model="propFile.recursive">
                                            <label class="form-check-label" for="propRecursive">Apply Recursively</label>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- WebDAV Modal -->
        <div class="modal fade" id="webdavModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h5 class="modal-title fs-6">{{ t('webdav_connect') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted">{{ t('webdav_help') }}</p>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">{{ t('webdav_url') }}</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" :value="webDavUrl" readonly id="webdav_url_input">
                                <button class="btn btn-outline-secondary" @click="copyWebDavUrl">
                                    <i class="ri-file-copy-line"></i>
                                </button>
                            </div>
                        </div>
                        <div class="alert alert-info py-2 small mb-0">
                            <i class="ri-information-line me-1"></i>
                            Use your eXtplorer <strong>{{ username }}</strong> credentials to login.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Context Menu -->
        <div v-if="contextMenu.visible" 
             class="dropdown-menu show context-menu">
            <template v-if="!store.isTrashMode">
                <a class="dropdown-item" href="#" @click.prevent="cmAction('open')">
                    <i class="ri-folder-open-line me-2"></i> {{ t('open') || 'Open' }}
                </a>
                <a class="dropdown-item" href="#" @click.prevent="cmAction('download')">
                    <i class="ri-download-line me-2"></i> {{ t('download') }}
                </a>
                <a class="dropdown-item" href="#" @click.prevent="cmAction('share')">
                    <i class="ri-share-line me-2"></i> {{ t('share_title') || 'Share' }}
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" @click.prevent="cmAction('copy')">
                    <i class="ri-file-copy-line me-2"></i> {{ t('copy') }}
                </a>
                <a class="dropdown-item" href="#" @click.prevent="cmAction('cut')">
                    <i class="ri-scissors-cut-line me-2"></i> {{ t('cut') }}
                </a>
                <a class="dropdown-item" href="#" @click.prevent="cmAction('paste')" :class="{disabled: store.clipboard.items.length === 0}">
                    <i class="ri-clipboard-line me-2"></i> {{ t('paste') }}
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" @click.prevent="cmAction('rename')">
                    <i class="ri-edit-line me-2"></i> {{ t('rename') }}
                </a>
                <a class="dropdown-item" href="#" @click.prevent="cmAction('perms')">
                    <i class="ri-lock-2-line me-2"></i> {{ t('perms') }}
                </a>
                <a class="dropdown-item" href="#" @click.prevent="cmAction('properties')">
                    <i class="ri-information-line me-2"></i> {{ t('properties') }}
                </a>
                <a v-if="contextMenu.file && contextMenu.file.type !== 'dir'" class="dropdown-item" href="#" @click.prevent="cmAction('history')">
                    <i class="ri-history-line me-2"></i> {{ t('version_history') || 'Version History' }}
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item text-danger" href="#" @click.prevent="cmAction('delete')">
                    <i class="ri-delete-bin-line me-2"></i> {{ t('delete') }}
                </a>
            </template>
            <template v-else>
                <a class="dropdown-item text-success" href="#" @click.prevent="cmAction('restore')">
                    <i class="ri-restart-line me-2"></i> {{ t('restore') || 'Restore' }}
                </a>
                <a class="dropdown-item" href="#" @click.prevent="cmAction('properties')">
                    <i class="ri-information-line me-2"></i> {{ t('properties') }}
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item text-danger" href="#" @click.prevent="cmAction('delete_perm')">
                    <i class="ri-delete-bin-2-line me-2"></i> {{ t('delete_perm') || 'Delete Permanently' }}
                </a>
            </template>
        </div>

    </div> <!-- End #app -->

    <!-- Scripts -->
    <script <?= csp_script_nonce() ?>>
        window.baseUrl = "<?= base_url() ?>";
        window.appVersion = "<?= config('App')->version ?>";
        window.userRole = "<?= session('role') ?>";
        window.username = "<?= session('username') ?>";
        window.userPermissions = <?= json_encode(session('permissions') ?? []) ?>;
        window.connectionMode = "<?= session('connection')['mode'] ?? 'local' ?>";
        window.forcePasswordChange = <?= session('force_password_change') ? 'true' : 'false' ?>;
        window.csrfTokenName = "<?= csrf_token() ?>";
        window.csrfHash = "<?= csrf_hash() ?>";
        window.cspStyleNonce = "<?= service('csp')->getStyleNonce() ?>";
    </script>
    <script src="<?= base_url('assets/js/vue.global.js') ?>"></script>
    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/sweetalert2.min.js') ?>"></script>
    <script <?= csp_script_nonce() ?>>
        (function() {
            if (!window.cspStyleNonce) return;
            const doc = document;
            const origCreateElement = doc.createElement.bind(doc);
            const origCreateElementNS = doc.createElementNS.bind(doc);

            doc.createElement = function(tagName, options) {
                const el = origCreateElement(tagName, options);
                if (String(tagName).toLowerCase() === 'style') {
                    el.setAttribute('nonce', window.cspStyleNonce);
                }
                return el;
            };
            doc.createElementNS = function(ns, tagName, options) {
                const el = origCreateElementNS(ns, tagName, options);
                if (String(tagName).toLowerCase() === 'style') {
                    el.setAttribute('nonce', window.cspStyleNonce);
                }
                return el;
            };

            window.__restoreCreateElement = function() {
                doc.createElement = origCreateElement;
                doc.createElementNS = origCreateElementNS;
                delete window.__restoreCreateElement;
            };
        })();
    </script>
    <script src="<?= base_url('assets/vendor/ace/ace.min.js') ?>"></script>
    <script <?= csp_script_nonce() ?>>
        (function() {
            if (window.ace && window.ace.require && window.cspStyleNonce) {
                const aceDom = window.ace.require("ace/lib/dom");
                const origCreate = aceDom.createElement.bind(aceDom);
                aceDom.createElement = function(tagName, ns) {
                    const el = origCreate(tagName, ns);
                    if (String(tagName).toLowerCase() === 'style') {
                        el.setAttribute('nonce', window.cspStyleNonce);
                    }
                    return el;
                };
            }
            if (window.__restoreCreateElement) window.__restoreCreateElement();
        })();
    </script>
    <script src="<?= base_url('assets/js/diff.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/diff2html-ui.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/api.js') ?>"></script>
    <script src="<?= base_url('assets/js/store.js') ?>"></script>
    <script src="<?= base_url('assets/js/i18n.js') ?>"></script>
    <script src="<?= base_url('assets/js/components/FileTree.js?v=' . config('App')->version) ?>"></script>
    <script src="<?= base_url('assets/js/components/UserAdmin.js?v=' . config('App')->version) ?>"></script>
    <script src="<?= base_url('assets/js/components/UserProfile.js?v=' . config('App')->version) ?>"></script>
    <script src="<?= base_url('assets/js/components/ShareModal.js?v=' . config('App')->version) ?>"></script>
    <script src="<?= base_url('assets/js/components/UploadModal.js?v=' . config('App')->version) ?>"></script>
    <script src="<?= base_url('assets/js/components/FileHistoryModal.js?v=' . config('App')->version) ?>"></script>
    <script src="<?= base_url('assets/js/components/TransferModal.js?v=' . config('App')->version) ?>"></script>
    <script src="<?= base_url('assets/js/app.js?v=' . time()) ?>"></script>
</body>
</html>
