#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/../../.."
compose=(docker compose -p extplorer-audit-remote -f tests/integration/remote/compose.yml)
trap '"${compose[@]}" down --volumes --remove-orphans' EXIT
"${compose[@]}" up --detach --build --wait remote-fixture
mkdir -p build/remote-fixture
"${compose[@]}" cp remote-fixture:/certificates/ca.pem build/remote-fixture/ca.pem
"${compose[@]}" exec --no-TTY remote-fixture ssh-keygen -lf /etc/ssh/ssh_host_ed25519_key.pub -E sha256 | awk '{print $2}' > build/remote-fixture/fingerprint
"${compose[@]}" cp remote-fixture:/cert-fixture-key build/remote-fixture/client-key
"${compose[@]}" cp remote-fixture:/cert-fixture-key.pub build/remote-fixture/client-key.pub
if ! "${compose[@]}" run --rm php; then
    "${compose[@]}" logs --no-color remote-fixture
    exit 1
fi
