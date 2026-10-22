// Fixtures for semgrep --test; this file is never executed or packaged.
// ruleid: extplorer.javascript.no-dynamic-code
eval(payload);
// ruleid: extplorer.javascript.no-dynamic-code
new Function(payload);
// ruleid: extplorer.javascript.no-dynamic-code
Function(payload);
// ruleid: extplorer.javascript.no-document-write
document.write(filename);
// ok: extplorer.javascript.no-document-write
element.textContent = filename;
// ok: extplorer.javascript.no-dynamic-code
JSON.parse(payload);
// ruleid: extplorer.javascript.no-interpolated-alert-html
Swal.fire({ title: 'Permissions', html: `<input value="${file.perms}">` });
// ruleid: extplorer.javascript.no-interpolated-alert-html
Swal.fire({ html: `<label>${i18n.t('mode')}</label><b>${file.name}</b>` });
// ok: extplorer.javascript.no-interpolated-alert-html
Swal.fire({ html: `<input placeholder="${i18n.t('octal_mode')}">`, didOpen: () => { input.value = file.perms; } });
// ok: extplorer.javascript.no-interpolated-alert-html
Swal.fire({ html: `<input placeholder="${(window.i18n && i18n.t('current_password')) || 'Current password'}">` });
// ruleid: extplorer.javascript.no-inner-html
element.innerHTML = message;
// ruleid: extplorer.javascript.no-inner-html
element.insertAdjacentHTML('beforeend', message);
