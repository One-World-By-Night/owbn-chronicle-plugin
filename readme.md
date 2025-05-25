# OWbN Chronicle Manager

The OWbN Chronicle Manager is a custom WordPress plugin designed to store, manage, and display detailed information about OWbN Chronicles in a structured, searchable, and filterable way. It provides a clean interface for public users, Chronicle owners, and administrators to interact with chronicle data, leveraging custom post types and shortcodes, without relying on third-party form field plugins like ACF.

## Purpose

This plugin aims to:

- Centralize all OWbN Chronicle data using a native WordPress custom post type (`owbn_chronicle`)
- Allow Chronicle owners to edit and propose updates to their chronicle information
- Provide admins with tools to approve sensitive changes
- Offer shortcode-based listing and filtering for public display of chronicles

## Architecture Overview

### Custom Post Type

All Chronicle data is stored in a custom post type: `owbn_chronicle`. Each entry represents a single Chronicle.

Key metadata fields are stored as custom fields and include structured support for:

- OOC/IC Locations (with geocoding and address metadata)
- Game site type (virtual/physical)
- Themes, mood, premise
- Genres supported
- Session timing and frequency
- Staff roles and selection processes
- Behind-the-scenes data (region, probation, satellite status)
- Social and document links

### User Roles and Views

| Role      | Access Level                      | Purpose                               |
|-----------|-----------------------------------|---------------------------------------|
| Public    | Read-only                         | Browse Chronicle data                 |
| Owner     | Edit (own chronicle only)         | Propose updates via form interface    |
| Admin     | Edit + approve submitted changes  | Moderate restricted fields            |

### Approval Workflow

A subset of fields require administrative approval to be published:

- Staff changes (HST, CM, AST)
- Genre declarations
- IC/OOC locations
- Probationary and satellite status
- Selection process policies

Admin view includes a change log and approval/rejection controls for these fields.

## Shortcodes

Chronicle lists can be embedded anywhere using shortcode filters.

### Basic Usage

[owbn-chronicles]

Renders a complete listing of all approved chronicles.

### Filtered Listings

Shortcodes accept attributes for field-based filtering:

[owbn-chronicles region="great lakes" genre="sabbat"]

Supported filters (case-insensitive):

- region
- genre
- country
- state
- game_type (virtual, in-person)
- active_players_min, active_players_max
- probationary (yes/no)
- satellite (yes/no)

Multiple filters can be combined to narrow down the results.

## Installation

1. Upload the plugin to `/wp-content/plugins/owbn-chronicle-manager/`
2. Activate via the WordPress admin panel
3. Use `[owbn-chronicles]` in pages or posts to render lists
4. Assign proper roles to Chronicle owners and admins

## Developer Notes

- All chronicle metadata is stored as post meta attached to the `owbn_chronicle` post type.
- Shortcode rendering is handled via template parts or inline rendering hooks.
- Future enhancements may include JSON API support, external data sync, and Gravity Flow integration for approvals.

## License

This plugin is licensed under the GNU General Public License v2.0.

See LICENSE for full text.

## Example Output

To see a visual example, visit:


## Roadmap

- Custom post type scaffolding
- User/admin roles & edit views
- Field storage and validation
- Admin change approval UI
- Gravity Flow integration for approval routing
- Custom REST API for federation and external tools

## Contributing

Want to help? Open a GitHub issue, fork the repo, or submit a pull request.

## Maintainers

This plugin is maintained by OWbN.org development volunteers.