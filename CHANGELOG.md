# 1.1.0 - (27-08-2026)

## Changed
- Compatibility with UnoPim v3.0.x, on PHP `^8.4.1` and Laravel `^13.0`.
- Credential and mapping screens submit over AJAX and use the core form component, with clickable datagrid rows.
- Connector export filters render through the core view hook on both the create and the edit screen.

## Added
- Locale files for all 32 supported languages.

## Fixed
- Exporters no longer fatal at class load on UnoPim 3.0 subclass signature changes.
- Two-level variant groups export as the flat variants Bagisto expects, instead of leaving configurables with no variants.
- Product types Bagisto cannot represent are reported as skipped with a reason instead of killing the batch.
- Missing export filter and attribute keys no longer abort an export batch.
- Categories with an unusable locale mapping are skipped with a reason instead of failing the batch.
- Required Bagisto fields are filled and incomplete products are skipped individually rather than failing the whole batch.
- Duplicate-rejected records count as updated, and `store_info` no longer accumulates duplicate channel mappings.
- Filter option endpoints return `page` and `lastPage` so the async select can load more.
- The connector filter card no longer repeats the media and SKU fields core already renders.
- The connector filter card renders only for Bagisto export types, instead of also appearing on another connector's export and duplicating its credential and channel fields.
- Removed the credentials create route that pointed at a missing controller method.
- The parent-aware `visible_individually` rule stays authoritative over configured fixed values.
- A failed API call is recorded instead of being swallowed, so an export no longer reports success when nothing reached Bagisto.
- Attributes export in full when no attribute mapping is configured, instead of exporting nothing.
- A missing channel or locale mapping is reported with a reason instead of completing the attribute export with zero rows.
- Attribute family export no longer reuses the previous item's response after a failure and maps it to the wrong Bagisto id.
- Measurement attributes such as weight export as their numeric value instead of an array.
- Variants inherit required Bagisto fields from their parent, including through an intermediate variant group.
- A bulk batch that Bagisto partly accepted maps the queued products instead of counting the whole batch as skipped.
- Saving a credential no longer requires filterable attributes, which blocked saving the channel and locale mapping.
- Store Configuration keeps its values after a credential is saved.
- Saving a credential clears the cached export job filters, so a new mapping takes effect on the next run.

# 1.0.4 - (08-07-2026)

## Fixed
- API client: re-throw connection failures (timeout, DNS, refused) instead of swallowing them to `null`, which previously caused a fatal error when the caller read `->failed()` on a null response during exports.

# 1.0.3 - (29-06-2026)

## Added
- `bagisto:sample-config:generate` command to load connector sample data, with an optional `--file` to load it from a custom JSON path.

# 1.0.2 - (24-06-2026)

## Changed
- Compatibility with UnoPim v2.1.x.

## Fixed
- Category export: coerce `position` to an integer, fall back `display_mode` to `products` when there is no description, and auto-fill the required `attributes` field from the store's filterable attributes.
- Product export: cast `weight` to a string, guard `visible_individually` for variants, and make the created/skipped counts reflect what was actually built and sent.
- Configurable export: include a configurable's variants in the export so Bagisto can create and link them.
- Support selecting multiple families in the product export filter.
- Namespace the job-filter cache per entity to avoid a product/category cache collision.
- Log a warning when a product is skipped because no channel/locale mapping matched.

# 1.0.1 - (04-05-2026)

## Changed
- Compatibility with UnoPIM v2.0.0.

# 1.0.0 - "Here We Go" (08-11-2024)

## Features
- Export categories from Unopim as collections in Bagisto.
- Export attributes seamlessly from Unopim to Bagisto.
- Export families from Unopim to Bagisto.
- Export products from Unopim to Bagisto, including both simple and configurable products.
- Utilize a bulk API for faster product export.
- Sync product images and videos from Unopim to Bagisto.
- Fully compatible with AWS S3 for image storage and retrieval.
