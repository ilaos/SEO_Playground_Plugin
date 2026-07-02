# Multi-session working agreement (two Claude Code sessions, one directory)

> Two Claude Code sessions are editing this same working tree at the same time.
> A single git working tree cannot hold two branches, so we coordinate by DISCIPLINE,
> not by branching. Read this before editing anything.

## Golden rules

1. **Read immediately before you Edit — every time.** The other session may have just
   changed the file. A stale read = you silently clobber their edit. Never edit from a
   copy you read minutes ago; re-Read first.

2. **Never run destructive git.** No `git reset --hard`, `git checkout -- <file>`,
   `git clean`, `git stash drop`, or force-push. These discard the *other* session's
   uncommitted work with no recovery. If you think you need one, STOP and ask the owner.

3. **Check `git status` before and after a work chunk.** If you see uncommitted changes
   you did not make, they belong to the other session — leave them alone, do not `git add -A`
   blindly, and do not revert them.

4. **Commit small and often, with a lane tag.** Small commits shrink the window where two
   sessions hold overlapping uncommitted edits. Prefix the commit subject with your lane
   (see below), e.g. `lint: ...` or `ux: ...`, so history is legible.

5. **Stage explicitly, never `git add -A` / `git add .`** while the other session has
   uncommitted work. Add only the files YOU changed, by path.

## Lanes (who owns what right now)

To avoid two sessions editing the same file, split by area. Update this list when it changes.

| Lane | Owner / purpose | Typical files |
|------|-----------------|---------------|
| `lint` | Plugin Check cleanup — warnings/errors, escaping, suppressions, headers | broad, any file (see below) |
| `ux` | Free-user experience, copy, CTAs, connection/tier UI | `admin/pages/*`, `admin/partials/metabox-callback.php`, welcome/overview |

**The lint lane touches files broadly**, so it has the highest collision risk. Mitigations:
- The lint session should tell the other which file/dir it's about to sweep next.
- The UX session should commit and pause edits in a file the lint session is actively sweeping.
- Whoever is about to touch a file the other is mid-edit on: wait for their commit first.

## Version / release ownership (single owner)

**Only ONE session bumps the version and edits `readme.txt` / release constants.**
Two sessions bumping versions produces constant merge churn and confusing changelogs.

**Current owner: the `lint` session, while Plugin Check cleanup is the active priority.**
Rationale: during cleanup the bulk of release-worthy diffs are lint's; making it hand each
batch off would just serialize everything. It also already holds the current version state
(it cut 1.21.10→1.21.12). When cleanup is done and feature/UX work is the driver again,
ownership returns to the `ux` session.

Release-owner-only files (the non-owner leaves these untouched):
- `almaseo-seo-playground/almaseo-seo-playground.php` header + the two `ALMASEO_PLUGIN_VERSION` constants
- `almaseo-seo-playground/readme.txt` (Stable tag + Changelog)
- `dashboard.py` release constants (server)

**Fold-in handoff (how the non-owner ships):** the non-owner commits its code changes
**by path, WITHOUT touching version/readme**, then tells the owner which commits are ready.
The owner rolls them into its next version bump + changelog so each release is one coherent
cut covering both lanes. The non-owner never bumps the version itself.

## Before you start a task

1. `git status` — note what is already dirty (the other session's in-flight work).
2. Decide your lane; if your task crosses into the other lane's files, coordinate first.
3. Re-Read each file right before editing it.

## After you finish a chunk

1. `git status` / `git diff` — confirm you only changed what you intended.
2. Stage YOUR files by path; commit with a lane-tagged message.
3. Leave the other session's uncommitted files untouched.

## The tier/Free-vs-Pro question

See `TIERS.md` (repo root). Short version: Free vs Pro lives in
`includes/license/license-helper.php` (`$pro_features`); the Pro gate currently defaults
to `'pro'` so it's dormant; "Alma-Enhanced" is a separate connection gate.
