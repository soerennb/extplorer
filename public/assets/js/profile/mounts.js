function useProfileMounts(t) {
    const { ref, reactive, computed, watch } = Vue;
    const mounts = ref([]);

    const mountsLoading = ref(false);

    const mountSaving = ref(false);

    const mountTesting = ref(false);

    const mountRemovingId = ref('');

    const mountMode = ref('create');

    const editingMountId = ref('');

    const mountMessage = reactive({ type: '', text: '' });

    const mountTestResult = reactive({ type: '', text: '' });

    const mountErrors = reactive({ name: '', path: '', host: '', user: '', pass: '' });

    const mountForm = reactive({
        id: '',
        name: '',
        type: 'local',
        hasStoredPass: false,
        hasPrivateKey: false,
        hasPublicKey: false,
        config: { path: '', host: '', port: 21, user: '', pass: '', root: '/', host_key_fingerprint: '', auth_method: 'password', private_key: '', public_key: '', private_key_passphrase: '', tls_spki_pin: '' },
    });

    const clearMountErrors = () => {
        mountErrors.name = '';
        mountErrors.path = '';
        mountErrors.host = '';
        mountErrors.user = '';
        mountErrors.pass = '';
    };

    const resetMountMessages = () => {
        mountMessage.type = '';
        mountMessage.text = '';
        mountTestResult.type = '';
        mountTestResult.text = '';
    };

    const resetMountForm = () => {
        mountForm.id = '';
        mountForm.name = '';
        mountForm.type = 'local';
        mountForm.hasStoredPass = false;
        mountForm.hasPrivateKey = false;
        mountForm.hasPublicKey = false;
        mountForm.config.path = '';
        mountForm.config.host = '';
        mountForm.config.port = 21;
        mountForm.config.user = '';
        mountForm.config.pass = '';
        mountForm.config.root = '/';
        mountForm.config.host_key_fingerprint = '';
        mountForm.config.auth_method = 'password';
        mountForm.config.private_key = '';
        mountForm.config.public_key = '';
        mountForm.config.private_key_passphrase = '';
        mountForm.config.tls_spki_pin = '';
    };

    const setMountMessage = (type, text) => {
        mountMessage.type = type;
        mountMessage.text = text;
    };

    const loadMounts = async () => {
        mountsLoading.value = true;
        try {
            mounts.value = await Api.get('mounts');
        } catch (e) {
            console.error(e);
            setMountMessage('error', e.message || (t('mount_load_failed') || 'Failed to load mounts'));
        } finally {
            mountsLoading.value = false;
        }
    };

    const validateMountForm = () => {
        clearMountErrors();
        let valid = true;

        if (!mountForm.name.trim()) {
            mountErrors.name = t('mount_error_name') || 'Name is required.';
            valid = false;
        }

        if (mountForm.type === 'local') {
            if (!mountForm.config.path.trim()) {
                mountErrors.path = t('mount_error_path') || 'Local path is required.';
                valid = false;
            }
        } else {
            if (!mountForm.config.host.trim()) {
                mountErrors.host = t('mount_error_host') || 'Host is required.';
                valid = false;
            }
            if (!mountForm.config.user.trim()) {
                mountErrors.user = t('mount_error_user') || 'Username is required.';
                valid = false;
            }
            const keyAuth = mountForm.type === 'sftp' && mountForm.config.auth_method === 'private_key';
            if (!keyAuth && !mountForm.config.pass.trim() && !mountForm.hasStoredPass) {
                mountErrors.pass = t('mount_error_pass') || 'Password is required.';
                valid = false;
            }
            if (keyAuth && ((!mountForm.config.private_key.trim() && !mountForm.hasPrivateKey) || (!mountForm.config.public_key.trim() && !mountForm.hasPublicKey))) {
                mountErrors.pass = t('mount_error_keys') || 'Private and public keys are required.';
                valid = false;
            }
        }

        return valid;
    };

    const canSubmitMount = computed(() => {
        if (!mountForm.name.trim()) return false;
        if (mountForm.type === 'local') return !!mountForm.config.path.trim();
        if (!mountForm.config.host.trim() || !mountForm.config.user.trim()) return false;
        const keyAuth = mountForm.type === 'sftp' && mountForm.config.auth_method === 'private_key';
        if (!keyAuth && !mountForm.config.pass.trim() && !mountForm.hasStoredPass) return false;
        if (keyAuth && ((!mountForm.config.private_key.trim() && !mountForm.hasPrivateKey) || (!mountForm.config.public_key.trim() && !mountForm.hasPublicKey))) return false;
        return true;
    });

    const canTestMount = canSubmitMount;

    const startEditMount = async (mount) => {
        resetMountMessages();
        mountMode.value = 'edit';
        editingMountId.value = mount.id;

        try {
            const fullMount = await Api.get(`mounts/${mount.id}`);
            mountForm.id = fullMount.id;
            mountForm.name = fullMount.name || '';
            mountForm.type = fullMount.type || 'local';
            mountForm.hasStoredPass = !!fullMount.has_pass;
            mountForm.hasPrivateKey = !!fullMount.has_private_key;
            mountForm.hasPublicKey = !!fullMount.has_public_key;

            mountForm.config.path = fullMount.config?.path || '';
            mountForm.config.host = fullMount.config?.host || '';
            mountForm.config.port = fullMount.config?.port || (mountForm.type === 'sftp' ? 22 : 21);
            mountForm.config.user = fullMount.config?.user || '';
            mountForm.config.pass = '';
            mountForm.config.root = fullMount.config?.root || '/';
            mountForm.config.host_key_fingerprint = fullMount.config?.host_key_fingerprint || '';
            mountForm.config.auth_method = fullMount.config?.auth_method || 'password';
            mountForm.config.private_key = '';
            mountForm.config.public_key = '';
            mountForm.config.private_key_passphrase = '';
            mountForm.config.tls_spki_pin = fullMount.config?.tls_spki_pin || '';
        } catch (e) {
            console.error(e);
            setMountMessage('error', e.message || (t('mount_edit_failed') || 'Failed to load mount details'));
        }
    };

    const cancelEditMount = () => {
        mountMode.value = 'create';
        editingMountId.value = '';
        resetMountForm();
        clearMountErrors();
        mountTestResult.type = '';
        mountTestResult.text = '';
    };

    const saveMount = async () => {
        resetMountMessages();
        if (!validateMountForm()) return;

        mountSaving.value = true;
        try {
            const payload = {
                name: mountForm.name,
                type: mountForm.type,
                config: { ...mountForm.config },
            };

            if (mountMode.value === 'edit' && editingMountId.value) {
                await Api.put(`mounts/${editingMountId.value}`, payload);
                setMountMessage('success', t('mount_updated') || 'Mount updated successfully.');
            } else {
                await Api.post('mounts', payload);
                setMountMessage('success', t('mount_added') || 'Mount added successfully.');
            }

            cancelEditMount();
            await loadMounts();
            store.reload();
        } catch (e) {
            setMountMessage('error', e.message || (t('mount_save_failed') || 'Failed to save mount'));
        } finally {
            mountSaving.value = false;
        }
    };

    const testMount = async () => {
        resetMountMessages();
        if (!validateMountForm()) return;

        mountTesting.value = true;
        try {
            const payload = {
                id: mountMode.value === 'edit' ? editingMountId.value : null,
                name: mountForm.name,
                type: mountForm.type,
                config: { ...mountForm.config },
            };
            const res = await Api.post('mounts/test', payload);
            mountTestResult.type = 'success';
            mountTestResult.text = t('mount_test_success') || 'Connection successful.';

            if (res?.config?.path && mountForm.type === 'local') {
                mountForm.config.path = res.config.path;
            }
        } catch (e) {
            mountTestResult.type = 'error';
            mountTestResult.text = e.message || (t('mount_test_failed') || 'Connection failed.');
        } finally {
            mountTesting.value = false;
        }
    };

    const removeMount = async (id) => {
        const confirmed = await Swal.fire({
            title: t('confirm_title') || 'Are you sure?',
            text: t('mount_remove_confirm') || 'This mount will be removed.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: t('remove') || 'Remove',
        });
        if (!confirmed.isConfirmed) return;

        mountRemovingId.value = id;
        try {
            await Api.delete('mounts/' + id);
            if (editingMountId.value === id) {
                cancelEditMount();
            }
            await loadMounts();
            store.reload();
        } catch (e) {
            setMountMessage('error', e.message || (t('mount_remove_failed') || 'Failed to remove mount'));
        } finally {
            mountRemovingId.value = '';
        }
    };

    const mountSummary = (mount) => {
        if (mount.type === 'local') return mount.config?.path || '';
        const host = mount.config?.host || '';
        const port = mount.config?.port || '';
        const root = mount.config?.root || '/';
        return `${(mount.type || 'remote').toUpperCase()} ${host}:${port}${root ? ' ' + root : ''}`;
    };

    const mountTypeLabel = (type) => {
        if (type === 'ftp') return 'FTP';
        if (type === 'ftps') return 'FTPS';
        if (type === 'sftp') return 'SFTP';
        return t('mount_type_local') || 'Local';
    };

    watch(() => mountForm.type, (val, prev) => {
        resetMountMessages();
        clearMountErrors();
        if (val === 'ftp' && (!mountForm.config.port || mountForm.config.port === 22 || mountForm.config.port === 990)) mountForm.config.port = 21;
        if (val === 'ftps' && (!mountForm.config.port || mountForm.config.port === 22 || mountForm.config.port === 990)) mountForm.config.port = 21;
        if (val === 'sftp' && (!mountForm.config.port || mountForm.config.port === 21)) mountForm.config.port = 22;
        if (val === 'local') {
            mountForm.hasStoredPass = false;
            mountForm.hasPrivateKey = false;
            mountForm.hasPublicKey = false;
            mountForm.config.pass = '';
            mountForm.config.auth_method = 'password';
        } else if (prev === 'local' && mountMode.value === 'edit') {
            mountForm.hasStoredPass = false;
            mountForm.hasPrivateKey = false;
        }
    });
    return { mounts, mountsLoading, mountSaving, mountTesting, mountRemovingId, mountMode, editingMountId, mountMessage, mountTestResult, mountErrors, mountForm, clearMountErrors, resetMountMessages, resetMountForm, setMountMessage, loadMounts, validateMountForm, canSubmitMount, canTestMount, startEditMount, cancelEditMount, saveMount, testMount, removeMount, mountSummary, mountTypeLabel };
}
