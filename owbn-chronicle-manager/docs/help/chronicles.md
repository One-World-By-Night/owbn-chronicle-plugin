# Chronicles

The list of OWbN's chronicles on chronicles.owbn.net, and where a new chronicle is added.

## Who can use this

- Site admins, the Exec team and the Web team see every chronicle and add new ones.
- An HST, CM or AST sees only their own chronicle, and a satellite's parent CM sees the satellite too.

## How to get there

wp-admin on chronicles.owbn.net → **OWBN C&C** → **All Chronicles** (the screen is titled **Chronicles**).

## The screen

- One row per chronicle. Click its name to open it, see [Editing a Chronicle](chronicle-edit.md).
- **Compliance** - **OK** when the chronicle has all three required documents, otherwise how many are missing. Point at the badge to see which.
- **All**, **Published**, **Draft** and **Decommissioned** above the list show the chronicles in that state. A decommissioned chronicle has closed; its record stays.
- **View** under a name opens the chronicle's public page.
- **Add New Chronicle**, next to the title, for site admins, the Exec team and the Web team.
- Everyone else sees "Showing only chronicles you have access to."

## Common tasks

### Add a new chronicle

1. On sso.owbn.net, go to **Users** → **accessSchema** and add the chronicle's roles, one per line: `chronicle/<slug>/hst`, `chronicle/<slug>/cm`, `chronicle/<slug>/staff` and `chronicle/<slug>/player`. A satellite gets no `cm` role. Click **Add Role(s)**.
2. Here, click **Add New Chronicle**.
3. Title it with the city, state and country, then the chronicle's name: "Harbor City, MA - USA, Tides of Ash".
4. Set the **Chronicle Slug**: 2 to 8 lowercase letters and numbers. It can't be changed once saved.
5. Tick **Probationary?** or **Satellite?** if they apply. A satellite also needs its **Parent Chronicle**.
6. On **Game, Schedule & Staff**, pick the **Genres** and **Game Type**, and fill in **HST Info** and, unless it's a satellite, **CM Info**.
7. On **Documents, Players & Sites**, give the House Rules, Disciplinary Policy and Accessibility Policy a link or a file.
8. Click **Publish**. The HST, CM and ASTs get their roles on sso.owbn.net.

### Close a chronicle

Site admins only.

1. Open the chronicle.
2. In **Publish**, set **Status** to **Decommissioned** and click **Update**.

## Things to know

- The slug is permanent: it's in the chronicle's roles, its address and its CM email, `<slug>-cm@owbn.net`.
- Saving a staff member before the chronicle's roles exist on sso.owbn.net gives them no role, and nothing says so. Add the roles first.
- Close a chronicle by decommissioning it. Only site admins, the Exec team and the Web team change a chronicle's status or move one to the Trash.
- Other OWbN sites keep a copy of the list for up to an hour.

## Troubleshooting

- **No Add New Chronicle button, or "You do not have permission to create new items."** Only site admins, the Exec team and the Web team add chronicles.
- **Your chronicle isn't in the list.** The list goes by your HST, CM or staff role for it. A role given in the last ten minutes may not count yet; reload then. With no role at all, ask the Web team.
- **The HST or CM got no role.** The chronicle's roles weren't on sso.owbn.net when they were saved, or they were picked as **[New User]**. Add the roles, pick their account and click **Update**.

## Related

- [Editing a Chronicle](chronicle-edit.md)
- [HST & AST Report](hst-ast-report.md)
- [C&C Manager Overview](../overview.md)
