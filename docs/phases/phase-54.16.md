# Phase 54.16 — Charts without JS, the Docker image's tooling and the test runs from the known-issues log + patch release

*Four open rows of the known-issues log closed: with JS off, every chart
card shows its figures as a table; the Docker image stops carrying the
developer tooling under `.claude/` and `.impeccable/`, and the committed
`impeccable` skill gets its licence text; two PHPUnit runs from one
checkout queue instead of wiping each other's data; and PHPStan moves to
2.3.*

Status: 🚧 in progress · releases **v3.21.12**, a patch release · file lives in `docs/phases/`

This is the batch after [Phase 54.14](phase-54.14.md), the last
known-issues batch. It takes the only rows of the log still *Open* that
can be built now: K192 (MEDIUM) and K193 (LOW), which waited on #607 and
#608 until the owner decided both on 2026-11-14, and K197 and K175 (LOW),
both tooling. 54.14 left K197 out as needing a batch of its own; one lock
in the test bootstrap answers both its causes (the shared SQLite file and
the shared `var/cache`), so it fits here. It is numbered 54.16 because
54.15 is the last row of the ROADMAP table and no phase is active. While
planning, K149 and K150 were found marked *Open* though their rows say
Phase 54.5 fixed them; this plan marks them *Fixed (Phase 54.5)*.

Read [`CLAUDE.md`](../../CLAUDE.md), [`spec.md`](../../spec.md) §1
(*Licence*), §8 (*Printing reports*, *Charts*), §9 (*Test suite only*),
§10 (*Docker*) and §13, `.claude/review-rules.md` §2 and
[`open-questions.md`](open-questions.md) first.

**Prerequisites:** [Phase 54.15](phase-54.15.md) merged and released as
v3.21.11.

---

## Goals

### Charts without JS

1. **K192.** With JS off, the Fuel tab's *Economy trend* and *Price
   trend*, *Odometer over time* on the Mileage tab and the *Fleet* card on
   the true-cost report each show their figures as a table, where today
   they show only a title. With JS on, the chart is drawn as before and
   the table is kept for screen readers and print. The *Economy trend*
   hint about the dashed line shows only when the chart is drawn. The
   *Fleet* card gains the table it never had, with a row per year and a
   column per vehicle, which also prints with the chart.

### Tooling

2. **K193.** The Docker image no longer carries `.claude/`, `.impeccable/`,
   `.playwright-mcp/` or `.DS_Store` files, and the repository's copy of
   the `impeccable` skill has its Apache 2.0 licence text and a notice for
   the screenshot library it bundles.
3. **K197.** A second PHPUnit run started from the same checkout while
   one is running waits for it to finish, saying so, instead of deleting
   its database and staged files.
4. **K175.** The lock holds the latest PHPStan 2.3, `composer analyse`
   passes, and Dependabot no longer holds PHPStan back.

## Not in scope

- **K145** needs a recorded Anthropic request, so a real key. **K174**
  waits on `firebase/php-jwt` supporting phpseclib 4. **K185**'s remaining
  parts (the Accessibility test and the time limit on Windows) have no
  known cause and can't be reproduced off Windows.
- No other row of the log is *Open*. A no-JS view for the charts that
  already sit beside a table of their own (the Fuel *Cost* panel, reports,
  the dashboard widgets) is unchanged: #607 covers only cards with no
  table.

---

## Decisions (and why)

- **K192 (#607):** option (1). `ui.chart_table()` loses `print-only` and
  takes the `.chart-table` class that the Fuel *Cost* panel's table
  already uses: shown without JS, visually hidden but read by screen
  readers with it (`.js .chart-table`), and printed by the existing
  `.js .print-report .chart-table` rule. Its callers are the three on the
  Fuel tab and the Mileage tab (`fuel/index.twig:105`, `:154`,
  `odometer/index.twig:58`); all four affected pages are `.print-report`
  pages, so print is unchanged. The `.chart-print-table` class and its
  caption stay for the print styles. Not chosen: only where the page has
  no other list (2), hiding the card without JS (3), leaving it (4).
- **K192's hint (#617):** the *Economy trend* hint (`fuel.chart.economy_hint`)
  describes a dashed line, so it shows only when the chart is drawn: it
  gets a class hidden unless the page has `.js`, as `.chart` is
  (`.js .chart` in `app.css`). No new string. Not chosen: a second hint for the table;
  leaving it.
- **K192's Fleet card (#618):** the *Fleet* card's chart is a `BarChart`
  (`TrueCostCharts::fleet()`), which `ui.chart_table()` can't read, so it
  has no table on screen or on paper, against spec §8's "every chart has a
  table". `BarChart` gains read accessors for its labels and series (names
  and values), and the card gets a table: a row per year (newest first,
  as the other chart tables), a column per vehicle, each cell the year's
  cost per distance at the chart's precision in the currency, `—` where a
  vehicle has no figure for that year. Its caption is the card's lead and
  the unit, as `ui.chart_table()` does; the first column header is a new
  string `true_cost.fleet_year`: "Year" in English, "Jahr" in German. It
  takes `.chart-table`, so it behaves as the others with and without JS
  and prints. Not chosen: a line pointing to each vehicle's table; hiding
  the card without JS.
- **K193 (#608):** option (1). `.dockerignore` gains `.claude`,
  `.impeccable`, `.playwright-mcp` and `**/.DS_Store` (the row names
  `.DS_Store` too; it is excluded with them). The skill's Apache 2.0 text
  goes in `.claude/skills/impeccable/LICENSE`, copied from the skill's
  upstream repository rather than retyped, and a
  `.claude/skills/impeccable/NOTICE` names `scripts/modern-screenshot.umd.js`,
  its upstream and its licence (taken from its upstream; the minified
  file has no header). `THIRD-PARTY-NOTICES.md` is unchanged: it lists
  what ships, and the tooling no longer does. Not chosen: keeping the
  tooling in the image and listing it in the notices (2); both (3).
- **K197 (#619):** a lock that waits. `tests/bootstrap.php` takes an
  exclusive `flock()` on `var/testing.lock` before it deletes or migrates
  anything and holds it for the whole run (the handle kept in a static of
  a small `Tests\Support\TestRunLock`, so it isn't closed early). If the
  lock is taken it prints
  `Waiting for another test run in this checkout to finish…` to STDERR
  once and blocks until it is free. One lock covers both causes in the
  row: the SQLite file and `var/cache` are only touched while it is held,
  and it serves every engine. The operating system frees it if a run
  dies. `.claude/review-rules.md` §2 says agents' test runs from one
  checkout now queue. Not chosen: refusing the second run at once; giving
  each run its own SQLite file and cache directory (server databases
  would still collide).
- **K175 (#620):** `composer update phpstan/phpstan` to the latest 2.3.x,
  then `composer analyse`. Each false error still reported gets a targeted
  `ignoreErrors` entry in `phpstan.neon.dist` (message and path, never a
  whole file or a wildcard over `src/`), with a comment naming K175 and
  the PHPStan version; real errors it finds are fixed in the code. The
  2.3 ignore in `.github/dependabot.yml` is removed. Not chosen: moving
  only if 2.3 is clean; changing the code to satisfy false errors. The
  number of errors 2.3.4 reported (28) wasn't re-measured on 2.3.5 while
  planning.
- **K149 and K150** are docs only: their status column said *Open*
  though the row records the fix in Phase 54.5. Marked *Fixed (Phase
  54.5)* in this plan.
- **Release (#616):** on its own as patch release v3.21.12, after 54.15.

## Tasks

### 54.16.0 Start of phase

- [x] Read `open-questions.md` and the *Open* and *Scheduled* rows of
      `known-issues.md` (CLAUDE.md §12). #607, #608 and #616–#620 are
      decided. Reviewed 2026-11-14: none of the questions still open
      (#221, #390, #391, #460, #462, #537–#542, #551) bears on K192, K193,
      K197 or K175, and none was found while starting it.

### 54.16.1 Charts without JS (K192)

- [x] `templates/macros/ui.twig` `chart_table()`: the wrapper drops
      `print-only`; the table takes `chart-table` beside
      `chart-print-table`; its comment says it shows without JS, is kept
      for screen readers with JS, and prints (#607). *Built:*
      `chart-table` sits on the `.table-wrap` rather than the table, as on
      the dashboard's monthly spend: a table can't shrink below its
      content, so hiding only the table left its scrolling wrapper an
      empty 15 px scrollbar with JS. The caption (chart and unit) gets a
      screen style: a muted line, left-aligned with the figures.
- [x] `templates/fuel/index.twig`: the *Economy trend* hint shows only with
      JS (#617); `assets/css/app.css` gets the rule beside `.js .chart`.
- [x] `Support\View\BarChart`: read accessors for its labels and its
      series (label and values). `templates/reports/true_cost.twig`: the
      *Fleet* card's table as #618 says (year rows newest first, a column
      per vehicle, `—` for a missing year, `true_cost.fleet_year` in both
      catalogues). Rebuild `public/assets`. *Built:* a
      `ui.bar_chart_table()` macro beside `chart_table()`; the caption's
      unit is the existing `fuel.chart.cost_axis` ("Cost per mile").
- [x] Tests: an HTTP test fetching the Fuel tab, the Mileage tab and the
      true-cost report with two vehicles: each `canvas[data-chart]` on
      those cards has a `table.chart-table` in its card, with none under
      `.print-only` (fails today); the Fleet table's first row is the
      newest year with each vehicle's figure; `BarChart` accessor unit
      test. `PrintReportsTest::testEveryChartHasATableThatPrints` adds
      `/reports/true-cost` and its `.print-only > table` lookup follows
      the new class. A CSS check (as `TapTargetTest` reads `app.css`) that
      the hint's class is hidden unless `.js`.
- [x] Checked in a browser with JS off and on, light and dark, at 375 px
      and wide: the four cards; print preview of the Fuel tab and the
      true-cost report. *Checked 2026-11-14* on the demo data at the
      `/logbook` subpath: without JS each card shows its table and the
      Economy hint is gone, with no page overflow at 375 px; with JS the
      chart draws and the table takes 1 px. Print follows the existing
      `.js .print-report .chart-table` rule (covered by
      `PrintReportsTest`); no print preview was taken.

### 54.16.2 The Docker image's tooling (K193)

- [ ] `.dockerignore`: `.claude`, `.impeccable`, `.playwright-mcp`,
      `**/.DS_Store`, with a comment naming K193 and #608.
- [ ] `.claude/skills/impeccable/LICENSE` (Apache 2.0, from the skill's
      upstream) and `.claude/skills/impeccable/NOTICE` (modern-screenshot,
      its upstream and licence).
- [ ] `LicenceTest`: `.dockerignore` excludes the four entries, and the
      skill's `LICENSE` exists and is the Apache License 2.0 (fails today).
- [ ] A clean `docker build`: the image has no `/var/www/html/.claude` or
      `.impeccable` (checked by hand, recorded in the merge review).

### 54.16.3 Test runs that queue (K197)

- [ ] `tests/Support/TestRunLock.php`: takes `flock(LOCK_EX)` on
      `var/testing.lock`, printing the #619 message to STDERR once if it
      has to wait, and keeps the handle for the process's life.
      `tests/bootstrap.php` calls it before the SQLite file is deleted.
- [ ] `TestRunLockTest`: while the test holds the lock, a `proc_open`
      child taking it does not finish and prints the message; it finishes
      once the lock is released (fails today: there is no lock).
- [ ] `.claude/review-rules.md` §2: test runs from one checkout queue on
      `var/testing.lock`; an agent with a short tool timeout runs its
      tests in its own worktree.
- [ ] Checked: two `composer test` runs at once from one checkout both
      pass, the second after the first; `bin/test-all-dbs.sh` still runs
      on all four engines (the lock sits in the bind-mounted `var/`).

### 54.16.4 PHPStan 2.3 (K175)

- [ ] `composer update phpstan/phpstan` to the latest 2.3.x;
      `composer analyse`; a targeted `ignoreErrors` entry per remaining
      false error with a K175 comment, and real errors fixed (#620).
- [ ] `.github/dependabot.yml`: the `phpstan/phpstan >=2.3.0` ignore and
      its comment removed.
- [ ] A test (beside `VendoredLibrariesTest`) that `composer.lock` holds
      `phpstan/phpstan` 2.3 or later and `dependabot.yml` doesn't ignore it
      (fails today).

### 54.16.5 Finish

- [ ] Known-issues rows K192, K193, K197 and K175 *Fixed* (Phase 54.16).
- [ ] CHANGELOG [Unreleased]; spec §13; ROADMAP status.
- [ ] Merge review; merge.

### Release

- [ ] VERSION 3.21.12, CHANGELOG 3.21.12, README status (#616).
- [ ] Tag `v3.21.12` once merged.

## Acceptance criteria

- Each code row has a regression test that fails without its fix: the
  chart tables without JS and the Fleet table (K192), the `.dockerignore`
  entries and the licence file (K193), the waiting second run (K197), and
  PHPStan 2.3 in the lock with no Dependabot ignore (K175).
- `composer lint`, `composer analyse` and `bin/test-all-dbs.sh` pass on
  SQLite, PostgreSQL, MySQL and MariaDB; `composer coverage:check`
  passes.
- `MarkdownLinksTest` passes.
- No migration and no new setting.
- `true_cost.fleet_year` is in every catalogue in `translations/`.
- The Docker image builds on amd64 and arm64 without the tooling
  directories.

## Open questions

- **#616 — Is Phase 54.16 released on its own (v3.21.12) or with the next
  releasing phase?** *Decided 2026-11-14:* on its own as patch release
  v3.21.12, after 54.15. Not chosen: with the next releasing phase.
- **#607 — What do chart cards with no table of their own show without
  JS (K192)?** *Decided 2026-11-14:* (1) `ui.chart_table()` on screen
  without JS, kept for screen readers with JS. Not chosen: only where the
  page has no other list (2); hide the card (3); leave it (4).
- **#608 — How is the committed tooling handled for the image and the
  notices (K193)?** *Decided 2026-11-14:* (1) excluded from the image in
  `.dockerignore`, with the skill's Apache 2.0 licence in its folder. Not
  chosen: keep it and list it in the notices (2); both (3).
- **#617 — What happens to the *Economy trend* hint without JS (K192)?**
  *Decided 2026-11-14:* it shows only when the chart is drawn. Not chosen:
  a second hint for the table; leave it.
- **#618 — What does the true-cost *Fleet* card show without JS, given
  its chart is a `BarChart` with no table (K192)?** *Decided 2026-11-14:* a
  years × vehicles table from the same data, with JS screen-reader only,
  and printed. Not chosen: a line pointing to each vehicle's table; hide
  the card.
- **#619 — How does a second test run from the same checkout behave
  (K197)?** *Decided 2026-11-14:* it waits on a lock, saying so. Not
  chosen: refuse at once; a database and cache per run.
- **#620 — How does PHPStan move off 2.2 (K175)?** *Decided 2026-11-14:*
  the latest 2.3.x with targeted ignores for false errors, and the
  Dependabot ignore removed. Not chosen: only if clean; change the code.
