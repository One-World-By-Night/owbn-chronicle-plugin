# C&C Manager Troubleshooting

Fixes for what goes wrong most when editing a chronicle or a coordinator office: fields that won't save, missing access, and roles that don't match the record.

## Who can use this

Anyone who edits a chronicle or an office. Some fixes need a site admin.

## How to get there

Most fixes start from wp-admin → **OWBN C&C** → **All Chronicles** or **All Coordinators** → the record's name.

## The screen

Most fixes use the record's edit screen: the bars at its top, the tab marked ⚠, and **Revisions** down the side. Waiting staff changes are on [Pending Staff Changes](pending-changes.md).

## Common tasks

### Someone got no role when they were put in a seat

1. If the record has a **Pending Staff Changes** bar, the change is waiting for approval.
2. Check their **User** is their account, not **[New User]** or empty.
3. Check the record's roles exist on sso.owbn.net under **Users** → **accessSchema**, such as `chronicle/<slug>/staff`.
4. Fix what was missing and click **Update** with them in the seat.

### Undo a change

1. Under **Revisions**, click **Browse**.
2. Find the version and click **Restore This Revision**.

## Things to know

- The lists and the edit screens go by your accessSchema roles, checked again every ten minutes.
- Other OWbN sites keep a copy of every record for up to an hour.
- Restoring a revision leaves the staff and the admin-only fields as they are, unless a site admin restores it.

## Troubleshooting

- **"Some fields could not be saved. All other edits WERE saved."** The fields named kept their old values, and their tab shows ⚠. Fix them and click **Update** again. A staff seat that has to be filled needs a **User**, a **Display Name** and a **Display Email**.
- **"Invalid or duplicate slug."** A chronicle slug is 2 to 8 lowercase letters and numbers, not already taken. A new chronicle with a bad slug saves as a draft.
- **A field is greyed out, or a tab is missing.** Only site admins change a chronicle's name, slug, probationary, satellite, parent, start date and region, or an office's **Basic Info** and **Coordinator**.
- **A staff change didn't appear.** It's waiting for approval.
- **"Sorry, you are not allowed to edit this item." or "You do not have permission to edit this Chronicle."** You hold no role for that chronicle or office. Ask the Web team.
- **Your chronicle or office isn't in the list.** A role given in the last ten minutes may not count yet; reload then.
- **Someone kept their role after being taken off.** They hold it some other way, such as a second seat. A site admin can check on sso.owbn.net.
- **A change isn't showing on another OWbN site.** That site still has its old copy. Its admin can click **Refresh All Cache** under **OWBN Core** → **Settings**.
- **"Required documents missing."** Give House Rules, Disciplinary Policy and Accessibility Policy a link or a file under **Document Links**. The chronicle still publishes without them.

## Related

- [Editing a Chronicle](chronicle-edit.md)
- [Editing a Coordinator Office](coordinator-edit.md)
- [Pending Staff Changes](pending-changes.md)
- [C&C Manager Overview](../overview.md)
