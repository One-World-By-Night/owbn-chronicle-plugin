=== OWBN Chronicle & Coordinator Manager ===
Contributors: gregwhacke, owbnwebteam
Donate link: https://www.owbn.net/
Tags: chronicle, coordinator, custom post types, entity management
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 2.2.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Manage OWBN Chronicle & Coordinator information using structured custom post types, approval workflows, and a REST API. Built on a generic entity registry for easy extensibility.

== Description ==

This plugin centralizes OWbN Chronicle & Coordinator data using native WordPress custom post types. It features a generic entity registry architecture where each entity type is driven by a configuration array — adding a new entity type requires only a config file and field definitions.

Key features:

* Generic entity registry — one codebase handles all entity types
* Per-entity feature toggles — enable only what each site needs
* Local/Remote data source modes — manage data locally or fetch from another site's API
* REST API with CORS support and API key authentication
* AccessSchema permission integration
* Structured admin metabox forms with 16+ field types
* Repeatable sub-records (locations, sessions, staff, documents, etc.)

== Installation ==

1. Upload the `owbn-chronicle-manager` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > C&C Plugin to enable entity types and configure data sources
4. Begin managing entities using the WordPress admin

== Changelog ==

= 2.2.2 =
* Upgraded embedded accessSchema client from v1.2.0 to v2.1.2
* Fixed client_id normalization in user_has_cap filter (options now correctly use lowercase slug)
* Fixed remote API URL construction to handle legacy stored URLs with REST path prefix
* Registered accessschema_get_roles_for_slug filter so utility role-matching functions work correctly
* Security hardening: nonce verification, output escaping, wp_safe_redirect across client library
* Removed empty placeholder files and debug code from client library

= 2.2.0 =
* Elementor Theme Builder integration

= 2.1.0 =
* Pending changeset workflow — staff field changes by non-admins on published posts are held pending admin approval instead of downgrading the post to draft
* Published posts remain visible on the front end while staff changes await review
* Validation failures on published posts now block the save entirely instead of demoting to draft
* Admin Approve/Reject UI for pending staff changes with submitter details and self-promotion detection
* Metabox banner showing pending change status for both admins and non-admins
* Fixed capability checks so non-admin users (HST, CM, Coordinator) can access CPT list and edit pages without requiring WordPress roles
* Grant edit_posts to authenticated users via capability filter to prevent WordPress admin menu from blocking CPT pages
* Hide default Posts and Comments menus for non-admin users
* map_meta_cap and edit permission checks now use cached AccessSchema roles before falling back to direct API calls

= 2.0.0 =
* Complete architecture refactor — generic entity registry pattern
* Unified save, validate, and API handlers for all entity types
* Coordinator entity type support
* Per-entity feature toggles (enable/disable per site)
* REST API with generic routes, CORS, and API key auth
* Data source banner showing Local/Remote mode in edit forms
* Removed per-record record_type field (now derived from site-level settings)
* Admin menu grouping for all entity types

= 1.5.0 =
* Coordinator management added
* Settings page with Local/Remote mode

= 1.1.5 =
* Validation and notice of approval requirements for staff changes

= 1.1.2 =
* Multi-Site control
