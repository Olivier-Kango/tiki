These are tiki's Smarty Templates.  https://doc.tiki.org/Smarty-Templates

The structure mostly mirrors the structure of the root php files.  So every tiki-something.php corresponds to templates/tiki-something.tpl.  Files that don't start with tiki- are usually included in others.

While in the decades of tiki development using templates for presentation has fallen out of favor (presentation is moving to CSS and JS), it is still extensively used and usefull for:

* Changing markup based on permissions or configuration
* An extension point for themes, that can override these files by https://themes.tiki.org/tiki-index.php?page_ref_id=5