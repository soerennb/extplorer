#!/usr/bin/env node

const fs = require('fs');
const path = require('path');
const { compile } = require('@vue/compiler-dom');

const root = path.resolve(__dirname, '..');
const componentSource = path.join(root, 'public', 'assets', 'js', 'components');
const componentOutput = path.join(root, 'public', 'assets', 'js', 'compiled', 'components');
const output = path.join(root, 'public', 'assets', 'js', 'compiled');

function write(file, content) {
    fs.mkdirSync(path.dirname(file), { recursive: true });
    const text = typeof content === 'string' ? content : content.toString('utf8');
    fs.writeFileSync(file, text.replace(/[ \t]+$/gm, ''));
}

function compiledRender(template, filename) {
    const errors = [];
    const result = compile(template, {
        mode: 'function',
        hoistStatic: true,
        filename,
        onError: (error) => errors.push(error),
    });

    if (errors.length > 0) {
        throw new Error(`${filename}: ${errors.map((error) => error.message || error).join('; ')}`);
    }

    return `((Vue) => {\n${result.code}\n})(Vue)`;
}

function replaceTemplate(source, filename) {
    const marker = source.indexOf('template: `');
    if (marker < 0) {
        throw new Error(`${filename}: template property not found`);
    }

    const templateStart = marker + 'template: `'.length;
    let templateEnd = templateStart;
    while (templateEnd < source.length) {
        if (source[templateEnd] === '`' && source[templateEnd - 1] !== '\\') {
            break;
        }
        templateEnd++;
    }
    if (templateEnd >= source.length) {
        throw new Error(`${filename}: unterminated template`);
    }

    const template = source.slice(templateStart, templateEnd);
    const render = compiledRender(template, filename);
    return source.slice(0, marker) + `render: ${render}` + source.slice(templateEnd + 1);
}

function buildComponents() {
    for (const file of fs.readdirSync(componentSource).filter((name) => name.endsWith('.js')).sort()) {
        const sourcePath = path.join(componentSource, file);
        const source = fs.readFileSync(sourcePath, 'utf8');
        write(path.join(componentOutput, file), replaceTemplate(source, file));
    }
}

function buildRootRender(viewName, marker, outputName, globalName) {
    const viewPath = path.join(root, 'app', 'Views', viewName);
    const view = fs.readFileSync(viewPath, 'utf8');
    const start = view.indexOf(marker);
    if (start < 0) {
        throw new Error(`${viewName}: root marker not found`);
    }
    const openEnd = view.indexOf('>', start);
    const scriptStart = view.indexOf('\n    <?php if (!$is_file): ?>', openEnd);
    const end = scriptStart >= 0 ? scriptStart : view.indexOf('\n    <script', openEnd);
    if (openEnd < 0 || end < 0) {
        throw new Error(`${viewName}: root boundary not found`);
    }

    const body = view.slice(openEnd + 1, end);
    const closing = body.lastIndexOf('\n    </div>');
    if (closing < 0) {
        throw new Error(`${viewName}: root closing element not found`);
    }

    const render = compiledRender(body.slice(0, closing), viewName);
    write(path.join(output, outputName), `const ${globalName} = ${render};\n`);
}

buildComponents();
buildRootRender('app.php', '<div id="app" v-cloak data-testid="app-shell">', 'app-render.js', 'appTemplateRender');
buildRootRender('shared.php', '<div id="app" class="shared-container">', 'shared-render.js', 'sharedAppTemplateRender');

const runtimeSource = require.resolve('vue/dist/vue.runtime.global.prod.js');
write(
    path.join(root, 'public', 'assets', 'js', 'vue.runtime.global.prod.js'),
    fs.readFileSync(runtimeSource)
);

console.log('Vue templates compiled without a runtime template compiler.');
