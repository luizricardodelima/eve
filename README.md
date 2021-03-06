# EVE
_Event management system_

## How to install
1. Upload all the files to the server. Set 777 permission to ./upload folder and its subfolders.
1. Find `evedbconfig.php` file (located on the base folder) and fill it in with correponding database information.
1. Access `[aplication_url]/setup` to test the database connection and to create the database structure.
1. Test the application.
1. When using this application in production, delete `/setup` folder for security reasons.

## Changelog

- 2018-03-14 **(usability)** Now using PHPX_XLSXwriter library instead of PHP Excel. Lighter code, faster deploys.
- 2018-03-14 **(usability)** User forms are not lockable anymore. In the beggining user forms could be locked after payments and afer the explicit command of the user, but this does not make sense (with the option of multiple payments and with the future feature of many events in one system)
- 2018-03-09 **(feature)** Message sender which uses the system capabilities to send bulk emails to the users.
- 2021-03-08 **(feature)** Remodeled Certifications area for administrators. The certification attribuition page nows functions both as listing and attribuition page, and it is more simple and intuitive. Certification attribuition now works with ajax, so, it is one http request for each attribuition, avoiding timeout problems when lots of attribuitions were asked in one request (and lots of e-mails being sent). The locking feature of crtifications has been removed, since it has proven to be useless.
- 2021-03-04 **(feature)** Payment plugins can now be activated and deactivated. It open rooms for new payment plugins to be developed and only the desired ones being available to the user.
- 2021-03-02 **(feature)** Better styling for pages and a new image manager for certification backgrounds and pages.
- 2021-01-08 **(feature)** Font selection on certification models.
- 2021-01-08 **(bug fix)** Certifications bug fixes on "exotic" characters which made all the text disappear and other security and code enhancements.
- 2020-12-16 **(usability)** Now using the flags from the project RegionFlags (https://github.com/google/region-flags)
- 2020-12-16 **(bug fix)** Now the sender email is not automatically copied from "username" from e-mail configuration, since it is explicity defined (see below).
- 2020-12-16 **(feature)** "fromname" e-mail configuration was replaced by "sendername" and "senderemail". PHPMailer was updated to the newest version so far (6.2.0)
- 2020-10-04 **(feature)** Payment groups. Several different payments separated by groups.
- 2020-09-30 **(usability)** Password change is now translatable 
- 2020-07-15 **(feature)** New Payment model with configurable options and simplified payment plugins.
- 2020-07-15 **(usability)** The entities Paymenttype and (user)category were removed. The removal of this rarely used elements will make room for more powerful relations between entities.
- 2020-06-03 **(feature)** Masks can be applied to user custom text fields now 
- 2020-05-28 **(bug fix)** Bug fixes for increased compatibility with SQL Strict mode - text fields on database are null by default
- 2020-05-28 **(security)** All database operations on settings screens now use prepared statements
- 2020-05-28 **(usability)** Better look and feel on settings screens
- 2020-05-27 **(feature)** Send e-mails on submission create, delete and update operations
- 2020-05-26 **(bug fix)** Now payments screen display table with 100% of width on summary mode
- 2020-05-26 **(usability)** Better error handling on certification error text. Certification error text is defined by a json structure, and the parser now displays messages in case of errors, making it easier to identify where the error is.
- 2020-05-24 **(usability)** New setup screen. It's much more modern and dynamic. Initial database settings are now stored in json files, with a separate file for custom deployment values.
- 2020-05-23 **(usability)** Removed alternate e-mail text (as an alternative of html e-mails) for all e-mails. Some settings screens are now simpler because of this.
- 2020-05-13 **(usability)** `readme.txt` was renamed to `README.md` and its contents were formatted to Markdown format
- 2020-05-13 **(feature)** Now this project is on github! https://github.com/luizricardodelima/eve/
- 2020-05-12 **(feature)** The custom field code used in submissions was refactored and now it's a standalone project. Lots of the bugs were fixed and it handles much better with validation. The project has his own page on GitHub https://github.com/luizricardodelima/dynamicform/
- 2020-04-30 **(feature)** Removed "Alternative email" from user data for being too specific. Now userdata has 5 custom text fields.
- 2020-04-28 **(feature)** Included a third gender: "Rather not say"
- 2020-04-27 **(usability)** Minor code fixes that remove warnings on PHP 7 when system tries to read an unset variable
- 2019-03-23 **(bug fix)** Code fix for increased compatibility - All `<?` occurrences were replaced by `<?php`
- 2019-03-23  **(usability)** Changes on the initial user settings for a fresh install. User category is not initially mandatory, fewer visible and mandatory fields by default. Easier for quick testing.
- 2018-02-22 **(security)** Database settings are not written in an .ini file anymore.
- 2018-02-22 **(feature)** Automatic database creation screen.
- 2018-02-22 **(feature)** This readme.txt file with some basic information.
