# Distribution verification - 2026-10-20

## Moodle plugin

MoodlIA plugin 0.1.220 (build 2026102000) is published in the
[Moodle Marketplace](https://marketplace.moodle.com/plugins/local_moodlia).
Marketplace version 29296 passed its automated tests, and the public product
page's default download points to that version. The uploaded ZIP is the
qualified GitHub release artifact with SHA-256
`e85bc85785d642e2b581c09e12c33a56023d22928328729dfaebeb978e9c3eef`.
A separate download round trip from Marketplace was not completed; its browser
download was cancelled. The GitHub ZIP and both installed copies were verified
independently, as recorded in the [deployment report](deployment-2026-10-20.md).

## npm

The following packages are publicly available, and their registry integrity
values match the exact submitted tarballs:

| Package | Version | Local checks before publication |
| --- | --- | --- |
| [moodlia](https://www.npmjs.com/package/moodlia/v/0.5.1) | 0.5.1 | Type checks and 174 tests passed |
| [moodlia-sync](https://www.npmjs.com/package/moodlia-sync/v/0.1.2) | 0.1.2 | Type checks and 187 tests passed |
| [moodlia-browser](https://www.npmjs.com/package/moodlia-browser/v/0.1.1) | 0.1.1 | Included in the CLI suite and existing browser CI |

A disposable Windows project installed the exact versions from the public
registry. Both CLI help commands ran, the CLI and optional browser component
loaded, and the native SQLite driver executed an in-memory query. Only the
pinned `better-sqlite3@12.4.1` install script was approved in that disposable
project; global npm policy was retained. Synchronization keeps its declared
`moodlia` dependency range; no dependency upgrade was introduced by publication.

`moodle-core-cli` stays at 0.4.1: comparison with the public package found only a
README difference, with identical runtime files. No new Core release was needed.
These package smoke checks do not establish a new live synchronization matrix
or test real production course writes.

Exact integrity values and scope are retained in the
[distribution evidence](evidence/distribution-2026-10-20.json).
