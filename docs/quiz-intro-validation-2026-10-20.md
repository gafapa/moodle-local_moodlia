# Quiz introduction validation - 2026-10-20

The unreleased introduction-only update was executed inside two isolated local
Moodle installations, using fictional courses, users, questions and files.
No production site was connected or modified.

| Moodle source snapshot | Native preservation tests | Independent REST calls |
| --- | --- | --- |
| 4.5.14+ (Build: 20261002) | 10 passed, 48 assertions | 38 verified |
| 5.2.3+ (Build: 20261002) | 10 passed, 48 assertions | 38 verified |

Both profiles used Windows x64, PHP 8.3.35 and a dedicated MariaDB 11.4.8
instance bound to loopback. The candidate is the working-tree patch over plugin
0.1.219 at validation time. Release 0.1.220 ships that runtime patch with a
version metadata bump and updated release documentation. Source revisions, candidate file hashes and
raw-result checksums are recorded in [the structured evidence](evidence/quiz-intro-2026-10-20.json).

## Native checks

`module_update_preservation_test` passed with `--fail-on-warning` on each profile.
The new cases exercise the external adapter and real Moodle database, File API,
capability checks, course cache and module update event. They cover:

- Editing an existing quiz while retaining its identity and stored text format.
- Preserving every unrelated quiz setting, question slots, a finished student
  attempt, its calculated grade, an administrator preview, feedback records and
  the bytes of an existing introduction file.
- Explicit HTML, plain, Markdown and Moodle formats, and deliberate clearing.
- Rejecting invalid input before a simultaneous rename.
- Rejecting students without module editing permission and non-quiz targets.

The same run also exercises the existing common-update preservation cases for
Choice responses, Assignment settings, URL parameters, Folder files, Lesson
media, rating windows and Workshop grade categories. It does not constitute a
run of the entire plugin PHPUnit suite or the full supported-version matrix.

The fixture supplies responses in Moodle's native localized summary format and
checks preview flags independently of database row order.

## Independent REST checks

`tools/compatibility-rest.mjs --regression-only` now includes a quiz. Creation,
updates and verification happen in separate HTTP requests. The checks read the
introduction through `get_course_quizzes`, compare unrelated returned settings,
retain the course-module and instance ids, preserve an omitted format, reject a
format-only update and verify deliberate clearing. The regression also retains
its Page and File resource checks.

Each profile made 38 verified MoodlIA calls, including two expected rejections:
invalid introduction input and an attempted cross-course module deletion.
HTTP uploads and the byte-checked resource download are additional requests.
All generated courses were removed afterwards.

## Reproduction and remaining boundaries

Use an isolated Moodle with the candidate plugin and initialized PHPUnit data:

```text
php vendor/bin/phpunit --fail-on-warning local/moodlia/tests/module_update_preservation_test.php
```

For Moodle 5.1 and later, use `public/local/moodlia/tests/module_update_preservation_test.php`.
For independent HTTP qualification, configure only a disposable loopback site
using `tools/compatibility-setup.php`, capture its synthetic token privately, and
run the REST regression with `MOODLE_BASE_URL` and `MOODLE_REST_TOKEN`.

`npm run check` also passed: 46 static tests, syntax checks for 582 PHP files,
JavaScript syntax, contract and manifest parity, boilerplate, documentation and
Marketplace source-package checks.

The initial Windows installation emitted unrelated LTI OpenSSL configuration
warnings. Subsequent native tests used the bundled `openssl.cnf` through
`OPENSSL_CONF`; both final native suites passed. No LTI qualification is claimed.

Raw JUnit, REST and check logs are retained locally in the workspace's
`.quiz-intro-validation-20261020/` directory. Synthetic REST tokens were revoked;
both HTTP servers and the dedicated database were stopped, with no owned
listeners remaining. No hosted CI, release publication or production deployment
was performed.
