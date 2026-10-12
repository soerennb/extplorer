# Beads issue tracking

This project uses [Beads](https://github.com/gastownhall/beads) in Dolt server
mode. The database is `extplorer3`, served on `127.0.0.1:14233` from the real
directory `.beads/dolt/`. Keep this directory local and ignored by Git; do not
replace it with a symlink, because Beads needs to lock the physical data root.

The supported tool versions for this setup are Beads **1.3.1** (schema v66) and
Dolt **2.2.0**. Dolt is pinned to the version tested by Beads, rather than the
latest release. Check `which -a bd dolt` and the actual versions when upgrading.

## Daily workflow

```bash
bd prime
bd ready
bd show <issue-id>
bd update <issue-id> --status in_progress
bd create --title="Describe the work" --type task --priority 2
bd close <issue-id>
```

The Dolt database is the source of truth. JSONL files are exports or interaction
logs; they are not the issue database. Git commits do not replace Dolt sync.

```bash
bd dolt pull
bd dolt push
```

Dolt data is synchronized to `git+ssh://git@github.com/soerennb/extplorer.git`
through `refs/dolt/data`, separately from the application's Git branches.
Follow the session-completion steps in `AGENTS.md` for both Beads and Git.
The generic `bd doctor` warning that the Dolt remote matches Git's origin is
expected with this Git-backed setup; retain the remote and verify both syncs.

## Server and hooks

```bash
bd version
dolt version
bd dolt status
bd dolt start
bd doctor --server
bd doctor --check=validate
bd hooks install
```

`bd dolt stop` stops only this project's managed server. The next Beads command
can start it again. After changing the Dolt executable, restart this server to
use the new version. Existing servers for other projects keep their running
version until separately restarted.

`bd hooks install` refreshes the Beads sections in `.git/hooks/` while retaining
other hook content. Generated server configuration, process files, locks,
backups and database contents must remain ignored by Git.

## Backups and schema upgrades

Use the binary compatible with the current schema to synchronize and export
before migrating:

```bash
bd dolt pull
bd dolt push
bd export --all -o <backup-directory>/issues.jsonl
```

An issue export does not preserve the full database. Take a Dolt-native backup
with `bd backup init <backup-directory>` and `bd backup sync`, or stop the server
and make a complete offline copy, including history and Dolt configuration.
Keep backups outside tracked files and verify them before replacing data.

For a remote-backed schema upgrade, designate exactly one installation to
migrate. Synchronize every client first and upgrade all clients sharing the
server before promoting its schema. On the designated installation:

```bash
bd migrate schema --force
bd recompute-blocked
bd doctor --server
bd doctor --check=validate
bd dolt push
```

`--force` acknowledges the designated-migrator role; do not set a permanent
migration bypass or independently migrate other clones. Verify issue contents
and dependencies, test a server restart, then verify a separate clone of the
published database before discarding recovery copies.

See the [Beads upgrade guide](https://beads.gascity.com/getting-started/upgrading)
and the [Dolt backend guide](https://github.com/gastownhall/beads/blob/v1.3.1/docs/architecture/dolt.md).
