#!/usr/bin/env bash
# Blocks direct commits to main or master branches.
# Use a feature branch and open a PR instead.

set -euo pipefail

BRANCH=$(git symbolic-ref --short HEAD 2>/dev/null || echo "")

if [[ "$BRANCH" == "main" || "$BRANCH" == "master" ]]; then
  echo "❌ Direct commits to '$BRANCH' are not allowed."
  echo ""
  echo "  Create a feature branch first:"
  echo "    git checkout -b feat/<your-branch-name>"
  echo ""
  echo "  Then open a PR to merge into $BRANCH."
  exit 1
fi
