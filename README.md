HOW TO INSTALL
--------------
1. Upload this all the files to the server. Set 777 
   permission to ./upload folder and its subfolders.

2. Find evedbconfig.php file (located on the base folder)
   and fill it in with correponding database information.
   Be careful to not mess up the file's syntax.

3. Access [aplication_url]/setup to test the database
   connection and to create the database structure.

4. Test the application.

5. If everything works well, for security reasons,
   delete /setup folder.

CHANGELOG
---------

- FUTURE FEATURE             New Password Retrieval dynamics - An expirable acess is sent to users e-mail. After access this code, user is prompted to change his password.
- UNDER DEVELOPMENT BUG FIX	Bug fixes for increased compatibility with SQL Strict mode - text fields on database are null by default
- UNDER DEVELOPMENT FEATURE	Removed alternate e-mail text for verification e-mail (now it works only with HTML)
- 2020-05-12 FEATURE   The custom field code used in submissions was refactored and now it's a standalone project. Lots of the bugs were fixed and id handles much better with validation. It has his own page on GitHub https://github.com/luizricardodelima/dynamicform/
- 2020-04-30 FEATURE   Removed "Alternative email" from user data for being too specific. Now userdata has 5 custom text fields.
- 2020-04-28 FEATURE	Included a third gender: "Rather not say"
- 2020-04-27 USABILITY Minor code fixes that remove warnings on PHP 7 when system tries to read an unset variable
- 2019-03-23 BUG FIX	Code fix for increased compatibility - All "<?" occurrences were replaced by "<?php"
- 2019-03-23 USABILITY	Changes on the initial user settings for a fresh install. User category is not initially mandatory, fewer visible and mandatory fields by default. Easier for quick testing.
- 2018-02-22 SECURITY  Database settings are not written in an .ini file anymore.
- 2018-02-22 FEATURE 	Automatic database creation screen.
- 2018-02-22 FEATURE 	This readme.txt file with some basic information.
