# Spec — Per-Client Profitability Dashboard (Agency)

*The cheapest high-impact Agency build. Rationale: every input already exists and is computed elsewhere — this is an aggregation + presentation layer, not new data plumbing. It turns the scattered Retainer/Work-Intelligence numbers into one "is this client worth it?" view, which makes the Agency profitability value-prop undeniable.*

---

## 1. The pitch (what the user sees)

A new view: **"Profitability"** — an agency-wide table, one row per client, answering *"which clients make me money and which bleed me dry?"* at a glance, with drill-down per client.

```
PROFITABILITY  ·  This month ▾                                  [sort: Margin ▾]

Client            Retainer   Hours    Eff. rate   Budget    Margin   Health
────────────────────────────────────────────────────────────────────────────
Acme Co           $2,000     12.5h    $160/hr     63% used   🟢 +$1,100   ●●●
Bright Dental     $1,500     21.0h    $71/hr      140% over  🔴 −$300     ●○○
Vertex Legal      $3,000     9.0h     $333/hr     45% used   🟢 +$2,100   ●●●
────────────────────────────────────────────────────────────────────────────
PORTFOLIO         $6,500     42.5h    $153/hr avg            🟢 +$2,900
```

- Red rows = clients where effective rate fell below a floor or hours blew past budget.
- Click a client → the existing Retainer Tracker detail.

---

## 2. Inputs (all already exist — no new data)

| Field needed | Where it already lives |
|---|---|
| Monthly fee per client | `RT-RET-FEE` — `retainer_fee` (dashboard.py:53987 validation) |
| Hours worked this period | Work Timer / time logs — `CP-WORK-TIMER`; `this_month_hrs` already computed for the ticker (dashboard.py:54934) |
| Effective rate (fee ÷ hours) | `RT-EFFECTIVE-RATE` — `actual_rate = total_revenue / this_month_hrs` (dashboard.py:54936) |
| Utilization % | ticker slide — `utilization = this_month_hrs / total_budget_hrs * 100` (dashboard.py:54941) |
| Budget / retainer hours | Retainer Tracker — `retainer_hours` / `billing_cycle_day` (dashboard.py:2875) |
| Budget overage flag | `AL-REPORT-OVERAGE` (work reports) |
| Revenue | `total_revenue` (already in the ticker calc) |

**The point:** the ticker already computes actual-rate + utilization + revenue *per the logged-in agency*. This feature is the same math **grouped by client** and laid out as a table. ~80% of the logic is copy-from-ticker.

---

## 3. Build outline

**Backend** — one endpoint, e.g. `GET /api/agency/profitability?period=this_month`:
1. Reuse the ticker's per-site loop (the code that already produces actual-rate/utilization/revenue) but emit a row per site instead of aggregate slides.
2. Compute `margin = retainer_fee − (hours × target_cost_rate)` — needs **one new config value**: a *team cost rate* (what an hour of work costs you). Default it (e.g. $50/hr) and make it a setting.
3. Return: `[{site_id, name, retainer_fee, hours, eff_rate, budget_pct, revenue, margin, health_flag}]` + portfolio totals.

**Frontend** — one new tab/page:
- Sortable table (sort by margin / eff-rate / utilization).
- Red/green row treatment on margin + over-budget.
- Period selector (this month / last month / billing period — reuse `AL-LAST-BILLING-PERIOD`).
- Row click → existing Retainer Tracker.

**The only genuinely new pieces:** (a) the team-cost-rate setting, (b) the margin calc, (c) the table UI. Everything else is reuse.

---

## 4. Effort estimate

🟢 **Low-to-medium.** ~1 backend endpoint (mostly refactored from the ticker), ~1 settings field, ~1 table view. No new tables, no new external integrations, no AI. A focused day or two.

---

## 5. Where it slots in the Product Map

New Agency row when built:
- Name `Profitability Dashboard`, ID `RT-PROFITABILITY`, parent `Retainer Tracker` (or a new "Agency Reports" group), Pricing Tier `Agency`, Delivery `Scan / Insights`, AI-Heavy No, visibility Public Supporting, Website Placement Features Page (it's marketable), Is Key Yes.
- It strengthens Moat value-prop #2 (Retainer & Profitability Management) — see `AGENCY_MOAT_MENU.md`.

---

## 6. Why this one first (vs. the bigger builds)

| Candidate | Impact | Effort | Verdict |
|---|---|---|---|
| **Profitability dashboard** | High | **Low** | ⭐ do first — data already exists |
| White-label client portal | Highest | High | do second — biggest upgrade driver but real build |
| Proposal/audit-to-pitch generator | High | Med | strong third — reuses First Impression + Recovery AI |
| Team seats + assignment | High | Med | needed before true multi-seat pricing |

Start with profitability because it's the only one where you're **presenting data you already have** — fastest path to a visible Agency-tier win.
