# Install MoodlIA Through Moodle's Web Interface

This guide covers a complete browser-based installation of the MoodlIA Moodle
plugin. It is intended for Moodle administrators who can install plugins from
ZIP files but do not necessarily have shell access to the server.

The procedure was verified end to end on 2 September 2026 with the following
clean environment:

| Component | Verified version |
| --- | --- |
| Moodle | 5.2.2 (Build: 20260810) |
| PHP | 8.3.15 |
| MariaDB | 11.4.12 |
| MoodlIA | 0.1.200 (`2026082802`) |

The screenshots show that validation run. Labels can differ slightly in other
Moodle themes or language packs.

## Before You Start

Confirm all of the following:

- The site runs Moodle 5.2. MoodlIA 0.1.200 supports Moodle 5.2 only.
- You are signed in as a site administrator.
- You have a current Moodle code, database, and `moodledata` backup.
- The web server can write to the Moodle code directory. If it cannot, use the
  [server deployment procedure](install-release-guide.md#deploy-to-a-standard-moodle-server).
- The PHP and web-server upload limits are larger than the plugin archive.
- You have the release archive named `local_moodlia-<release>.zip`.

For a production site, schedule the installation during a maintenance window.
The database upgrade is short because MoodlIA creates no plugin-owned database
tables, but Moodle can temporarily redirect users to the upgrade screen.

## Obtain the ZIP Package

Use the ZIP attached to the intended MoodlIA release. If you are installing a
checked-out development version, build the same archive locally:

```bash
npm ci
npm run plugin:archive
```

The generated file is stored under `empaquetado/`, for example:

```text
empaquetado/local_moodlia-0.1.200.zip
```

Do not upload a generic GitHub source-code ZIP. The installable archive must
contain one top-level `moodlia/` directory, and `moodlia/version.php` must
declare the component `local_moodlia`.

## Install the Plugin

1. Open **Site administration**.
2. Select **Plugins**, then **Install plugins**.
3. In **Install plugin from ZIP file**, select **Choose a file...**.
4. In Moodle's file picker, select **Upload a file**, choose the MoodlIA ZIP,
   and select **Upload this file**.
5. Confirm that the selected filename is `local_moodlia-<release>.zip`, then
   select **Install plugin from the ZIP file**.

![MoodlIA ZIP selected in Moodle's plugin installer](images/web-installation/moodlia-plugin-zip-selected.png)

Moodle extracts the archive and validates its component metadata. The result
must identify `local_moodlia` and end with **Validation successful,
installation can continue**.

![Successful validation of local_moodlia](images/web-installation/moodlia-plugin-validation-success.png)

The `MATURITY_BETA` message is informational for release 0.1.200. Stop if the
page reports any other warning or error, such as an unsupported Moodle version,
an invalid component name, or an unwritable destination directory.

6. Select **Continue**.
7. Review Moodle's server checks. Continue only when Moodle reports that the
   server environment meets all minimum requirements.
8. On **Plugins check**, confirm the following row before changing the
   database:

   - Plugin name: `MoodlIA`
   - Directory: `/local/moodlia`
   - New version: the version expected from the release
   - Required Moodle range: `502 - 502` for release 0.1.200
   - Status: **Additional** and **To be installed**

![MoodlIA ready to be installed on the Plugins check page](images/web-installation/moodlia-plugin-check.png)

9. Select **Upgrade Moodle database now**.
10. Wait for the `local_moodlia` upgrade result. Do not reload the page while
    the upgrade is running.

![Successful local_moodlia database upgrade](images/web-installation/moodlia-plugin-upgrade-success.png)

11. Select **Continue** to return to Moodle administration.

## Verify the Installation

Open **Site administration → Plugins → Plugins overview** and locate **Local
plugins**. The MoodlIA row must show:

- Name: `MoodlIA`
- Component: `local_moodlia`
- Release: `0.1.200`, or the release you installed
- Version: `2026082802`, or the version declared by that release
- Source: **Additional**

![MoodlIA listed as an installed local plugin](images/web-installation/moodlia-plugin-installed.png)

The MCP endpoint is now present at:

```text
https://your-moodle.example/local/moodlia/mcp.php
```

An unauthenticated browser request is not a functional MCP test. The endpoint
requires the supported HTTP method, MCP headers, and a Moodle REST token
authorised for the MoodlIA service.

## Configure Access After Installation

Installing the plugin does not grant an external client access to Moodle.
Complete the following separately:

1. Enable Moodle web services under **Site administration → General → Advanced
   features** if they are disabled.
2. Open **Site administration → Server → Web services → External services** and
   confirm that **MoodlIA service** is enabled. Its short name is
   `local_moodlia` and it is restricted to authorised users.
3. Create a dedicated Moodle user for automation.
4. Assign `local/moodlia:useapi` at system context and only the Moodle
   capabilities required by that user's intended operations.
5. Add the user to **MoodlIA service**.
6. Create a REST token for that user and service under **Manage tokens**.
7. Store the token outside source control and test a read-only operation before
   permitting write operations.

Do not grant `local/moodlia:manageplugins` unless the service user explicitly
needs plugin inventory or enabled-state operations. Never reuse a full site
administrator token for general automation.

## Common Problems

### The Install Plugins Page Is Unavailable

The site may disable web-based code deployment, or the web server may not be
allowed to write to the Moodle code directory. Install the `moodlia` directory
under `<moodle-root>/local/moodlia` and run Moodle's CLI upgrade instead.

### Moodle Rejects the ZIP Structure

Build the archive with `npm run plugin:archive`. A valid package has one
top-level `moodlia/` directory. Renaming a repository source archive is not
equivalent to building the plugin package.

### The Archive Exceeds the Upload Limit

Increase the applicable PHP and reverse-proxy limits, or use server deployment.
Check `upload_max_filesize`, `post_max_size`, and the fronting web server's body
size limit.

### Moodle Reports an Unsupported Version

Match the target Moodle release against `version.php`. MoodlIA 0.1.200 declares
`$plugin->supported = [502, 502]` and must not be installed on a different
Moodle series without a compatible plugin release.

### The Upgrade Was Interrupted

Return to `/admin/index.php` as an administrator and inspect the current state
before retrying. Do not upload the ZIP repeatedly after a timeout; the file
installation may already have completed.

## Updating an Existing Installation

Upload the newer `local_moodlia-<release>.zip` through the same page. Moodle
will identify the existing component and present it as an upgrade. Back up the
site first, verify both current and target versions on **Plugins check**, and do
not attempt an arbitrary downgrade through the web installer.
