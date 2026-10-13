function useProfileSecurity(t, details, forcePasswordChange, loadWebDavCredentials, copyText) {
    const { ref, reactive, computed, watch } = Vue;
    const passwordForm = reactive({ current: '', new: '', confirm: '' });

    const passwordSaving = ref(false);

    const passwordMessage = reactive({ type: '', text: '' });

    const twoFaMessage = reactive({ type: '', text: '' });

    const twoFaLoading = ref(false);

    const setup = reactive({ step: 0, qr: '', secret: '', code: '', recoveryCodes: [] });

    const disable2faState = reactive({ open: false, password: '', code: '', loading: false });

    const passwordChecks = computed(() => {
        const value = passwordForm.new || '';
        return {
            length: value.length >= 12,
            case: /[a-z]/.test(value) && /[A-Z]/.test(value),
            number: /\d/.test(value),
            symbol: /[^A-Za-z0-9]/.test(value),
        };
    });

    const passwordScore = computed(() => {
        const checks = passwordChecks.value;
        let score = 0;
        if (checks.length) score += 1;
        if (checks.case) score += 1;
        if (checks.number) score += 1;
        if (checks.symbol) score += 1;
        return score;
    });

    const passwordStrength = computed(() => {
        const score = passwordScore.value;
        const labelMap = {
            0: t('password_strength_0') || 'Too weak',
            1: t('password_strength_1') || 'Weak',
            2: t('password_strength_2') || 'Fair',
            3: t('password_strength_3') || 'Good',
            4: t('password_strength_4') || 'Strong',
        };
        const widthClass = `strength-score-${score}`;
        const colorClass = score >= 4 ? 'bg-success' : score >= 3 ? 'bg-info' : score >= 2 ? 'bg-warning' : 'bg-danger';
        const textClass = score >= 4 ? 'text-success' : score >= 3 ? 'text-info' : score >= 2 ? 'text-warning' : 'text-danger';
        return { score, label: labelMap[score], widthClass, colorClass, textClass };
    });

    const passwordTouched = computed(() => passwordForm.new.length > 0 || passwordForm.confirm.length > 0);

    const passwordMismatch = computed(() => passwordForm.new !== '' && passwordForm.confirm !== '' && passwordForm.new !== passwordForm.confirm);

    const passwordInvalidMessage = computed(() => {
        if (!passwordChecks.value.length) return t('password_rule_length') || 'At least 12 characters';
        if (passwordMismatch.value) return t('password_mismatch') || 'Passwords do not match';
        return t('password_requirements_not_met') || 'Password requirements are not met';
    });

    const passwordInvalid = computed(() => {
        if (!passwordForm.current) return true;
        if (!passwordChecks.value.length) return true;
        if (passwordMismatch.value) return true;
        return false;
    });

    const twoFaStepperVisible = computed(() => setup.step > 0 || setup.recoveryCodes.length > 0);

    const twoFaStepClass = (step) => {
        let current = 0;
        if (setup.recoveryCodes.length > 0) {
            current = 3;
        } else if (setup.step === 1) {
            current = setup.code.trim() ? 2 : 1;
        }
        if (step < current) return 'is-complete';
        if (step === current) return 'is-active';
        return '';
    };

    const canSubmitDisable2fa = computed(() => {
        return disable2faState.password.trim() !== '' || disable2faState.code.trim() !== '';
    });

    const setTwoFaMessage = (type, text) => {
        twoFaMessage.type = type;
        twoFaMessage.text = text;
    };

    const setPasswordMessage = (type, text) => {
        passwordMessage.type = type;
        passwordMessage.text = text;
    };

    const updatePassword = async () => {
        setPasswordMessage('', '');
        if (passwordInvalid.value) return;

        passwordSaving.value = true;
        try {
            await Api.put('profile/password', {
                password: passwordForm.new,
                old_password: passwordForm.current,
            });
            setPasswordMessage('success', t('password_updated') || 'Password updated.');
            passwordForm.current = '';
            passwordForm.new = '';
            passwordForm.confirm = '';
            forcePasswordChange.value = false;
            window.forcePasswordChange = false;
            await loadWebDavCredentials();
        } catch (e) {
            setPasswordMessage('error', e.message || (t('password_update_failed') || 'Failed to update password'));
        } finally {
            passwordSaving.value = false;
        }
    };

    const start2faSetup = async () => {
        setTwoFaMessage('', '');
        twoFaLoading.value = true;
        try {
            const res = await Api.get('profile/2fa/setup');
            setup.qr = res.qr;
            setup.secret = res.secret;
            setup.step = 1;
            setup.code = '';
        } catch (e) {
            setTwoFaMessage('error', e.message || (t('twofa_setup_failed') || 'Failed to start 2FA setup'));
        } finally {
            twoFaLoading.value = false;
        }
    };

    const cancel2faSetup = () => {
        setup.step = 0;
        setup.code = '';
        setup.qr = '';
        setup.secret = '';
    };

    const finish2faSetup = async () => {
        setTwoFaMessage('', '');
        if (!setup.code) return;

        twoFaLoading.value = true;
        try {
            const res = await Api.post('profile/2fa/enable', {
                secret: setup.secret,
                code: setup.code,
            });
            details.value['2fa_enabled'] = true;
            setup.step = 2;
            setup.recoveryCodes = res.recovery_codes || [];
            setup.code = '';
            await loadWebDavCredentials();
        } catch (e) {
            setTwoFaMessage('error', e.message || (t('twofa_verify_failed') || 'Invalid verification code'));
        } finally {
            twoFaLoading.value = false;
        }
    };

    const openDisable2fa = () => {
        disable2faState.open = true;
        disable2faState.password = '';
        disable2faState.code = '';
        setTwoFaMessage('', '');
    };

    const closeDisable2fa = () => {
        disable2faState.open = false;
        disable2faState.password = '';
        disable2faState.code = '';
    };

    const submitDisable2fa = async () => {
        setTwoFaMessage('', '');
        if (!canSubmitDisable2fa.value) return;

        disable2faState.loading = true;
        try {
            await Api.post('profile/2fa/disable', {
                password: disable2faState.password,
                code: disable2faState.code,
            });
            details.value['2fa_enabled'] = false;
            closeDisable2fa();
            setTwoFaMessage('success', t('twofa_disabled') || 'Two-factor authentication disabled.');
            await loadWebDavCredentials();
        } catch (e) {
            setTwoFaMessage('error', e.message || (t('twofa_disable_failed') || 'Failed to disable 2FA'));
        } finally {
            disable2faState.loading = false;
        }
    };

    const copySecret = () => copyText(setup.secret, t('copy_code_success') || 'Code copied');

    const copyRecoveryCodes = () => copyText(setup.recoveryCodes.join('\n'), t('copy_recovery_codes_success') || 'Recovery codes copied');

    const downloadRecoveryCodes = () => {
        const content = setup.recoveryCodes.join('\n');
        const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'extplorer-recovery-codes.txt';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    };

    const completeRecoveryCodes = () => {
        setup.recoveryCodes = [];
        setup.step = 0;
    };
    return { passwordForm, passwordSaving, passwordMessage, twoFaMessage, twoFaLoading, setup, disable2faState, passwordChecks, passwordScore, passwordStrength, passwordTouched, passwordMismatch, passwordInvalidMessage, passwordInvalid, twoFaStepperVisible, twoFaStepClass, canSubmitDisable2fa, setTwoFaMessage, setPasswordMessage, updatePassword, start2faSetup, cancel2faSetup, finish2faSetup, openDisable2fa, closeDisable2fa, submitDisable2fa, copySecret, copyRecoveryCodes, downloadRecoveryCodes, completeRecoveryCodes };
}
