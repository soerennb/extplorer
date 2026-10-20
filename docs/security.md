# Security boundaries and verification

Report suspected vulnerabilities through the repository's
[private vulnerability reporting](https://github.com/soerennb/extplorer/security/advisories/new).
Include the affected version/deployment, required permissions, reproduction
with test data, expected boundary and observed result. Do not include real
credentials or customer files. Maintainers coordinate publication and credit
with the reporter; public development is possible when explicitly selected
for a particular fix.

## Trust model

The server operator controls the deployed code, worker environment, trusted
executables, filesystem permissions and network policy. Application
administration does not confer permission to execute server programs.
Sendmail's executable therefore belongs exclusively to operator configuration.
Ordinary uploaded files remain data regardless of their filename, contents
or executable bit.

Application permissions are independent capabilities. `admin_users` does not
grant `admin_settings`. Delegated administrators can only manage permissions
and user homes within their current authority, with extension policies at
least as restrictive as their own; the `*` administrator remains
the application-wide authority. Role/group mutations must account for every
affected account and invalidate its earlier authorization version.

Home directories are private scopes unless deliberately shared by their
configuration. `Shared` is intentionally common storage. A public share is a
limited capability: its owner must remain active and authorized, its path must
resolve inside its intended namespace, and its password, expiry, mode and
revocation must remain effective on every public operation.

Treat operating-system users with write access to application code, operator
environment, private state or runtime configuration as trusted operators.
Filesystem path validation alone cannot provide an OS sandbox against hostile
local processes racing file operations. Use separate OS identities/containers
for such workloads, keep application code read-only and do not expose mutable
operator files through external mounts.

## Review procedure

Use [OWASP ASVS](https://owasp.org/projects/asvs) Level 2 as a verification
catalog, with additional file-manager abuse cases. A source review and green
scanner run do not establish ASVS certification or prove absence of defects.

For each entry point, trace input through authorization, canonicalization,
state lookup and the final filesystem/network/process operation. Include
saved historical values, CLI notifications, public shares, WebDAV and remote
adapters; browser-only validation is insufficient. Review combinations of
individually authorized features such as upload, chmod and settings changes.

Exercise guests, ordinary users with separate homes, delegated user/settings
administrators, wildcard administrators and public-share recipients. Use only
isolated containers or extracted archives with fixture accounts and secrets.
Check both successful use and denial before side effects. Recheck concurrent
state transitions, revocation, quota enforcement and failed activation.

Confirmed findings need a reproducible fixture, affected trust boundary,
severity/rationale and a regression that fails before the fix. Record
unverified concerns as concerns rather than claiming an exploit. Track work in
Beads; keep validation reports as evidence, not a second task tracker.

## Automated checks

`composer test`, PHPStan, frontend tests, translation builds and strict CSP
source checks run in Quality. The normal browser smoke verifies the reported
extensionless-upload/chmod/mail attack. `tests/integration/mail/run.sh` tests
real operator-owned Sendmail execution without a network, including shell
quoting and refusal of a PHP-owned executable. Remote-protocol tests run with
disposable FTP/FTPS/SFTP servers; strict mode refuses legacy FTPS because PHP's
FTP extension does not authenticate the actual login connection. A successful
TLS preflight on another connection cannot replace that check.

Run `sh scripts/security-source.sh` for pinned Semgrep and reviewed local PHP
and JavaScript rules. The rule fixtures test positive and negative matches.
These rules prohibit direct application process execution, dynamic code
evaluation/object deserialization, setting-selected mail programs and browser
string execution/document.write. They are targeted regression guardrails,
not whole-application interprocedural taint analysis. Process execution in
operator/release scripts and bundled library internals requires manual review.

Dependency, copied-browser-library, container and secret scans supplement
application review. Existing scheduled scans remain enabled. New high-risk
entry points or trust-boundary changes require corresponding behavioral tests.

## Release verification

Follow the dependency/release contract: exact-commit PHP compatibility checks,
fresh container scans, packaged translation checks and native routing at the
domain root and in a subdirectory, each with and without `index.php`. Preserve
CSRF randomization, regeneration and refresh headers on challenge/error paths.
Verify upgrades against the actual published 3.1.1 archive/image with fixture
state, including refusal of an old custom mail executable. Retain tool versions,
test/audit results, checksums and CI links with the release evidence.

Only tested artifacts may be published. An unavailable network, cancelled job
or missing runner is an incomplete gate. Keep major transport/dependency
migrations separate from the smallest effective security patch.
