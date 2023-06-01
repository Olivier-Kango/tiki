# vendor/ directory

For user installed composer packages installed from (<https://composer.tiki.org/>) through the tiki package admin interface.  It will write packages here and modify the root composer.json.  

They are shown in 'Packages' in Tiki Packages section tiki-admin.php?page=packages

Used by lib/core/Tiki/Package/VendorHelper.php to find packages not bundled in tiki (not installed in vendor_bundled).

They can be either generic composer packages or https://doc.tiki.org/Packages-that-extend-Tiki tiki extensions.

More details at:

* <https://doc.tiki.org/Packages>
* <https://doc.tiki.org/Packages-that-extend-Tiki>
* <https://dev.tiki.org/packages-tiki-org>
