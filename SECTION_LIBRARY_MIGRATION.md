# Section Library Migration

## Agreed Decisions

- Numbered handles apply to:
  - fieldset handles
  - filenames
  - root CSS classes
- Fieldset titles stay human-friendly and can vary per project.
- Section bundles include all relevant files:
  - fieldset YAML
  - Antlers template
  - SCSS partial
  - optional JS module
  - optional form config / form blueprint
- JS should be modular and imported into `resources/js/site.js`.
- Non-builder blocks belong in the registry under `blocks`.

## Migration Order

1. Keep `resources/section-library.yaml` as the source of truth for planned handles, classes, files, and dependencies.
2. Modularize set-specific JS before renaming section files.
3. Rename one family at a time:
   - fieldset YAML
   - Antlers template
   - SCSS partial
   - root CSS classes
4. Update imports and registrations:
   - `resources/css/site.scss`
   - `resources/js/site.js`
   - `resources/blueprints/collections/pages/page.yaml`
5. Migrate existing content handles in:
   - `content/collections/pages`
   - any other collections using those sets
6. Run verification after each family:
   - `php artisan statamic:stache:warm`
   - `npm run build`

## Notes

- `cta_form` backend form files stay as:
  - `resources/forms/cta_form.yaml`
  - `resources/blueprints/forms/cta_form.yaml`
- `features` becomes the `icon_cards` family:
  - `features` -> `icon_cards_001`
- `cta` and `cta_form` remain separate families:
  - `cta_001`
  - `cta_form_001`

## Next Stage: Pattern Registry

The current Section Library should evolve into a broader pattern system.
Sections remain one pattern type, but headers, footers, and page layouts
follow the same GitHub-backed install/remove workflow.

### Core Principle

- Patterns are still discrete bundles stored in GitHub.
- Patterns can still be added to or removed from a project cleanly.
- The main difference between pattern types is how they are attached to the site.

### Pattern Types

- `sections`
  - Page-builder content modules used inside page content.
- `layout_patterns.headers`
  - Site-level header variants mounted into the layout shell.
- `layout_patterns.footers`
  - Site-level footer variants mounted into the layout shell.
- `page_layouts`
  - Collection and route templates such as blog index, blog show, category,
    case studies index, and case studies show.
- `blocks`
  - Non-builder content blocks embedded inside rich entry content.

### Shared Bundle Rules

All installable patterns should keep the same bundle mindset:

- template
- styles
- optional script
- optional blueprint / fieldset / globals config
- registry entry

Numbered handles continue to apply across all pattern types:

- `hero_001`
- `header_001`
- `footer_001`
- `blog_index_001`
- `case_studies_show_001`

### Attachment Strategy By Type

- `sections`
  - Added to builder blueprints and rendered through the page builder.
- `headers`
  - Registered as available site chrome and rendered from the main layout.
- `footers`
  - Registered as available site chrome and rendered from the main layout.
- `page_layouts`
  - Registered against specific collections / route types and rendered as
    collection or template-level views.
- `blocks`
  - Added to collection-specific block builders.

### Install And Activation Rules

- Installing a pattern should always register it and copy its files into the project.
- Installing a first header or footer may safely make it the default active variant.
- Installing an additional header or footer should make it selectable, not
  automatically replace the active one.
- Installing a page layout should register it as available, but activating it
  should be an explicit step unless no layout currently exists for that target.

### Override Model

Headers and footers should support a predictable override cascade:

1. Per-page override
2. Collection / template default
3. Global site default

This allows:

- a global site header/footer
- collection-specific defaults for blog, case studies, landing pages, etc.
- one-off page overrides when needed

### Proposed Future Registry Shape

This is the target shape for the registry once the broader pattern system is introduced.
The current `resources/section-library.yaml` can remain in place until the migration begins.

```yaml
sections:
  - handle: hero_001
    family: hero
    title: Hero
    root_class: hero-001
    files:
      fieldset: resources/fieldsets/hero_001.yaml
      template: resources/views/sets/hero_001.antlers.html
      styles: resources/css/sets/_hero_001.scss
      script: null
    usage:
      builder: page
      contexts:
        - pages
    dependencies:
      blueprints:
        - resources/blueprints/collections/pages/page.yaml
      content:
        - content/collections/pages
      forms: []
    status: stable

layout_patterns:
  headers:
    - handle: header_001
      family: header
      title: Header
      root_class: site-header-001
      files:
        blueprint: resources/blueprints/globals/header_001.yaml
        template: resources/views/layout_patterns/headers/header_001.antlers.html
        styles: resources/css/layout_patterns/_header_001.scss
        script: resources/js/layout_patterns/header_001.js
      usage:
        scope: site
        defaultable: true
        override_scope:
          - global
          - collection
          - page
      dependencies:
        globals:
          - resources/blueprints/globals/site_header.yaml
        navigation:
          - main
      status: stable

  footers:
    - handle: footer_001
      family: footer
      title: Footer
      root_class: site-footer-001
      files:
        blueprint: resources/blueprints/globals/footer_001.yaml
        template: resources/views/layout_patterns/footers/footer_001.antlers.html
        styles: resources/css/layout_patterns/_footer_001.scss
        script: null
      usage:
        scope: site
        defaultable: true
        override_scope:
          - global
          - collection
          - page
      dependencies:
        globals:
          - resources/blueprints/globals/site_footer.yaml
        navigation:
          - footer
      status: stable

page_layouts:
  - handle: blog_index_001
    family: blog_index
    title: Blog Index
    root_class: blog-index-001
    files:
      blueprint: resources/blueprints/collections/blog/index_001.yaml
      template: resources/views/page_layouts/blog/index_001.antlers.html
      styles: resources/css/page_layouts/_blog_index_001.scss
      script: null
    usage:
      collection: blog
      route_type: index
      selectable: false
      activatable: true
    dependencies:
      collections:
        - blog
      taxonomies:
        - categories
      sections:
        - featured_blog_posts_001
    status: stable

  - handle: case_studies_show_001
    family: case_studies_show
    title: Case Studies Show
    root_class: case-study-001
    files:
      blueprint: resources/blueprints/collections/case_studies/show_001.yaml
      template: resources/views/page_layouts/case_studies/show_001.antlers.html
      styles: resources/css/page_layouts/_case_studies_show_001.scss
      script: null
    usage:
      collection: case_studies
      route_type: show
      selectable: false
      activatable: true
    dependencies:
      collections:
        - case_studies
      blocks:
        - quote_001
    status: stable

blocks:
  - handle: quote_001
    family: quote
    title: Quote
    files:
      fieldset: resources/fieldsets/blocks/quote_001.yaml
      template: resources/views/blocks/quote_001.antlers.html
      styles: null
      script: null
    usage:
      builder: rich_content
      contexts:
        - blog
        - case_studies
    dependencies:
      blueprints:
        - resources/blueprints/collections/blog/blog.yaml
        - resources/blueprints/collections/case_studies/case_studies.yaml
    status: stable
```

### Recommended Migration Path

1. Keep the current registry structure live until headers / footers / page layouts are ready to migrate.
2. Add the first `header_001` and `footer_001` as installable layout patterns.
3. Move the inline footer in `resources/views/layout.antlers.html` into a footer pattern partial.
4. Introduce global settings for active header/footer selection.
5. Add per-collection and per-page override support.
6. Add the first page layout family, likely `blog_index_001` and `blog_show_001`.
7. Rename the registry only when it truly becomes broader than sections in day-to-day use.
