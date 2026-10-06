#!/usr/bin/env node

const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { spawnSync } = require('node:child_process');

const manifest = require('./vendor-assets.json');
const dependencies = {};

for (const pkg of manifest.packages) {
  if (dependencies[pkg.name] && dependencies[pkg.name] !== pkg.version) {
    process.stderr.write(`Conflicting pinned versions for ${pkg.name}.\n`);
    process.exit(1);
  }
  dependencies[pkg.name] = pkg.version;
}

const auditDir = fs.mkdtempSync(path.join(os.tmpdir(), 'extplorer-vendor-audit-'));
let status = 0;

try {
  fs.writeFileSync(path.join(auditDir, 'package.json'), `${JSON.stringify({
    name: 'extplorer3-vendor-assets-audit',
    version: '1.0.0',
    private: true,
    dependencies,
  }, null, 2)}\n`);

  for (const args of [
    ['install', '--package-lock-only', '--ignore-scripts', '--no-audit', '--no-fund'],
    ['audit', '--package-lock-only', '--audit-level=high'],
  ]) {
    const result = spawnSync('npm', args, { cwd: auditDir, stdio: 'inherit' });
    if (result.error) {
      process.stderr.write(`Could not run npm ${args[0]}: ${result.error.message}\n`);
      status = 1;
      break;
    }
    if (result.status !== 0) {
      status = result.status || 1;
      break;
    }
  }
} finally {
  fs.rmSync(auditDir, { recursive: true, force: true });
}

process.exit(status);
