# OWBN Chronicle & Coordinator Manager

The organizational directory for One World by Night. Manages chronicle and coordinator records as structured WordPress content.

**Version:** 2.13.0
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

## License

GPL-2.0-or-later
