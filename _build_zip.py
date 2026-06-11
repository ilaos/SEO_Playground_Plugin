"""
Build the AlmaSEO SEO Playground release zip with proper POSIX path
separators. Never use PowerShell Compress-Archive — it writes backslash
arcnames which break WP install on Linux (per feedback memory).
"""
import os
import sys
import zipfile

VERSION = "1.19.3"
SRC_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "almaseo-seo-playground")
OUT_ZIP = os.path.join(os.path.dirname(os.path.abspath(__file__)), f"almaseo-seo-playground-v{VERSION}.zip")

# Skip these — they're dev artifacts, not part of a release.
SKIP_DIRS = {"__pycache__", ".git", ".idea", ".vscode", "node_modules"}
SKIP_SUFFIXES = (".bak", ".broken", ".backup_good", ".swp")
SKIP_CONTAINS = (".bak.", ".backup_good")

def should_skip(path):
    base = os.path.basename(path)
    if base in SKIP_DIRS:
        return True
    if base.startswith("."):
        # Allow .htaccess etc. only if not in skip set
        return base in {".DS_Store", ".git", ".idea", ".vscode"}
    if any(base.endswith(s) for s in SKIP_SUFFIXES):
        return True
    if any(s in base for s in SKIP_CONTAINS):
        return True
    return False

def main():
    if not os.path.isdir(SRC_DIR):
        print(f"ERROR: Source dir not found: {SRC_DIR}", file=sys.stderr)
        sys.exit(1)

    if os.path.exists(OUT_ZIP):
        os.remove(OUT_ZIP)

    n = 0
    with zipfile.ZipFile(OUT_ZIP, "w", zipfile.ZIP_DEFLATED, compresslevel=9) as zf:
        for root, dirs, files in os.walk(SRC_DIR):
            # Prune unwanted dirs in place
            dirs[:] = [d for d in dirs if not should_skip(os.path.join(root, d))]
            for f in files:
                full = os.path.join(root, f)
                if should_skip(full):
                    continue
                # arcname relative to PARENT of SRC_DIR so the zip's top-level
                # folder is "almaseo-seo-playground/"
                rel = os.path.relpath(full, os.path.dirname(SRC_DIR))
                arc = rel.replace(os.sep, "/")
                zf.write(full, arc)
                n += 1

    # Verification: ensure main plugin file present, no backslashes anywhere.
    with zipfile.ZipFile(OUT_ZIP, "r") as zf:
        names = zf.namelist()
        main_file = "almaseo-seo-playground/almaseo-seo-playground.php"
        if main_file not in names:
            print(f"ERROR: Main plugin file missing from zip: {main_file}", file=sys.stderr)
            sys.exit(2)
        if any("\\" in name for name in names):
            print("ERROR: Backslash separators in zip entries:", file=sys.stderr)
            for name in names:
                if "\\" in name:
                    print(f"  {name}", file=sys.stderr)
            sys.exit(3)

    size_kb = os.path.getsize(OUT_ZIP) // 1024
    print(f"OK: {OUT_ZIP}")
    print(f"   Entries: {n}, Size: {size_kb} KB")

if __name__ == "__main__":
    main()
