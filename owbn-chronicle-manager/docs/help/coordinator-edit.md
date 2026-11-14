# Editing a Coordinator Office

One office's record: its description, term, sub-coordinators, history and links. Every OWbN site shows what's saved here.

## Who can use this

- The office's coordinator and sub-coordinators. For an administrative office, its coordinator and staff.
- Site admins, the Exec team and the Web team, on every office and every field.

## How to get there

wp-admin on council.owbn.net → **OWBN C&C** → **All Coordinators** → click the office's name.

## The screen

- Six tabs. Coordinators and sub-coordinators see four; **Basic Info** and **Coordinator** are for site admins only.
  - **Basic Info** - **Coordinator Slug**, **Office Title**, **Appointment** (Elected or Appointed) and **Type** (Administrative, Genre or Clan).
  - **Description** - **Hosting Chronicle** (a genre or clan office's home chronicle), **Website URL**, **Term Start Date** and **Term End Date**, then the office's public description below them: office hours, how R&U requests reach the office, anything else members need.
  - **Coordinator** - who holds the office.
  - **Staff** - the **Sub-Coordinators**, see below.
  - **History** - **Previous Coordinators**, a read-only log of each past coordinator and their term.
  - **Links** - **Document Links**, **Email Lists**, **Discord Lists** and **Player Lists**.
- Down the side: **Publish**, with **Update**, and **Revisions**.

### Sub-coordinators

Each sub-coordinator has:

- **User** - their account on this site, which gets the sub-coordinator role. For someone without an account yet, leave **User** empty and pick their account once they have one.
- **Display Name** and **Display Email** - shown on the office's public page.
- **Role** - their job in the office, if they have one.
- **Actual Email** - their own address, seen only by the office's coordinator and staff and by site admins.

**Add** adds a sub-coordinator; **Remove** takes one off. Picking an account doesn't fill in the names or emails; type them.

## Common tasks

### Update the office's details

1. Change what you need on any tab.
2. Click **Update**.

### Change the sub-coordinators

1. On **Staff**, add, remove or change sub-coordinators.
2. Click **Update**.
3. Unless you're a site admin, the change now waits for approval, and the top of the page says so. Your other changes are saved.

### Hand the office to a new coordinator

Site admins only.

1. On **Coordinator**, pick the new coordinator's account and fill in their name and emails.
2. On **Description**, set the new **Term Start Date** and **Term End Date**.
3. Click **Update**. The outgoing coordinator moves to **History**, with their term ending today, and loses the coordinator role; the new one gets it.

## Things to know

- Taking someone off **Staff** ends their role at once, even while the change waits for approval. If the change is rejected, they get the role back.
- Restoring a revision leaves the coordinator, the sub-coordinators and the **Basic Info** fields as they are, unless a site admin restores it.
- Only site admins, the Exec team and the Web team change an office's status or visibility, or move it to the Trash.
- Other OWbN sites keep a copy of the office for up to an hour, so a change can take that long to show everywhere.

## Troubleshooting

- **No Basic Info or Coordinator tab.** Those are for site admins. Ask the Web team to change the office's title, type or coordinator.
- **"Some fields could not be saved. All other edits WERE saved."** The fields named kept their old values. Fix them and click **Update** again.
- **Your staff change didn't appear.** It's waiting for approval; the **Pending Staff Changes** bar says so.

## Related

- [Coordinator Offices](coordinators.md)
- [Pending Staff Changes](pending-changes.md)
- [C&C Manager Troubleshooting](troubleshooting.md)
