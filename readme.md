# OWBN Chronicle & Coordinator Manager

The organizational directory for One World by Night. Manages chronicle and coordinator records as structured WordPress content.

**Version:** 2.15.2
**Deployed to:** council.owbn.net, chronicles.owbn.net (network-activated on both)

## What It Does

Every OWBN chronicle (local game chapter) and coordinator (genre/clan authority) has a structured profile — contact info, staff lists, status, territory, and operational details. This plugin stores those as custom post types with structured fields, handles edit permissions via accessSchema roles, and syncs staff assignments back to accessSchema so the rest of the platform knows who holds what position.

Key features:

- **Chronicle profiles** — CM, HST, AST staff fields, status, session schedule, timezone, territory, genre focus
- **Coordinator profiles** — coordinator and sub-coordinator contacts, genre scope, proposals
- **Session schedule** — recurring session rules (frequency, day, start time) with chronicle timezone for cross-site calendar rendering
- **accessSchema integration** — edit access scoped to your own chronicle/coordinator record; staff field changes auto-sync ASC roles
- **Approval workflow** — changes can require approval before publishing
- **Shortcodes and Elementor** — display chronicle/coordinator data on front-end pages
- **Entity registry pattern** — extensible to new entity types via config files

## Architecture

The plugin registers custom post types for each entity (chronicles, coordinators) with per-entity config files defining fields, capabilities, and features. accessSchema role paths (`chronicle/{slug}/cm`, `coordinator/{slug}/coordinator`) control who can edit what.

Staff role sync is bidirectional — updating a chronicle's HST field grants that user the `chronicle/{slug}/hst` ASC role, and vice versa.

## Requirements

- WordPress 5.0+, PHP 7.4+
- accessSchema for permissions
- Network-activated on WordPress multisite

## Changelog

### 2.15.2

- Fix: phantom session row written on every save. Empty-row check used wrong sentinel; fixed to test `start_time`/`notes`/`genres` only.
- Temp: POST logging for `session_list` to diagnose multi-row save bug.

### 2.15.1

- Fix: chronicle ASTs (`chronicle/{slug}/staff`) can now edit their chronicle. Plugin auto-granted the role via `staff_role_map` but `access_patterns` did not honor it.
- Fix: Administrative coordinator staff (`exec/{slug}/staff`) can now edit their coordinator record. Same root cause.

### 2.15.0

- Per-field integrity validation: a single bad field no longer discards every other edit on the same save. Previously a missing required document silently wiped every meta write.
- Required documents reclassified from save-time gate to persistent compliance signal. Enforced only at draft→publish transition.
- New `_owbn_{entity_key}_compliance_gaps` post meta, admin list column, and dashboard widget surface non-compliant entities.
- One-time upgrade backfill grades every existing chronicle; legacy `validation_blocked` transients are cleared.
- Metabox shows persistent compliance banner, inline per-field errors, and re-renders errored fields with the user's submitted values.
- Document Links: top-of-field summary of incomplete required docs, auto-expanded row bodies for rows that need action.
- Fixed session_list phantom-row bug: the JS clone template was leaking an `__INDEX__` row and empty rows into every save. Sanitizer now skips both; template wrapped in `<fieldset disabled>` for defense in depth. Same fix applied to `location_group` and `repeatable_group`.

### 2.14.0

- Added `owbn_chronicle_expand_session_dates($session, $tz_name, $from, $to)` helper in `includes/helpers/sessions.php` — canonical session_list recurrence expansion. Returns sorted UTC timestamps for each occurrence in the requested window. "Every Other" uses an epoch-anchored parity so the same chronicle shows the same dates to every viewer regardless of when they load the calendar. Consumed by owbn-board's calendar tile.

## License

GPL-2.0-or-later
