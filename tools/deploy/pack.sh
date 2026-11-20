#!/usr/bin/env bash
# Pacchettizza tema e plugin in dist/ (zip con percorsi a slash, compatibili con ZipArchive su Linux).
# Uso: bash tools/deploy/pack.sh [theme|plugin|all]
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
TAR="/c/Windows/System32/tar.exe"; [ -x "$TAR" ] || TAR="tar"
WHAT="${1:-all}"
mkdir -p "$ROOT/dist"
if [ "$WHAT" = "theme" ] || [ "$WHAT" = "all" ]; then
  node "$ROOT/tools/build/build.mjs"
  ( cd "$ROOT/wp-content/themes" && rm -f "$ROOT/dist/ecm-theme.zip" && "$TAR" -a -cf "$(cygpath -w "$ROOT/dist/ecm-theme.zip" 2>/dev/null || echo "$ROOT/dist/ecm-theme.zip")" --exclude "ecm/assets/css/src" ecm )
  echo "dist/ecm-theme.zip"
fi
if [ "$WHAT" = "plugin" ] || [ "$WHAT" = "all" ]; then
  ( cd "$ROOT/wp-content/plugins" && rm -f "$ROOT/dist/ecm-core.zip" && "$TAR" -a -cf "$(cygpath -w "$ROOT/dist/ecm-core.zip" 2>/dev/null || echo "$ROOT/dist/ecm-core.zip")" ecm-core )
  echo "dist/ecm-core.zip"
fi
