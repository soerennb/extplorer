# Release 3.2.0 validation

Tracking: ex3-2ad. Stable release of the complete main branch since 3.1.4,
including external authentication, session lifecycle and security corrections.
Dependency evidence: [2026-11-06 maintenance](dependency-maintenance-2026-11-06.md).

## Upgrade requirements

Back up writable data, configuration, encryption keys and external databases.
Preserve them during upgrade; run `php spark security:migrate` and
`php spark system:readiness` before serving native installations. PHP 8.2–8.5
remain supported and the state migration schema remains 5.

Review the 3.2.0 changelog: Public mounts require EXTPLORER_PUBLIC_MOUNT=1,
private SMTP relays require EXTPLORER_SMTP_ALLOW_PRIVATE_TARGETS=1, custom roles
need rename permission, and extension/password/share policies are stricter.
Update the Compose Nginx image alongside the application image. External login
is opt-in; keep a local recovery administrator and schedule auth:cleanup hourly.
For rollback, stop writes and restore the previous application plus its matching
backup. The prior security/FTPS limitations remain documented in 3.1.2.

## Release gates

The final release SHA must pass Quality, Security audit and Secret scanning.
The tag workflow verifies the actual ZIP in four URL layouts, external login
against the extracted ZIP, upgrades from checksum-pinned 3.1.1 and 3.1.4 ZIPs,
and both architecture images by digest before publication. Fixable HIGH/CRITICAL
findings and CVE-2026-58055 block publication. Verification evidence and final
published checksums/digests will be recorded after publication without moving
the tag.

## Published verification

Published stable/latest on 2026-11-07 at 07:47:54 UTC.
Release commit: `11a0488a77495bab360b840945ddbcec0e8eb560`.

- [Quality](https://github.com/soerennb/extplorer/actions/runs/42351900556),
  [Security](https://github.com/soerennb/extplorer/actions/runs/42351900277) and
  [Secret scanning](https://github.com/soerennb/extplorer/actions/runs/42351900276) passed for the release SHA.
- [Release](https://github.com/soerennb/extplorer/actions/runs/42352372061) passed: actual ZIP routing, packaged OIDC/website login and both native upgrade bases, plus both architecture image smoke suites and scans.
- Local candidate: 544 PHP tests / 2,488 assertions (nine environment-dependent skips), PHPStan, 25 frontend tests, 22 publication tests, translations and vendor audits passed. Local ZIP/TAR.GZ translation checks passed. Native and Docker upgrade/restore from 3.1.1 and 3.1.4 and packaged external authentication passed. Local image scan found no vulnerabilities.
- Published ZIP downloaded and SHA-256 verified: `3364352b902940fd2098fd5e2da8eb89eeb8282a6390acaf981a7903986b71c8`.
- Verified multi-architecture manifest: `sha256:e363a306a2cae8604f4b04af68c8dbba4bab17d852fa4d59509eb8a0d9922afc`.
- Registry inspection confirms `3.2.0`, `3.2`, `3` and `latest` share this digest and include Linux AMD64/ARM64. `3.1` retains the previous 3.1.4 digest.
- Full release image scan severity counts (including unfixed findings): `{"amd64": {}, "arm64": {}}`.
- Original CI evidence downloaded under `build/release-3.2.0/`; original workflow artifacts retained for 30 days. This evidence-only follow-up does not move the release tag.
