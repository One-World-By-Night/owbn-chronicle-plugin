# OWBN Chronicle & Coordinator Manager

**Plugin Name**: OWBN Chronicle & Coordinator Manager
**Plugin URI**: https://www.owbn.net
**Description**: Manage OWBN Chronicle & Coordinator information using structured custom post types, shortcodes, and approval workflows. Built on a generic entity registry so new entity types require only a config file and field definitions.
**Version**: 2.2.3
**Author**: OWBN Web Coordination Team, Greg Hacke
**Author URI**: https://www.owbn.net
**Tags**: chronicle, coordinator, custom post types, entity management
**Requires at least**: 6.0
**Tested up to**: 6.8
**Requires PHP**: 7.4
**License**: GPL-2.0-or-later
**License URI**: http://www.gnu.org/licenses/gpl-2.0.html
**Text Domain**: owbn-chronicle-manager

---

## Purpose

This plugin aims to:

- Centralize all OWbN Chronicle & Coordinator data using native WordPress custom post types
- Allow Chronicle and Coordinator staff to manage and update their profiles
- Provide admins with oversight and editorial control
- Expose a REST API for cross-site data sharing (local/remote modes)
- Support per-entity feature toggles — enable only the entity types each site needs
- Store all data internally without external form builders or plugins

---

## Architecture Overview

### Entity Registry (v2.0.0)

All entity types (Chronicles, Coordinators, etc.) are driven by a generic entity registry. Each entity type is defined by a configuration array that specifies:

- CPT registration (labels, capabilities, menu position)
- Field definitions (admin metabox form fields)
- Save/validation behavior (immutable fields, restricted fields, slug patterns)
- REST API configuration (list/detail fields, API key option, personnel filtering)
- Permission patterns (AccessSchema integration)

Adding a new entity type requires only:
1. A config file in `includes/entities/`
2. Field definitions in `includes/fields.php`
3. One `require_once` line in the main plugin file

### Custom Post Types

Each entity type registers a WordPress CPT. Metadata is attached to the post, including structured objects (e.g., staff, locations) and simple scalar values (e.g., region, slug).

### Repeatable Sub-records

Entities can have multiple nested elements stored as JSON objects:

- OOC and IC Locations
- Game Sites (virtual and physical)
- Sessions
- Assistant Storytellers (ASTs)
- Documents
- Email Lists
- Social URLs

These are saved under single meta keys and parsed/rendered accordingly.

---

## REST API

The plugin exposes generic REST API routes for all entity types:

- `POST /wp-json/owbn-cc/v1/entities/{type}/list` — list entities with filtered fields
- `POST /wp-json/owbn-cc/v1/entities/{type}/detail` — full entity detail by slug

All routes require API key authentication. Personnel fields (user IDs, actual emails) are automatically filtered from API responses.

Legacy routes for backward compatibility:
- `POST /wp-json/owbn-cc/v1/chronicles`
- `POST /wp-json/owbn-cc/v1/coordinator-detail`

---

## Settings

Navigate to **Settings > C&C Plugin** to configure:

- **Enable/Disable** each entity type independently
- **Data Source** per entity type: Local (this site manages data) or Remote (fetch from another site's API)
- **API Key** generation for local mode (share with remote consumers)
- **Remote URL/Key** for remote mode (point to the source site)
- **Genre List** and **Region List** (shared dropdown values)

---

## Installation

1. Copy the `owbn-chronicle-manager` folder into `/wp-content/plugins/`
2. Activate via the WordPress dashboard
3. Go to **Settings > C&C Plugin** to enable desired entity types and configure data sources
4. Begin managing entities using the WordPress admin

---

## Developer Notes

- Data is stored using `register_post_meta()` for REST access
- All fields are editable via internal admin tools
- Repeatables are saved as JSON under single meta keys (e.g., `ast_list`)
- Plugin includes metabox rendering for admin forms
- Entity types are registered via `owbn_register_entity_type()` — see existing configs for examples
- Permission checks use AccessSchema patterns when available, with fallback to staff field assignments

---

## Updates

- 1.1.2: Multi-Site control
- 1.1.5: Validation and notice of approval requirements for staff changes
- 2.0.0: Complete architecture refactor — generic entity registry, unified save/validate/API handlers, coordinator support, per-entity feature toggles, REST API with CORS, data source banner (local/remote)
- 2.2.3: AccessSchema Client updated to v2.4.0 — fixed duplicate role display in Users table when multiple client plugins are active; shared cache architecture for all client instances

---

## License

This plugin is licensed under the [GNU General Public License v2.0](http://www.gnu.org/licenses/gpl-2.0.html)

---

## Contributing

Fork the repository, open issues, or submit pull requests via:

[https://github.com/One-World-By-Night/owbn-chronicle-plugin](https://github.com/One-World-By-Night/owbn-chronicle-plugin)

---

## Maintainers

This plugin is maintained by the OWBN.org Web Coordination team.
