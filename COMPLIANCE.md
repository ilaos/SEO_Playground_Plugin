# WordPress.org Submission Compliance — AlmaSEO SEO Playground

Durable guidance for shipping this plugin to the wordpress.org directory. Read this
before touching any feature that has a free/premium split. These rules are derived
from WP.org Plugin Guidelines **5 (Trialware)** and **6 (Serviceware)**.

---

## The core rule

> **A control that implies it's a working feature must actually work — fully, for
> free, with no upgrade required.**

The boundary is **not** "if it's visible it must work." It is narrower than that.
What gets a plugin rejected is functionality that is **present but deliberately
broken or locked until payment.**

### What gets you REJECTED (trialware / crippleware)
- Disabled or greyed-out inputs/checkboxes that a paid tier would enable
- A dropdown option that reverts when you select it
- A quota / counter that caps usage (`"3 of 5 used — upgrade for more"`)
- A button wired to a feature that throws **"Pro only"** when clicked
- A field that pretends to save but doesn't
- **Premium logic gated inside the free codebase** — even if the user never sees a
  broken control. Shipping the real feature behind `if ( ! $is_pro ) return;` is a
  violation *by itself*. The reviewer reads the source.

### What IS allowed (advertising premium)
- "Upgrade to Pro" buttons/links pointing to a sales page
- Banners, notices, a dedicated "Upgrade" tab
- Plain text describing what Pro adds
- A locked card/screen that is **clearly an advertisement**, not a disabled feature

---

## The mental test: "working control, or a poster?"

For every piece of UI, ask one question:

- **Working control** → it must function **100% free**. No gate, no quota, no
  `is_pro` branch anywhere in its code path.
- **Poster** (text + a link out) → fine. Advertise freely.

The **danger zone** is anything *in between* — something that looks operable but
isn't. Remove the operable-looking shell; keep only words + a link.

---

## Two conditions that make a "poster" compliant

Anchoring example: an SEO plugin has a **focus keyword** field that works for free
but "does more" for paying members. May it show, under the field, the text
*"This field can pull real Google Search Console data for members"*?

**Yes — that's a poster.** But only if BOTH hold:

1. **No dead control next to the note.** The *text* is fine. What is NOT fine is
   rendering a disabled "Connect GSC" dropdown / toggle / widget beside it that
   does nothing until you pay. Keep it **words + a link**, never a
   wired-up-looking widget.

2. **The premium capability is not in the free codebase at all** — not even as
   dead or gated code. If free ships the GSC integration behind
   `if ( ! $is_pro ) return;`, that violates the guideline even though the user
   never sees a broken control. The real feature must live in a **separate premium
   plugin** that switches it on **by being installed**.

---

## The compliant pattern (hook handoff)

Free plugin renders the note and fires an extension hook. A **separately-installed
premium plugin** hooks in and replaces the ad with the real, working control.
There is **no premium logic in the free plugin** — only the seam.

```php
// FREE plugin, under the focus-keyword field:
echo '<p class="description">Focus keyword saved. ';

do_action( 'almaseo_focus_keyword_after_field' ); // premium hooks the real GSC UI here

if ( ! has_action( 'almaseo_focus_keyword_after_field' ) ) {
    // No premium installed → show the ad text + link only.
    echo 'Members can connect this to live Search Console data. '
       . '<a href="https://almaseo.com/pro">Learn more</a>';
}
echo '</p>';
```

- No premium → `has_action()` is false → user sees the **ad text + link**.
- Premium plugin installed → it has registered the action → user sees the **real,
  working control**, and the ad text is suppressed.
- The free plugin contains **zero** gated premium logic. It ships only the seam.

> **Status in this repo:** this hook contract does **not exist yet**. The current
> codebase uses `almaseo_feature_available()` gates and `almaseo_render_feature_locked()`
> lock screens — the *opposite* of this pattern. Migrating to the hook-handoff model
> is the cleanup work. Pick a stable, documented prefix for the seam hooks
> (suggform: `almaseo_{feature}_{location}`).

---

## Phrasing guidance

Frame the free tier as **complete and additive**, never as secretly hobbled.

- ✅ "Members can connect this to live Search Console data." *(additive)*
- ⚠️ "Upgrade to **unlock** real keyword data." *("unlock" implies free is
  deliberately crippled — avoid)*
- ❌ "This feature is **Pro only**." next to a real-looking control.

Avoid the words **unlock / locked / disabled** attached to anything that looks
like a control. They are fine on an obvious advertisement (an "Upgrade" tab).

---

## How this maps to the current codebase (cleanup targets)

The following constructs are the violation surface and must be resolved before
submission. Each must end up as either **(a)** a fully-working free control, or
**(b)** a pure poster with the real code removed to a separate premium plugin.

| Construct | File | Disposition |
|-----------|------|-------------|
| `almaseo_feature_available( $feature )` gates | `includes/license/license-helper.php` + ~35 call sites | Each call site must be resolved to (a) or (b). The gate function itself only survives if it gates **nothing that ships in free**. |
| `almaseo_is_pro_active()` (defaults everyone to `'pro'`) | `license-helper.php` | Today this returns `true` for everyone, so gated code actually runs — but the gated code paths still exist in source, which is the violation. |
| `almaseo_render_feature_locked()` / `almaseo_render_locked_feature()` lock screens | `includes/license/locked-ui.php` | A full-page lock screen is an acceptable **poster** ONLY if the real feature's code is not in the free plugin. If the feature code is still present and merely hidden, that's trialware. |
| `coming-soon-ui.php` (DataForSEO) | `includes/optimization/` | Verify it is a poster (text + link), not a wired control. |
| Per-post "Pro only" branches in the metabox | `admin/partials/metabox-callback.php` (10 hits) | Highest-risk: this is user-facing editing UI. Audit every gated control. |

> **Rule of thumb for this repo:** a free feature that is currently *gated but
> running* (because the tier defaults to `'pro'`) is still non-compliant — the
> reviewer flags the `if ( ! almaseo_feature_available(...) )` source, not the
> runtime behavior. Decide per feature: ship it free (remove the gate) or move it
> out (delete from free, leave a poster).
