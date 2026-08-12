# Security Policy

## Supported Version

Security fixes are provided for the latest published MoodlIA release. The
current release supports Moodle 5.2.

## Reporting A Vulnerability

Do not disclose suspected vulnerabilities in a public issue.

Before the first public Marketplace release, enable GitHub private vulnerability
reporting for this repository. Once enabled, use:

https://github.com/gafapa/moodle-local_moodlia/security/advisories/new

Include the affected version, Moodle version, required permissions, a minimal
reproduction, and the likely impact. Do not include live access tokens,
credentials, personal data, or production-site URLs.

If private reporting is not yet available, contact the maintainer through the
GitHub profile and request a private reporting channel without disclosing the
issue details publicly.

You should receive an initial acknowledgement within seven days. Confirmed
issues will be coordinated privately until a fix and release guidance are
available.

## Deployment Guidance

- Use a dedicated Moodle service account for automation.
- Grant `local/moodlia:useapi` explicitly and only to trusted service users.
- Grant only the Moodle capabilities required by the intended operations.
- Keep `local/moodlia:manageplugins` separate from normal course automation.
- Use HTTPS and protect REST tokens as credentials.
- Rotate any token exposed to an untrusted client or log.
