#!/usr/bin/env bash
# Enforces the project commit message style defined in docs/standards/git.md:
#   - Start with a capital imperative verb (Add, Fix, Register, Extract, etc.)
#   - No trailing period
#   - 72 characters or fewer
#   - No vague subjects

set -euo pipefail

COMMIT_MSG_FILE="$1"

# Strip comment lines and leading/trailing blank lines, take the subject line
SUBJECT=$(grep -v '^#' "$COMMIT_MSG_FILE" | sed '/^[[:space:]]*$/d' | head -1)

if [ -z "$SUBJECT" ]; then
  echo "❌ Commit message is empty."
  exit 1
fi

# Must start with a capital letter (imperative verb)
if ! echo "$SUBJECT" | grep -qE '^[A-Z]'; then
  echo "❌ Commit subject must start with a capital imperative verb."
  echo ""
  echo "  Good: Add CD calculator core engine"
  echo "  Good: Fix loanAmount rounding in summary"
  echo "  Bad:  feat: add CD calculator"
  echo "  Bad:  added CD calculator"
  echo ""
  echo "  Got: $SUBJECT"
  exit 1
fi

# No trailing period
if echo "$SUBJECT" | grep -qE '\.$'; then
  echo "❌ Commit subject must not end with a period."
  echo ""
  echo "  Got: $SUBJECT"
  exit 1
fi

# 72-character limit
LENGTH=${#SUBJECT}
if [ "$LENGTH" -gt 72 ]; then
  echo "❌ Commit subject is too long ($LENGTH chars, max 72)."
  echo ""
  echo "  Got: $SUBJECT"
  exit 1
fi

# Block vague subjects
VAGUE_PATTERN='^(updates?|misc|fix(es)?|cleanup|clean up|wip|temp|tmp|test|asdf|stuff)[[:space:]]*$'
if echo "$SUBJECT" | grep -iqE "$VAGUE_PATTERN"; then
  echo "❌ Commit subject is too vague: \"$SUBJECT\""
  echo ""
  echo "  Each commit should be a complete thought."
  echo "  Good: Fix loanAmount rounding in loan-credit-line summary"
  exit 1
fi
