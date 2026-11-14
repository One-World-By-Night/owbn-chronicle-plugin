# Coordinator Offices

The list of OWbN's coordinator offices on council.owbn.net, and where a new office is added.

## Who can use this

- Site admins, the Exec team and the Web team see every office and add new ones.
- A coordinator or sub-coordinator sees only their own office.

## How to get there

wp-admin on council.owbn.net → **OWBN C&C** → **All Coordinators** (the screen is titled **Coordinators**).

## The screen

- One row per office. Click its name to open it, see [Editing a Coordinator Office](coordinator-edit.md).
- **All**, **Published**, **Draft** and **Decommissioned** above the list show the offices in that state.
- **View** under a name opens the office's public page.
- **Add New Coordinator**, next to the title, for site admins, the Exec team and the Web team.
- Everyone else sees "Showing only coordinators you have access to."

## Common tasks

### Add a new office

1. On sso.owbn.net, go to **Users** → **accessSchema** and add the office's roles, one per line. A genre or clan office gets `coordinator/<slug>/coordinator` and `coordinator/<slug>/sub-coordinator`; an administrative office gets `exec/<slug>/coordinator` and `exec/<slug>/staff`. Click **Add Role(s)**.
2. Here, click **Add New Coordinator**.
3. Title it with the office's name, such as "Sabbat Coordinator".
4. On **Basic Info**, set the **Coordinator Slug** (lowercase letters, numbers and dashes), the **Office Title**, the **Appointment** and the **Type**.
5. On **Coordinator**, pick the coordinator's account and fill in their name and emails.
6. Click **Publish**. The coordinator gets the office's coordinator role on sso.owbn.net.

### Close an office

Site admins only.

1. Open the office.
2. In **Publish**, set **Status** to **Decommissioned** and click **Update**.

## Things to know

- An office's **Type** decides its roles: an administrative office's start with `exec/`, a genre or clan office's with `coordinator/`.
- The slug can't be changed once saved.
- Close an office by decommissioning it. Only site admins, the Exec team and the Web team change an office's status or move one to the Trash.
- Add the office's roles on sso.owbn.net before saving its coordinator, or nobody gets a role.
- Other OWbN sites keep a copy of the list for up to an hour.

## Troubleshooting

- **No Add New Coordinator button, or "You do not have permission to create new items."** Only site admins, the Exec team and the Web team add offices.
- **Your office isn't in the list.** The list goes by your coordinator or sub-coordinator role for the office. A role given in the last ten minutes may not count yet; reload then. With no role at all, ask the Web team.

## Related

- [Editing a Coordinator Office](coordinator-edit.md)
- [Pending Staff Changes](pending-changes.md)
- [C&C Manager Overview](../overview.md)
