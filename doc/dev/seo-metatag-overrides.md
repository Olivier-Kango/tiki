# Explicit SEO meta tag overrides

The wiki edit form exposes description, keywords and robots overrides. Values belong
to the wiki page name, so each translated wiki page can have its own values.
Empty fields inherit metadata rather than suppressing it.

Category administration exposes defaults and, when multilingual support is enabled,
optional values for each available language. For wiki pages, category metadata uses
the content language (`info.lang`), falling back to the current interface language
only when the content has no language. Other objects use the current language.
Language-specific attributes are supported for the default site language as well.

Resolution is independent for each field:

1. Object attribute, localized value then default value.
2. For robots only, the existing wiki custom robots value when its preference is enabled.
3. Categories in ascending category ID order, localized value then default value.
4. Existing generated metadata, left unchanged when there is no explicit override.

Attributes use `tiki.object.metatag.{description,keywords,robots}` and
`tiki.category.metatag.{description,keywords,robots}`, optionally followed by a
language code. Saving a blank value removes that attribute. The generic resolver
supports Tiki objects; the new object editing interface covers wiki pages.

## Rendering compatibility

The Smarty output filter changes named meta elements only inside the HTML head,
after metadata generation. This adapter works with both the legacy header template
and the HtmlHead renderer introduced by MR !9444, without editing either renderer.
It leaves script blocks (including Schema JSON-LD from MR !9664) unchanged. Robots
are also sent through HeaderLib's X-Robots-Tag API. Wiki edit/admin screens retain
their indexing restrictions. Metadata fields in the wiki editor are included before
the tabset, away from the Schema builder added by !9664.

## Validation

Run `vendor/bin/phpunit lib/test/Core/Seo/MetaTagOverrideTest.php` with Tiki's PHP
dependencies installed. The tests cover per-field inheritance, localization,
legacy robots, both quote/attribute-order styles of metadata, HTML escaping,
JSON-LD preservation and removing submitted attributes.

Application verification should include:

- Save and reopen category defaults and translated values.
- Assign a wiki page to that category; inspect its HTML head and X-Robots-Tag.
- Set page overrides, preview, save and reopen; clear them to restore inheritance.
- Use translated pages with different content languages and a different UI language.
- Assign multiple categories and verify the ascending-ID priority independently per field.
- Verify generated metadata with no overrides and the edit screen's noindex directives.
- With !9444/!9664 applied, verify that metadata occurs once and JSON-LD remains unchanged.

The overlapping files were checked using three-way merges against the downloaded
MR diffs. This does not guarantee compatibility with future revisions of those MRs.
