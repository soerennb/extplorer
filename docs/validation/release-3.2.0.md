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
