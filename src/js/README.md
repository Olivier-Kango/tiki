# src/js directory

This is where all the javascript source code will eventually live, and where the [Vite](https://vitejs.dev/) build system lives.  It builds from here and into the [public/generated/js/](../../public/generated/README.md) repository at the root.

It's also involved in [src/scss](../scss/README.md) to build the legacy themes directory.

Much of the current documentation (aside from the README files) is currently in comments in the [vite.config.mjs](./vite.config.mjs) file.

This is run from the root as part of:

* npm run build
* npm run watch

## Migrating dependencies

Javascript dependencies are still being moved from composer.  For examples of how to migrate (examples are up to date as of 2023-12-04 - benoitg):

1. Dependencies compiled-in.  
    * Ideal from a developer experience, simplest by far, but implies the javascript **using** them is already migrated in src/js.  
    * Applies to specific dependencies used from specific systems (possibly more than one), that will benefit from tree-shaking.
    * Example: see how @fullcalendar is used, compiled into tiki-calendar.js and wikiplugin-trackercalendar.js
1. Common, large dependencies used a lot:  Make them available in common-externals, AND define them as externals in vite.config.js so they are not compiled in.  
    * Two variants:  as ESM module if available, or as normal scripts.
    * Js code in src/js and legacy js code can share them.
    * Example: See how vue.js is used
1. Include dependencies as native ESM modules, adding them to the importmap.
    * Requires the dependency to be distributed as a ESM module
    * Easy to maintain
    * Example: See how bootstrap's javascript is used.
1. Dependencies included as "normal" js files, like we used to do in composer:  This is the most direct migration path (technically), but (usually) the hardest to maintain.
    * Example:  See how jquery-ui is used.
