# User management release and deployment - 28 October 2026

MoodlIA Moodle plugin **0.1.222**, build **2026102800**, is published on [GitHub](https://github.com/gafapa/moodle-local_moodlia/releases/tag/v0.1.222) and installed on both managed Moodle sites. The maintainer uploaded the same validated local ZIP to [Moodle Marketplace](https://marketplace.moodle.com/plugins/local_moodlia/versions), version **29727**. Its public 0.1.222 version page reports **0 test problems found** and **All automated tests passed successfully**.

The immutable release ZIP SHA-256 is `03589819fcb5554315186eea56e3017daedea067d88206e6f70e53b5e80c93c2`. All **594** packaged files matched on each installed site. The GitHub workflow initially regenerated an equivalent ZIP with LF line endings in 12 text files; byte comparison proved there were no other differences. After that workflow completed, the final GitHub assets were aligned with the exact ZIP deployed on both sites and uploaded to Marketplace. The generated variant is retained in local preparation evidence. Release commit: `9891b38a7294e3f34b0913f6ecd99260d3c7087c`.

## Qualification

- [Full native Moodle CI](https://github.com/gafapa/moodle-local_moodlia/actions/runs/40777293885) passed all ten PHP/database profiles across Moodle 4.5 through 5.3 beta, plus coding-style and metadata checks.
- [General CI](https://github.com/gafapa/moodle-local_moodlia/actions/runs/40777293894) and the [release archive workflow](https://github.com/gafapa/moodle-local_moodlia/actions/runs/40778703553) passed.
- [CLI CI](https://github.com/gafapa/moodlia-cli/actions/runs/40776825039) passed its Linux/Windows and Node 22/24 checks, coverage gates, browser checks, package checks, and sibling contract parity.
- Local browser tests now compare actual tool names with the canonical contract instead of a fixed operation count. The catalog contains 252 operations.

## Managed sites

| Site | Moodle | Disk/database build | Final state |
| --- | --- | --- | --- |
| Aula | 4.5.15 | 2026102800 / 2026102800 | Healthy, maintenance disabled, HTTPS login 200 |
| Clases | 5.2.4 | 2026102800 / 2026102800 | Healthy, maintenance disabled, HTTPS login 200 |

Database and preceding-plugin recovery archives passed gzip and SHA-256 checks before switching files. Retained recovery directories:

- `/opt/docker/backups/moodle-aula/plugin-0.1.222-20261028T221644Z`
- `/opt/docker/backups/moodle/plugin-0.1.222-20261028T221845Z`

Before/after counts matched for courses, activities, quizzes, quiz slots, attempts, quiz grades, assignment submissions, grade records, and users. Moodle core, WebMCP settings, runtime images, and account permissions were preserved. PHP tokenizer/core compatibility passed before the upgrade.

## Authenticated HTTP verification

Clases passed REST account search, the authenticated 252-tool MCP catalog, MCP account search with `suspended=false`, and invalid suspension-target rejection. Aula's existing course-scoped token passed the MCP catalog check and correctly received permission-denied responses for global directory search through both REST and MCP. Its permissions were preserved. Invalid suspension targets were also rejected.

No accounts were created, updated, suspended, or deleted by these production probes. Native lifecycle writes were qualified only in disposable Moodle test environments. Sanitized installation and probe evidence is in [deployment evidence](evidence/deployment-2026-10-28.json).
