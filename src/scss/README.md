# src/scss directory

This is a npm workspace to build the themes using [dart-css](https://github.com/sass/dart-sass) .  It builds from AND into the [themes/](../../themes/README.md) directory at the root, for legacy reasons (avoid breaking the the theme system, especially in custom packages)

This not normally ran directly, it's normally run from the root as part of:

* npm run build
* npm run watch

When developing SCSS files, you are expected to run npm run watch at the root in a terminal.  After that, you can just edit .scss files, and the new css will be available a fraction of a second later.

For actual documentation on the theme system se the [README for the themes/ directory](../../themes/README.md)
