#!/usr/bin/env node

const crypto = require('node:crypto');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const manifestPath = path.join(__dirname, 'vendor-assets.json');

function fail(message) {
  process.stderr.write(`Vendor asset check failed: ${message}\n`);
  process.exit(1);
}

let manifest;
try {
  manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
} catch (error) {
  fail(`cannot read ${path.relative(root, manifestPath)}: ${error.message}`);
}

if (!Array.isArray(manifest.packages) || manifest.packages.length === 0) {
  fail('manifest must contain package entries');
}

const checked = new Set();
for (const pkg of manifest.packages) {
  if (!pkg.name || !pkg.version || !pkg.source || !pkg.integrity || !pkg.files) {
    fail('each package entry needs name, version, source, integrity and files');
  }

  for (const [relativePath, expectedHash] of Object.entries(pkg.files)) {
    if (!relativePath.startsWith('public/assets/') || path.isAbsolute(relativePath)) {
      fail(`unexpected asset path ${relativePath}`);
    }
    const assetPath = path.resolve(root, relativePath);
    if (!assetPath.startsWith(`${root}${path.sep}`)) {
      fail(`asset path escapes the project: ${relativePath}`);
    }
    if (!/^[a-f0-9]{64}$/.test(expectedHash)) {
      fail(`invalid SHA-256 for ${relativePath}`);
    }
    if (checked.has(relativePath)) {
      fail(`asset is listed more than once: ${relativePath}`);
    }
    checked.add(relativePath);

    let content;
    try {
      content = fs.readFileSync(assetPath);
    } catch {
      fail(`missing ${relativePath} (${pkg.name}@${pkg.version})`);
    }
    const actualHash = crypto.createHash('sha256').update(content).digest('hex');
    if (actualHash !== expectedHash) {
      fail(`${relativePath} differs from the recorded ${pkg.name}@${pkg.version} asset`);
    }
  }
}

process.stdout.write(`Verified ${checked.size} copied vendor assets from ${manifest.packages.length} package records.\n`);
