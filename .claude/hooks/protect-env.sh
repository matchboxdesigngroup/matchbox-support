#!/usr/bin/env bash
# Blocks staging or committing .env files and other secrets.

set -euo pipefail

BLOCKED_PATTERNS=(
  '\.env$'
  '\.env\.'
  '\.env\.local'
  '\.env\.production'
  '\.env\.staging'
  'credentials\.json$'
  'secrets\.json$'
  '\.pem$'
  '\.key$'
  'id_rsa$'
  'id_ed25519$'
  '\.p12$'
  '\.pfx$'
)

STAGED=$(git diff --cached --name-only 2>/dev/null || true)

if [ -z "$STAGED" ]; then
  exit 0
fi

FOUND=()
for pattern in "${BLOCKED_PATTERNS[@]}"; do
  while IFS= read -r file; do
    FOUND+=("$file")
  done < <(echo "$STAGED" | grep -E "$pattern" || true)
done

if [ ${#FOUND[@]} -gt 0 ]; then
  echo "❌ Blocked: attempt to commit sensitive file(s):"
  for f in "${FOUND[@]}"; do
    echo "   $f"
  done
  echo ""
  echo "  Remove these files from staging with: git reset HEAD <file>"
  echo "  Add them to .gitignore to prevent future accidents."
  exit 1
fi
