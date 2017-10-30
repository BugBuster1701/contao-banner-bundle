# Upgrade Notes

These upgrade hints are for the change from Contao 3 to Contao 4 when using the extension "Banner", new: "Contao-Banner-Bundle".

1. Backward incompatible changes
2. Database changes
3. Upgrade from Contao 3.5.x with "Banner" to Contao 4 with "Contao Banner Bundle"


## Backward incompatible changes

No support for Flash banner anymore and therefore no files with extension *swf* or *swc*.


## Database changes

In the tl_banner_category table, there were the following changes, but they only affect in the debug mode:

### New column

* banner_expert_debug_all

### Remove columns

* banner_expert_debug_tag
* banner_expert_debug_helper
* banner_expert_debug_image
* banner_expert_debug_referrer


## Upgrade from Contao 3.5.x with "Banner" to Contao 4 with "Contao Banner Bundle"

There are several ways to upgrade from Contao Contao 3 to 4. Here is *one* way as an example to preserve the data from the extension "Banner".

* Contao 4 is installed and gets a complete copy of the Contao 3 database when you enter it in the Install Tool.
* In the Install Tool, tables and fields are offered for deletion and new creation. In doing so:
  * do **not** **delete** tables that start with *tl_banner*. 
  * do **not** **delete** columns in the table *tl_module* that start with *banner_*

After migrating from Contao 3 to 4 through the Install Tool (for Contao itself), the installation of Contao-Banner-Bundle (bugbuster/contao-banner-bundle) can begin.
Installation instructions see: [INSTALLATION_EN.md](INSTALLATION_EN.md)

When the Install Tool is called, it is offered to make the changes as described in the section [Database changes] (UPGRADE_	EN.md#database-changes).
This can be done without hesitation, since these fields as mentioned only affect settings for the debug mode.


