# User management

MoodlIA exposes account management through the same Moodle external functions for REST, MCP, and the MoodlIA CLI. The new operations are `list_users` and `set_user_suspended`. Existing operations provide `get_user_details`, `create_user`, `update_user`, `delete_user`, `enrol_user`, `unenrol_user`, course roles, groups, and cohort membership.

Upgrade the Moodle plugin to build `2026102800` or later through Moodle's normal upgrade flow to register the new service functions. Update the CLI's bundled operation contract and reconnect MCP clients to refresh their tool catalog. The compatible releases are plugin `0.1.222` and CLI `moodlia@0.5.2`. Publication and deployment evidence is tracked separately.

## Search and inspect

`list_users` returns `users`, `total`, `offset`, and `limit`. It excludes deleted, guest, and remote MNet accounts. Results are ordered by user id, with a default page size of 50 and a maximum of 100. `search` matches a literal substring in username, first name, last name, or email; SQL wildcard characters are treated literally. Exact `username`, `email`, and `auth` filters combine with the optional `suspended` filter. Omit `suspended` to include both states. No password hashes or tokens are returned.

The account directory requires `local/moodlia:useapi`, `moodle/user:viewdetails`, and `moodle/user:viewalldetails` in the system context. A teacher's course permissions alone do not grant global account search.

```sh
moodlia plugin list-users --search student --limit 25 --offset 0
moodlia plugin list-users --username student01
moodlia plugin list-users --auth manual --suspended true
moodlia plugin get-user-details --user-id 42
```

Equivalent MCP tool calls:

```json
{"name":"list_users","arguments":{"search":"student","limit":25,"offset":0}}
{"name":"list_users","arguments":{"username":"student01"}}
{"name":"get_user_details","arguments":{"user_id":42}}
```

## Create, update, suspend, and delete

Account creation requires `moodle/user:create`; updates, password changes, suspension, and reactivation require `moodle/user:update`; deletion requires `moodle/user:delete`. Each also requires `local/moodlia:useapi` at system context.

Use the existing `create_user` operation with `username`, `firstname`, `lastname`, `email`, and `password`. Supply passwords through your client's protected input handling. `update_user` supports first name, last name, email, password, authentication plugin, and suspension state.

```sh
moodlia plugin update-user --user-id 42 --firstname Alex --lastname Example
moodlia plugin set-user-suspended --user-id 42 --suspended true
moodlia plugin set-user-suspended --user-id 42 --suspended false
moodlia plugin get-user-details --user-id 42
```

```json
{"name":"set_user_suspended","arguments":{"user_id":42,"suspended":true}}
{"name":"set_user_suspended","arguments":{"user_id":42,"suspended":false}}
```

Suspension terminates existing Moodle sessions. Reactivation permits login again without changing profile data or enrolments. Both operations return the saved user profile. The same protection applies to `update_user` when its `suspended` field is used.

Guest, deleted, and remote accounts cannot be mutated. Only a site administrator may edit another site administrator. Site administrators cannot be deleted or suspended through these operations. Callers cannot delete or suspend their own account.

`delete_user` / `moodlia plugin delete-user --user-id 42` performs Moodle account deletion. Use it only when account deletion is intended; suspension is reversible. Course enrolment changes use `enrol_user` and `unenrol_user` independently of the account's suspension state.

## Validation

See [local validation evidence](user-management-validation-2026-10-28.md) for native Moodle versions, transport checks, and test scope.
