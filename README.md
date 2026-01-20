# RadicalMart Fields: Standard

**RadicalMart Fields: Standard** is the core field-type plugin for **RadicalMart 3**.
It provides the default field types, filter UI layouts, and helper logic used by RadicalMart when working with product fields, filtering, and meta-product variability.

> Extension: `plg_radicalmart_fields_standard` (group: `radicalmart_fields`)

---

## What this plugin does

- Registers the **standard field types** available in RadicalMart.
- Supplies XML form definitions for field configuration.
- Provides layouts for:
  - displaying field values,
  - rendering filter controls,
  - rendering meta-product variability selectors.
- Normalises and de-duplicates **option values** (URL-safe aliases) when saving field options.
- Adds a CLI command to build **option → categories** indexes for faster filtering scenarios.

This plugin is intended to be enabled on any site using RadicalMart fields.

---

## Supported field types

The plugin implements these field types:

- **List**
- **Checkboxes**
- **Text**
- **Text area**
- **Editor**
- **Number**
- **Range**

---

## CLI command

The plugin registers a console command that performs a **mass scan** and creates indexes for field options.
It is designed for fields of type **List / Checkboxes** with filter display enabled.

Command name:

```bash
php cli/joomla.php radicalmart:fields:standard:update_options_categories_indexes
````

What it does:

* Finds Standard fields (`plugin = standard`) of type **list** or **checkboxes** with filtering enabled.
* Clears existing option category index data.
* Scans products in chunks and rebuilds the mapping of **option values → categories**.

Use it after large imports or when you need to rebuild filter indexes in bulk.

---

## Requirements

Validated by the installer script:

* **Joomla:** 4.2+
* **PHP:** 7.4+
* **RadicalMart:** 3.0.0+

---

## Installation

1. Install the ZIP via Joomla:
   **System → Install → Extensions**
2. Enable the plugin:
   **System → Manage → Plugins → “RadicalMart Fields: Standard”**

> On a fresh install it can be enabled automatically by the installer.

---

## Where to look in the codebase

* `src/Extension/Standard.php` — event subscriber and the main integration layer.
* `forms/products/*.xml` — XML definitions for field settings.
* `layouts/field/*` — filter / variability / subform rendering.
* `src/Console/UpdateOptionsCategoriesIndexesCommand.php` — CLI index builder.

---

## License

GNU/GPL v2 or later.

---

## Support / Updates

The extension includes an update server entry in its manifest.
