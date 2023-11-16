# src/js directory

This is where all the javascript source code will eventually live, and where the [Vite](https://vitejs.dev/) build system lives.  It builds from into the [public/generated/js/](../../public/generated/README.md) repository at the root, for legacy reasons (avoid breaking the the theme system, especially in custom packages)

Much of the current documentation (aside from the README files) is currently in comments in the [vite.config.mjs](./vite.config.mjs) file.

This is run from the root as part of:

* npm run build
* npm run watch

