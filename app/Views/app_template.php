    <div id="app" v-cloak data-testid="app-shell">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-dark app-navbar">
            <div class="container-fluid">
                <!-- Mobile Sidebar Toggle -->
                <button class="btn btn-outline-light btn-sm d-lg-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-label="Menu">
                    <i class="ri-menu-line" aria-hidden="true"></i>
                </button>

                <a class="navbar-brand d-flex align-items-center" href="#">
                    <img :src="baseUrl + 'logo-dark.svg'" alt="Logo" class="navbar-logo">
                </a>

                <div class="d-lg-none text-white mobile-current-path me-auto">
                    <span class="mobile-current-path-text">{{ store.cwd || '/' }}</span>
                </div>

                <div class="d-flex align-items-center text-white me-3 desktop-path" data-testid="current-path">
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

                <div class="me-3 navbar-search">
                    <div class="input-group input-group-sm">
                        <input id="navbarSearchInput" type="text" class="form-control bg-body-tertiary text-body border-secondary-subtle"
                               :placeholder="t('filter_placeholder')"
                               aria-label="Search files"
                               v-model="store.searchQuery"
                               @keyup.enter="store.performSearch(store.searchQuery)">
                        <button class="btn btn-outline-secondary" type="button" @click="store.performSearch(store.searchQuery)" aria-label="Search">
                            <i class="ri-search-line" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex gap-2 navbar-nav-tools">
                    <button class="btn btn-outline-light btn-sm d-none d-sm-inline-flex align-items-center" @click="goUp" :disabled="!store.cwd" data-testid="go-up">
                        <i class="ri-arrow-up-line"></i> {{ t('up') }}
                    </button>
                    <button class="btn btn-outline-light btn-sm d-none d-sm-inline-flex align-items-center" @click="reload" aria-label="Refresh" title="Refresh">
                        <i class="ri-refresh-line" aria-hidden="true"></i>
                    </button>
                    <button class="btn btn-outline-light btn-sm d-inline-flex align-items-center" @click="openCommandPalette" :aria-label="t('command_palette')" :title="t('command_palette')">
                        <i class="ri-command-line" aria-hidden="true"></i>
                    </button>
                    <div class="btn-group btn-group-sm d-none d-sm-flex" role="group" aria-label="View mode">
                        <button class="btn btn-outline-light" :class="{active: store.viewMode === 'grid'}" @click="store.toggleViewMode('grid')" aria-label="Grid view" :aria-pressed="store.viewMode === 'grid'">
                            <i class="ri-grid-fill" aria-hidden="true"></i>
                        </button>
                        <button class="btn btn-outline-light" :class="{active: store.viewMode === 'list'}" @click="store.toggleViewMode('list')" aria-label="List view" :aria-pressed="store.viewMode === 'list'">
                            <i class="ri-list-check" aria-hidden="true"></i>
                        </button>
                    </div>

	                    <div class="dropdown">
                        <button class="btn btn-outline-light btn-sm ms-2 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-label="User menu" data-testid="user-menu">
                            <i class="ri-user-line" aria-hidden="true"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
	                            <li><a class="dropdown-item" href="#" @click.prevent="openProfile"><i class="ri-user-settings-line me-2"></i>{{ t('profile_settings') || 'Profile & Settings' }}</a></li>
	                            <li v-if="isAdmin"><a class="dropdown-item" href="#" @click.prevent="openAdmin"><i class="ri-flashlight-line me-2"></i>Quick Admin</a></li>
                            <li v-if="isAdmin"><a class="dropdown-item" :href="baseUrl + 'admin'"><i class="ri-settings-3-line me-2"></i> Admin Console</a></li>
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
                            <li><a class="dropdown-item text-danger" href="#" @click.prevent="logout" data-testid="logout"><i class="ri-logout-box-r-line me-2"></i>{{ t('logout') || 'Logout' }}</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Components -->
        <user-admin ref="userAdmin"></user-admin>
        <user-profile ref="userProfile"></user-profile>
        <share-modal ref="shareModal" @transfer="openTransferWithFile"></share-modal>
        <upload-modal ref="uploadModal"></upload-modal>
        <file-history-modal ref="fileHistoryModal"></file-history-modal>
        <transfer-modal ref="transferModal"></transfer-modal>

        <div v-if="commandPaletteOpen" class="command-palette-backdrop" @mousedown.self="closeCommandPalette">
            <section class="command-palette" role="dialog" aria-modal="true" :aria-label="t('command_palette')">
                <div class="command-palette-search input-group input-group-lg">
                    <span class="input-group-text bg-transparent border-0">
                        <i class="ri-search-line" aria-hidden="true"></i>
                    </span>
                    <input
                        ref="commandPaletteInput"
                        id="commandPaletteSearch"
                        name="command_palette_search"
                        type="text"
                        class="form-control border-0 shadow-none"
                        v-model="commandPaletteQuery"
                        autocomplete="off"
                        :placeholder="t('command_palette_placeholder')"
                        :aria-label="t('command_palette')"
                        @keydown.down.prevent="moveCommandSelection(1)"
                        @keydown.up.prevent="moveCommandSelection(-1)"
                        @keydown.enter.prevent="runSelectedCommand"
                        @keydown.esc.prevent="closeCommandPalette"
                    >
                    <button type="button" class="btn btn-link text-secondary text-decoration-none" @click="closeCommandPalette" :aria-label="t('close') || 'Close'">
                        <i class="ri-close-line" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="command-palette-list py-1">
                    <button
                        v-for="(command, index) in filteredCommandActions"
                        :key="command.id"
                        type="button"
                        class="command-palette-item"
                        :class="{ active: index === commandPaletteSelectedIndex }"
                        :disabled="!command.enabled"
                        @mouseenter="commandPaletteSelectedIndex = index"
                        @click="runCommand(command)"
                    >
                        <span class="command-palette-icon"><i :class="command.icon" aria-hidden="true"></i></span>
                        <span class="flex-grow-1">
                            <span class="d-block fw-semibold">{{ command.label }}</span>
                            <span v-if="!command.enabled && command.reason" class="d-block small text-muted">{{ command.reason }}</span>
                        </span>
                    </button>
                    <div v-if="filteredCommandActions.length === 0" class="text-center text-muted small py-4">
                        {{ t('command_no_results') }}
                    </div>
                </div>
            </section>
        </div>

        <!-- Toolbar -->
        <div class="bg-body-tertiary border-bottom p-2 d-flex gap-1 gap-md-2 align-items-center flex-wrap app-toolbar">
            <template v-if="!store.isTrashMode">
                <button class="btn btn-primary btn-sm d-none d-sm-inline-flex align-items-center" @click="createFolder" :title="t('new_folder')" data-testid="create-folder">
                    <i class="ri-folder-add-line"></i> <span class="d-none d-md-inline">{{ t('new_folder') }}</span>
                </button>
                <button class="btn btn-primary btn-sm app-toolbar-primary d-inline-flex align-items-center" @click="uploadFile" :title="t('upload')" data-testid="upload">
                    <i class="ri-upload-cloud-2-line"></i> <span>{{ t('upload') }}</span>
                </button>

                <div class="vr mx-1 d-none d-sm-block"></div>

                <button class="btn btn-outline-secondary btn-sm d-none d-sm-inline-flex align-items-center" @click="copySelected" :disabled="store.selectedItems.length === 0" :title="t('copy')">
                    <i class="ri-file-copy-line"></i> <span class="d-none d-xl-inline">{{ t('copy') }}</span>
                </button>
                <button class="btn btn-outline-secondary btn-sm d-none d-sm-inline-flex align-items-center" @click="cutSelected" :disabled="store.selectedItems.length === 0" :title="t('cut')">
                    <i class="ri-scissors-cut-line"></i> <span class="d-none d-xl-inline">{{ t('cut') }}</span>
                </button>
                <button class="btn btn-outline-success btn-sm d-none d-sm-inline-flex align-items-center" @click="paste" :disabled="store.clipboard.items.length === 0" :title="t('paste')">
                    <i class="ri-clipboard-line"></i>
                    <span class="d-none d-md-inline">{{ t('paste') }}</span>
                    <span v-if="store.clipboard.items.length > 0" class="badge bg-success ms-1">{{ store.clipboard.items.length }}</span>
                </button>

                <div class="vr mx-1 d-none d-sm-block"></div>

                <button class="btn btn-outline-danger btn-sm d-none d-sm-inline-flex align-items-center" @click="deleteSelected" :disabled="store.selectedItems.length === 0" :title="t('delete')">
                    <i class="ri-delete-bin-line"></i> <span class="d-none d-md-inline">{{ t('delete') }}</span>
                </button>
                <button class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center" @click="toggleDetailsPane" :class="{active: detailsPaneOpen}" :aria-pressed="detailsPaneOpen" :title="t('details_pane')">
                    <i class="ri-sidebar-right-line" aria-hidden="true"></i> <span class="d-none d-xl-inline">{{ t('details') }}</span>
                </button>

                <!-- Overflow Menu -->
                <div class="dropdown app-toolbar-overflow">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-label="More actions">
                        <i class="ri-more-2-fill" aria-hidden="true"></i>
                        <span class="d-sm-none">{{ t('more_actions') }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li class="d-sm-none"><a class="dropdown-item" href="#" @click.prevent="createFolder">
                            <i class="ri-folder-add-line me-2"></i> {{ t('new_folder') }}
                        </a></li>
                        <li class="d-sm-none"><a class="dropdown-item" :class="{disabled: store.selectedItems.length === 0}" href="#" @click.prevent="copySelected">
                            <i class="ri-file-copy-line me-2"></i> {{ t('copy') }}
                        </a></li>
                        <li class="d-sm-none"><a class="dropdown-item" :class="{disabled: store.selectedItems.length === 0}" href="#" @click.prevent="cutSelected">
                            <i class="ri-scissors-cut-line me-2"></i> {{ t('cut') }}
                        </a></li>
                        <li class="d-sm-none"><a class="dropdown-item" :class="{disabled: store.clipboard.items.length === 0}" href="#" @click.prevent="paste">
                            <i class="ri-clipboard-line me-2"></i> {{ t('paste') }}
                        </a></li>
                        <li class="d-sm-none"><a class="dropdown-item text-danger" :class="{disabled: store.selectedItems.length === 0}" href="#" @click.prevent="deleteSelected">
                            <i class="ri-delete-bin-line me-2"></i> {{ t('delete') }}
                        </a></li>
                        <li class="d-sm-none"><hr class="dropdown-divider"></li>
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
                            <i class="ri-git-merge-line me-2"></i> Diff
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
                <button class="btn btn-success btn-sm me-2" @click="restoreSelected" :disabled="store.selectedItems.length === 0" data-testid="trash-restore">
                    <i class="ri-restart-line"></i> {{ t('restore') || 'Restore' }}
                </button>
                <button class="btn btn-outline-danger btn-sm" @click="emptyTrash" data-testid="empty-trash">
                    <i class="ri-delete-bin-2-line"></i> {{ t('empty_trash') || 'Empty Trash' }}
                </button>
            </template>
        </div>

        <div v-if="store.selectedItems.length > 0"
             class="selection-action-bar"
             role="region"
             :aria-label="t('selection_bar_label')">
            <div class="selection-action-bar-count" aria-live="polite">
                <i class="ri-checkbox-multiple-line" aria-hidden="true"></i>
                <span>{{ t('selected_count', {count: store.selectedItems.length}) }}</span>
            </div>

            <div class="selection-action-bar-actions">
                <button type="button"
                        class="btn btn-outline-secondary btn-sm"
                        @click="store.clearSelection()"
                        :aria-label="t('clear_selection')">
                    <i class="ri-close-line" aria-hidden="true"></i>
                    <span>{{ t('clear_selection') }}</span>
                </button>

                <template v-if="store.isTrashMode">
                    <button type="button" class="btn btn-success btn-sm" @click="restoreSelected" data-testid="selection-restore">
                        <i class="ri-restart-line" aria-hidden="true"></i>
                        <span>{{ t('restore') }}</span>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" @click="deletePermanent">
                        <i class="ri-delete-bin-2-line" aria-hidden="true"></i>
                        <span>{{ t('delete_perm') }}</span>
                    </button>
                </template>

                <template v-else>
                    <button v-if="store.selectedItems.length === 1"
                            type="button"
                            class="btn btn-primary btn-sm"
                            @click="downloadSelected"
                            data-testid="selection-download">
                        <i class="ri-download-line" aria-hidden="true"></i>
                        <span>{{ t('download') }}</span>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" @click="copySelected">
                        <i class="ri-file-copy-line" aria-hidden="true"></i>
                        <span>{{ t('copy') }}</span>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" @click="cutSelected">
                        <i class="ri-scissors-cut-line" aria-hidden="true"></i>
                        <span>{{ t('cut') }}</span>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" @click="deleteSelected" data-testid="selection-delete">
                        <i class="ri-delete-bin-line" aria-hidden="true"></i>
                        <span>{{ t('delete') }}</span>
                    </button>

                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle"
                                type="button"
                                data-bs-toggle="dropdown"
                                data-testid="selection-more"
                                :aria-label="t('selection_more_actions')">
                            <i class="ri-more-2-fill" aria-hidden="true"></i>
                            <span>{{ t('selection_more_actions') }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li v-if="store.selectedItems.length === 1">
                                <a class="dropdown-item" href="#" @click.prevent="renameSelected" data-testid="selection-rename">
                                    <i class="ri-edit-line me-2" aria-hidden="true"></i>{{ t('rename') }}
                                </a>
                            </li>
                            <li v-if="store.selectedItems.length === 1">
                                <a class="dropdown-item" href="#" @click.prevent="openShare">
                                    <i class="ri-share-line me-2" aria-hidden="true"></i>{{ t('share_title') || 'Share' }}
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" @click.prevent="chmodSelected">
                                    <i class="ri-lock-2-line me-2" aria-hidden="true"></i>{{ t('perms') }}
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" @click.prevent="showProperties">
                                    <i class="ri-information-line me-2" aria-hidden="true"></i>{{ t('properties') }}
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" @click.prevent="createArchive">
                                    <i class="ri-file-zip-line me-2" aria-hidden="true"></i>{{ t('archive') }}
                                </a>
                            </li>
                            <li v-if="store.selectedItems.length === 1 && isArchive(store.selectedItems[0])">
                                <a class="dropdown-item" href="#" @click.prevent="extractArchive">
                                    <i class="ri-folder-zip-line me-2" aria-hidden="true"></i>{{ t('extract') }}
                                </a>
                            </li>
                            <li v-if="store.selectedItems.length === 1 && store.selectedItems[0].type !== 'dir'">
                                <a class="dropdown-item" href="#" @click.prevent="openHistory">
                                    <i class="ri-history-line me-2" aria-hidden="true"></i>{{ t('version_history') }}
                                </a>
                            </li>
                            <li v-if="store.selectedItems.length === 2">
                                <a class="dropdown-item" href="#" @click.prevent="diffSelected">
                                    <i class="ri-git-merge-line me-2" aria-hidden="true"></i>Diff
                                </a>
                            </li>
                        </ul>
                    </div>
                </template>
            </div>
        </div>

        <!-- Main -->
        <div class="main-container"
             :class="{'drag-over': store.isDraggingOver}"
             @click="store.clearSelection(); hideContextMenu()"
             @contextmenu.prevent="hideContextMenu()"
             @dragover.prevent="onDragOver($event, null)"
             @dragleave="onDragLeave(null)"
             @drop.prevent="onDrop($event, null)">
            <div v-if="dragIndicatorMode === 'copy'"
                 class="drag-operation-indicator drag-operation-indicator-copy">
                <span class="drag-operation-indicator-icon">
                    <i class="ri-file-copy-line"></i>
                </span>
                <span>{{ t('copy') }}</span>
            </div>

            <!-- Sidebar -->
            <div class="sidebar offcanvas-lg offcanvas-start p-2" id="sidebarOffcanvas" tabindex="-1">
                 <div class="offcanvas-header d-lg-none">
                     <h5 class="offcanvas-title">Menu</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebarOffcanvas" :aria-label="t('close') || 'Close'"></button>
                 </div>
                 <div class="offcanvas-body d-flex flex-column p-0">
                     <!-- Connect -->
                     <div class="mb-4 px-2 d-flex gap-2">
                         <button class="btn btn-primary btn-sm flex-fill" @click="openTransfer">
                             <i class="ri-send-plane-fill me-1"></i> {{ t('send_files') || 'Send Files' }}
                         </button>
                         <button v-if="webdavEnabled" class="btn btn-outline-primary btn-sm" @click="showWebDav" title="WebDAV Connect" aria-label="WebDAV Connect">
                             <i class="ri-link" aria-hidden="true"></i>
                         </button>
                     </div>

                     <!-- Bookmarks -->
                     <div class="mb-4">
                         <div class="d-flex justify-content-between align-items-center mb-2 px-2">
                             <h6 class="small fw-bold text-uppercase text-muted mb-0">{{ t('bookmarks') }}</h6>
                             <button class="btn btn-link btn-sm p-0 text-decoration-none" @click="toggleBookmark(currentFolderBookmark)" :aria-label="t('bookmark_current_folder')" :title="t('bookmark_current_folder')">
                                 <i :class="store.isBookmarked(currentFolderBookmark) ? 'ri-star-fill' : 'ri-star-line'" aria-hidden="true"></i>
                             </button>
                         </div>
                         <div v-if="store.bookmarks.length === 0" class="small text-muted px-2">{{ t('bookmarks_empty') }}</div>
                         <div v-for="bookmark in store.bookmarks" :key="bookmark.path"
                              class="d-flex align-items-center py-1 px-2 rounded small file-item"
                              data-bs-dismiss="offcanvas" data-bs-target="#sidebarOffcanvas">
                             <i :class="getIcon(bookmark)" class="me-2"></i>
                             <span class="text-truncate flex-grow-1" @click="open(bookmark)">{{ bookmark.name }}</span>
                             <button class="btn btn-link btn-sm p-0 text-muted bookmark-item-action" @click.stop="store.removeBookmark(bookmark.path)" :aria-label="t('remove_bookmark') + ': ' + bookmark.name" :title="t('remove_bookmark')">
                                 <i class="ri-close-line" aria-hidden="true"></i>
                             </button>
                         </div>
                     </div>

                     <!-- Recent Files -->
                     <div v-if="store.recentFiles.length > 0" class="mb-4">
                         <div class="d-flex justify-content-between align-items-center mb-2 px-2">
                             <h6 class="small fw-bold text-uppercase text-muted mb-0">{{ t('recent_files') }}</h6>
                             <button class="btn btn-link btn-sm p-0 text-decoration-none" @click="store.clearRecent()" aria-label="Clear recent files" title="Clear recent files">
                                 <i class="ri-delete-bin-7-line" aria-hidden="true"></i>
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
                              data-testid="trash-toggle"
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
                        <button v-if="store.cwd" class="btn btn-light btn-sm position-absolute top-0 end-0 m-2 shadow-sm" @click="goUp" :title="t('up_one_level') || 'Up one level'" :aria-label="t('up_one_level') || 'Up one level'">
                            <i class="ri-arrow-up-line" aria-hidden="true"></i>
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
                             data-testid="file-item"
                             :data-file-name="file.name"
                             :data-file-path="file.path || file.originalPath"
                             :class="{'selected': store.isSelected(file), 'drag-over': file.isDragOver}"
                             draggable="true"
                             @dragstart="onDragStart($event, file)"
                             @dragend="onDragEnd"
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
            <aside v-if="detailsPaneOpen" class="details-pane p-3" aria-labelledby="detailsPaneTitle" @click.stop>
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <h6 id="detailsPaneTitle" class="mb-0">{{ t('details_pane') }}</h6>
                    <button class="btn btn-link btn-sm text-muted p-0" @click="closeDetailsPane" :aria-label="t('close') || 'Close'">
                        <i class="ri-close-line" aria-hidden="true"></i>
                    </button>
                </div>
                <div v-if="!detailsItem" class="text-center text-muted small py-4">
                    <i class="ri-information-line d-block fs-2 mb-2" aria-hidden="true"></i>
                    {{ t('details_empty') }}
                </div>
                <div v-else>
                    <div class="details-preview rounded d-flex align-items-center justify-content-center mb-3 overflow-hidden">
                        <img v-if="!detailsItem.isBulk && isImage(detailsItem)" :src="getThumbUrl(detailsItem)" class="w-100 h-100 object-fit-cover" :alt="detailsItem.name">
                        <i v-else :class="getIcon(detailsItem)" class="details-preview-icon" aria-hidden="true"></i>
                    </div>
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                        <div class="min-w-0">
                            <div class="fw-semibold text-truncate" :title="detailsItem.name">{{ detailsItem.name }}</div>
                            <div class="small text-muted text-truncate" :title="'/' + detailsItem.path">/{{ detailsItem.path }}</div>
                        </div>
                        <button v-if="!detailsItem.isBulk" class="btn btn-outline-secondary btn-sm" @click="toggleBookmark(detailsItem)" :aria-label="store.isBookmarked(detailsItem) ? t('remove_bookmark') : t('add_bookmark')" :title="store.isBookmarked(detailsItem) ? t('remove_bookmark') : t('add_bookmark')">
                            <i :class="store.isBookmarked(detailsItem) ? 'ri-star-fill' : 'ri-star-line'" aria-hidden="true"></i>
                        </button>
                    </div>
                    <dl class="row small mb-3">
                        <dt class="col-4">{{ t('type') }}</dt>
                        <dd class="col-8 text-truncate">{{ detailsItem.mime || detailsItem.type }}</dd>
                        <dt class="col-4">{{ t('size') }}</dt>
                        <dd class="col-8">{{ formatSize(detailsItem.size || 0) }}</dd>
                        <dt class="col-4">{{ t('date') }}</dt>
                        <dd class="col-8">{{ detailsItem.mtime ? formatDate(detailsItem.mtime) : '-' }}</dd>
                        <dt class="col-4">{{ t('perms') }}</dt>
                        <dd class="col-8">{{ detailsItem.perms || '-' }}</dd>
                        <template v-if="!detailsItem.isBulk">
                            <dt class="col-4">{{ t('share') }}</dt>
                            <dd class="col-8">
                                <span class="badge" :class="detailsItem.is_shared ? 'text-bg-primary' : 'text-bg-secondary'">{{ detailsShareLabel }}</span>
                            </dd>
                        </template>
                    </dl>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary btn-sm" @click="showProperties" :disabled="store.selectedItems.length === 0">
                            <i class="ri-information-line me-1" aria-hidden="true"></i>{{ t('properties') }}
                        </button>
                        <button v-if="store.selectedItems.length === 1" class="btn btn-outline-primary btn-sm" @click="openShare" :disabled="store.isTrashMode">
                            <i class="ri-share-line me-1" aria-hidden="true"></i>{{ t('share') }}
                        </button>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Status Bar -->
        <div class="bg-body-tertiary border-top px-3 py-2 small text-muted d-flex flex-wrap align-items-center gap-2 app-statusbar">
            <div class="d-flex align-items-center me-auto app-statusbar-summary">
                <span class="me-2 fw-bold text-primary version-label">v{{ appVersion }}</span>
                <span>{{ t('items_count', {count: store.pagination.total}) }}</span>
                <span class="ms-2 connection-label">({{ connectionMode === 'local' ? t('local_fs') : connectionMode.toUpperCase() }})</span>
            </div>

            <div v-if="store.pagination.total > store.pagination.pageSize" class="d-flex align-items-center gap-2 mx-auto order-2 order-md-1 app-statusbar-pagination">
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

            <div class="d-flex align-items-center gap-2 ms-auto order-1 order-md-2 app-statusbar-pagesize">
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
                        <button type="button" class="btn-close" data-bs-dismiss="modal" :aria-label="t('close') || 'Close'"></button>
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
                        <button type="button" class="btn-close" data-bs-dismiss="modal" :aria-label="t('close') || 'Close'"></button>
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
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" :aria-label="t('close') || 'Close'"></button>
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
                        <button v-if="previewState.list.length > 1" class="btn btn-dark bg-opacity-50 position-absolute start-0 m-3 rounded-circle" @click.stop="prevPreview" :disabled="previewState.index <= 0" :aria-label="t('prev') || 'Previous'">
                            <i class="ri-arrow-left-s-line fs-4" aria-hidden="true"></i>
                        </button>
                        <button v-if="previewState.list.length > 1" class="btn btn-dark bg-opacity-50 position-absolute end-0 m-3 rounded-circle" @click.stop="nextPreview" :disabled="previewState.index >= previewState.list.length - 1" :aria-label="t('next') || 'Next'">
                            <i class="ri-arrow-right-s-line fs-4" aria-hidden="true"></i>
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
                        <button type="button" class="btn-close" data-bs-dismiss="modal" :aria-label="t('close') || 'Close'"></button>
                    </div>
                    <div v-if="propFile" class="modal-body">
                        <div class="text-center mb-3">
                            <i :class="getIcon(propFile)" class="icon-large"></i>
                            <h6 class="mt-2">{{ propFile.name }}</h6>
                        </div>
                        <section aria-labelledby="propertiesMetadataHeading">
                            <h6 id="propertiesMetadataHeading" class="text-uppercase text-muted small fw-bold mb-2">{{ t('properties_metadata') }}</h6>
                            <table class="table table-sm small mb-0">
                                <tbody>
                                    <tr><th>{{ t('name') }}</th><td>{{ propFile.name }}</td></tr>
                                    <tr><th>{{ t('location') }}</th><td>/{{ propFile.path }}</td></tr>
                                    <tr><th>{{ t('size') }}</th><td>
                                        {{ formatSize(propFile.size) }}
                                        <button v-if="propFile.type === 'dir'" class="btn btn-link btn-sm p-0 ms-2 text-decoration-none" @click="calcDirSize">
                                            <i class="ri-calculator-line" aria-hidden="true"></i> {{ t('calculate') }}
                                        </button>
                                    </td></tr>
                                    <tr><th>{{ t('mime') }}</th><td>{{ propFile.mime }}</td></tr>
                                    <tr><th>{{ t('date') }}</th><td>{{ formatDate(propFile.mtime) }}</td></tr>
                                    <tr><th>{{ t('perms') }}</th><td>{{ propFile.perms }}</td></tr>
                                    <tr><th>{{ t('owner') }}</th><td>{{ propFile.owner || '-' }}</td></tr>
                                    <tr><th>{{ t('group') }}</th><td>{{ propFile.group || '-' }}</td></tr>
                                </tbody>
                            </table>
                        </section>

                        <section v-if="isAdmin" class="border border-warning-subtle rounded bg-warning-subtle p-3 mt-3" aria-labelledby="propertiesAdminHeading">
                            <div class="d-flex align-items-start gap-2 mb-3">
                                <i class="ri-shield-keyhole-line text-warning-emphasis fs-5" aria-hidden="true"></i>
                                <div>
                                    <h6 id="propertiesAdminHeading" class="mb-1">{{ t('properties_ownership_actions') }}</h6>
                                    <div class="small text-warning-emphasis">{{ t('properties_ownership_desc') }}</div>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-sm-6">
                                    <label class="form-label small" for="propOwner">{{ t('owner') }}</label>
                                    <input id="propOwner" type="text" class="form-control form-control-sm" v-model="propFile.owner" autocomplete="off">
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label small" for="propGroup">{{ t('group') }}</label>
                                    <input id="propGroup" type="text" class="form-control form-control-sm" v-model="propFile.group" autocomplete="off">
                                </div>
                            </div>
                            <div class="form-check small mt-3">
                                <input class="form-check-input" type="checkbox" id="propRecursive" v-model="propFile.recursive">
                                <label class="form-check-label" for="propRecursive">{{ t('apply_recursively') }}</label>
                            </div>
                            <div v-if="propFile.recursive" class="alert alert-warning small py-2 mt-2 mb-0">
                                {{ t('recursive_owner_warning') }}
                            </div>
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
                                <div class="small text-muted">{{ propertiesTargetSummary }}</div>
                                <button class="btn btn-warning btn-sm" type="button" @click="saveChown">
                                    <i class="ri-shield-check-line me-1" aria-hidden="true"></i>{{ t('apply_owner_group') }}
                                </button>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>

        <!-- WebDAV Modal -->
        <div class="modal fade" id="webdavModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h5 class="modal-title fs-6">{{ t('webdav_connect') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" :aria-label="t('close') || 'Close'"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted">{{ t('webdav_help') }}</p>
                        <div class="mb-3">
                            <label class="form-label small fw-bold" for="webdav_url_input">{{ t('webdav_url') }}</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" :value="webDavUrl" readonly id="webdav_url_input">
                                <button class="btn btn-outline-secondary" @click="copyWebDavUrl">
                                    <i class="ri-file-copy-line" aria-hidden="true"></i>
                                    <span>{{ t('webdav_copy_url') }}</span>
                                </button>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <div class="border rounded p-3 h-100">
                                    <div class="fw-bold small mb-2"><i class="ri-windows-line me-1" aria-hidden="true"></i>{{ t('webdav_windows_title') }}</div>
                                    <div class="small text-muted">{{ t('webdav_windows_hint') }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3 h-100">
                                    <div class="fw-bold small mb-2"><i class="ri-apple-line me-1" aria-hidden="true"></i>{{ t('webdav_macos_title') }}</div>
                                    <div class="small text-muted">{{ t('webdav_macos_hint') }}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3 h-100">
                                    <div class="fw-bold small mb-2"><i class="ri-terminal-box-line me-1" aria-hidden="true"></i>{{ t('webdav_linux_title') }}</div>
                                    <div class="small text-muted">{{ t('webdav_linux_hint') }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info py-2 small mb-2">
                            <i class="ri-key-2-line me-1" aria-hidden="true"></i>
                            {{ t('webdav_credentials_note', {username: username}) }}
                        </div>
                        <div class="alert alert-warning py-2 small mb-2">
                            <i class="ri-shield-check-line me-1" aria-hidden="true"></i>
                            {{ t('webdav_https_note') }}
                        </div>
                        <div class="alert alert-light border py-2 small mb-0">
                            <i class="ri-admin-line me-1" aria-hidden="true"></i>
                            {{ t('webdav_policy_note') }}
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
