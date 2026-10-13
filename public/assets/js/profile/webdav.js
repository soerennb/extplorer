function useProfileWebDav(t, copyText) {
    const { ref, reactive, computed, watch } = Vue;
    const webDavCredentials = ref([]);

    const webDavLabel = ref('');

    const webDavSecret = ref('');

    const webDavLoading = ref(false);

    const webDavMessage = reactive({ type: '', text: '' });

    const loadWebDavCredentials = async () => {
        webDavLoading.value = true;
        try {
            const response = await Api.get('profile/webdav-credentials');
            webDavCredentials.value = response.items || [];
        } catch (e) {
            webDavMessage.type = 'error';
            webDavMessage.text = e.message || (t('webdav_load_failed') || 'Failed to load WebDAV credentials.');
        } finally {
            webDavLoading.value = false;
        }
    };

    const createWebDavCredential = async () => {
        if (!webDavLabel.value.trim()) return;
        webDavLoading.value = true;
        webDavMessage.text = '';
        webDavSecret.value = '';
        try {
            const response = await Api.post('profile/webdav-credentials', { label: webDavLabel.value.trim() });
            webDavSecret.value = response.secret || '';
            webDavLabel.value = '';
            await loadWebDavCredentials();
        } catch (e) {
            webDavMessage.type = 'error';
            webDavMessage.text = e.message || (t('webdav_create_failed') || 'Failed to create WebDAV credential.');
        } finally {
            webDavLoading.value = false;
        }
    };

    const deleteWebDavCredential = async (id) => {
        webDavLoading.value = true;
        webDavMessage.text = '';
        try {
            await Api.delete('profile/webdav-credentials/' + encodeURIComponent(id));
            webDavSecret.value = '';
            await loadWebDavCredentials();
        } catch (e) {
            webDavMessage.type = 'error';
            webDavMessage.text = e.message || (t('webdav_delete_failed') || 'Failed to revoke WebDAV credential.');
        } finally {
            webDavLoading.value = false;
        }
    };

    const copyWebDavSecret = () => copyText(webDavSecret.value, t('copy_code_success') || 'Password copied');

    const formatCredentialDate = (timestamp) => new Date(Number(timestamp) * 1000).toLocaleString();
    return { webDavCredentials, webDavLabel, webDavSecret, webDavLoading, webDavMessage, loadWebDavCredentials, createWebDavCredential, deleteWebDavCredential, copyWebDavSecret, formatCredentialDate };
}
