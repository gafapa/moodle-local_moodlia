# MCP testing

MCP is verified separately from REST and WebMCP. Successful REST calls do not
establish MCP tool discovery, protocol negotiation or JSON-RPC compatibility.

## Fast checks

`npm run check` and `npm run test:static` include:

- Actual PHP catalog serialization for every declared MCP tool, with explicit
  regressions for empty object maps and argument normalization.
- Ajv JSON Schema 2020-12 compilation for all 250 schemas, representative valid
  arguments and rejection of arrays, unknown properties and missing required
  arguments. Mutation cases reintroduce the original empty-array defect and
  malformed nested schemas to ensure the validator rejects them.
- An ephemeral loopback PHP server running the production `mcp.php`, manifest,
  argument adapter and version metadata. Only Moodle bootstrap and the REST
  client are stubbed. No installed Moodle or real token is needed.
- The official TypeScript SDK v1 HTTP client exercises initialization,
  initialized notification, full tool discovery, ping and both no-argument tool
  calls. Structured results must match their JSON text representation.
- Independent HTTP requests exercise all three supported legacy versions and
  the plugin's stateless 2026-07-28 mode, IDs, bodyless notifications, discovery,
  method/name routing, metadata, content negotiation and error responses.
- Missing/invalid tokens, cross-origin requests, unsupported methods and
  versions, malformed JSON, unknown tools, missing names and scalar arguments
  must fail without returning a result or exposing the supplied token.

Use `npm run test:mcp:http` to run only the HTTP fixture. PHP must be on PATH or
selected with `MOODLE_SERVER_PHP`. SDK and validator packages are development
dependencies; they are not included in the installable Moodle plugin.

These checks found another interoperability defect: legacy `ping` returned an
empty JSON array. It now returns an empty JSON object, accepted by the SDK's
response validation. Modern ping results retain their existing metadata.

## Native Moodle qualification

`npm run test:mcp:moodle` requires `MOODLIA_COMPATIBILITY_SITE=1`,
`MOODLE_BASE_URL` pointing to loopback and `MOODLE_REST_TOKEN` for the disposable
site prepared by `tools/compatibility-setup.php`. It deliberately refuses remote
hosts. It runs the same HTTP/SDK checks against the actual endpoint and creates
a temporary hidden course and Page using object-typed options, changes visibility
through `update_module`, updates Page content through `update_page`, reads the
stored content and visibility back and deletes the course in cleanup.
Missing required operation arguments must also be rejected by Moodle.

The Moodle 4.5–5.3-beta CI matrix runs this after independent REST qualification
and retains `compatibility-mcp.json`. The temporary PHP server uses multiple
workers because the MCP endpoint calls REST on the same site; a single worker
would block its own request. The explicitly opted-in fixture setup permits
loopback callbacks and its HTTP port in that disposable Moodle's outbound HTTP
policy while retaining the other private/cloud host blocks. This is not a
production configuration change. The runner stops that isolated server process group
on exit. No production Moodle or external AI provider is involved.

The fixture does not prove native Moodle authorization or business behavior.
Native matrix results are separate evidence. Neither layer establishes that
every hosted LLM or the issue reporter's private Claude Code environment works;
provider-specific qualification remains distinct.
