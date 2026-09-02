# Connect ChatGPT or Claude to the MoodlIA MCP Server

This guide explains how to connect an MCP client after the MoodlIA Moodle
plugin has been installed and a dedicated service user has been enabled.

Complete the [web installation and user enablement
guide](web-installation.md#enable-a-user-to-use-moodlia) first. The examples
below use this endpoint:

```text
https://your-moodle.example/local/moodlia/mcp.php
```

Replace `your-moodle.example` with the real Moodle host. The MCP endpoint is
not Moodle's standard REST endpoint at `/webservice/rest/server.php`.

## Client Compatibility

MoodlIA currently authenticates MCP requests with a static Moodle REST token
in the `Authorization: Bearer ...` header. That authentication model determines
which clients can connect directly.

| Client | Direct connection | Recommended configuration |
| --- | --- | --- |
| ChatGPT desktop app, Codex CLI, or Codex IDE extension | Yes | Streamable HTTP with the token read from an environment variable |
| ChatGPT on the web | Not through the local Codex host configuration | Use a ChatGPT plugin or registered connection that exposes the server securely |
| Claude Code | Yes | Remote HTTP MCP server with an environment-backed `Authorization` header |
| Claude.ai or the Claude Desktop remote connector | Not with MoodlIA's current static bearer token | Use an OAuth-capable gateway, or use Claude Code for a direct connection |

Do not work around an authentication limitation by adding the token to the
URL, disabling authentication, or exposing an unauthenticated proxy.

## Prerequisites

Before configuring a client, confirm that:

1. Moodle web services and the REST protocol are enabled.
2. The dedicated service user is authorised for the **MoodlIA service**.
3. The user's system role allows `webservice/rest:use` and
   `local/moodlia:useapi`.
4. The user has the operation-specific capabilities needed in each target
   course, category, activity, or other Moodle context.
5. A current token has been created specifically for that user and service.
6. The Moodle site uses valid HTTPS.

Use a dedicated account instead of an administrator. Grant only the Moodle
capabilities required for the intended tasks. Separate tokens per client make
revocation and auditing easier.

## Keep the Token Outside Client Configuration

The examples use `MOODLE_REST_TOKEN`. Set it without typing the value into a
command that may be saved in shell history.

In PowerShell 7:

```powershell
$env:MOODLE_REST_TOKEN = Read-Host "Moodle REST token" -MaskInput
```

In Bash or Zsh:

```bash
read -rsp "Moodle REST token: " MOODLE_REST_TOKEN
export MOODLE_REST_TOKEN
printf '\n'
```

These commands set the variable only for the current shell and its child
processes. A desktop application launched elsewhere will not inherit it. For a
desktop application, configure a protected user-level environment variable or
start the application through a secret-injecting launcher, then fully restart
the application.

Never commit a token, paste it into a shared configuration file, send it in a
prompt, or include it in a screenshot.

## Configure ChatGPT Desktop or Codex

ChatGPT desktop, the Codex CLI, and the Codex IDE extension use the same MCP
host configuration. The CLI is the least error-prone way to add the server.

### Add the Server with the Codex CLI

In PowerShell:

```powershell
codex mcp add moodlia `
  --url https://your-moodle.example/local/moodlia/mcp.php `
  --bearer-token-env-var MOODLE_REST_TOKEN
```

In Bash or Zsh:

```bash
codex mcp add moodlia \
  --url https://your-moodle.example/local/moodlia/mcp.php \
  --bearer-token-env-var MOODLE_REST_TOKEN
```

The command stores the environment variable name, not the token itself.

### Configure the Server Manually

The default user configuration file is `~/.codex/config.toml`. A trusted
project can instead use `.codex/config.toml` inside the project.

```toml
[mcp_servers.moodlia]
url = "https://your-moodle.example/local/moodlia/mcp.php"
bearer_token_env_var = "MOODLE_REST_TOKEN"
default_tools_approval_mode = "writes"
```

The `writes` approval mode allows read-only operations without repeated
approval while keeping approval prompts for operations that change Moodle.

For an especially cautious first connection, temporarily expose only the
initial read-only tools:

```toml
[mcp_servers.moodlia]
url = "https://your-moodle.example/local/moodlia/mcp.php"
bearer_token_env_var = "MOODLE_REST_TOKEN"
default_tools_approval_mode = "writes"
enabled_tools = ["get_current_user", "get_moodlia_status", "get_courses"]
```

Remove or expand `enabled_tools` only after the identity and permissions have
been verified.

### Verify the ChatGPT or Codex Connection

1. Restart the desktop application or IDE after setting the environment
   variable and configuration.
2. Open **Settings → MCP Servers** and confirm that `moodlia` is enabled. The
   same server can also be added there as a **Streamable HTTP** server.
3. Enter `/mcp` in the composer and confirm that the server is connected and
   its tools are visible.
4. Run these read-only requests first:

   ```text
   Use MoodlIA get_current_user and report only the authenticated user's ID
   and username. Do not call any write tool.
   ```

   ```text
   Use MoodlIA get_moodlia_status, then list no more than 10 visible courses.
   Do not change Moodle.
   ```

5. Confirm that the returned identity is the dedicated service account, not
   the administrator who created the token.

The current host configuration and UI are documented in [OpenAI's Model
Context Protocol guide](https://learn.chatgpt.com/docs/extend/mcp).

### ChatGPT Web Limitation

The local `~/.codex/config.toml` file configures the desktop MCP host, Codex
CLI, and IDE extension. It does not add a server to an ordinary hosted ChatGPT
web conversation.

Hosted ChatGPT can receive remote MCP tools through plugins or registered
connections, but this repository does not currently include such a ChatGPT
plugin. Until one is deployed, use the desktop/Codex route above. A future web
integration must keep the Moodle token on a trusted server; it must never send
the token to browser code or embed it in a public plugin definition.

## Configure Claude Code

Claude Code can connect directly because it supports HTTP MCP servers with
custom headers and environment-variable expansion.

### Add the Server with the Claude CLI

In PowerShell:

```powershell
claude mcp add --transport http --scope user `
  --header 'Authorization: Bearer ${MOODLE_REST_TOKEN}' `
  moodlia https://your-moodle.example/local/moodlia/mcp.php
```

In Bash or Zsh:

```bash
claude mcp add --transport http --scope user \
  --header 'Authorization: Bearer ${MOODLE_REST_TOKEN}' \
  moodlia https://your-moodle.example/local/moodlia/mcp.php
```

Keep the single quotes around the header so the literal environment-variable
reference is saved instead of its value. The `user` scope makes the server
available to the current user across projects.

### Use a Project Configuration

For a project-scoped connection, create `.mcp.json` in the project root:

```json
{
  "mcpServers": {
    "moodlia": {
      "type": "http",
      "url": "https://your-moodle.example/local/moodlia/mcp.php",
      "headers": {
        "Authorization": "Bearer ${MOODLE_REST_TOKEN}"
      }
    }
  }
}
```

The placeholder can be committed when sharing a trusted project, but the real
token must remain outside the repository. Claude Code asks for approval before
using project-scoped servers from a new project.

### Verify the Claude Code Connection

From a shell that has `MOODLE_REST_TOKEN` set, run:

```bash
claude mcp list
claude mcp get moodlia
```

Then start Claude Code, enter `/mcp`, and confirm that `moodlia` is connected.
Use the same read-only identity and status prompts from the ChatGPT procedure
before allowing any write operation.

The available transports, scopes, headers, and verification commands are
documented in [Anthropic's Claude Code MCP
guide](https://code.claude.com/docs/en/mcp).

## Claude.ai and Claude Desktop Remote Connectors

Claude's hosted custom connectors currently support OAuth or unauthenticated
remote servers. They do not provide a field for MoodlIA's arbitrary static
`Authorization: Bearer ...` header. Therefore, entering the MoodlIA endpoint
directly in **Customize → Connectors** will not create a usable authenticated
connection.

Use Claude Code for the direct configuration above, or deploy an
OAuth-capable gateway in front of MoodlIA. The gateway must:

- Be publicly reachable over HTTPS from Anthropic's cloud.
- Authenticate every user with OAuth.
- Map each identity to appropriately restricted Moodle access.
- Keep Moodle tokens encrypted on the server side.
- Never share an administrator token across users.
- Preserve MoodlIA's request and response semantics without exposing secrets
  in URLs, logs, or client configuration.

After such a gateway exists, an individual Pro or Max user can open
**Customize → Connectors → Add custom connector**, enter the gateway URL and
OAuth client details, connect the account, and enable it for a conversation
through **+ → Connectors**. Team and Enterprise owners add the connector under
**Organization settings → Connectors** before members connect it.

See [Anthropic's remote custom connector
guide](https://support.claude.com/en/articles/11175166-get-started-with-custom-connectors-using-remote-mcp)
for the current plan availability, administration flow, and network
requirements.

## First Safe Tasks

Begin with operations that do not change Moodle:

1. Call `get_current_user` and verify the dedicated account.
2. Call `get_moodlia_status` and inspect the reported capabilities.
3. Call `get_courses` with a small limit and confirm the expected visibility.
4. Read one known course before attempting to edit it.

For the first write, name the exact course or activity, request only one
change, approve that call, and read the object again to verify the result.
Client approval prompts are an additional safeguard; Moodle permissions remain
the authoritative security boundary.

## Troubleshooting

| Symptom | Likely cause and action |
| --- | --- |
| The client cannot find the server | Confirm the exact `/local/moodlia/mcp.php` URL, valid HTTPS, DNS, and proxy routing. A successful page load in a browser is not an MCP protocol test. |
| The server returns `401` or reports an invalid token | Confirm that the client inherited `MOODLE_REST_TOKEN`, the token is current, and it belongs to the authorised MoodlIA service user. Fully restart a GUI client after changing its environment. |
| `get_current_user` works but another operation is denied | Authentication is working. Grant the operation-specific Moodle capability in the correct course, category, module, or system context. |
| Claude.ai or a Claude Desktop remote connector requests OAuth | This is the expected compatibility limitation. Use Claude Code or an OAuth-capable gateway. Do not put the Moodle token in the URL. |
| ChatGPT web does not show the server | Local Codex host configuration does not configure hosted web chats. Use ChatGPT desktop/Codex or deploy a suitable ChatGPT plugin connection. |
| A cloud connector times out while local clients work | Check public reachability, firewall rules, reverse proxies, TLS, and any IP allowlist. Cloud connectors do not originate from the user's browser or local computer. |
| Tools are listed but write calls fail | The least-privilege account may intentionally lack the required capability. Add only the specific permission required for the intended task. |
| The tool list is too large | Start with an `enabled_tools` allowlist in Codex. In Claude Code, rely on tool search and make prompts name the intended MoodlIA operation. |

## Rotate or Revoke Access

Create separate tokens for separate clients when practical. If a token may
have been exposed:

1. Revoke it in Moodle's **Manage tokens** administration page.
2. Create a replacement for the same dedicated user and MoodlIA service.
3. Update the protected environment variable or secret store.
4. Fully restart the MCP client.
5. Re-run `get_current_user` before any other operation.

Revoking a client token does not require disabling the service user or other
clients that use separate tokens.

## Official Client Documentation

Client interfaces and availability can change. These official sources were
checked on 3 September 2026:

- [OpenAI: Model Context Protocol](https://learn.chatgpt.com/docs/extend/mcp)
- [Anthropic: Get started with custom connectors using remote MCP](https://support.claude.com/en/articles/11175166-get-started-with-custom-connectors-using-remote-mcp)
- [Anthropic: Connect Claude Code to tools via MCP](https://code.claude.com/docs/en/mcp)
