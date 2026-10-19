const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { createHash } = require('node:crypto');
const { createVersionManifest, promoteAliases, publishRelease, github, verifySourceRun } = require('../../scripts/release-publication.cjs');
const oldDigest = `sha256:${'a'.repeat(64)}`;
const newDigest = `sha256:${'b'.repeat(64)}`;
function manifest(digest, architectures = ['amd64', 'arm64']) {
    return { digest, manifests: architectures.map((architecture) => ({ digest: `${digest}-${architecture}`, platform: { os: 'linux', architecture } })) };
}
function remote() {
    const tags = { '3.1.1': manifest(newDigest), '3.1': manifest(oldDigest), '3': manifest(oldDigest), latest: manifest(oldDigest) };
    return { tags, inspect: (ref) => tags[ref], set(ref, [digest]) { tags[ref] = manifest(digest); } };
}
function fixture(t) {
    const directory = fs.mkdtempSync(path.join(os.tmpdir(), 'release-test-'));
    t.after(() => fs.rmSync(directory, { recursive: true, force: true }));
    const archive = 'eXtplorer-3.1.1-release.zip';
    const content = 'final checked archive';
    fs.writeFileSync(path.join(directory, archive), content);
    fs.writeFileSync(path.join(directory, `${archive}.sha256`), `${createHash('sha256').update(content).digest('hex')}  ${archive}\n`);
    fs.writeFileSync(path.join(directory, 'release-notes.md'), 'Release notes');
    const release = { id: 1, draft: true, target_commitish: 'c'.repeat(40), assets: [] };
    const assets = new Map();
    const releases = {
        get: () => release,
        upload(tag, file) { assets.set(path.basename(file), fs.readFileSync(file)); release.assets.push({ name: path.basename(file) }); },
        download(tag, name, destination) { fs.writeFileSync(path.join(destination, name), assets.get(name)); },
        publish() { release.draft = false; },
    };
    const options = { directory, tag: 'v3.1.1', sha: release.target_commitish, digest: newDigest, gates: { archive: 'success', container: 'success' } };
    return { directory, archive, release, releases, options, assets };
}
for (const gate of ['archive', 'container']) {
    for (const result of ['failure', 'cancelled', 'skipped']) test(`${gate} ${result} cannot publish or move aliases`, (t) => {
        const { options, releases, release } = fixture(t);
        const registry = remote();
        options.gates[gate] = result;
        assert.throws(() => publishRelease(options, registry, releases), /Successful/);
        assert.equal(registry.tags.latest.digest, oldDigest);
        assert.equal(release.assets.length, 0);
    });
}
test('missing ARM64 image blocks publication', (t) => {
    const { options, releases } = fixture(t);
    const registry = remote();
    registry.tags['3.1.1'] = manifest(newDigest, ['amd64']);
    assert.throws(() => publishRelease(options, registry, releases), /arm64/);
    assert.equal(registry.tags.latest.digest, oldDigest);
});
test('actual artifact checksum mismatch blocks publication', (t) => {
    const { options, releases, directory, archive } = fixture(t);
    fs.appendFileSync(path.join(directory, archive), 'tampered');
    assert.throws(() => publishRelease(options, remote(), releases), /checksum/);
});
test('a partial registry write is rolled back, including the failed alias', () => {
    const registry = remote();
    const write = registry.set.bind(registry);
    registry.set = (ref, sources) => {
        write(ref, sources);
        if (ref === '3' && sources[0] === newDigest) throw new Error('connection lost after server accepted write');
    };
    assert.throws(() => promoteAliases('v3.1.1', newDigest, registry), /connection lost/);
    for (const alias of ['3.1', '3', 'latest']) assert.equal(registry.tags[alias].digest, oldDigest);
});
test('GitHub publication failure rolls aliases back; retry reuses identical assets', (t) => {
    const { options, releases, release, assets } = fixture(t);
    const registry = remote();
    releases.publish = () => { throw new Error('GitHub unavailable'); };
    assert.throws(() => publishRelease(options, registry, releases), /GitHub unavailable/);
    assert.equal(release.draft, true);
    assert.equal(registry.tags.latest.digest, oldDigest);
    assert.equal(assets.size, 2);
    releases.publish = () => { release.draft = false; };
    releases.upload = () => { throw new Error('retry must reuse assets'); };
    publishRelease(options, registry, releases);
    assert.equal(release.draft, false);
    for (const alias of ['3.1', '3', 'latest']) assert.equal(registry.tags[alias].digest, newDigest);
    publishRelease(options, registry, releases);
});
test('lost GitHub success response does not revert a published release', (t) => {
    const { options, releases, release } = fixture(t);
    const registry = remote();
    releases.publish = () => { release.draft = false; throw new Error('lost response'); };
    publishRelease(options, registry, releases);
    assert.equal(registry.tags.latest.digest, newDigest);
});
test('existing different release asset cannot be overwritten', (t) => {
    const { options, releases } = fixture(t);
    const registry = remote();
    publishRelease(options, registry, releases);
    releases.download = (tag, name, destination) => fs.writeFileSync(path.join(destination, name), 'different artifact');
    assert.throws(() => publishRelease(options, registry, releases), /differing asset/);
});
test('immutable version manifest is reused and differing rebuilds are rejected', () => {
    const registry = remote();
    const sources = [`sha256:${'d'.repeat(64)}`, `sha256:${'e'.repeat(64)}`];
    registry.source = (digest) => ({ manifests: [{ digest, platform: { os: 'linux', architecture: digest === sources[0] ? 'amd64' : 'arm64' } }] });
    registry.tags['3.1.1'].manifests[0].digest = sources[0];
    registry.tags['3.1.1'].manifests[1].digest = sources[1];
    registry.set = () => { throw new Error('must not overwrite an existing version'); };
    assert.equal(createVersionManifest('v3.1.1', sources, registry), newDigest);
    assert.throws(() => createVersionManifest('v3.1.1', [sources[0], `sha256:${'f'.repeat(64)}`], registry), /overwrite/);
});
test('new version manifest needs both platforms and creates no moving tags', () => {
    const registry = remote();
    delete registry.tags['3.1.1'];
    const sources = [`sha256:${'d'.repeat(64)}`, `sha256:${'e'.repeat(64)}`];
    registry.source = (digest) => ({ manifests: [{ digest, platform: { os: 'linux', architecture: digest === sources[0] ? 'amd64' : 'arm64' } }] });
    const writes = [];
    registry.set = (ref, digests) => { writes.push(ref); registry.tags[ref] = manifest(newDigest); };
    assert.equal(createVersionManifest('v3.1.1', sources, registry), newDigest);
    assert.deepEqual(writes, ['3.1.1']);
    assert.equal(registry.tags.latest.digest, oldDigest);
    registry.source = (digest) => ({ manifests: [{ digest, platform: { os: 'linux', architecture: 'amd64' } }] });
    assert.throws(() => createVersionManifest('v3.1.2', sources, registry), /arm64/);
    assert.deepEqual(writes, ['3.1.1']);
});
test('rollback failures remain explicit publication failures', () => {
    const registry = remote();
    registry.set = () => { throw new Error('registry offline'); };
    assert.throws(() => promoteAliases('v3.1.1', newDigest, registry), /rollback incomplete/);
});
test('draft lookup uses the authenticated paginated listing after the tag endpoint omits it', () => {
    const draft = { id: 123, tag_name: 'v3.1.1', draft: true };
    const calls = [];
    const releases = github('owner/repo', (program, args) => {
        calls.push([program, ...args]);
        if (args[1].includes('/tags/')) throw Object.assign(new Error('not found'), { stderr: 'gh: Not Found (HTTP 404)' });
        return JSON.stringify(draft);
    });
    assert.deepEqual(releases.get('v3.1.1'), draft);
    assert.equal(calls.length, 2);
    assert.ok(calls[1].includes('--paginate'));
    assert.ok(calls[1].includes('repos/owner/repo/releases?per_page=100'));
});
test('draft lookup distinguishes an absent release from authentication/network failures', () => {
    const missing = github('owner/repo', (program, args) => {
        if (args[1].includes('/tags/')) throw Object.assign(new Error('missing'), { stderr: 'HTTP 404' });
        return '';
    });
    assert.equal(missing.get('v3.1.1'), null);
    const denied = github('owner/repo', () => { throw Object.assign(new Error('denied'), { stderr: 'HTTP 403' }); });
    assert.throws(() => denied.get('v3.1.1'), /denied/);
});
test('new draft publication uses the create response without a second lookup', (t) => {
    const { options, releases, release } = fixture(t);
    let lookups = 0;
    releases.get = () => { lookups++; return null; };
    releases.create = () => release;
    publishRelease(options, remote(), releases);
    assert.equal(lookups, 1);
    assert.equal(release.draft, false);
});
test('REST draft creation returns the release object and reads notes from a file', () => {
    const created = { id: 123, target_commitish: 'c'.repeat(40), draft: true, assets: [] };
    const releases = github('owner/repo', (program, args) => {
        assert.equal(program, 'gh');
        assert.deepEqual(args.slice(0, 4), ['api', '--method', 'POST', 'repos/owner/repo/releases']);
        assert.ok(args.includes('draft=true'));
        assert.ok(args.includes('body=@/tmp/notes.md'));
        return JSON.stringify(created);
    });
    assert.deepEqual(releases.create('v3.1.1', 'c'.repeat(40), '/tmp/notes.md'), created);
});
test('resuming publication requires original successful archive and both image gates', () => {
    const run = { head_branch: 'v3.1.1', head_sha: 'c'.repeat(40), path: '.github/workflows/release.yml', event: 'push', status: 'completed', conclusion: 'failure' };
    const jobs = ['Verify existing gates for release SHA', 'Create deployable archive', 'Publish container image / Build amd64 image',
        'Publish container image / Build arm64 image', 'Publish container image / Create multi-architecture manifest'].map((name) => ({ name, conclusion: 'success' }));
    assert.deepEqual(verifySourceRun(run, jobs, run.head_sha), { tag: 'v3.1.1', sha: run.head_sha });
    assert.throws(() => verifySourceRun(run, jobs, 'd'.repeat(40)), /unchanged commit/);
    assert.throws(() => verifySourceRun({ ...run, event: 'pull_request' }, jobs, run.head_sha), /completed tag/);
    assert.throws(() => verifySourceRun({ ...run, path: '.github/workflows/quality.yml' }, jobs, run.head_sha), /completed tag/);
    for (let index = 0; index < jobs.length; index++) {
        const failed = jobs.map((job, position) => position === index ? { ...job, conclusion: 'failure' } : job);
        assert.throws(() => verifySourceRun(run, failed, run.head_sha), /gate did not succeed/);
    }
});
