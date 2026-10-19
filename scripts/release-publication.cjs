// Release artifacts are immutable; only the moving aliases participate in rollback.
const { execFileSync } = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { createHash } = require('node:crypto');

function command(program, args) {
    return execFileSync(program, args, { encoding: 'utf8', timeout: 120000, stdio: ['ignore', 'pipe', 'pipe'] });
}

function versionOf(tag) {
    if (!/^v\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/.test(tag)) throw new Error('Invalid release tag');
    return tag.slice(1);
}

function assertManifest(manifest, digest) {
    if (!/^sha256:[a-f0-9]{64}$/.test(manifest.digest) || (digest && manifest.digest !== digest)) {
        throw new Error('Unexpected manifest digest');
    }
    for (const architecture of ['amd64', 'arm64']) {
        if (!manifest.manifests?.some((entry) => entry.platform?.os === 'linux' && entry.platform.architecture === architecture)) {
            throw new Error(`Missing linux/${architecture} image`);
        }
    }
}

function registry(image) {
    return {
        inspect(ref) {
            return JSON.parse(command('docker', ['buildx', 'imagetools', 'inspect', `${image}:${ref}`, '--format', '{{json .Manifest}}']));
        },
        set(ref, sources) {
            command('docker', ['buildx', 'imagetools', 'create', '--tag', `${image}:${ref}`, ...sources.map((digest) => `${image}@${digest}`)]);
        },
        source(digest) {
            return JSON.parse(command('docker', ['buildx', 'imagetools', 'inspect', `${image}@${digest}`, '--format', '{{json .Manifest}}']));
        },
    };
}

function isMissing(error) {
    // Authentication, registry outages and rate limits must never mean "absent".
    return /(?:manifest unknown|MANIFEST_UNKNOWN|not found)/i.test(error.stderr?.toString() || '');
}

function createVersionManifest(tag, sources, remote) {
    const version = versionOf(tag);
    if (sources.length !== 2) throw new Error('Exactly two verified image digests required');
    const descriptors = sources.flatMap((digest) => {
        const source = remote.source(digest);
        return source.manifests || [source];
    });
    assertManifest({ digest: sources[0], manifests: descriptors });
    const expected = descriptors.map((entry) => entry.digest).sort();
    let existing;
    try { existing = remote.inspect(version); } catch (error) { if (!isMissing(error)) throw error; }
    if (existing) {
        const actual = existing.manifests?.map((entry) => entry.digest).sort();
        if (JSON.stringify(actual) !== JSON.stringify(expected)) throw new Error('Refusing to overwrite a different version manifest');
    } else {
        remote.set(version, sources);
        existing = remote.inspect(version);
    }
    assertManifest(existing);
    return existing.digest;
}

function promoteAliases(tag, digest, remote, finish = () => {}, record = () => {}) {
    const version = versionOf(tag);
    assertManifest(remote.inspect(version), digest);
    // Prereleases never move stable aliases.
    const aliases = version.includes('-') ? [] : [version.split('.').slice(0, 2).join('.'), version.split('.')[0], 'latest'];
    const previous = Object.fromEntries(aliases.map((alias) => [alias, remote.inspect(alias).digest]));
    record({ tag, digest, previous });
    try {
        for (const alias of aliases) {
            remote.set(alias, [digest]);
            assertManifest(remote.inspect(alias), digest);
        }
        finish();
    } catch (failure) {
        const rollbackErrors = [];
        for (const [alias, oldDigest] of Object.entries(previous)) {
            try {
                remote.set(alias, [oldDigest]);
                assertManifest(remote.inspect(alias), oldDigest);
            } catch (error) { rollbackErrors.push(`${alias}: ${error.message}`); }
        }
        if (rollbackErrors.length) throw new AggregateError([failure], `Publication failed; rollback incomplete: ${rollbackErrors.join('; ')}`);
        throw failure;
    }
}

function github(repository) {
    const api = (...args) => JSON.parse(command('gh', ['api', ...args]));
    return {
        get(tag) {
            try { return api(`repos/${repository}/releases/tags/${tag}`); }
            catch (error) { if (/HTTP 404/.test(error.stderr?.toString() || '')) return null; throw error; }
        },
        create(tag, sha, notes) {
            command('gh', ['release', 'create', tag, '--repo', repository, '--verify-tag', '--target', sha, '--title', `eXtplorer ${versionOf(tag)}`, '--draft', '--notes-file', notes]);
        },
        upload(tag, file) { command('gh', ['release', 'upload', tag, file, '--repo', repository]); },
        download(tag, name, directory) {
            command('gh', ['release', 'download', tag, '--repo', repository, '--pattern', name, '--dir', directory]);
        },
        publish(release, prerelease) {
            api('--method', 'PATCH', `repos/${repository}/releases/${release.id}`, '-F', 'draft=false', '-F', `prerelease=${prerelease}`, '-f', `make_latest=${prerelease ? 'false' : 'true'}`);
        },
    };
}

function sha256(file) { return createHash('sha256').update(fs.readFileSync(file)).digest('hex'); }

function publishRelease(options, remote, releases) {
    const { tag, sha, digest, directory, gates } = options;
    const version = versionOf(tag);
    if (!/^[a-f0-9]{40}$/.test(sha) || !['archive', 'container'].every((gate) => gates[gate] === 'success')) {
        throw new Error('Successful archive and container gates for the release SHA required');
    }
    const archive = `eXtplorer-${version}-release.zip`;
    const files = [archive, `${archive}.sha256`];
    const checksum = fs.readFileSync(path.join(directory, files[1]), 'utf8').trim().split(/\s+/);
    if (checksum[0] !== sha256(path.join(directory, archive)) || checksum[1] !== archive) throw new Error('Archive checksum mismatch');
    assertManifest(remote.inspect(version), digest);
    let release = releases.get(tag);
    if (!release) {
        releases.create(tag, sha, path.join(directory, 'release-notes.md'));
        release = releases.get(tag);
    }
    if (release.target_commitish !== sha) throw new Error('Release points to a different commit');
    const temporary = fs.mkdtempSync(path.join(os.tmpdir(), 'extplorer-publish-'));
    try {
        for (const file of files) {
            if (!release.assets.some((asset) => asset.name === file)) {
                if (!release.draft) throw new Error('Published release is missing an immutable asset');
                releases.upload(tag, path.join(directory, file));
            }
            releases.download(tag, file, temporary);
            if (sha256(path.join(temporary, file)) !== sha256(path.join(directory, file))) throw new Error(`Refusing to replace differing asset ${file}`);
        }
        if (!release.draft) {
            // A retry after a lost API response may already have finished.
            for (const alias of version.includes('-') ? [] : [version.split('.').slice(0, 2).join('.'), version.split('.')[0], 'latest']) {
                assertManifest(remote.inspect(alias), digest);
            }
            return;
        }
        promoteAliases(tag, digest, remote, () => {
            try { releases.publish(release, version.includes('-')); }
            catch (error) {
                // Do not roll back a successful publication whose response was lost.
                if (releases.get(tag)?.draft !== false) throw error;
            }
        }, (state) => fs.writeFileSync(path.join(directory, 'publication-state.json'), JSON.stringify({ sha, ...state }, null, 2) + '\n'));
    } finally { fs.rmSync(temporary, { recursive: true, force: true }); }
}

if (require.main === module) {
    try {
        const tag = process.env.RELEASE_TAG;
        const remote = registry(process.env.IMAGE_NAME || 'ghcr.io/soerennb/extplorer3');
        const releases = github(process.env.GITHUB_REPOSITORY);
        switch (process.argv[2]) {
            case 'manifest': {
                const sources = fs.readdirSync('digests').filter((file) => /^[a-f0-9]{64}$/.test(file)).map((file) => `sha256:${file}`);
                const digest = createVersionManifest(tag, sources, remote);
                fs.writeFileSync('manifest.json', JSON.stringify({ tag, sha: process.env.RELEASE_SHA, digest }, null, 2) + '\n');
                if (process.env.GITHUB_OUTPUT) fs.appendFileSync(process.env.GITHUB_OUTPUT, `manifest_digest=${digest}\n`);
                console.log(digest);
                break;
            }
            case 'promote': {
                const release = releases.get(tag);
                if (!release || release.draft || release.prerelease) throw new Error('Only a published stable release can be promoted manually');
                promoteAliases(tag, remote.inspect(versionOf(tag)).digest, remote);
                break;
            }
            case 'publish':
                publishRelease({ tag, sha: process.env.RELEASE_SHA, digest: process.env.MANIFEST_DIGEST,
                    directory: process.env.RELEASE_DIRECTORY || 'release-artifacts', gates: JSON.parse(process.env.RELEASE_GATES || '{}') }, remote, releases);
                break;
            default: throw new Error('Usage: node scripts/release-publication.cjs <manifest|publish|promote>');
        }
    } catch (error) { console.error(error); process.exitCode = 1; }
}

module.exports = { assertManifest, createVersionManifest, promoteAliases, publishRelease };
