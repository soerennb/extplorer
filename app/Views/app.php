<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eXtplorer 3</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/remixicon.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/diff2html.min.css') ?>">
    <style>
        body, html { height: 100%; overflow: hidden; }
        #app { display: flex; flex-direction: column; height: 100%; }
        .main-container { flex: 1; display: flex; overflow: hidden; }
        
        .sidebar { 
            width: 250px; 
            border-right: 1px solid var(--bs-border-color); 
            overflow-y: auto; 
            background-color: var(--bs-body-bg); 
        }
        
        .content-area { flex: 1; overflow-y: auto; padding: 1rem; background-color: var(--bs-body-bg); }
        
        .file-item { 
            cursor: pointer; 
            border-radius: 4px;
            transition: background-color 0.2s;
            color: var(--bs-body-color);
        }
        .file-item:hover { background-color: var(--bs-tertiary-bg); }
        .file-item.selected { background-color: var(--bs-primary) !important; color: white !important; }
        .file-item.selected .file-meta { color: rgba(255,255,255,0.8) !important; }
        
        /* Grid View */
        .grid-view .file-item { 
            width: 120px; 
            padding: 15px; 
            margin: 5px; 
            text-align: center; 
            display: inline-flex;
            flex-direction: column;
            align-items: center;
        }
        .grid-view .file-icon { font-size: 3rem; line-height: 1; margin-bottom: 5px; }
        .grid-view .file-name { 
            width: 100%; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis; 
            font-size: 0.9rem;
        }

        /* List View */
        .list-view .file-item {
            display: flex;
            align-items: center;
            padding: 8px 15px;
            border-bottom: 1px solid var(--bs-border-color);
            width: 100%;
        }
        .list-view .file-icon { font-size: 1.5rem; margin-right: 15px; width: 30px; text-align: center;}
        .list-view .file-name { flex: 1; }
        .list-view .file-meta { font-size: 0.8rem; color: var(--bs-secondary-color); margin-left: 15px; width: 100px; text-align: right;}
        
        .rotate-90 { transform: rotate(90deg); }
        
        [v-cloak] { display: none; }
        
        [data-bs-theme="dark"] .navbar { background-color: var(--bs-body-bg) !important; border-bottom: 1px solid var(--bs-border-color); }
        [data-bs-theme="light"] .navbar { background-color: var(--bs-dark) !important; }
        
        .dropdown-menu { box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); }
    </style>
</head>
<body>
    <div id="app" v-cloak>
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container-fluid">
                <a class="navbar-brand d-flex align-items-center" href="#">
                    <i class="ri-folder-shield-2-line me-2"></i> {{ t('app_name') }}
                </a>
                
                <div class="d-flex align-items-center text-white me-3">
                    <span class="me-2 text-white-50">{{ t('path') }}</span> /{{ store.cwd }}
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
                        <button class="btn btn-outline-light" :class="{active: store.viewMode === 'grid'}" @click="store.viewMode = 'grid'">
                            <i class="ri-grid-fill"></i>
                        </button>
                        <button class="btn btn-outline-light" :class="{active: store.viewMode === 'list'}" @click="store.viewMode = 'list'">
                            <i class="ri-list-check"></i>
                        </button>
                    </div>
                    
                    <div class="dropdown">
                        <button class="btn btn-outline-light btn-sm ms-2 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="ri-user-line"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><a class="dropdown-item" href="#" @click.prevent="changePassword">{{ t('change_password') || 'Change Password' }}</a></li>
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

        <!-- Toolbar -->
        <div class="bg-body-tertiary border-bottom p-2 d-flex gap-2 align-items-center">
            <button class="btn btn-primary btn-sm" @click="createFolder">
                <i class="ri-folder-add-line"></i> {{ t('new_folder') }}
            </button>
            <button class="btn btn-outline-secondary btn-sm" @click="uploadFile">
                <i class="ri-upload-cloud-2-line"></i> {{ t('upload') }}
            </button>
            <button class="btn btn-outline-secondary btn-sm" @click="downloadSelected" :disabled="store.selectedItems.length !== 1">
                <i class="ri-download-line"></i> {{ t('download') }}
            </button>
            <button class="btn btn-sm" :class="store.showHidden ? 'btn-secondary' : 'btn-outline-secondary'" @click="store.toggleHidden()">
                <i class="ri-eye-off-line"></i> {{ t('show_hidden') }}
            </button>
            
            <div class="vr mx-2"></div>

            <button class="btn btn-outline-secondary btn-sm" @click="copySelected" :disabled="store.selectedItems.length === 0">
                <i class="ri-file-copy-line"></i> {{ t('copy') }}
            </button>
            <button class="btn btn-outline-secondary btn-sm" @click="cutSelected" :disabled="store.selectedItems.length === 0">
                <i class="ri-scissors-cut-line"></i> {{ t('cut') }}
            </button>
            <button class="btn btn-outline-success btn-sm" @click="paste" :disabled="store.clipboard.items.length === 0">
                <i class="ri-clipboard-line"></i> {{ t('paste') }}
                <span v-if="store.clipboard.items.length > 0" class="badge bg-success ms-1">{{ store.clipboard.items.length }}</span>
            </button>
            
            <div class="vr mx-2"></div>
            
            <button class="btn btn-outline-danger btn-sm" @click="deleteSelected" :disabled="store.selectedItems.length === 0">
                <i class="ri-delete-bin-line"></i> {{ t('delete') }}
            </button>
            <button class="btn btn-outline-secondary btn-sm" @click="renameSelected" :disabled="store.selectedItems.length !== 1">
                <i class="ri-edit-line"></i> {{ t('rename') }}
            </button>
            <button class="btn btn-outline-secondary btn-sm" @click="chmodSelected" :disabled="store.selectedItems.length === 0">
                <i class="ri-lock-2-line"></i> {{ t('perms') }}
            </button>
            <button class="btn btn-outline-secondary btn-sm" @click="showProperties" :disabled="store.selectedItems.length === 0">
                <i class="ri-information-line"></i> {{ t('properties') }}
            </button>
            <button class="btn btn-outline-info btn-sm" @click="diffSelected" :disabled="store.selectedItems.length !== 2">
                <i class="ri-diff-line"></i> Diff
            </button>
            <button class="btn btn-outline-secondary btn-sm" @click="createArchive" :disabled="store.selectedItems.length === 0">
                <i class="ri-file-zip-line"></i> {{ t('archive') }}
            </button>
            <button class="btn btn-outline-secondary btn-sm" @click="extractArchive" :disabled="store.selectedItems.length !== 1 || !isArchive(store.selectedItems[0])">
                <i class="ri-folder-zip-line"></i> {{ t('extract') }}
            </button>
        </div>

        <!-- Main -->
        <div class="main-container" 
             @click="store.clearSelection(); hideContextMenu()" 
             @contextmenu.prevent="hideContextMenu()"
             @dragover.prevent="onDragOver" 
             @drop.prevent="onDrop($event, null)">
            
            <!-- Sidebar -->
            <div class="sidebar p-2">
                 <!-- Connect -->
                 <div class="mb-4 px-2">
                     <button class="btn btn-outline-primary btn-sm w-100" @click="showWebDav">
                         <i class="ri-link me-1"></i> {{ t('webdav_connect') }}
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
                          @click="open(file)">
                         <i :class="getIcon(file)" class="me-2"></i>
                         <span class="text-truncate">{{ file.name }}</span>
                     </div>
                 </div>

                 <h6 class="small fw-bold text-uppercase text-muted mb-2 px-2">Explorer</h6>
                 <file-tree path="" name="Root" :root="true"></file-tree>
            </div>

            <!-- Content -->
            <div class="content-area position-relative" @click.stop="store.clearSelection(); hideContextMenu()">
                <div v-if="store.isLoading" class="position-absolute top-50 start-50 translate-middle">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
                
                <div v-else-if="store.error" class="alert alert-danger m-3">
                    <i class="ri-error-warning-line me-2"></i> {{ store.error }}
                </div>

                <div v-else>
                    <div :class="{'d-flex flex-wrap align-content-start': store.viewMode === 'grid'}">
                        <div v-if="store.files.length === 0" class="w-100 text-center text-muted mt-5">
                            <i class="ri-folder-open-line" style="font-size: 4rem;"></i>
                            <p>{{ t('empty_dir') }}</p>
                        </div>

                        <!-- Header for List View -->
                        <div v-if="store.viewMode === 'list' && store.files.length > 0" class="d-flex text-muted border-bottom px-3 py-2 small fw-bold user-select-none w-100">
                            <div style="width: 45px;"></div>
                            <div class="flex-grow-1" @click="setSort('name')" style="cursor: pointer;">
                                {{ t('name') }}
                                <i v-if="store.sortBy === 'name'" :class="store.sortDesc ? 'ri-arrow-down-s-fill' : 'ri-arrow-up-s-fill'"></i>
                            </div>
                            <div style="width: 100px; text-align: right;" @click="setSort('size')" style="cursor: pointer;">
                                {{ t('size') }}
                                <i v-if="store.sortBy === 'size'" :class="store.sortDesc ? 'ri-arrow-down-s-fill' : 'ri-arrow-up-s-fill'"></i>
                            </div>
                            <div style="width: 150px; text-align: right;" @click="setSort('mtime')" style="cursor: pointer;">
                                {{ t('date') }}
                                <i v-if="store.sortBy === 'mtime'" :class="store.sortDesc ? 'ri-arrow-down-s-fill' : 'ri-arrow-up-s-fill'"></i>
                            </div>
                        </div>

                        <!-- File Loop -->
                        <div v-for="file in filteredFiles" :key="file.name" class="file-item" 
                             :class="[containerClass, {'selected': store.isSelected(file)}]"
                             draggable="true"
                             @dragstart="onDragStart($event, file)"
                             @dragover.prevent="file.type === 'dir' ? onDragOver($event) : null"
                             @drop.prevent="file.type === 'dir' ? onDrop($event, file) : null"
                             @click.stop="store.toggleSelection(file); hideContextMenu()"
                             @dblclick.stop="open(file)"
                             @contextmenu.prevent.stop="showContextMenu($event, file)">
                            
                            <div class="file-icon">
                                <img v-if="store.viewMode === 'grid' && isImage(file)" 
                                     :src="getThumbUrl(file)" 
                                     class="rounded shadow-sm"
                                     style="width: 64px; height: 64px; object-fit: cover;"
                                     draggable="false">
                                <i v-else :class="getIcon(file)"></i>
                            </div>
                            
                            <div class="file-name" :title="file.name">
                                {{ file.name }}
                            </div>
                            
                            <!-- List View Meta -->
                            <template v-if="store.viewMode === 'list'">
                                <div class="file-meta">{{ formatSize(file.size) }}</div>
                                <div class="file-meta" style="width: 150px;">{{ formatDate(file.mtime) }}</div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Status Bar -->
        <div class="bg-body-tertiary border-top px-3 py-1 small text-muted d-flex justify-content-between align-items-center">
            <div>
                <span>{{ t('items_count', {count: store.pagination.total}) }}</span>
                <span class="ms-2">({{ window.connectionMode === 'local' ? t('local_fs') : window.connectionMode.toUpperCase() }})</span>
            </div>
            
            <div v-if="store.pagination.total > store.pagination.pageSize" class="d-flex align-items-center gap-3">
                <button class="btn btn-link btn-sm p-0 text-decoration-none" :disabled="store.pagination.page <= 1" @click="changePage(-1)">
                    <i class="ri-arrow-left-s-line"></i> {{ t('prev') }}
                </button>
                <span>{{ t('page_info', {current: store.pagination.page, total: Math.ceil(store.pagination.total / store.pagination.pageSize)}) }}</span>
                <button class="btn btn-link btn-sm p-0 text-decoration-none" :disabled="store.pagination.page >= Math.ceil(store.pagination.total / store.pagination.pageSize)" @click="changePage(1)">
                    {{ t('next') }} <i class="ri-arrow-right-s-line"></i>
                </button>
            </div>
        </div>

        <!-- Modals -->
        
        <!-- Editor Modal -->
        <div class="modal fade" id="editorModal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-xl modal-dialog-centered" style="height: 90vh;">
                <div class="modal-content h-100">
                    <div class="modal-header py-2">
                        <h5 class="modal-title fs-6">{{ t('editing', {name: editorFile?.name}) }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-0" style="overflow: hidden;">
                        <div id="aceEditor" style="height: 100%; width: 100%;"></div>
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
            <div class="modal-dialog modal-xl modal-dialog-centered" style="height: 90vh;">
                <div class="modal-content h-100">
                    <div class="modal-header py-2">
                        <h5 class="modal-title fs-6">Compare Files</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="overflow-y: auto;">
                        <div id="diffViewer"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Viewer Modal -->
        <div class="modal fade" id="imageModal" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content bg-transparent border-0 shadow-none">
                    <div class="modal-body p-0 text-center position-relative d-flex align-items-center justify-content-center">
                        <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" style="z-index: 1060;"></button>
                        <button v-if="imageViewer.list.length > 1" class="btn btn-dark bg-opacity-50 position-absolute start-0 m-3 rounded-circle" @click.stop="prevImage" :disabled="imageViewer.index <= 0">
                            <i class="ri-arrow-left-s-line fs-4"></i>
                        </button>
                        <img v-if="imageViewer.src" :src="imageViewer.src" class="img-fluid rounded" style="max-height: 90vh;">
                        <button v-if="imageViewer.list.length > 1" class="btn btn-dark bg-opacity-50 position-absolute end-0 m-3 rounded-circle" @click.stop="nextImage" :disabled="imageViewer.index >= imageViewer.list.length - 1">
                            <i class="ri-arrow-right-s-line fs-4"></i>
                        </button>
                        <div v-if="imageViewer.list.length > 1" class="position-absolute bottom-0 mb-3 badge bg-dark bg-opacity-75">
                            {{ imageViewer.index + 1 }} / {{ imageViewer.list.length }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Properties Modal -->
        <div class="modal fade" id="propModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h5 class="modal-title fs-6">{{ t('properties') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div v-if="propFile" class="modal-body">
                        <div class="text-center mb-3">
                            <i :class="getIcon(propFile)" style="font-size: 4rem;"></i>
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
             class="dropdown-menu show" 
             :style="{top: contextMenu.y + 'px', left: contextMenu.x + 'px', position: 'fixed', zIndex: 1050}">
            <a class="dropdown-item" href="#" @click.prevent="cmAction('open')">
                <i class="ri-folder-open-line me-2"></i> {{ t('open') || 'Open' }}
            </a>
            <a class="dropdown-item" href="#" @click.prevent="cmAction('download')">
                <i class="ri-download-line me-2"></i> {{ t('download') }}
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
            <div class="dropdown-divider"></div>
            <a class="dropdown-item text-danger" href="#" @click.prevent="cmAction('delete')">
                <i class="ri-delete-bin-line me-2"></i> {{ t('delete') }}
            </a>
        </div>

    </div> <!-- End #app -->

    <!-- Scripts -->
    <script>
        window.baseUrl = "<?= base_url() ?>";
        window.userRole = "<?= session('role') ?>";
        window.username = "<?= session('username') ?>";
        window.userPermissions = <?= json_encode(session('permissions') ?? []) ?>;
        window.connectionMode = "<?= session('connection')['mode'] ?? 'local' ?>";
        window.csrfTokenName = "<?= csrf_token() ?>";
        window.csrfHash = "<?= csrf_hash() ?>";
    </script>
    <script src="<?= base_url('assets/js/vue.global.js') ?>"></script>
    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/sweetalert2.all.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/ace/ace.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/diff.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/diff2html-ui.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/api.js') ?>"></script>
    <script src="<?= base_url('assets/js/store.js') ?>"></script>
    <script src="<?= base_url('assets/js/i18n.js') ?>"></script>
    <script src="<?= base_url('assets/js/components/FileTree.js') ?>"></script>
    <script src="<?= base_url('assets/js/components/UserAdmin.js') ?>"></script>
    <script src="<?= base_url('assets/js/app.js') ?>"></script>
</body>
</html>