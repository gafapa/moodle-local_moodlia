# Release and deployment verification, 24 October 2026

MoodlIA Moodle plugin **0.1.221**, build **2026102400**, is published on
[GitHub](https://github.com/gafapa/moodle-local_moodlia/releases/tag/v0.1.221)
and [Moodle Marketplace](https://marketplace.moodle.com/plugins/local_moodlia).
Marketplace version **29559** reports **Automated test passed**.

The uploaded archive is the GitHub release asset `local_moodlia-0.1.221.zip`.
Its SHA256 is
`aa29f89c64e84dbc44aa1cc10ffa2c79d08cbbc3c46dd7c2cc531483a8472d93`.
The Marketplace upload used that asset; an unauthenticated Marketplace download
redirects to login, so a second download hash is not claimed here.

## Qualification

- [Native Moodle Plugin CI](https://github.com/gafapa/moodle-local_moodlia/actions/runs/40129059459)
  passed all ten PHP/database profiles across Moodle 4.5–5.3. Each native MCP
  report passed 45 checks and validated all 250 schemas. Independent REST checks
  and native teacher access/token regressions passed too.
- [General CI](https://github.com/gafapa/moodle-local_moodlia/actions/runs/40129059432)
  and the [release workflow](https://github.com/gafapa/moodle-local_moodlia/actions/runs/40130217613)
  passed. The local dependency audit reported zero known vulnerabilities.
- [Test Lab weekly qualification](https://github.com/gafapa/moodlia-test-lab/actions/runs/40129063627)
  passed all 25 source/target branch pairs on SQLite plus the PostgreSQL
  large-backup case. Its reports qualify their recorded package versions and
  golden-image versions, independently of production deployment.

SDK write/read/cleanup checks use disposable Moodle fixtures. No AI provider is
contacted by these qualification tests.

## Managed deployment

| Site | Moodle | Plugin disk/database build | Package verification | Final status |
| --- | --- | --- | --- | --- |
| Aula | 4.5.15 | 2026102400 / 2026102400 | All 589 files matched | Healthy, maintenance disabled, HTTPS login 200 |
| Clases | 5.2.4 | 2026102400 / 2026102400 | All 589 files matched | Healthy, maintenance disabled, HTTPS login 200 |

Both deployments preserved the existing Moodle version and WebMCP setting.
Before/after counts matched for courses, activities, quizzes, quiz slots,
attempts, quiz grades, assignment submissions, grade records and users. Existing
quiz introduction support remained available, and the two no-argument MCP
schemas exposed object-valued `properties`. These are installation and local
contract checks, not a new authenticated external MCP session on production.

Database and preceding-plugin archives were gzip-tested and SHA256-verified
before switching files. Recovery copies and preceding runtime images remain
retained in the managed server inventory.

## Runtime incident and repair

The first Aula cache purge exposed a pre-existing mismatch between its PHP
8.3.36 core and an older tokenizer extension. The extension loaded normally but
returned token IDs inconsistent with PHP constants, causing PHP-DI to generate
invalid cache factories. The previous plugin was restored while the runtime was
repaired; protected record counts were unchanged.

Tokenizer was rebuilt from the same pinned PHP 8.3.36 source for both managed
images. The patched PHP core, other extensions, mounts and application versions
were retained. Synthetic lexer checks, module comparison, Moodle cache
regeneration and HTTPS checks passed on both sites. This extends the previous
runtime verification: a matching module list alone had not proved tokenizer
compatibility.

Recovery also left Moodle's global code-version hash referring to the first
successful upgrade. The deployment reset that detection cache under maintenance
and completed Moodle's normal CLI upgrade. Final direct database versions and
code-version hashes agree. A tokenizer preflight and explicit final-version
checks now precede and follow managed plugin deployment.
