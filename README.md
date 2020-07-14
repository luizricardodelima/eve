# EVE
_Event management system_

## Installing
1. Upload this all the files to the server. Set 777 permission to ./upload folder and its subfolders.
1. Find `evedbconfig.php` file (located on the base folder) and fill it in with correponding database information.
1. Access `[aplication_url]/setup` to test the database connection and to create the database structure.
1. Test the application.
1. When using this application in production, delete `/setup` folder for security reasons.

## Changelog
- **(under development usability)** Change the flags for the flags from the project RegionFlags (https://github.com/google/region-flags)
- **(under development usability)** Changed the deprecated phpExcel library to PHP_XLSXWriter (https://github.com/mk-j/PHP_XLSXWriter). Although PHPExcel (Now PHPSpreadsheet) is good, this application perform basic exports and a simpler library will consume less resources and new deploys will be faster (PHPExcel has too many files).
- **(under development feature)** New Password Retrieval dynamics - An expirable acess is sent to users e-mail. After access this code, user is prompted to change his password.
- 2019-06-03 **(feature)** Masks can be applied to user custom text fields now 
- 2019-05-28 **(bug fix)** Bug fixes for increased compatibility with SQL Strict mode - text fields on database are null by default
- 2020-05-28 **(security)** All database operations on settings screens now use prepared statements
- 2020-05-28 **(usability)** Better look and feel on settings screens
- 2019-05-27 **(feature)** Send e-mails on submission create, delete and update operations
- 2019-05-26 **(bug fix)** Now payments screen display table with 100% of width on summary mode
- 2020-05-26 **(usability)** Better error handling on certification error text. Certification error text is defined by a json structure, and the parser now displays messages in case of errors, making it easier to identify where the error is.
- 2020-05-24 **(usability)** New setup screen. It's much more modern and dynamic. Initial database settings are now stored in json files, with a separate file for custom deployment values.
- 2020-05-23 **(usability)** Removed alternate e-mail text (as an alternative of html e-mails) for all e-mails. Some settings screens are now simpler because of this.
- 2020-05-13 **(usability)** `readme.txt` was renamed to `README.md` and its contents were formatted to Markdown format
- 2020-05-13 **(feature)** Now this project is on github! https://github.com/luizricardodelima/eve/
- 2020-05-12 **(feature)** The custom field code used in submissions was refactored and now it's a standalone project. Lots of the bugs were fixed and id handles much better with validation. The project has his own page on GitHub https://github.com/luizricardodelima/dynamicform/
- 2020-04-30 **(feature)** Removed "Alternative email" from user data for being too specific. Now userdata has 5 custom text fields.
- 2020-04-28 **(feature)** Included a third gender: "Rather not say"
- 2020-04-27 **(usability)** Minor code fixes that remove warnings on PHP 7 when system tries to read an unset variable
- 2019-03-23 **(bug fix)** Code fix for increased compatibility - All `<?` occurrences were replaced by `<?php`
- 2019-03-23  **(usability)** Changes on the initial user settings for a fresh install. User category is not initially mandatory, fewer visible and mandatory fields by default. Easier for quick testing.
- 2018-02-22 **(security)** Database settings are not written in an .ini file anymore.
- 2018-02-22 **(feature)** Automatic database creation screen.
- 2018-02-22 **(feature)** This readme.txt file with some basic information.
