<?php

// This file adds a little bit of a wrapper over MySQL, and a little
//  bit of convenience functionality.

require_once 'common.php';

// This should have the following lines, minus comments:
//
//  $dbuser = 'username';
//  $dbpass = 'password';
//
// Obviously, those should be a real username and password for the database.
require_once 'dbpasswd.php';

$dblink = NULL;

function get_dblink()
{
    global $dblink;

    if ($dblink == NULL)
    {
        global $dbuser, $dbpass;
        $dblink = mysql_connect('localhost', $dbuser, $dbpass);
        if (!$dblink)
        {
            $err = mysql_error();
            write_error("Failed to open database link: ${err}.");
            $dblink = NULL;
        } // if

        if (!mysql_select_db("alextreg"))
        {
            $err = mysql_error();
            write_error("Failed to select database: ${err}.");
            mysql_close($dblink);
            $dblink = NULL;
        } // if
    } // if

    return($dblink);
} // get_dblink


function db_escape_string($str)
{
    return(mysql_escape_string($str));
} // db_escape_string


function do_dbquery($sql, $link = NULL)
{
    if ($link == NULL)
        $link = get_dblink();

    if ($link == NULL)
        return(false);

    write_debug("SQL query: [$sql]");

    $rc = mysql_query($sql, $link);
    if ($rc == false)
    {
        $err = mysql_error();
        write_error("Problem in SELECT statement: {$err}");
        return(false);
    } // if

    return($rc);
} // do_dbquery


function do_dbwrite($sql, $verb, $expected_rows = 1, $link = NULL)
{
    if ($link == NULL)
        $link = get_dblink();

    if ($link == NULL)
        return(false);

    write_debug("SQL $verb: [$sql]");

    $rc = mysql_query($sql, $link);
    if ($rc == false)
    {
        $err = mysql_error();
        $upperverb = strtoupper($verb);
        write_error("Problem in $upperverb statement: {$err}");
        return(false);
    } // if

    $retval = mysql_affected_rows($link);
    if (($expected_rows >= 0) and ($retval != $expected_rows))
    {
        $err = mysql_error();
        write_error("Database $verb error: {$err}");
    } // if

    return($retval);
} // do_dbwrite


function do_dbinsert($sql, $expected_rows = 1, $link = NULL)
{
    return(do_dbwrite($sql, 'insert', $expected_rows, $link));
} // do_dbinsert


function do_dbupdate($sql, $expected_rows = 1, $link = NULL)
{
    return(do_dbwrite($sql, 'update', $expected_rows, $link));
} // do_dbupdate


function do_dbdelete($sql, $expected_rows = 1, $link = NULL)
{
    return(do_dbwrite($sql, 'delete', $expected_rows, $link));
} // do_dbdelete


function db_num_rows($query)
{
    return(mysql_num_rows($query));
} // db_num_rows


function db_fetch_array($query)
{
    return(mysql_fetch_array($query));
} // db_fetch_array


function db_free_result($query)
{
    return(mysql_free_result($query));
} // db_free_result

?>