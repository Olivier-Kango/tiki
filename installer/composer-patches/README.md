# composer-patches

A patch file serves as a record of alterations made to a source code, facilitating their application through patching tools. Within our workflow, we employ [cweagans/composer-patches](https://packagist.org/packages/cweagans/composer-patches) for this purpose. This mechanism proves invaluable when necessitating fixes or enhancements that haven't yet been formally incorporated into a package.

## How to Utilize

1. **Incorporate Your Patch File**: Place your patch file within this designated folder.
   
2. **Update `composer-patches.json`**:
   - Identify the targeted package for patch application.
   - Utilize the description of your patch as the corresponding key.
   - Specify the relative path to your patch file as the associated value.

## Example

```json
{
    "vendor/package": {
        "Change xyz to zyx to match format (example)": "../installer/composer-patches/vendor_package_change_xyz_to_zyx_to_match_format.patch"
    }
}
```

> **Note:** Ensure the patch file path is relative, as if the JSON data were referenced from the file [vendor_bundled/composer.json](./vendor_bundled/composer.json).

## Apply Your Patch File

Finally, to ensure that you're using your changes, execute the composer install process via `sh setup.sh`.