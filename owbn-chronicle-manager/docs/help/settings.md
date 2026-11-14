# C&C Settings

Switches chronicles and coordinator offices on for this site, and holds the genres and regions the chronicle editor offers.

## Who can use this

Site admins.

## How to get there

wp-admin → **OWBN C&C** → **Settings** (the screen is titled **C&C Plugin Settings**).

## The screen

- A note that other OWbN sites read these records through OWBN Core's gateway, under `owbn/v1/`.
- **Chronicles** → **Enable Chronicle management** - turns on **All Chronicles** and the chronicle screens.
- **Coordinators** → **Enable Coordinator management** - turns on **All Coordinators** and the office screens.
- **Genre List** → **Available Genres** - one per line. A chronicle's **Genres** and its sessions' genres are picked from these.
- **Region List** → **Available Regions** - one per line. A chronicle's **Region** is picked from these.
- **Save Changes**.

## Common tasks

### Add a genre or a region

1. Add it on a line of its own in **Available Genres** or **Available Regions**.
2. Click **Save Changes**. The chronicle editor offers it straight away.

## Things to know

- Switching a type off hides its screens on this site. Its records stay, and come back when it's switched on.
- Taking a genre or region off a list doesn't take it off the chronicles that have it.
- The key and the address other sites use to read the records are set in **OWBN Core** → **Settings**.

## Troubleshooting

- **No All Chronicles or All Coordinators under OWBN C&C.** Its switch is off on this site.
- **A genre or region is missing from the chronicle editor.** Add it here.

## Related

- [Chronicles](chronicles.md)
- [C&C Manager Troubleshooting](troubleshooting.md)
- [C&C Manager Overview](../overview.md)
