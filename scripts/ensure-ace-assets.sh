#!/bin/bash

set -euo pipefail

ACE_VERSION="${ACE_VERSION:-1.43.3}"
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

mkdir -p "$ACE_TARGET_DIR"

echo "Fetching Ace ${ACE_VERSION}..."
curl -fsSL "$ACE_TARBALL_URL" -o "${tmp_dir}/ace-builds.tgz"
tar -xzf "${tmp_dir}/ace-builds.tgz" -C "$tmp_dir"

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
