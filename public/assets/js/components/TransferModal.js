const TransferModal = {
    template: `
    <div class="modal fade" id="transferModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-send-plane-fill me-2"></i>Send Files</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs mb-3">
                        <li class="nav-item">
                            <a class="nav-link" :class="{active: tab === 'new'}" href="#" @click.prevent="tab = 'new'">New Transfer</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" :class="{active: tab === 'history'}" href="#" @click.prevent="loadHistory">History</a>
                        </li>
                    </ul>

                    <!-- New Transfer Tab -->
                    <div v-if="tab === 'new'">
                        <div v-if="successLink" class="text-center py-5">
                            <div class="mb-4 text-success display-1"><i class="ri-checkbox-circle-line"></i></div>
                            <h3>Transfer Sent!</h3>
                            <p class="text-muted">Your files have been sent and the link created.</p>
                            <div class="input-group mb-3 w-75 mx-auto">
                                <input type="text" class="form-control" :value="successLink" readonly id="transferLink">
                                <button class="btn btn-outline-primary" @click="copyLink">Copy</button>
                            </div>
                            <button class="btn btn-primary" @click="resetForm">Send Another</button>
                        </div>

                        <div v-else>
                            <div v-if="resumeState" class="alert alert-info d-flex align-items-center justify-content-between mb-3">
                                <div>
                                    <i class="ri-history-line me-2"></i> Unfinished transfer found ({{ resumeState.fileNames.length }} files).
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-danger" @click="resetForm">Discard</button>
                                    <button class="btn btn-sm btn-primary" @click="resumeUpload">Resume</button>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Left: File Drop -->
                                <div class="col-md-6 border-end">
                                    <div class="dropzone p-4 mb-3 border border-2 border-dashed rounded text-center bg-light transfer-dropzone"
                                         @dragover.prevent="isDragOver = true"
                                         @dragleave="isDragOver = false"
                                         @drop.prevent="handleDrop"
                                         :class="{'bg-primary-subtle border-primary': isDragOver}"
                                    >
                                        
                                        <div v-if="files.length === 0">
                                            <i class="ri-upload-cloud-2-line display-4 text-muted mb-2"></i>
                                            <p class="mb-2">Drag & Drop files or folders here</p>
                                            <button class="btn btn-sm btn-outline-primary position-relative overflow-hidden">
                                                Browse Files
                                                <input type="file" multiple class="position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer" @change="handleFileSelect">
                                            </button>
                                            <div class="mt-2 text-muted small">or</div>
                                            <button class="btn btn-sm btn-link position-relative overflow-hidden text-decoration-none">
                                                Browse Folder
                                                <input type="file" multiple webkitdirectory class="position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer" @change="handleFileSelect">
                                            </button>
                                        </div>
                                        
                                        <div v-else class="text-start w-100">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="fw-bold">{{ files.length }} Files</span>
                                                <span class="badge bg-secondary">{{ formatSize(totalSize) }}</span>
                                            </div>
                                            <div class="list-group list-group-flush overflow-auto transfer-files-list">
                                                <div v-for="(f, idx) in files" :key="idx" class="list-group-item d-flex justify-content-between align-items-center p-2 small">
                                                    <div class="text-truncate me-2" :title="f.name">
                                                        <i :class="getIcon(f.name)" class="me-1"></i> {{ f.name }}
                                                    </div>
                                                    <i class="ri-close-line text-danger cursor-pointer" @click="removeFile(idx)"></i>
                                                </div>
                                            </div>
                                            <button class="btn btn-sm btn-outline-danger w-100 mt-2" @click="files = []">Clear All</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right: Form -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Send To (Email)</label>
                                        <div class="input-group input-group-sm mb-1" v-for="(email, idx) in recipients" :key="idx">
                                            <input type="email" class="form-control" v-model="recipients[idx]" placeholder="recipient@example.com">
                                            <button class="btn btn-outline-danger" @click="recipients.splice(idx, 1)" v-if="recipients.length > 1"><i class="ri-delete-bin-line"></i></button>
                                        </div>
                                        <button class="btn btn-link btn-sm p-0 text-decoration-none" @click="recipients.push('')">+ Add Recipient</button>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Subject</label>
                                        <input type="text" class="form-control form-control-sm" v-model="form.subject" placeholder="Files for you">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Message</label>
                                        <textarea class="form-control form-control-sm" rows="3" v-model="form.message" placeholder="Here are the files..."></textarea>
                                    </div>

                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">Expires In</label>
                                            <select class="form-select form-select-sm" v-model="form.expiresIn">
                                                <option value="1">1 Day</option>
                                                <option value="3">3 Days</option>
                                                <option value="7">7 Days</option>
                                                <option value="14">14 Days</option>
                                                <option value="30">30 Days</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">&nbsp;</label>
                                            <div class="form-check form-check-sm">
                                                <input class="form-check-input" type="checkbox" id="notifyDown" v-model="form.notifyDownload">
                                                <label class="form-check-label" for="notifyDown">Notify on Download</label>
                                            </div>
                                        </div>
                                    </div>

                                    <button class="btn btn-primary w-100" @click="sendTransfer" :disabled="isUploading || files.length === 0">
                                        <span v-if="isUploading">
                                            <span class="spinner-border spinner-border-sm me-1"></span> Sending... {{ uploadProgress }}%
                                        </span>
                                        <span v-else>Transfer</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- History Tab -->
                    <div v-if="tab === 'history'">
                        <div v-if="loadingHistory" class="text-center p-3"><div class="spinner-border text-primary"></div></div>
                        <div v-else-if="history.length === 0" class="text-center text-muted p-5">
                            <i class="ri-history-line display-4 opacity-50"></i>
                            <p class="mt-2">No transfers sent yet.</p>
                        </div>
                        <div v-else class="table-responsive">
                            <table class="table table-hover table-sm small align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Subject</th>
                                        <th>To</th>
                                        <th>Date</th>
                                        <th>Size</th>
                                        <th>Downloads</th>
                                        <th>Expires</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in history" :key="item.hash">
                                        <td class="fw-bold">{{ item.subject || '(No Subject)' }}</td>
                                        <td class="text-truncate transfer-recipient" :title="item.recipients?.join(', ')">
                                            {{ item.recipients ? item.recipients[0] + (item.recipients.length > 1 ? ' +' + (item.recipients.length-1) : '') : '-' }}
                                        </td>
                                        <td>{{ formatDate(item.created_at) }}</td>
                                        <td>{{ formatSize(item.total_size) }}</td>
                                        <td>
                                            <span class="badge" :class="item.downloads > 0 ? 'bg-success' : 'bg-secondary'">{{ item.downloads }}</span>
                                        </td>
                                        <td>
                                            <span :class="{'text-danger': isExpired(item.expires_at)}">
                                                {{ isExpired(item.expires_at) ? 'Expired' : formatDate(item.expires_at) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-link p-0 me-2" @click="copyItemLink(item.hash)" title="Copy Link"><i class="ri-links-line"></i></button>
                                            <button class="btn btn-link p-0 text-danger" @click="deleteItem(item.hash)" title="Delete"><i class="ri-delete-bin-line"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    `,
    data() {
        return {
            tab: 'new',
            files: [],
            recipients: [''],
            form: {
                subject: '',
                message: '',
                expiresIn: 7,
                notifyDownload: true
            },
            isDragOver: false,
            isUploading: false,
            uploadProgress: 0,
            successLink: null,
            
            resumeState: null,
            
            history: [],
            loadingHistory: false,
            modal: null
        };
    },
    computed: {
        totalSize() {
            return this.files.reduce((acc, f) => acc + f.size, 0);
        }
    },
    mounted() {
        this.modal = new bootstrap.Modal(document.getElementById('transferModal'));
        document.getElementById('transferModal').addEventListener('show.bs.modal', this.checkResume);
    },
    methods: {
        checkResume() {
            const saved = localStorage.getItem('extplorer_transfer_resume');
            if (saved) {
                try {
                    const state = JSON.parse(saved);
                    // Basic expiry check (24h)
                    if (Date.now() - state.timestamp < 86400000) {
                        this.resumeState = state;
                    } else {
                        localStorage.removeItem('extplorer_transfer_resume');
                    }
                } catch(e) { localStorage.removeItem('extplorer_transfer_resume'); }
            }
        },
        open() {
            this.modal.show();
            if (!this.files.length && !this.successLink) {
                 this.tab = 'new';
            }
        },
        resetForm() {
            this.files = [];
            this.recipients = [''];
            this.form = { subject: '', message: '', expiresIn: 7, notifyDownload: true };
            this.successLink = null;
            this.uploadProgress = 0;
            this.isUploading = false;
            this.resumeState = null;
            localStorage.removeItem('extplorer_transfer_resume');
        },
        async resumeUpload() {
            if (!this.resumeState) return;
            
            const { sessionId, recipients, form, fileNames } = this.resumeState;
            
            // Restore Form
            this.recipients = recipients;
            this.form = form;
            this.resumeState.isResuming = true;
            
            // Ask user to re-select files (security restriction: can't restore File objects)
            const { value: fileList } = await Swal.fire({
                title: 'Resume Upload',
                text: `Please re-select the following files to resume: ${fileNames.join(', ')}`,
                input: 'file',
                inputAttributes: { multiple: 'multiple' },
                showCancelButton: true
            });

            if (fileList && fileList.length > 0) {
                // Verify files match
                const selectedNames = Array.from(fileList).map(f => f.name).sort();
                const savedNames = fileNames.sort();
                
                // Simple name check
                if (JSON.stringify(selectedNames) !== JSON.stringify(savedNames)) {
                    return Swal.fire('Error', 'Selected files do not match the pending upload.', 'error');
                }
                
                this.files = Array.from(fileList);
                this.sendTransfer(sessionId); // Pass existing session ID
            }
        },
        handleDrop(e) {
            this.isDragOver = false;
            const items = e.dataTransfer.items;
            if (items) {
                for (let i=0; i < items.length; i++) {
                    const item = items[i];
                    if (item.kind === 'file') {
                        const entry = item.webkitGetAsEntry();
                        if (entry.isFile) entry.file(f => this.files.push(f));
                    }
                }
                if (this.files.length === 0) [...e.dataTransfer.files].forEach(f => this.files.push(f));
            }
        },
        handleFileSelect(e) {
            [...e.target.files].forEach(f => this.files.push(f));
        },
        removeFile(idx) {
            this.files.splice(idx, 1);
        },
        async sendTransfer(existingSessionId = null) {
            const validRecipients = this.recipients.filter(e => e && e.includes('@'));
            if (validRecipients.length === 0) return alert('Please enter at least one valid recipient email.');

            this.isUploading = true;
            this.resumeState = null; // Hide resume UI
            this.uploadProgress = 0;
            
            const sessionId = existingSessionId || (Date.now().toString(36) + Math.random().toString(36).substr(2));
            
            // Save state for resume
            localStorage.setItem('extplorer_transfer_resume', JSON.stringify({
                sessionId,
                recipients: this.recipients,
                form: this.form,
                fileNames: this.files.map(f => f.name),
                timestamp: Date.now()
            }));

            try {
                // 1. Upload Files
                const totalBytes = this.totalSize;
                let uploadedBytes = 0; // Local counter for progress

                for (let file of this.files) {
                    const CHUNK_SIZE = 1024 * 1024; // 1MB
                    const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
                    
                    // Check status from server to skip chunks
                    let startChunk = 0;
                    if (existingSessionId) {
                        try {
                            const status = await Api.get(`transfer/status?sessionId=${sessionId}&fileName=${encodeURIComponent(file.name)}`);
                            if (status.status === 'complete') {
                                uploadedBytes += file.size;
                                continue; // Skip file
                            }
                            if (status.status === 'partial') {
                                startChunk = Math.floor(status.uploaded / CHUNK_SIZE);
                                uploadedBytes += (startChunk * CHUNK_SIZE);
                            }
                        } catch(e) { console.warn("Status check failed", e); }
                    }

                    for (let i = startChunk; i < totalChunks; i++) {
                        const chunk = file.slice(i * CHUNK_SIZE, (i + 1) * CHUNK_SIZE);
                        const fd = new FormData();
                        fd.append('file', chunk);
                        fd.append('sessionId', sessionId);
                        fd.append('fileName', file.name);
                        fd.append('chunkIndex', i);
                        fd.append('totalChunks', totalChunks);
                        
                        await this.uploadChunk(fd);
                        
                        uploadedBytes += chunk.size;
                        // Avoid >100% due to chunk overhead/estimates
                        this.uploadProgress = Math.min(95, Math.round((uploadedBytes / totalBytes) * 95));
                    }
                }
                
                this.uploadProgress = 98;

                // 2. Finalize Send
                const res = await Api.post('transfer/send', {
                    sessionId,
                    recipients: validRecipients,
                    subject: this.form.subject,
                    message: this.form.message,
                    expiresIn: this.form.expiresIn,
                    notifyDownload: this.form.notifyDownload
                });

                this.uploadProgress = 100;
                this.successLink = res.link;
                localStorage.removeItem('extplorer_transfer_resume'); // Clear resume state

            } catch (e) {
                alert('Transfer failed: ' + e.message);
                this.isUploading = false;
            }
        },
        async uploadChunk(formData) {
             const headers = { 'X-Requested-With': 'XMLHttpRequest' };
             if (window.csrfHash) headers['X-CSRF-TOKEN'] = window.csrfHash;
             
             // Retry logic
             let retries = 3;
             while (retries > 0) {
                 try {
                     const res = await fetch(window.baseUrl + 'api/transfer/upload', { method: 'POST', headers, body: formData });
                     if (!res.ok) throw new Error('Upload failed');
                     return;
                 } catch(e) {
                     retries--;
                     if (retries === 0) throw e;
                     await new Promise(r => setTimeout(r, 1000));
                 }
             }
        },
        copyLink() {
            const el = document.getElementById('transferLink');
            el.select();
            document.execCommand('copy');
        },
        copyItemLink(hash) {
            const link = window.baseUrl + 's/' + hash;
            navigator.clipboard.writeText(link);
        },
        async loadHistory() {
            this.tab = 'history';
            this.loadingHistory = true;
            try {
                this.history = await Api.get('transfer/history');
            } catch (e) { console.error(e); }
            finally { this.loadingHistory = false; }
        },
        async deleteItem(hash) {
            if (!confirm('Are you sure?')) return;
            try {
                await Api.delete('transfer/' + hash);
                this.loadHistory();
            } catch (e) { alert(e.message); }
        },
        
        // Helpers
        formatSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        formatDate(ts) { return new Date(ts * 1000).toLocaleDateString(); },
        isExpired(ts) { return Date.now()/1000 > ts; },
        getIcon(name) { return 'ri-file-line'; }
    }
};
