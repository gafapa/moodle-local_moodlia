# Submission Checklist

## Repository

- [ ] The public repository is named `moodle-local_moodlia`.
- [ ] The repository root contains `version.php`, `classes/`, `db/`, and `lang/`.
- [ ] GitHub Issues is enabled and publicly accessible.
- [ ] GitHub private vulnerability reporting is enabled.
- [ ] CI is green for PHP 8.3 and 8.4 on PostgreSQL and MariaDB.
- [ ] Release tag `v0.1.190` points to the reviewed release commit.
- [ ] The GitHub release contains `local_moodlia-0.1.190.zip`.

## Package

- [ ] `npm ci` completes without vulnerabilities.
- [ ] `npm run release:check` passes.
- [ ] The ZIP has one top-level directory named `moodlia`.
- [ ] The ZIP contains no secrets, development tooling, Node dependencies, or
      repository metadata.
- [ ] The package installs and upgrades cleanly on Moodle 5.2.
- [ ] Developer debugging produces no warnings or notices.
- [ ] REST and MCP read-only smoke tests pass.
- [ ] Capability-denied negative tests pass.

## Marketplace Listing

- [ ] Name, short description, full description, privacy summary, installation
      instructions, and reviewer test plan are copied from `marketplace/README.md`.
- [ ] Price is set to Free.
- [ ] Moodle 5.2 is selected as the supported version.
- [ ] Source, issue tracker, documentation, and website links are set.
- [ ] The maintainer profile and support contact are complete.
- [ ] The independent-project trademark disclaimer remains visible.
- [ ] The provider terms and intellectual-property declarations have been
      reviewed and accepted by the maintainer.

## Post-submission

- [ ] Record the Marketplace listing URL in the root README.
- [ ] Respond to automated precheck and reviewer findings publicly.
- [ ] Do not create a replacement ZIP without incrementing both `$plugin->version`
      and `$plugin->release`.
