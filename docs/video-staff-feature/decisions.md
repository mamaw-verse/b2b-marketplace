# Decisions — Slugs, Product Video, Seller Staff

These decisions carry the proposed defaults in `requirements.md` §6 into implementation. They are scope decisions, not verification results; runtime-dependent checks remain in `tasks.md`.

| ID | Decision | Basis |
|---|---|---|
| D1 | Slugs are globally unique per table. | Preserves the existing `/products/{slug}` URL shape and centralized `route()` calls. |
| D2 | Sellers can edit product and shop slugs; previous slugs redirect through a redirect table. | Preserves stable links after edits. Shop slug editing remains owner-only. |
| D3 | Videos are capped at 50 MB and uploaded through Livewire; raise PHP and Livewire limits and throttle the temporary upload endpoint. | Simpler initial implementation; re-evaluate if upload scale requires direct-to-S3 multipart uploads. |
| D4 | Deliver video through a presigned S3/MinIO URL so storage handles byte ranges. | Local MinIO supports byte ranges (`206`) when the URL host matches the signed host. Laravel's `temporary_url` host rewrite currently yields `SignatureDoesNotMatch`; use endpoint-aware signing or a Range-aware proxy before enabling. Browser/device playback is still required. |
| D5 | Staff seat cap comes from config/environment, default 2. | Keeps the cap independent of subscription plans. |
| D6 | Staff may publish products. | Matches the proposed permission matrix. |
| D7 | Staff at suspended or unsubscribed shops are blocked under the same access rules as the owner. | Keeps staff access aligned with the existing shop access gate. |
