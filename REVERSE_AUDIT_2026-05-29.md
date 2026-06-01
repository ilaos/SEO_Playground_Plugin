# Reverse Audit — shipped code with no Product Map row (refreshed 2026-05-29)

*Method: diffed the app's in-code feature catalog (`dashboard.py:~56794`, "feature tracking and marketing planning", 84 entries) against the now-complete 195-row Product Map. 54 matched cleanly; 30 needed manual triage (the matcher misses `&`/synonym cases). Triage below is verified against server code.*

---

## ✅ TRUE GAPS — real, shipped, and genuinely undocumented (add rows)

| Feature | Evidence | Recommended row |
|---|---|---|
| **Renewal Calendar** | `dashboard.py:56885` catalog: slug `renewals`, "Track domain, hosting, SSL renewal dates", **status: shipped** | Name `Renewal Calendar`, ID `CP-INFRA-RENEWALS`, parent **Infrastructure Scan**, **Agency**, Live/GA, AI-Heavy No, Delivery `Notification / Alert`. *Agency-moat material — client-asset expiry tracking.* |
| **Branding** | `data-tab="branding"`; Brand Voice / Slogan / Tagline / brand colors (`:11046`) used by reports + emails | Name `Branding`, ID `CP-BRANDING`, parent **Client Profile**, **Pro**, Live/GA, AI-Heavy No, Delivery `In-app action` |
| **Unsplash Images** | `image_option == "unsplash"`, `unsplash_image_url` (`:5519,5535`) — a 3rd featured-image source beyond DALL-E/Nano Banana | sub-row under **Content Output / Featured Image**, ID `CC-COUT-IMG-UNSPLASH`, **Pro**, Live/GA, Integration `Unsplash API` (or None) |
| **Image Upload** | `image_option == "upload"` — manual featured-image source | sub-row under Featured Image, ID `CC-COUT-IMG-UPLOAD`, **Pro**, Live/GA |
| **Awards & Certifications** | `dashboard.py:9527` "Awards & Credentials" business-profile field; injected into generated content for trust/E-E-A-T (`:15057`). *(Originally mis-classified as roadmap — it IS built.)* | Name `Awards & Certifications`, ID `CP-BIZ-AWARDS`, parent **Business Information**, **Pro**, Live/GA, AI-Heavy No |

---

## ⚠️ VERIFY / LIKELY-MERGE (don't add blindly)

| Feature | Concern |
|---|---|
| **Credentials Vault** | Catalog lists it, but code grep only surfaced OAuth-credential plumbing. **Very likely the same thing as the existing `Ownership Tracker` (CP-OWN)** ("spreadsheet-based client info"). → Confirm; if same, just note "aka Credentials Vault" on CP-OWN rather than a new row. If it's a separate *encrypted* vault, add it (Agency). |
| **Sticky Notes** | Catalog says `shipped`, but the only "sticky" in code is the AI-rollout *cohort* logic (unrelated). → Need to eyeball the UI to confirm a real notes feature exists before documenting. |
| **Client Communications** | Likely already covered by `Client Report Builder` + `Email Report Sender`. Confirm it's not a distinct comms log. |

---

## 🔮 CATALOG-LISTED BUT NO CODE FOUND (probably roadmap, not shipped)

- **FTP/SFTP Deployment** — no FTP/SFTP/deploy code found (only the catalog entry). Static *export* exists (`CC-PEX-MULTI`, `CA-SCHEMA-STATIC`); auto-*deploy* does not appear built. Catalog status was `beta`; **corrected to `planned` on the server 2026-05-29** (`dashboard.py:56831`) so it stops implying a built feature.

*(Correction: **Awards & Certifications** was initially listed here but is actually BUILT — moved up to TRUE GAPS. Verify-before-edit caught it before any wrong downgrade.)*

---

## 🔧 INTERNAL / NON-SELLABLE (skip, or document as internal-only)

Job Queue System · Worker Health Monitor · Admin Broadcasts · WhatsApp Support — backend/admin/support infrastructure, not customer-facing features. Document only if you want internal-ops coverage.

---

## 🟡 MINOR SUB-FEATURES (covered by a parent; add only if you want granularity)

These are real but live *inside* an existing documented feature:
- **Multi-Location Management / Bulk Location Import / Service Area Business / Acceptable Addresses** → all under **Locations** (`CP-LOC`) and **NAP Shield**. Add as CP-LOC-* sub-rows only if you want that detail.
- **Master NAP Record / Citation Scanning / NAP Health Score** → components of **NAP Shield** (`CP-NAP`). Already conceptually covered.
- **Notification Preferences** → under Site Settings / Notifications.

---

## ❌ FALSE POSITIVES (the matcher missed these — they ARE documented)

Not gaps, listed so they're not re-flagged: `Domains & DNS` = CP-INFRA-DNS · `Custom Permalinks` = CC-ACF-PERM · `Category Assignment` = CC-ACF-CAT · `Schedule Preview` = Scheduled Posts · `Indexing Status` = Search Console · `Recovery Plan Generator` = SEO Recovery Review · `Backlink Verification` = Backlinks Overview · `Topic Queue` = Topic Scheduling · `Publishing Schedule` = Content Automation.

---

## Bottom line

Out of 84 cataloged features, the **only clearly-undocumented shipped capabilities are: Renewal Calendar, Branding, and the two extra image sources (Unsplash, Upload).** Everything else is either already documented, an internal-infra item, a roadmap item the catalog over-claims as shipped, or a sub-feature of an existing row. **The table is in very good shape** — the walk caught nearly everything. Renewal Calendar is the most valuable add (it's shipped, sellable, and Agency-flavored).
