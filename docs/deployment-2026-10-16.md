# Release 0.1.219 Deployment

The release was deployed to the two running managed Moodle sites after the
five-branch compatibility workflow passed:
[Moodle PHPUnit run 38819943494](https://github.com/gafapa/moodle-local_moodlia/actions/runs/38819943494).
The checked source commit is `c1f5372`; the release documentation does not change
the qualified runtime files.

| Site | Moodle | Plugin | Stored build | WebMCP tools |
| --- | --- | --- | --- | --- |
| `https://clases.gallego.top` | 5.2.4 | 0.1.219 | 2026101502 | 251 |
| `https://aula.gallego.top` | 4.5.15 | 0.1.219 | 2026101502 | 251 |

WebMCP is explicitly enabled on both sites. Existing Moodle sessions, CSRF
checks, and the `local/moodlia:useapi` capability continue to govern access.
Anonymous POST requests are rejected with the endpoint's structured error
response and HTTP 400.

Verification at `2026-10-15T23:03:53Z` independently compared all 587 installed
files on each site with the deployment archive. Disk and database builds match;
both login pages return HTTP 200, both containers are healthy, and maintenance
mode is disabled. The exact readback is retained in
[deployment evidence](evidence/deployment-2026-10-16.json).

The installed archive SHA-256 is
`106925c0f92a50b43e8b378aa8cf1e97d189aed53fb85486c6520e49c5362004`.
Plugin and database recovery copies remain in the server's protected backup
directories. Upgrade logs and database dumps remain on the managed server;
they are not included in this repository. No real course content was changed
for deployment verification. The stopped disposable Moodle lab was not started.

These live checks verify deployment and access boundaries. The authenticated
browser operation paths were qualified separately using synthetic data, as
recorded in [the WebMCP qualification report](webmcp-validation-2026-10-15.md).
