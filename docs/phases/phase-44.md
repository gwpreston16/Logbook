# Phase 44 — A *Next 3 months* total on *Coming up* + release

*What the next three months will probably cost, where the items already
are.*

Status: 🚧 in progress · file lives in `docs/phases/`

The prototype's insight "about £x due in the next 3 months" was handed
to the model in Phase 33.4 (#174). Phase 42 decided it is not an insight
(#355): §7.8 says insights never repeat *Coming up* ("amounts due"), and
the outlook is a sum of *Coming up*'s own items. So the total goes on
*Coming up* itself, the page and the widget, beside the items it adds up.

Read [`CLAUDE.md`](../../CLAUDE.md), [`spec.md`](../../spec.md) §7.8
(*Coming up* widget, *Insights*), §7.18 (*Coming up*, its costs), §7.21
(`ViewCosts`) and [Phase 42](phase-42.md) first.

**Prerequisites:** [Phase 42](phase-42.md) complete and green.

---

## Goals

1. A *Next 3 months* total on the *Coming up* page and widget, from the
   costs *Coming up* already shows.
2. Ask's `coming_up` tool returns the same total, so no model adds it up.
3. Release.

## Not in scope

- An insight for it (#355).
- New cost estimates: only the costs *Coming up* already shows are summed.

---

## Tasks

### 44.0 Spec first
- [x] §7.18 and §7.8 (*Coming up* widget), §7.26 (`coming_up`), §13;
      open questions below decided; `ROADMAP.md` row.

### 44.1 The total
- [x] The sum of the first three months' totals (this month with the
      overdue items, and the two after; planned and fuel), per currency
      (never converted); "at least" with any item of unknown cost,
      hidden costs included (#379–#383). A line above the 12-month one on
      the widget and the overview card; a fourth stat in the page's
      summary; translations.
- [x] `coming_up` returns it as raw values and display strings;
      `horizon_months` counts calendar months as the page does (#381).

### 44.2 Tests and release
- [x] The sum to the penny; currencies kept apart; `ViewCosts`; items
      without a cost; the window's edges in the owner's time zone; the
      tool's items and total cover the same months.
- [x] `VERSION` → 3.10.0, `CHANGELOG.md`, README; `ROADMAP.md` row ✅.
- [ ] Tag `v3.10.0` once merged.

---

## Acceptance criteria

1. *Coming up* shows what the next 3 months will probably cost, from its
   own items.
2. Ask answers "what's due in the next 3 months" with that figure.
3. Definition of done (CLAUDE.md §11) holds.

## Open questions

Numbered in `open-questions.md` when logged. Not built until decided.

- **A. Which items count?** Reminders with a last-time cost only, or
  also renewals with a known premium and services with an estimate?
  Answered from the spec for the items: every item with the expected
  cost §7.18 already shows (a schedule's last time, a document's cost,
  tyre shares, finance payments; never an average, which the prototype
  used). The open part was fuel. Options: (1) include the fuel
  estimate, as the 12-month total does; (2) bills only, as the
  prototype. *Recommendation:* (1). *Decided 2026-10-10:* (1). (#379)
- **B. Items without a cost.** Leave them out and say how many ("3 items
  with no cost yet"), or show no total while any item lacks one?
  Answered from the spec: §7.18 *Totals* already reads "at least £…" and
  counts them; the 3-month total does the same. (#380)
- **C. Window.** The next 3 calendar months, or 90 days from today?
  Options: (1) this month (overdue included) and the two after, the sum
  of the page's first three month totals; (2) today to the same day
  three months on; (3) 90 days. *Recommendation:* (1): the sections,
  chart and totals agree, and fuel is already worked out per month.
  *Decided 2026-10-10:* (1). The `coming_up` tool filtered
  `horizon_months` from today, so its items wouldn't have matched the
  total: it now counts calendar months too (found while starting). (#381)
- **D. Where on the widget.** A footer line, or a figure in its header?
  Options: (1) a line above the 12-month one on the widget and the
  overview card (which share it) and a fourth stat in the page's
  summary; (2) a figure in the widget's header; (3) the widget only.
  *Recommendation:* (1). *Decided 2026-10-10:* (1). (#382)
- **Found while starting it (2026-10-10):**
  - **Vehicles whose costs the viewer may not see.** Options: (1) their
    items count as having no known cost ("at least"), as the 12-month
    total does today; (2) leave them out silently. *Recommendation:*
    (1). *Decided 2026-10-10:* (1). The digest's #375 stays a digest
    question. (#383)
  - The carried #374–#377 (Phase 43's digest) and #221 (outside the
    app) were reviewed and change nothing in Phase 44.
- **Found by the reviews (2026-10-10), decided by the owner the same
  day:**
  - **A zero total.** The page showed "—" but Ask's `coming_up` said
    "at least £0.00" (what a member without cost access got), and the
    widget's "—" had no words for screen readers. *Decided:* "—"
    everywhere, read out as "No known cost"; the tool gives no display
    string and no figure for it. (#384)
  - **The page's summary heading** read "Next 12 months" over the
    *Next 3 months* tile. *Decided:* the heading is "Summary" ("Summary
    in {currency}"), and the 12-month total tile is labelled "Next 12
    months". (#385)
  - The security review found nothing; the spec review's paperwork gaps
    were fixed.
