const fs = require('node:fs');
const tag = process.env.RELEASE_TAG;
if (!/^v\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/.test(tag)) throw new Error('Invalid release tag');
const version = tag.slice(1);
const changelog = fs.readFileSync('CHANGELOG.md', 'utf8');
const start = changelog.indexOf(`## [${version}]`);
if (start < 0) throw new Error('Release changelog entry missing');
const end = changelog.indexOf('\n## [', start + 1);
const notes = changelog.slice(start, end < 0 ? undefined : end).trim();
fs.writeFileSync('release-artifacts/release-notes.md', `${notes}

### Installation and upgrade

Use \`eXtplorer-${version}-release.zip\` and verify its accompanying SHA-256 file. GitHub's automatically generated source archives are not installation packages.

Back up the writable tree, configuration, encryption key and any external storage/database before upgrading. Keep the same writable state, configuration and encryption key. Native installations support PHP 8.2–8.5; replace application files without overwriting writable data and run \`php spark security:migrate\`. Container users can pin \`ghcr.io/soerennb/extplorer3:${version}\` or its immutable digest and run the documented Compose update procedure. The init container activates the new code release automatically. SFTP is bundled and no longer needs the optional SSH2 extension.

For rollback, stop writes, restore the previous image/application and matching data/configuration backup. See [installation](https://github.com/${process.env.GITHUB_REPOSITORY}/blob/${tag}/docs/installation.md) and [Docker update/rollback instructions](https://github.com/${process.env.GITHUB_REPOSITORY}/blob/${tag}/README.md).

### Validation

- Release source: \`${process.env.GITHUB_SHA}\`
- AMD64/ARM64 image manifest: \`${process.env.MANIFEST_DIGEST}\`
- [Release checks and downloadable verification evidence](https://github.com/${process.env.GITHUB_REPOSITORY}/actions/runs/${process.env.GITHUB_RUN_ID})
- [Upgrade and release validation](https://github.com/${process.env.GITHUB_REPOSITORY}/blob/${tag}/docs/validation/release-${version}.md)
`);
