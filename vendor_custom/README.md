For user installed tiki packages not managed by composer (https://packages.tiki.org/)

They are shown in 'Packages Custom' in Tiki Packages section tiki-admin.php?page=packages

The subfolders MUST be named tiki-pkg-nameofpackage for tiki to use them, any other will be ignored.

Used by lib/core/Tiki/Package/VendorHelper.php to find custom user packages or packages not bundled in tiki (not installed in vendor_bundled) for users that do not have shell access.

Since https://gitlab.com/tikiwiki/tiki/-/commit/5a501773f4eddb7b4417f0c6e9aefa27f84f3422 it support PSR4 autoloading of libs/ directories within extension packages that are pre-packaged in vendor_custom.

More details at:
 * https://doc.tiki.org/Packages 
 * https://dev.tiki.org/packages-tiki-org