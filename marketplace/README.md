# Moodle Marketplace Submission Pack

This directory contains the copy and operator checklist for the first MoodlIA
listing in Moodle Marketplace. It is release metadata and is deliberately not
included in the installable plugin ZIP.

## Listing Identity

- Name: MoodlIA
- Frankenstyle component: `local_moodlia`
- Plugin type: Local plugin
- Price: Free
- Maturity: Beta
- Licence: GNU GPL v3 or later
- Supported Moodle versions: 5.2
- Release: 0.1.200
- Source code: https://github.com/gafapa/moodle-local_moodlia
- Issue tracker: https://github.com/gafapa/moodle-local_moodlia/issues
- Documentation: https://github.com/gafapa/moodle-local_moodlia#readme
- Website: https://github.com/gafapa/moodle-local_moodlia

## Short Description

Connect trusted AI agents, CLI workflows, and automation tools to Moodle through
permission-checked REST and MCP operations.

## Full Description

MoodlIA is a local Moodle plugin for controlled course creation,
administration, inspection, and repair from trusted automation clients. It
exposes a shared operation contract through Moodle REST web services and a
Moodle-hosted MCP endpoint, while retaining Moodle as the system of record and
enforcing the authenticated user's Moodle capabilities.

This first Marketplace release is marked as beta while broader production-site
feedback is collected.

The plugin supports course and category management, activities, question banks,
quizzes, enrolments, groups, cohorts, gradebook workflows, assignments, content
files, backups, audits, and other common course-building operations. REST, MCP,
and the optional public `moodlia` npm CLI use the same canonical operation names.

MoodlIA does not install remote plugin code and does not bypass Moodle's roles or
capabilities. Administrators explicitly grant `local/moodlia:useapi` to a
dedicated service user and should grant only the Moodle permissions needed for
the intended workflows.

The plugin is free and open source. It does not require a paid subscription, a
vendor-hosted service, vendor credentials, or an account with the plugin author.
Administrators choose and control any REST, MCP, CLI, or AI client that connects
to their own Moodle site.

## Privacy Summary

MoodlIA creates no plugin-owned database tables and stores no personal data in
plugin-owned storage. It can expose or modify existing Moodle data only when the
authenticated token owner has the required Moodle capabilities. External clients
may receive existing Moodle personal data, so site administrators must assess
those clients, document their use, protect tokens, and apply least privilege.

## Installation Summary

Upload `local_moodlia-0.1.200.zip` through Moodle's plugin installer or extract
the `moodlia` directory into `<moodle-root>/local/`. Complete the Moodle upgrade,
enable web services, grant the MoodlIA capability to a dedicated service user,
and create a token for the included `MoodlIA` external service.

## Reviewer Test Plan

1. Install the ZIP on a clean Moodle 5.2 site with developer debugging enabled.
2. Confirm the plugin installs without creating plugin-owned database tables.
3. Create a dedicated service user and grant `local/moodlia:useapi` plus
   `moodle/course:view` at system level.
4. Add the user to the `MoodlIA` external service and create a REST token.
5. Call `local_moodlia_get_moodlia_status` and
   `local_moodlia_get_current_user` through Moodle REST.
6. POST `tools/list` to `/local/moodlia/mcp.php` using the same token as a
   bearer credential and the documented MCP headers.
7. Confirm a write operation fails until its corresponding Moodle capability is
   granted.
8. Confirm the plugin sends no request to a vendor-controlled service.

No demo credentials are required because MoodlIA has no external vendor service.

## Keywords

MCP, AI, automation, REST, CLI, course authoring, question bank, administration

## Required Human Account Steps

- Sign in to Moodle Marketplace with the maintainer account.
- Confirm that `local_moodlia` is available and register the listing.
- Enter the listing copy above.
- Upload `empaquetado/local_moodlia-0.1.200.zip`.
- Add the maintainer profile and support contact required by Marketplace.
- Accept the Marketplace provider terms and submit the listing for review.

These account and legal-consent actions are intentionally not automated.
