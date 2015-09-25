<?php

$enable_debug = (!empty($_REQUEST['debug']));

function get_form_tag()
{
    global $enable_debug;
    if ($enable_debug)
        return("<form><input type='hidden' name='debug' value='true'>");
    return("<form method='post' action='${_SERVER['PHP_SELF']}'>");
} // get_form_tag


function is_authorized_vendor()
{
    return(!empty($_SERVER['REMOTE_USER']));
} // is_authorized_vendor


function write_error($err)
{
    echo "<p><center><font color='#FF0000'>";
    echo   "ERROR: $err<br>";
    echo "</font></center>\n";
} // write_error


function write_debug($dbg)
{
    global $enable_debug;
    if ($enable_debug)
    {
        echo "<p><center><font color='#0000FF'>";
        echo   "DEBUG: $dbg<br>";
        echo "</font></center>\n";
    } // if
} // write_debug


function current_sql_datetime()
{
    $t = localtime(time(), true);
    return( "" . ($t['tm_year'] + 1900) . '-' .
                 ($t['tm_mon'] + 1) . '-' .
                 ($t['tm_mday']) . ' ' .
                 ($t['tm_hour']) . ':' .
                 ($t['tm_min']) . ':' .
                 ($t['tm_sec']) );
} // current_sql_datetime


function get_alext_wiki_url($extname)
{
    $htmlextname = htmlentities($extname, ENT_QUOTES);
    return("wiki/$htmlextname");
} // get_alext_wiki_url


function get_alext_url($extname)
{
    $htmlextname = htmlentities($extname, ENT_QUOTES);
    return("${_SERVER['PHP_SELF']}?operation=op_showext&extname=${htmlextname}");
} // get_alext_url


function get_input_sanitized($reqname, $reqtype, &$reqval, $defval=false)
{
    $val = $_REQUEST[$reqname];
    if (!isset($val))
    {
        if (!$defval)
        {
            write_error("No $reqtype specified.");
            return false;
        } // if
        $reqval = $defval;
        return true;
    } // if

    $reqval = trim($val);
    if ($reqval == '')
    {
        write_error("$reqtype is blank: Please fill out all fields.");
        return false;
    } // if

    return true;
} // get_input_sanitized


function get_input_string($reqname, $reqtype, &$reqval, $defval=false)
{
    return get_input_sanitized($reqname, $reqtype, $reqval, $defval);
} // get_input_string


function get_input_bool($reqname, $reqtype, &$reqval, $defval=false)
{
    $tmp = '';
    if (!get_input_sanitized($reqname, $reqtype, $tmp, $defval))
        return false;

    $tmp = strtolower($tmp);
    if (($tmp == 'y') || ($tmp == 'yes') ||
        ($tmp == 't') || ($tmp == 'true') ||
        ($tmp == '1'))
    {
        $reqval = 1;
        return true;
    } // if

    if (($tmp == 'n') || ($tmp == 'no') ||
        ($tmp == 'f') || ($tmp == 'false') ||
        ($tmp == '0'))
    {
        $reqval = 0;
        return true;
    } // if

    write_error("$reqtype is not true or false");
    return false;
} // get_input_bool


function get_input_number($reqname, $reqtype, &$reqval, $defval=false)
{
    if (!get_input_sanitized($reqname, $reqtype, $reqval, $defval))
        return false;

    if (sscanf($reqval, "0x%X", &$hex) == 1) // it's a 0xHEX value.
        $reqval = $hex;

    if (!is_numeric($reqval))
    {
        write_error("$reqtype isn't a number");
        return false;
    } // if

    return true;
} // get_input_number


function get_input_int($reqname, $reqtype, &$reqval, $defval=false)
{
    if (!get_input_number($reqname, $reqtype, $reqval))
        return false;

    $reqval = (int) $reqval;
    return true;
} // get_input_int


function get_input_float($reqname, $reqtype, &$reqval, $defval=false)
{
    if (!get_input_number($reqname, $reqtype, $reqval))
        return false;

    $reqval = (float) $reqval;
    return true;
} // get_input_float

?>