# temp/

You must run setup.sh to properly set the permissions for this directory

The temp dir is for re-generateable files that are NOT web accessible.  It should NOT be backed-up, and any code that depends on a file in it should tolerate it's files not being present, and regenerate them as needed.

Some things that write here.  If you know of additional files/directories, or which code create and user the paths below, please add them.

Directories:

* temp/cache
  * Mostly caches from various tiki php files
* temp/cache/container.php
  * Symfony dependency injection cache
* temp/composer/*
  * composer temporary files
* temp/cypth/*
* temp/mail_attachs/
* [temp/public/](./public/README.md)
* temp/templaces_c/*
  * Compiled smarty templates
* temp/inified-index/

Files:

* temp/composer.phar
* temp/mail_debug
  * from lib/mail/maillib.php
* temp/Mail_*.eml
* temp/phpunit.junit.xml
* tikihybrid3.log
