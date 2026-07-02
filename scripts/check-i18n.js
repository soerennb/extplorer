#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const sourceDir = path.join(root, 'resources', 'i18n');
const outputDir = path.join(root, 'public', 'assets', 'i18n');
const manifestPath = path.join(sourceDir, 'locales.json');
const fallbackLocale = 'en';
const placeholderPattern = /\{[A-Za-z0-9_]+\}/g;

let failed = false;

function fail(message) {
    failed = true;
    console.error(`i18n: ${message}`);
}

function readJson(file) {
    try {
        return JSON.parse(fs.readFileSync(file, 'utf8'));
    } catch (error) {
        fail(`${path.relative(root, file)} is not valid JSON: ${error.message}`);
        return null;
    }
}

function stableJson(value) {
    return JSON.stringify(value, null, 4) + '\n';
}

function localeCodes() {
    const manifest = readJson(manifestPath);
    if (!Array.isArray(manifest)) {
        fail('resources/i18n/locales.json must contain an array');
        return [];
    }

    const seen = new Set();
    const codes = [];
    for (const entry of manifest) {
        if (!entry || typeof entry.code !== 'string' || entry.code === '') {
            fail('each locale manifest entry must have a non-empty code');
            continue;
        }
        if (seen.has(entry.code)) {
            fail(`duplicate locale code "${entry.code}" in manifest`);
            continue;
        }
        seen.add(entry.code);
        codes.push(entry.code);
    }

    if (!seen.has(fallbackLocale)) {
        fail(`fallback locale "${fallbackLocale}" is missing from manifest`);
    }

    return codes;
}

function buildLocale(locale) {
    const localeDir = path.join(sourceDir, locale);
    if (!fs.existsSync(localeDir)) {
        fail(`missing source directory resources/i18n/${locale}`);
        return {};
    }

    const messages = {};
    const files = fs.readdirSync(localeDir)
        .filter((file) => file.endsWith('.json'))
        .sort((a, b) => a.localeCompare(b));

    if (files.length === 0) {
        fail(`resources/i18n/${locale} has no JSON files`);
    }

    for (const file of files) {
        const data = readJson(path.join(localeDir, file));
        if (!data || typeof data !== 'object' || Array.isArray(data)) {
            fail(`resources/i18n/${locale}/${file} must contain an object`);
            continue;
        }
        for (const [key, value] of Object.entries(data)) {
            if (Object.prototype.hasOwnProperty.call(messages, key)) {
                fail(`duplicate key "${key}" in resources/i18n/${locale}`);
            }
            if (typeof value !== 'string') {
                fail(`key "${key}" in resources/i18n/${locale}/${file} must be a string`);
            } else if (value.trim() === '') {
                fail(`key "${key}" in resources/i18n/${locale}/${file} is empty`);
            }
            messages[key] = value;
        }
    }

    return messages;
}

function placeholders(value) {
    return [...new Set(value.match(placeholderPattern) || [])].sort();
}

const locales = localeCodes();
const built = Object.fromEntries(locales.map((locale) => [locale, buildLocale(locale)]));
const reference = built[fallbackLocale] || {};
const referenceKeys = Object.keys(reference);

for (const locale of locales) {
    const messages = built[locale] || {};
    const keys = Object.keys(messages);
    const missing = referenceKeys.filter((key) => !Object.prototype.hasOwnProperty.call(messages, key));
    const extra = keys.filter((key) => !Object.prototype.hasOwnProperty.call(reference, key));

    if (missing.length > 0) {
        fail(`${locale} is missing keys: ${missing.join(', ')}`);
    }
    if (extra.length > 0) {
        fail(`${locale} has extra keys: ${extra.join(', ')}`);
    }

    for (const key of referenceKeys) {
        if (!Object.prototype.hasOwnProperty.call(messages, key)) {
            continue;
        }
        const expected = placeholders(reference[key]).join(',');
        const actual = placeholders(messages[key]).join(',');
        if (expected !== actual) {
            fail(`${locale}.${key} placeholders differ: expected [${expected}], got [${actual}]`);
        }
    }

    const outputPath = path.join(outputDir, `${locale}.json`);
    if (!fs.existsSync(outputPath)) {
        fail(`missing runtime file public/assets/i18n/${locale}.json`);
        continue;
    }
    const output = fs.readFileSync(outputPath, 'utf8');
    if (output !== stableJson(messages)) {
        fail(`public/assets/i18n/${locale}.json is out of date; run node scripts/build-i18n.js`);
    }
}

const runtimeManifestPath = path.join(outputDir, 'locales.json');
if (!fs.existsSync(runtimeManifestPath)) {
    fail('missing runtime file public/assets/i18n/locales.json');
} else {
    const manifest = readJson(manifestPath);
    const runtimeManifest = fs.readFileSync(runtimeManifestPath, 'utf8');
    if (runtimeManifest !== stableJson(manifest)) {
        fail('public/assets/i18n/locales.json is out of date; run node scripts/build-i18n.js');
    }
}

if (failed) {
    process.exit(1);
}

console.log(`i18n: ${locales.length} locales and ${referenceKeys.length} keys are valid`);
