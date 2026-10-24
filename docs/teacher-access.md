# Individual teacher access and personal tokens

Issue [#6](https://github.com/gafapa/moodle-local_moodlia/issues/6) asks whether
each teacher can connect MCP, edit only their courses, and obtain their own
token. Moodle already provides these capabilities; MoodlIA uses the identity
and permissions associated with each personal token.

## Administrator setup

1. Enable web services and the REST protocol.
2. Create a system role with only `local/moodlia:useapi`, `webservice/rest:use`
   and, for self-service tokens, `moodle/webservice:createtoken`. Assign it only
   to approved teachers. An editing-teacher role assigned inside a course does
   not grant the system-level MoodlIA entry capability.
3. Keep the MoodlIA external service enabled and its authorized-users restriction
   enabled. Add the approved teachers under **Site administration > Server >
   Web services > External services > MoodlIA service > Authorized users**.
4. Retain each teacher's normal course-level roles. Do not grant Manager,
   `moodle/course:update`, or activity-management permissions at system level.
   Do not grant `local/moodlia:manageplugins` for ordinary teaching.

The administrator approves access once. Moodle's authorized-users list remains
the admission control; teachers do not authorize themselves for the service.
Changing that list to unrestricted access is not required for self-service.

## Teacher self-service

Sign in with your own non-administrator teacher account, then open
**Preferences > User account > Security keys** (`/user/managetoken.php`). Moodle
generates and displays personal web-service tokens for authorized services when
the account has `moodle/webservice:createtoken`. Menu labels can vary by theme.
Use the MoodlIA service token in your client's protected environment variable;
connect to `/local/moodlia/mcp.php` as described in the
[MCP client guide](mcp-client-setup.md).

Do not share an administrator token between teachers. Token reset and revocation
use Moodle's existing security-key and administrator interfaces. Administrators
can continue to issue tokens centrally instead of enabling self-service.

## Meaning of "my courses"

Edits require the operation's Moodle capability in the target course or activity.
A teacher with editing rights in two courses can edit both. A category or system
role can intentionally grant broader rights. Course listings may include visible
courses outside the teacher's enrolments; visibility does not grant editing.
MoodlIA does not impose a separate course-owner model or hide the public catalog.

Native regressions exercise an enrolled editing teacher against their own and
another teacher's activities through the external adapter used by REST/MCP and
the session-based WebMCP adapter. They also exercise Moodle's personal-token
generation before and after service authorization and ensure repeated generation
does not duplicate the token. These are disposable Moodle tests, not changes to
production roles or accounts.
