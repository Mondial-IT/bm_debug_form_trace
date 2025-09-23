#!/usr/bin/env bash
set -euo pipefail

# --- CONFIG ---
REPO_URL="git@github.com:Mondial-IT/bm_debug_form_trace.git"   # HTTPS or SSH
DEFAULT_BRANCH="main"
KEEP_FILE="$(basename "$0")"
# --------------

TARGET_REF="${1:-$DEFAULT_BRANCH}"   # First argument = branch or tag, fallback to default

echo "Bootstrapping Git repo for $(pwd)"
echo "Target ref: $TARGET_REF"

# Initialize repo if needed
if [ ! -d .git ]; then
  git init
fi

# Add or update origin
if git remote get-url origin >/dev/null 2>&1; then
  git remote set-url origin "$REPO_URL"
else
  git remote add origin "$REPO_URL"
fi

# Fetch the requested ref (shallow clone for speed)
git fetch --depth=1 origin "$TARGET_REF" || {
  echo "❌ Could not fetch ref '$TARGET_REF' from $REPO_URL"
  exit 1
}
# Clean untracked files/dirs, but keep this script
git clean -fdx -e "$KEEP_FILE"

# Check out a local branch pointing at the target ref
git checkout -B "$TARGET_REF" "origin/$TARGET_REF" 2>/dev/null || \
git checkout "tags/$TARGET_REF" -b "tag-$TARGET_REF"

# Reset to ensure working tree matches
git reset --hard



echo "✅ Repo is now tracking $REPO_URL at $TARGET_REF"
