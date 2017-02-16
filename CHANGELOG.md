# [Program O](http://www.program-o.com)

##CHANGELOG info:

- Version: 2.6.4
- Authors: Elizabeth Perreau and Dave Morton
- Date: June 11th 2016


##Version History:

2.6.4   Reimplemented Custom Session Handling, Bug Fixes

1. After months of researching, pulling hair out and banging heads against computer monitors,
   we were finally able to work out a better implementation of custom session handling that is
   both functional and secure. This time it should stick! __NOTE!__ This will require a "clean"
   install of Program O, which is why we have a new version number.

2. We also added in the option for botmasters to assign a file name other than 'admin_added.aiml'
   to new AIML categories in the Teach page of the admin. The default is still 'admin_added.aiml',
   but now it's not hard-coded in.

3. The list of bugs fixed is too large to list here, but suffice it to say that most of the bugs
   listed in the issues section in 2016 have been found and either killed or at least rendered
   ineffective. There are still some things to address, but not as many as before.


2.6.3   New Features!

1. We expanded upon the success of the jQuery-based Search/Edit page and incorporated the new
   design into the srai_lookup page. Now botmasters can add, edit and delete entries in the
   srai_lookup table to better help with performance of their chatbots.

2. Improved the algorithm that initially populates the srai_lookup table so that simple wildcards
   are included in the search. This added more than 30% additional entries to the table. Between
   this improvement and the ability to edit directly what is already in the table, botmasters can
   expect to see as much as a 65-70% improvement in bot response times.

3. We had to fall back on not checking for "valid text" (e.g. just letters, numbers, spaces and
   the two wildcards) within `<pattern>` and `<that>` tags due to a conflict with PHP's XML
   parsing and validating functions not being compatible with XML Schema 1.1 specifications, so
   it's now up to the botmaster to make sure that no punctuation is included within these AIML
   tags.

4. Corrected some minor visual bugs in the CSS for both the Search/Edit page and the srai_lookup
   page that was causing some slight alignment issues.

5. Resigned the parse_learn_tag() and parse_eval_tag() functions to make them work properly, based
   on papers at [alicebot.org](http://www.alicebot.org/) that describe how they are implemented.

6. Altered the first entry of all debugging modes to include relevant system specs (Program O
   version, Server software type and version, OS and OS version, MySQL version and whether
   Mulit-byte functions are enabled), making it easier to troubleshoot problems should any occur.

7. Added a script to the  debug viewer that allows for primitive "profiling of the selected debug
   file. this profiler  looks through all of the debug file's entries and gathers relevant
   information, then orders that data by elapsed time, listing the longest elapsed times first.

2.6.2   Add Option to Update From DEV Branch, Other Features/Bug Fixes

1. Added an option in the Select Bot panel to allow checking for version updates from either the
   MASTER or DEV branches, giving botmasters the ability to more readily use new features/fixes
   that haven't yet made it into the stable release.
2. Added links to the admin nav panel to the debug and logs folders, making it easier to access
   both debug files and error logs, directly from the admin page. These folders require admin
   level access to be able to view the files themselves, so the information in these files is
   still safe from unauthorized access.
3. Transitioned from YUI to jQuery for the Search/Edit AIML page, both for consistency, and to
   correct an intermittent bug (issue #140) that prevents some edited AIML categories from being
   saved.

2.6.1   AIML Validation Change

1. Changed the validation method in admin/validateAIML.php from using XML DocType Declaration (DTD)
    to XML Schema (XSD). This allows the validation script to check for improper characters in both
    `<pattern>` and `<that>` tags, which has been a real issue in the past
2. Incorporated the same validation method into the AIML upload script, so that you are not now
    required to validate the files before uploading them. You now cannot upload invalid AIML files.

2.6.0   Restructure the aiml_userdefined Table, Other Fixes and Changes

1. Changed the structure of the aiml_userdefined table to correct a bug where the user ID didn't match
    the current user, even though the conversation ID was the same
2. Created an XML Schema file for AIML in preparation for later changes to be implemented with the AIML
    validation script
3. Improved the internal documentation for the file PDO_functions.php as part of an ongoing effort to
    update and improve all internal documentation

**While this version includes changes to the structure of the database, a patch file is included that will make the
necessary changes so that a clean install should not be required. Please note, however, that it will empty all entries
in the affected table (aiml_userdefined). This is a necessary step**

2.5.4   Multiple Bug Fixes and Changes

1. Fixed a bug in the function that parses the `<condition>` tag that failed to properly normalize multi-byte strings. Thanks to @LorenzCK for this fix!
2. Fixed a bug in the function that scores AIML categories that use the `<that>` tag that was allowing incorrect categories to be selected.
3. Corrected several typos and uninitiated variables that were silently creating errors.
4. Updated version information in 62 files throughout the script.

2.4.9 - 2.5.3 - Changes "slipped through the cracks" - oops?

2.4.8   Housekeeping

1. Removed dead links from the admin area and obsolete information pages
2. Merge pull requests including topic upload fix and an expansion to the make_like_pattern()


2.4.7   Security fixes

1. Added XSS and CSRF attack prevention to the code-base
    **Please note that this bug fix affects the config file, so will require a reinstall of the script**

2.4.6   Bug fixes, refined the admin login routines

1. Added code to the install script that detects the current session path, creating a new,
    uniquely named folder for the session files if one does not currently exist.
2. Fixed a bug with the Search/Edit AIML admin page that prevented any AIML categories from
    being displayed due to the wrong session variables being loaded.
3. Fixed a bug with the RSS feed display where garbage was sometimes displayed if the forum
    pages were down.
4. Fixed another bug with the RSS feed display where the admin script would time out if the
    forum pages were not responding.
5. Cleaned up some of the code where needed, standardizing the remaining PHP comment headers
    to make them all the same.
6. Added getbots.php to the project, which returns a JSON encoded array of ID/name pairs for
    all active chatbots, enabling botmasters to build selectboxes or radio button groups of
    chatbots. No more hard-coding bot IDs or bot names! there is a new example GUI in the
    jQuery folder (multibot_gui_with_chatlog.php) that can be used to learn how to implement
    this wih a selectbox in an AJAX driven chatbot interface page.

2.4.5   Relocated the session save path

1. Moved the session save path from the server default to a new folder. this increases
    security for those on shared hosts, and ensures that there are no permissions problems
    (or problems with the session save path not being set in the PHP config) for those who
    have been experiencing login problems due to sessions not being saved.

2.4.4   Multiple bug fixes, consolidated SELECT type DB queries into calls to two functions

1. Fixed a bug in the core code that allowed fatal errors to be generated if the variable
    bot_id is empty or not set
2. Fixed a bug in the new Search/Edit AIML admin page that caused only the default chatbot's
    AIML to be displayed, rather than the currently selected chatbot.
3. Created 2 new PDO query functions (db_fetch() and db_fetchAll()) to handle retrieving all
    SELECT type queries, resulting in fewer lines of code to the tune of over 150
4. Fixed a security bug where users could directly access certain admin pages without actually
    being logged in.
5. Changed the download admin page to enable downloading multiple files as a zip arcvhive.

2.4.3   Multiple bug fixes, started adding internal documentation with descriptions

1. Fixed a bug in the XML GUI that was creating fatal errors when called from the admin page
2. Fixed bugs in the functions that parse SRAI, SR and CONDITION tags
3. Corrected a bug where certain admin pages didn't display if a new chatbot was created but not saved.
4. Began the process of adding internal documentation to the script, which will allow for later
    automatic generation of external documentation.
5. Refactored and/or removed some duplicate functions within the admin pages, to avoid conflicts
    with the generation of the above mentioned external documentation.

2.4.2   Added Local AIML Validation and SRAI Lookup Admin

1. Added a "local" AIML validator script, specifically designed to validate
    AIML files for Program O and Pandorabots.
2. Added a new page to the admin to work with the new SRAI lookup table. For
    now, the page only fills the lookup table with direct matches to existing
    SRAI calls within the AIML table, but soon it will also include functionality
    for searching the lookup table, finding "indirect match" patterns, and the
    ability to edit the templates for matched SRAI calls.
3. Several minor bug fixes, including one that limited the number of characters
    that the admin user name could have (without any warning of the limit). The
    new limit is 255 characters, instead of the previous 10.

2.4.1   Complete refactor for PDO support, added SRAI lookup

1. Refactored the code that deals with DB access to completely remove the
    last of the mysql_* functions, including "fallback support".
2. Added an additional table to the database to handle looking up previously
    used calls to <srai> tags. This will improve performance in the future,
    in that if an <srai> tag is stored in the lookup table, the script doesn't
    have to run through the entire AIML table to find it. If a suitable srai
    category is found that isn't already in the lookup table, it's added for
    future use. While the table is being added now, the feature is not yet
    enabled. We'll be enabling it in the near future, once further testing is
    completed.

2.4.0   Conditional PDO support added, major bug fixes

1. Added PDO support for PHP versions that support it (and have it enabled),
    with a fallback to the original MySQL functions if no PDO support is detected
2. Finally found the problem with template-side <that> tags not being displayed
    correctly. Special thanks to Tom (AKA Slow Putzo) for the assist with this!
3. Also fixed the long-standing bug with pattern-side <that> tags not being
    scored and chosen correctly. Once again, Thanks Tom! :D
4. Removed the DB stats page from the admin. It was more or less a failed
    experiment, and really needed to be removed.
5. Also removed the scripts to upgrade from version 2.0 to 2.3 - If you have any
    version of Program O that's older than 2.3.1, you need to do a fresh install
    and more or less start from scratch. Sorry, but that's just the way it is.
6. Other miscellaneous, minor bug fixes, mainly dealing with unnecessary error
    log entries.

2.3.1   Bug fixes, repaired missing table columns

1. Corrected a bug in the function parse_learn_tag that prevented new data from being
    inserted into the aiml_userdefined table
2. Added a column in the aiml_userdefined table that was missing, which was also
    preventing new data from being added.

2.3.0   Minor DB refactor, in preparation for version 2.5

1. removed the last of the PHP code columns from the database
2. added an unknown_user field to the bots table, allowing per-bot settings for what the
    will call someone they don't know
3. Altered the chatbot general config page in the admin to reflect the above changes
4. added a bot_id field and dropped a user_id field in the unknown_inputs table
5. dropped the user_id field from the undefined_defaults table, as it was not used
6. added a "memory bailout" feature to the function that returns potential AIML categories
    to prevent memory overflow errors
7. other miscellaneous minor bug fixes

2.2.2   Bug Fixes/versioning refactor

1. Fixed a bug that prevented proper implementation of template-side <that> tags
2. updated and corrected the script version in all files that contain version information
3. implemented functionality that sets the scripts internal version based on the contents
    of version.txt, rather than hard-coding it

2.2.1   Major bug fixes

1. Fixed a bug that was mangling Unicode characters while removing punctuation
2. Fixed a bug where the chatbot's debug level wasn't being used
3. re-wrote several functions to be more efficient


2.2.0   Foreign Langague Support

1. Program O now support foreign languages! YEEHAAAA
2. Tested against, Russian, Arabic, Turkish, French, Thai, Greek, Chinese
3. Converted all character coding both internal and external to UTF-8
4. Added the correct XML header to the display conversation functions for xml requests
5. Updated the XML example gui to use simpleXML to send requests to the bot


2.1.5   Bug fixes/merge to "Master" branch

1. Corrected a bug that improperly ordered the collections of words gathered in <pattern> and <that> tags
2. Corrected multiple minor bugs in the Download script that affected the use of said downloaded AIML
    files with Pandorabots.
3. Adjusted the function that checks GitHub for the current version of the script, based on changes they
    made to their API. Also added some error handling, in case GitHub can't be reached.
4. Modified some of the debugging output descriptors to avoid unnecessary and potentially confusing
    message duplication.
5. Corrected a bug in the main SQL query that caused certain valid AIML categories that use the <that> tag
    to be missed in the main search.
6. Added 'experimental' support for Pandorabots style <date> tags, adding functionality for both LOCALE
    and TIMEZONE attributes (support for these attributes was mentioned previously, but not added at that
    time).
7. Other minor cosmetic changes and typo corrections that didn't affect functionality.

2.1.4   Bug fixes & minor styling/feature changes

1. Fixed several bugs that arose from the database refactor.
2. Fixed the long-standing bug that caused the script to create a new user
    instead of just updating the current user when they typed in the phrase
    "clear properties".
3. Fixed bugs in both the word censor and spell checker addons that prevented
    botmasters from making changes to the DB.
4. Updated the spell checker addon to allow botmasters to enable/disable
    spell checking by editing the config file. In time, this will expand to
    the ability to enable or disable the addon per bot (future plans).
5. Changed the styling of the admin pages to include visible cues for BBCode
    [code] blocks from the Program O Support Forums, when viewing posts from
    the Support tab of the admin pages.

2.1.3   Added the last of the AIML tag functions/Refactored the DB/Multiple Bug Fixes

1. Added functions for the remaining AIML tags:
        <thatstar>
        <topicstar>
        <gossip>
2. Refactored the database, standardizing field names across all of the tables.
3. Refactored the admin pages, replacing $_GET, $_POST and $_REQUEST with input
    filtering functions.
4. Fixed several minor bugs that were discovered during the database refactoring
    process.
5. Re-designed the AIML tag functions, consolidating duplicate code into a single
    function. this cut more than 70 lines of code from the script while retaining
    exactly the same functionality and performance.
6. Created an experimental config file editor to the admin folder, to allow
    botmasters to edit the file directly. For now, the script doesn't actually
    change the config file, but that functionality will be added in the coming
    weeks.
7. Updated and modified the automatic upgrade script to incorporate all of the
    above listed changes.

2.1.2   Added more AIML tag functions/Script Streamlining

1. Added functions for the following AIML tags:
       <condition>
       <system>
       <learn>
2. I went through the entire script, looking for and deleting "orphaned" functions
    and streamlining code wherever possible.

2.1.1   Added functions for some AIML tags

1. Added functions for the following AIML tags:
        <gender>
        <person>
        <person2>

2.1.0   Major revision change / Bug fixes

1. Altered the way that client properties are handled, by storing them in a table
    in the DB, rather than keeping them in an element of the conversation array.
2. Removed the AIML to PHP code functions, replacing it with an XML parser to
    both improve performance and to address several bugs that arose from trying to
    evaluate faulty PHP code strings.
3. removed the function get_convo_var, replacing calls to that function with direct
    queries to the conversation array.
4. Corrected several minor bugs that were preventing proper use/setting of some variables
        Please note that at this point, several functions still need to be written to parse certain AIML
        tags. The file TODO lists all of the tags that are still not handled.

2.0.9   Feature Update

1. Added a function to the admin page to poll GitHub for the current release version, and notify
    the botmaster if a new version is available, providing a link to the latest version.

2.0.8   Performed the following upgrades/fixes:

1. Added version information to the admin page. so that botmasters can see at a glance which version
    they're using. This is a prelude to a new "version check" feature that I'm working on.
2. Corrected a bug where changing the bot's default page format wasn't being reflected in the config
    file. PLEASE NOTE that if you have several chatbots, this will affect ALL of them, but if that's
    the case, you shouldn't be relying on the default chatbot page anyway.

2.0.7   Two major changes, this time:

1. Removed some settings from the install script that had been causing new chatbots to
  fail to respond. These settings are still available in the admin pages, but during installation
  are given default values.
2. Added support for uploading ZIP file archives of AIML files, to make the process of adding
  AIML files less faster and less tedious. the size limit for uploading files is still 2MB,
  but a 2MB ZIP file can hold a LOT of AIML files.

2.0.6   Performed the following upgrades/fixes:

1. Corrected typographical errors in several files, both in the admin pages, and in the config files.
2. Consolidated error logging, adding a /logs/ folder to the base directory. Error logs are
  also named for the pages where the errors occur. (e.g. admin.error.log for the admin pages,
  install.error.log, etc.
3. Updated the addon checkForBan, activating it, and adding functionality to add banned users
  to the list of banned IP addresses. It's still up to the botmaster to implement banning in
  their AIML files. To ban a user, insert the following into the apropriate AIML template:
  <ban><get name="ip_address" /></ban>
          For further assistance, please check out the Program O Support Forums.

2.0.5   Bug fixes

1. Fixed a bug where uploaded AIML files were not being added to the DB, even if they passed validation.
2. Added the variable $default_charset to allow character encoding other than UTF-8 for both AIML files
    and chatbot pages.
3. Added experimental support for international characters. This is far from it's "final" implementation,
    but we hope that it's a start.

2.0.4   Fixed a bug in the debugging functions that caused empty debugging files to be created on non-Windows systems

2.0.3   Added simple AIML validation to the upload script, and restyled the admin pages accordingly

2.0.2   Unspecified Bug fixes

2.0.1   Unspecified Bug fixes

2.0.0   Initial Release
