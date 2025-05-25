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

All Chronicle data is stored in a custom post type: `owbn_chronicle`. Each post represents a unique Chronicle entry within the OWbN network. This structure enables robust, extensible, and permission-aware data storage using WordPress's built-in systems for revisions, metadata, and user relationships.

#### Sub-Records and Multiplicity

Several Chronicle fields support **multiple entries** to reflect the complex nature of OWbN chronicles:

- OOC and IC Locations
- Game Sites (physical and virtual)
- Sessions (repeatable entries per genre)
- ASTs (Assistant Storytellers)
- Documents, Email Lists, and External Links

#### Field Definitions

- **OOC Location**:
  - One or more entries
  - Each includes a location selector, geocode, and structured address (City/State/Country)

- **IC Location**:
  - Supports multiple entries (multi-select)
  - Each includes a name, geocode, and address
  - Stored as child entries or sub-records

- **Game Site**:
  - Can include both Virtual and Physical entries
  - **Virtual Site**: Type (select dropdown) and URL
  - **Physical Address**: Full address and structured location fields

- **Chronicle Data**:
  - Premise (WYSIWYG)
  - Game Theme (WYSIWYG)
  - Game Mood (WYSIWYG)
  - Information for Travellers (WYSIWYG)

- **Genre Information**:
  - Multi-select field
  - Genres include: Vampire (Anarch, Camarilla, Sabbat, Independent, Giovanni, Clan-Specific), Changing Breeds (Garou, Other), Changeling, Demon, Hunter, Kuei-Jin, Mage, Wraith, Other

- **Active Players / Session Count**:
  - Numeric estimate of currently active players per game session

- **Web URL**:
  - Chronicle website or primary presence

- **Social Media Addresses**:
  - One or more entries, including platforms like Discord, Facebook, etc.

- **Sessions**:
  - Repeatable
  - Fields include:
    - Type (Game/Meetup)
    - Frequency (1st–5th, Every, Every Other, Random, Other)
    - Day or Range (Monday–Sunday, Week, Other)
    - Time (local)
    - Game Date Notes
    - Genre (Multi-select)

- **Staff Information**:
  - HST: Name, Email, and linked to a WordPress user account
  - CM: Name, Email, and linked to a WordPress user account
  - AST(s): Multiple entries, each with Name, Email, optional user account, and custom title
  - Admin: Name and Email (optional, for backend management)

- **Staff Selection Processes**:
  - Defined per role (HST, CM, AST)
  - Options include: Player Vote, By HST, By Staff, Other

- **Document Links**:
  - Multiple entries allowed
  - Each includes a descriptive label and document URL

- **Chronicle Email Lists**:
  - Multiple named lists, each with a contact email

- **Behind the Scenes**:
  - Chronicle Start Date
  - Region (selectable)
  - Probationary Status (Yes/No)
  - Satellite Status (Yes/No)
    - If yes, also includes linked Parent Chronicle

This structured model ensures each Chronicle entry captures comprehensive details while maintaining flexibility and editability for owners, visibility for users, and approval control for administrators.

- **OOC Locations**: Allows one or more OOC locations to be listed, each with its own geocode and address metadata. For example, a chronicle may operate in multiple counties.
- **IC Locations**: Supports multiple in-character locations. Each entry includes a name, geocode, and structured address. These are treated as repeatable sub-records.
- **Game Sites**: Chronicles may run both physical and virtual games. Each game site is stored independently and may include:
  - **Virtual**: Type (e.g., Discord, Zoom), URL
  - **Physical**: Full address and city/state/country fields
- **Chronicle Data**: Each data point (Premise, Game Theme, Game Mood, Information for Travellers) is stored as a WYSIWYG/HTML field to allow for full formatting and links.
- **Genres**: Multi-select field. A single chronicle may list support for multiple genres (e.g., Vampire, Wraith, Mage, etc.) and sub-genres (e.g., Sabbat, Camarilla).
- **Sessions**: Repeatable entries that can vary by genre. Each session entry includes:
  - Session type (Game/Meetup)
  - Frequency (e.g., 1st Saturday, Every Other Week, etc.)
  - Day and Time
  - Game Date Notes
  - Genres covered
- **Staff**:
  - **HST** (Head Storyteller): Single required entry. Should be linkable to an existing user account.
  - **CM** (Chronicle Manager): Single required entry. Should be linkable to an existing user account.
  - **ASTs** (Assistant Storytellers): One or more entries. Each AST can include a unique title and contact information. User linkage optional.
- **Staff Selection Processes**: Stores how each staff member is selected. Includes options like Player Vote, Appointed by HST, or Other. Stored per role (HST, CM, AST).
- **Document Links**: Supports multiple document entries (PDFs, Google Docs, etc.). Each entry includes a label and link.
- **Chronicle Email Lists**: Allows multiple entries, each with a name and address.
- **Web and Social Media URLs**: Includes standard Chronicle website, Discord invite URLs, Facebook pages, and other external links.

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

Chronicle lists can be embedded anywhere using shortcode filters and enhanced with client-side filtering using Select2.js.

### Basic Usage

[owbn-chronicles]

Renders a complete listing of all approved chronicles in a table format.

### Filtered Listings via Shortcode

Shortcodes accept attributes for server-side field-based filtering:

[owbn-chronicles region="great lakes" genre="sabbat"]

Supported attributes (case-insensitive):

- region
- genre
- country
- state
- game_type (virtual, in-person)

Multiple filters can be combined to narrow the results before rendering.

### Dynamic Filtering with Select2.js

When the shortcode is rendered, the output includes a table of chronicles with class-based annotations for each metadata attribute (e.g., region, genre, game type). These values are embedded as both visible content and CSS classnames on each table row.

An enhanced Select2.js-powered filter interface is displayed above the table, allowing users to dynamically filter chronicles without reloading the page.

Client-side filters will mirror the server-side attributes:

- Region
- Genre
- Country
- State
- Game Type

Select2 is initialized using `<select>` elements populated from the dataset rendered in the table. Rows will be hidden/shown based on user selections.

This feature allows users to begin with the full list of chronicles and refine the results interactively on the frontend.

## Installation

1. Upload the plugin to `/wp-content/plugins/owbn-chronicle-manager/`
2. Activate via the WordPress admin panel
3. Use `[owbn-chronicles]` in pages or posts to render lists
4. Assign proper roles to Chronicle owners and admins

## Developer Notes

- All chronicle metadata is stored as post meta attached to the `owbn_chronicle` post type.
- Shortcode rendering is handled via template parts and inline rendering hooks.
- Future enhancements will include JSON API support, external data sync, and Gravity Flow integration for approvals.

## License

This plugin is licensed under the GNU General Public License v2.0.

See LICENSE for full text.

## Example Output

To see a visual example, visit:

https://www.owbn.net/chronicles/new-york-city-ny-usa-kings-of-new-york

## Roadmap

- **Custom post type scaffolding**  
  This is the foundational code that defines the `owbn_chronicle` post type. It includes labels, supported features (title, editor, custom fields), capabilities, rewrite rules, and UI integration. This scaffolding allows WordPress to treat chronicles as first-class data entities and lays the groundwork for storing all structured chronicle information.

- **User/admin roles & edit views**  
  Role-based editing ensures that Chronicle Owners (e.g., the HST or CM) can update their own chronicle, while Admins have broader access to view and manage all records. Edit views define what fields each role can see or change, and how that UI is presented—typically using metaboxes or custom forms.

- **Field storage and validation**  
  This covers the creation of all data fields used to represent a chronicle (locations, staff, genres, etc.) and the mechanisms to validate inputs. Validation ensures that fields like email addresses, URLs, or dropdowns are correctly formatted and stored in post meta. This includes support for multi-value fields, sub-records, and repeatable groups.

- **Admin change approval UI**  
  For fields that require administrative approval (like staff changes or genre updates), this component introduces a review interface. Admins can see proposed edits, compare them to existing values, and approve or reject them. This UI ensures oversight on critical data while preserving Chronicle Owner autonomy for routine updates.

- **Gravity Flow integration for approval routing**  
  Gravity Flow will be integrated to automate the workflow behind change approvals. When a Chronicle Owner submits a form (e.g., to update staff info), Gravity Flow routes the submission to the appropriate Admin(s) for review. It manages notifications, approvals, rejections, and audit logging of the approval process.

- **Custom REST API for federation and external tools**  
  A secure REST API will expose chronicle data to other systems—like OWbN.net or third-party tools. This supports federated data access, syncing, or listing chronicles externally without duplicating data. The API will include endpoints for listing, filtering, and optionally submitting updates with authentication.

## Contributing

Want to help? Open a GitHub issue, fork the repo, or submit a pull request.

## Maintainers

This plugin is maintained by OWbN.org development volunteers.