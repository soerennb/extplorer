#!/bin/sh
set -eu

# Reviewed local rules and an immutable engine; no remote rules or telemetry.
image=semgrep/semgrep:1.181.0@sha256:81474464c1f07483ba0682a44787fd93c91397419762a04abe2bafaae396304b
for fixture in .semgrep/php-security.php .semgrep/javascript-security.js; do
    docker run --rm --network none --cpus 2 --memory 2g -v "$(pwd):/src:ro" -w /src "$image" semgrep scan --jobs 2 \
        --test --config "${fixture%.*}.yaml" "$fixture" --metrics off --disable-version-check
done
docker run --rm --network none --cpus 2 --memory 2g -v "$(pwd):/src:ro" -w /src "$image" semgrep scan --jobs 2 \
    --config .semgrep --error --strict --metrics off --disable-version-check \
    --exclude '*.min.js' --exclude 'public/assets/js/compiled/**' \
    app public/assets/js
