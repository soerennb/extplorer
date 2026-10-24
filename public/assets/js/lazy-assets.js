// Loads the editor (Ace) and diff viewer bundles on first use instead of on
// every page load. Ace injects its own <style> elements, so they receive the
// page's CSP style nonce both while its script runs and through its DOM helper.
(function () {
    const pending = {};
    const assetUrl = (path) => window.baseUrl + path;

    const stampNonce = (element, tagName) => {
        if (window.cspStyleNonce && String(tagName).toLowerCase() === 'style') {
            element.setAttribute('nonce', window.cspStyleNonce);
        }
        return element;
    };

    const once = (key, load) => {
        if (!pending[key]) {
            pending[key] = load().catch((error) => {
                delete pending[key];
                throw error;
            });
        }
        return pending[key];
    };

    const loadScript = (path) => new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = assetUrl(path);
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Unable to load ' + path));
        document.head.appendChild(script);
    });

    const loadStyle = (path) => new Promise((resolve, reject) => {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = assetUrl(path);
        link.onload = () => resolve();
        link.onerror = () => reject(new Error('Unable to load ' + path));
        document.head.appendChild(link);
    });

    const loadAce = async () => {
        if (window.ace && typeof window.ace.edit === 'function') return window.ace;

        const doc = document;
        const ownCreate = Object.prototype.hasOwnProperty.call(doc, 'createElement');
        const ownCreateNS = Object.prototype.hasOwnProperty.call(doc, 'createElementNS');
        const createElement = doc.createElement;
        const createElementNS = doc.createElementNS;
        doc.createElement = function (tagName, options) {
            return stampNonce(createElement.call(doc, tagName, options), tagName);
        };
        doc.createElementNS = function (ns, tagName, options) {
            return stampNonce(createElementNS.call(doc, ns, tagName, options), tagName);
        };

        try {
            await loadScript('assets/vendor/ace/ace.min.js');
        } finally {
            if (ownCreate) doc.createElement = createElement; else delete doc.createElement;
            if (ownCreateNS) doc.createElementNS = createElementNS; else delete doc.createElementNS;
        }

        const aceDom = window.ace.require('ace/lib/dom');
        const aceCreate = aceDom.createElement.bind(aceDom);
        aceDom.createElement = (tagName, ns) => stampNonce(aceCreate(tagName, ns), tagName);
        window.ace.config.set('basePath', assetUrl('assets/vendor/ace'));
        return window.ace;
    };

    const loadDiff = async () => {
        await Promise.all([
            loadStyle('assets/css/diff2html.min.css'),
            loadScript('assets/js/diff.min.js'),
            loadScript('assets/js/diff2html-ui.min.js'),
        ]);
    };

    window.LazyAssets = {
        ace: () => once('ace', loadAce),
        diff: () => once('diff', loadDiff),
    };
})();
