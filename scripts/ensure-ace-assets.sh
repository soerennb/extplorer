#!/bin/bash

set -euo pipefail

ace_pin="$(node -e 'const manifest = require("./scripts/vendor-assets.json"); const pin = manifest.packages.find(entry => entry.name === "ace-builds"); if (!pin) process.exit(1); process.stdout.write(`${pin.version}\t${pin.integrity}`);')"
IFS=$'\t' read -r ACE_DEFAULT_VERSION ACE_DEFAULT_INTEGRITY <<< "$ace_pin"
ACE_VERSION="${ACE_VERSION:-${ACE_DEFAULT_VERSION}}"
if [ "${ACE_VERSION}" != "${ACE_DEFAULT_VERSION}" ] && [ -z "${ACE_TARBALL_INTEGRITY:-}" ]; then
  echo "Set ACE_TARBALL_INTEGRITY when overriding ACE_VERSION." >&2
  exit 1
fi
ACE_TARBALL_INTEGRITY="${ACE_TARBALL_INTEGRITY:-${ACE_DEFAULT_INTEGRITY}}"
ACE_TARGET_DIR="${ACE_TARGET_DIR:-public/assets/vendor/ace}"
ACE_TARBALL_URL="https://registry.npmjs.org/ace-builds/-/ace-builds-${ACE_VERSION}.tgz"

ACE_FILES=(
  "ace.js"
  "mode-css.js"
  "mode-html.js"
  "mode-javascript.js"
  "mode-json.js"
  "mode-markdown.js"
  "mode-php.js"
  "mode-sql.js"
  "mode-xml.js"
  "theme-chrome.js"
  "theme-monokai.js"
  "worker-css.js"
  "worker-html.js"
  "worker-javascript.js"
  "worker-json.js"
  "worker-php.js"
)

tmp_dir="$(mktemp -d)"
cleanup() {
  rm -rf "$tmp_dir"
}
trap cleanup EXIT

echo "Fetching Ace ${ACE_VERSION}..."
tarball="${tmp_dir}/ace-builds.tgz"
curl -fsSL "$ACE_TARBALL_URL" -o "$tarball"
actual_integrity="sha512-$(node -e 'const fs = require("node:fs"); const crypto = require("node:crypto"); process.stdout.write(crypto.createHash("sha512").update(fs.readFileSync(process.argv[1])).digest("base64"));' "$tarball")"
if [ "$actual_integrity" != "$ACE_TARBALL_INTEGRITY" ]; then
  echo "Ace ${ACE_VERSION} tarball integrity mismatch." >&2
  echo "Expected: ${ACE_TARBALL_INTEGRITY}" >&2
  echo "Actual:   ${actual_integrity}" >&2
  exit 1
fi

mkdir -p "$ACE_TARGET_DIR"
tar -xzf "$tarball" -C "$tmp_dir"

src_dir="${tmp_dir}/package/src-min"
for file in "${ACE_FILES[@]}"; do
  src_file="${src_dir}/${file}"
  dest_file="${ACE_TARGET_DIR}/${file}"
  min_file="${ACE_TARGET_DIR}/${file%.js}.min.js"

  if [ ! -f "$src_file" ]; then
    echo "Missing Ace asset: ${file}" >&2
    exit 1
  fi

  cp "$src_file" "$dest_file"
  cp "$src_file" "$min_file"
done

echo "Ace assets written to ${ACE_TARGET_DIR}"
