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
