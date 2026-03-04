# OWBN Chronicle & Coordinator Manager

WordPress plugin for managing OWbN Chronicle and Coordinator data using custom post types with structured fields, approval workflows, and AccessSchema-based permissions.

**Version**: 2.7.1
**Requires PHP**: 7.4
**License**: GPL-2.0-or-later

## Installation

1. Copy `owbn-chronicle-manager/` into `/wp-content/plugins/`
2. Activate in WordPress admin
3. Configure at **OWBN C&C > Settings**

## Adding Entity Types

Each entity type needs a config file in `includes/entities/`, field definitions in `includes/fields.php`, and one `require_once` in the main plugin file. See `chronicle-config.php` for the pattern.

## Changelog

### 2.7.1

- Fixed super admin (multisite network admin) not seeing all chronicles/coordinators or create button

### 2.7.0

- Stripped comment bloat and redundant PHPDoc

### 2.6.0

- Removed blanket edit_posts grant for non-admin users
- Fixed menu access for non-admin ASC role holders

### 2.5.0

- Centralized ASC migration, source tracking

### 2.4.0

- Admin menu restructure

### 2.0.0

- Generic entity registry, unified save/validate handlers, coordinator support, per-entity feature toggles

## Contributing

[github.com/One-World-By-Night/owbn-chronicle-plugin](https://github.com/One-World-By-Night/owbn-chronicle-plugin)

Maintained by the OWBN Web Coordination team.
