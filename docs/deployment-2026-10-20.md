# Release 0.1.220 deployment

Verified on 20 October 2026 (Europe/Madrid) after the ten-profile compatibility
matrix passed on release commit `af4a76892a977e9813eaf4e95ac5d58a034641a5`.

| Site | Moodle | Plugin | Stored build | Package files verified |
| --- | --- | --- | --- | --- |
| `https://aula.gallego.top` | 4.5.15 | 0.1.220 | 2026102000 | 588 |
| `https://clases.gallego.top` | 5.2.4 | 0.1.220 | 2026102000 | 588 |

Both installations use the published GitHub ZIP with SHA-256
`e85bc85785d642e2b581c09e12c33a56023d22928328729dfaebeb978e9c3eef`.
Every installed package file matches that archive. Disk and database builds
agree; both containers are healthy, maintenance mode is disabled and login
pages return HTTP 200. WebMCP remains enabled, and anonymous endpoint POST
requests are rejected with HTTP 400. The quiz introduction helper loads on both
sites.

Each upgrade retained a protected, gzip-tested and checksum-verified database
snapshot and copy of plugin 0.1.219 on the managed server. The replacement was
staged before a brief maintenance window, followed by the Moodle upgrade,
cache purge and application restart to clear web OPcache. Moodle branches and
application data volumes were retained.

Before/after counts match for courses, modules, quizzes, question slots, quiz
attempts and grades, assignment submissions, gradebook grades and users. No
production course was created or edited as a deployment test. These deployment
checks do not replace the native preservation and independent REST
[qualification](quiz-intro-validation-2026-10-20.md).

Exact readback is retained in [the deployment evidence](evidence/deployment-2026-10-20.json).
Database dumps, upgrade logs and recovery paths remain on the protected server.
