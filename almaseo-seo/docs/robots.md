# Robots.txt Editor

Edit and manage your site's robots.txt with dual-mode support. Choose between virtual delivery (via WordPress filter) or physical file writing.

---

## How It Works

The robots.txt file tells search engine crawlers which parts of your site they can and cannot access. AlmaSEO provides a visual editor to customize this file without manually editing server files.

---

## Modes

### Virtual Mode (Default)

AlmaSEO intercepts WordPress's robots.txt output and serves your custom content. No physical file is created on disk.

**Advantages:**
- Works on any hosting environment
- No file permission issues
- Changes take effect immediately

### Physical Mode

AlmaSEO writes a `robots.txt` file to your site's root directory. The web server serves this file directly.

**Advantages:**
- Served by the web server without PHP processing
- Slightly faster delivery

**Requirements:**
- WordPress must have write access to the root directory
- Falls back to virtual mode if write fails

---

## Editor

Go to **AlmaSEO > Robots.txt** to open the editor:

1. Choose your mode (virtual or physical)
2. Edit the robots.txt content in the text area
3. Click **Save**

### Default Template

Click **Load Default** to populate the editor with AlmaSEO's recommended robots.txt template, or WordPress's default template.

### Test Output

Click **Test** to preview exactly what search engines see when they request your robots.txt. This verifies your configuration is working correctly.

---

## Safety Features

- **PHP tag removal** — Strips any PHP code from robots.txt content
- **Line length limit** — Truncates lines longer than 500 characters
- **Physical file detection** — Warns if a physical robots.txt exists when using virtual mode
- **Automatic fallback** — Falls back to virtual mode if physical file write fails

---

## Tier

**Free** — Robots.txt editor is available to all users.

---

## Summary

- Dual-mode: virtual (WordPress filter) or physical (file on disk)
- Visual editor with syntax highlighting
- Default template loading
- Test/preview output
- Safety features: PHP stripping, line limits, fallback
- Physical file detection and warnings
