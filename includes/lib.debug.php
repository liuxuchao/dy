<?php

/************************************************
** Title.........: PHP4+ Debug Helper
** Author........: Thomas Sch&#51180;er <code at atomar dot de>
** Filename......: debuglib.php(s)
** Last changed..: 12.07.2004 14:13
** License.......: Free to use. Postcardware ;)
**
*************************************************
**
** Functions in this library:
**
** print_a( array array [,int returnmode] [,bool show object vars] [,int max entries] )
**
**   prints arrays in a readable form.
**   if mode is defined the function returns the output instead of printing it to the output buffer
**
**   print_a( $array, #, 1 ) shows also object properties
**   print_a( $array, 1, # ) returns the table as a string instead of printing it to the output buffer
**   print_a( $array, 'WindowName', #) opens the output in a window indentified by the string.
**   print_a( $array, '_WindowName', #) prints the array inside a frame (<fieldset><label>WindowName</label>...</fieldset>)
**   print_a( $array, 3, # ) opens a new browser window with a serialized version of your array (save as a textfile and can it for later use ;).
**
** show_vars( [bool verbose] [, bool show_object_vars ] [, int limit] )
**
**   use this function on the bottom of your script to see all
**   superglobals and global variables in your script in a nice
**   formated way
**
**   show_vars() without parameter shows $_GET, $_POST, $_SESSION,
**   $_FILES and all global variables you've defined in your script
**
**   show_vars(1) shows $_SERVER and $_ENV in addition
**   show_vars(#,1) shows also object properties
**   show_vars(#, #, 15) shows only the first 15 entries in a numerical keyed array (or an array with more than 50 entries)  ( standard is 5 )
**   show_vars(#, #, 0) shows all entries
**
**
**
** ** print_result( result_handle ) **
**   prints a mysql_result set returned by mysql_query() as a table
**   this function is work in progress! use at your own risk
**
**
** Happy debugging and feel free to email me your comments.
**
**
**
** History: (starting at 2003-02-24)
**
**   - added tooltips to the td's showing the type of keys and values (thanks Itomic)
** 2003-07-16
**   - pre() function now trims trailing tabulators
** 2003-08-01
**   - silly version removed.. who needs a version for such a thing ;)
** 2003-09-24
**   - changed the parameters of print_a() a bit
**     see above
**   - addet the types NULL and bolean to print_a()
**   - print_a() now replaces trailing spaces in string values with red underscores
** 2003-09-24 (later that day ;)
**   - oops.. fixed the print_a() documentation.. parameter order was wrong
**   - added mode 3 to the second parameter
** 2003-09-25
**   - added a limit parameter to the show_vars() and print_a() functions
**     default for show_vars() is 5
**     show_vars(#,#, n) changes that (0 means show all entries)
**     print_a() allways shows all entries by default
**     print_a(#,#,#, n) changes that
**
**     this parameter is used to limit the output of arrays with a numerical index (like long lists of similiar elements)
**     i added this option for performance reasons
**     it has no effect on arrays where one ore more keys are not number-strings
** 2003-09-27
**   - reworked the pre() and _remove_exessive_leading_tabs() functions
**     they now work like they should :)
**   - some cosmetic changes
** 2003-10-28
**   - fixed multiline string display
** 2003-11-14
**   - argh! uploaded the wrong version :/ ... fixed.. sorry
** 2003-11-16
**   - fixed a warning triggered by _only_numeric_keys()
**     thanx Peter Valdemar :)
**   - fixed a warning when print_a was called directly on an object
**     thanx Hilton :)
** 2003-12-01
**   - added slashes in front of the print_a(#,3) output
** 2004-03-17
**   - fixed a problem when print_a(#,2) was called on an array containing newlines
** 2004-03-26
**   - added a variation of mode 2 for print_a().
**     when a string is passed as the second parameter, a new window with the string as prefix gets opened for every differend string.. #TODO_COMMENT#
** 2004-07-12
**   - print_a($array, '_MyLabel') draws a frame with a label around the output
************************************************/

if (!defined('USE_DEBUGLIB')) define('USE_DEBUGLIB', true);

if (USE_DEBUGLIB) {

    # This file must be the first include on your page.

    /* used for tracking of generation-time */
    {
        $MICROTIME_START = microtime();
        @$GLOBALS_initial_count = count($GLOBALS);
    }

    /************************************************
    ** print_a class and helper function
    ** prints out an array in a more readable way
    ** than print_r()
    **
    ** based on the print_a() function from
    ** Stephan Pirson (Saibot)
    ************************************************/

    class Print_a_class {

    }   
} // use debuglib

// Define no-op functions in case debug functions were accidentally left
// in the live system.
else {
    
} // don't use debuglib

?>