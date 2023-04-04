# themes/templates/

Putting any file here which has the same name as a template file in the root templates directory will make this file be used by Tiki instead of the file in the root [templates/](../templates/templates) directory, but also instead of the currently loaded theme's template/ directory.

This allows you to customise base templates without conflicts with the files you get from Tiki on upgrades.  But you are far better off creating your own theme.

If you want to customize templates for an existing theme or theme option, do it in themes/themename/templates/ or themes/themename/options/optionname/templates/

After modifications in template files, you need to clear caches from Tiki or in command line:
php console.php cache:clear

No versionned files should be put here, so any user files survives upgrades.
