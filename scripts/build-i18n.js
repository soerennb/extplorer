#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const sourceDir = path.join(root, 'resources', 'i18n');
const outputDir = path.join(root, 'public', 'assets', 'i18n');
const manifestPath = path.join(sourceDir, 'locales.json');
const outputManifestPath = path.join(outputDir, 'locales.json');

function readJson(file) {
    return JSON.parse(fs.readFileSync(file, 'utf8'));
}

function writeJson(file, value) {
    fs.mkdirSync(path.dirname(file), { recursive: true });
    fs.writeFileSync(file, JSON.stringify(value, null, 4) + '\n');
}

function localeCodes() {
    const locales = readJson(manifestPath);
    return locales.map((locale) => locale.code);
}

function buildLocale(locale) {
    const localeDir = path.join(sourceDir, locale);
    const files = fs.readdirSync(localeDir)
        .filter((file) => file.endsWith('.json'))
        .sort((a, b) => a.localeCompare(b));

    const messages = {};
    for (const file of files) {
        Object.assign(messages, readJson(path.join(localeDir, file)));
    }

    return messages;
}

for (const locale of localeCodes()) {
    writeJson(path.join(outputDir, `${locale}.json`), buildLocale(locale));
}

writeJson(outputManifestPath, readJson(manifestPath));
