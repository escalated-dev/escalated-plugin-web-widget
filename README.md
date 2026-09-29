# Escalated Plugin: Web Widget

**Status: unreleased experimental prototype. Not available for merchant or public use.**

This repository contains an incomplete plugin implementation. The version in
`package.json` is a development version, not a claim of release readiness.
`private: true` prevents accidental npm publication while the plugin is incomplete.
There is no supported installation or embed snippet yet.

The built-in widget supplied by some Escalated backends is a separate feature.
Its availability and security guarantees must be checked for the selected backend.

## Known gaps

- `frontend/index.js` points to a JavaScript module that is not shipped.
- The advertised `widget.js` bundle and `WebWidgetConfigurator` component are absent.
- `allowed_origins` is read from settings but is not enforced. It provides no
  CORS protection in this prototype.
- The public endpoint, raw request/response and ticket-creation contracts need
  integration with the plugin bridge. Declared handlers are not working public APIs.
- The current rate limiter uses non-atomic storage and untrusted forwarded IP
  headers. A public embed key does not authenticate a recipient.
- Tenant routing, verified recipient access and end-to-end browser tests are missing.

The branding, custom fields, endpoints and configurator declared in source describe
work in progress. They are not supported features.

## Release criteria

Before removing the unreleased status:

1. Ship and test the packed frontend, configurator and embeddable script.
2. Enforce an explicit Origin allowlist and preflight policy at the public boundary.
3. Add trusted, atomic rate limiting and server-side validation of every submission.
4. Create tickets through the authorized, tenant-aware backend service and verify
   recipient identity before granting access to correspondence.
5. Exercise the published artifact from a different browser origin, including
   denied origins, expired access, retries and disabled-widget behavior.
6. Document the supported backend and SDK/runtime versions, then publish a release.

## License

MIT - Copyright (c) Escalated.dev. See [LICENSE](LICENSE).
