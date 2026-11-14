# Course item backups release and deployment - 14 November 2026

MoodlIA Moodle plugin **0.1.224**, build **2026111400**, is published on [GitHub](https://github.com/gafapa/moodle-local_moodlia/releases/tag/v0.1.224), as [Moodle Marketplace](https://marketplace.moodle.com/plugins/local_moodlia/versions) version **30743**, and installed on both managed Moodle sites. Marketplace reports that its automated tests passed. Release 0.1.223 was never deployed: it stored backups created without a filename as `backup.mbz`, so each one replaced the previous one in the same area.

The release ZIP SHA-256 is `19c237ae7d5c7ed1092c149f936dbf13953bbf68f54815692f6874d4bb28e13b`. The same file was uploaded to Marketplace and installed on both sites. All **601** packaged files matched byte for byte on each site after installation.

## Qualification

- [Full native Moodle CI](https://github.com/gafapa/moodle-local_moodlia/actions/runs/43636394007) passed all PHP/database profiles across Moodle 4.5 through 5.3 beta, including the item backup coverage gates.
- Pull request CI for the release commit and the release archive workflow passed.
- CLI `moodlia@0.5.3` was published on npm after its CI passed on Linux and Windows with Node 22 and 24; a clean installation contained the 255-operation contract.

## Managed sites

| Site | Moodle | Disk/database build | Final state |
| --- | --- | --- | --- |
| Aula | 4.5.15 | 2026111400 / 2026111400 | Maintenance disabled, HTTPS login 200 |
| Clases | 5.2.4 | 2026111400 / 2026111400 | Maintenance disabled, HTTPS login 200 |

Each site was put in maintenance mode, backed up, switched to the new plugin directory, upgraded with `admin/cli/upgrade.php --non-interactive`, and had its caches purged. The release has no database schema changes; the upgrade registered the three item backup web service functions. The database dump passed a gzip check, and both recovery archives passed SHA-256 checks before the plugin files were switched. Retained recovery directories:

- `/opt/docker/backups/moodle-aula/plugin-0.1.224-20261114T231742Z`
- `/opt/docker/backups/moodle/plugin-0.1.224-20261114T231838Z`

Before/after counts matched for courses, course modules, quizzes, quiz slots, attempts, quiz grades, assignment submissions, grade records, users, and files. The WebMCP setting was preserved. On Aula the deployment script reported a failure after the site was back online, when it tried to delete a temporary state file inside the container. The final state and HTTP check had already been recorded, and the file was then removed as root.

## Verification

On each site, the plugin's MCP manifest lists 255 tools, including `backup_course_module`, `backup_course_section`, and `restore_course_item_backup`, and 255 `local_moodlia` web service functions are registered. Both MCP endpoints answered an empty request with HTTP 415, as expected. No authenticated REST or MCP calls were made, and no course content, backups, or accounts were created or changed. Item backup and restore were qualified only in disposable Moodle test environments. Sanitized state evidence is in [deployment evidence](evidence/deployment-2026-11-14.json).
