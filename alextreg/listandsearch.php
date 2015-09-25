<?php

require_once 'operations.php';
require_once 'database.php';
require_once 'common.php';

$queryfuncs = array();


function render_extension_list($wantname, $query)
{
    $count = db_num_rows($query);
    if (($wantname) and ($count > 1))
        write_error('(Unexpected number of results from database!)');

    print("Extensions:\n<ul>\n");
    while ( ($row = db_fetch_array($query)) != false )
    {
        $url = get_alext_url($row['extname']);
        print("  <li><a href='$url'>${row['extname']}</a>\n");
    } // while
    print("</ul>\n<p>Total results: $count\n<p>\n");
} // render_extension_list


function render_token_list($wantname, $query)
{
    $count = db_num_rows($query);
    if (($wantname) and ($count > 1))
        write_error('(Unexpected number of results from database!)');

    print("Tokens:\n<ul>\n");
    while ( ($row = db_fetch_array($query)) != false )
    {
        $url = get_alext_url($row['extname']);
        $hex = sprintf("0x%X", $row['tokenval']);  // !!! FIXME: faster way to do this?
        print("  <li>${row['tokenname']} ($hex)");
        print(" from <a href='$url'>${row['extname']}</a>\n");
    } // while
    print("</ul>\n<p>Total results: $count\n<p>\n");
} // render_token_list


function render_entrypoint_list($wantname, $query)
{
    $count = db_num_rows($query);
    if (($wantname) and ($count > 1))
        write_error('(Unexpected number of results from database!)');

    print("Entry points:\n<ul>\n");
    while ( ($row = db_fetch_array($query)) != false )
    {
        $url = get_alext_url($row['extname']);
        print("  <li>${row['entrypointname']} ");
        print(" from <a href='$url'>${row['extname']}</a>\n");
    } // while
    print("</ul>\n<p>Total results: $count\n<p>\n");
} // render_entrypoint_list


$queryfuncs['extension'] = 'find_extension';
function find_extension($wantname)
{
    $sql = 'select extname from alextreg_extensions where (1=1)';

    if (!is_authorized_vendor())
        $sql .= " and (public=1)";

    if ($wantname)
    {
        $sqlwantname = db_escape_string($wantname);
        $sql .= " and (extname='$sqlwantname')";
    } // if

    $sql .= ' order by extname';

    $query = do_dbquery($sql);
    if ($query == false)
        return;  // error output is handled in database.php ...
    else
        render_extension_list($wantname, $query);

    db_free_result($query);
} // find_extension


function find_token($additionalsql, $wantname)
{
    if (!get_input_bool('sortbyvalue', 'Sort By Value', &$sbv, 'n')) return;

    $sql = 'select tok.tokenname as tokenname,' .
           ' tok.tokenval as tokenval,' .
           ' ext.extname as extname' .
           ' from alextreg_tokens as tok' .
           ' left outer join alextreg_extensions as ext' .
           ' on tok.extid=ext.id where (1=1)' .
           $additionalsql;

    if (!is_authorized_vendor())
        $sql .= " and (ext.public=1)";

    $sql .= ' order by ' . (($sbv) ? 'tok.tokenval' : 'tok.tokenname');

    $query = do_dbquery($sql);
    if ($query == false)
        return;  // error output is handled in database.php ...
    else
        render_token_list($wantname, $query);

    db_free_result($query);
} // find_token


$queryfuncs['tokenname'] = 'find_tokenname';
function find_tokenname($wantname)
{
    $additionalsql = '';
    if ($wantname)
    {
        $sqlwantname = db_escape_string($wantname);
        $additionalsql .= " and (tok.tokenname='$sqlwantname')";
    } // if

    find_token($additionalsql, $wantname);
} // find_tokenname


$queryfuncs['tokenvalue'] = 'find_tokenvalue';
function find_tokenvalue($wantname)
{
    $additionalsql = '';
    if ($wantname)
    {
        if (!is_numeric($wantname))
            return;
        $sqlwantname = db_escape_string($wantname);
        $additionalsql .= " and (tok.tokenval=$sqlwantname)";
    } // if

    find_token($additionalsql, $wantname);
} // find_tokenvalue


$queryfuncs['entrypoint'] = 'find_entrypoint';
function find_entrypoint($wantname)
{
    $sql = 'select ent.entrypointname as entrypointname,' .
           ' ext.extname as extname' .
           ' from alextreg_entrypoints as ent' .
           ' left outer join alextreg_extensions as ext' .
           ' on ent.extid=ext.id where (1=1)';

    if (!is_authorized_vendor())
        $sql .= " and (ext.public=1)";

    if ($wantname)
    {
        $sqlwantname = db_escape_string($wantname);
        $sql .= " and (ent.entrypointname='$sqlwantname')";
    } // if

    $sql .= ' order by ent.entrypointname';

    $query = do_dbquery($sql);
    if ($query == false)
        return;  // error output is handled in database.php ...
    else
        render_entrypoint_list($wantname, $query);

    db_free_result($query);
} // find_entrypoint


$queryfuncs['anything'] = 'find_anything';
function find_anything($wantname)
{
    find_extension($wantname);
    find_tokenname($wantname);
    find_tokenvalue($wantname);
    find_entrypoint($wantname);
} // find_anything


function do_find($wanttype, $wantname = NULL)
{
    global $queryfuncs;

    $queryfunc = $queryfuncs[$wanttype];
    if (!isset($queryfunc))
    {
        write_error('Invalid search type.');
        return;
    } // if

    $queryfunc($wantname);

    echo "\n<hr>\n";
} // do_find


$operations['op_findone'] = 'op_findone';
function op_findone()
{
    if (!get_input_string('wanttype', 'Database field type', $wanttype)) return;
    if (!get_input_string('wantname', 'Database field name', $wantname)) return;
    write_debug("called op_findone($wanttype, $wantname)");
    do_find($wanttype, $wantname);
} // op_findone


function show_one_extension($extrow)
{
    $is_vendor = is_authorized_vendor();

    $extname = $extrow['extname'];
    $extid = $extrow['id'];
    $public = $extrow['public'];
    $wikiurl = get_alext_wiki_url($extname);
    $htmlextname = htmlentities($extname, ENT_QUOTES);

    if ((!$public) and (!$is_vendor))  // sanity check.
        return;

    echo "<p><h1><u>$htmlextname</u></h1>\n";
    echo "<p>&nbsp;&nbsp;<a href='${wikiurl}'>Click here for documentation and discussion.</a>\n";

    $tab = '&nbsp;&nbsp;&nbsp;&nbsp;';
    echo "<p><font size='-1'>\n";
    echo "${tab}Registered on ${extrow['entrydate']} by ${extrow['author']}<br>\n";
    echo "${tab}Last edited on ${extrow['lastedit']} by ${extrow['lasteditauthor']}<br>\n";
    echo "</font>\n";

    // get the tokens, move it to an array of db rows...
    $tokens = array();
    $sql = 'select * from alextreg_tokens as tok' .
           ' left outer join alextreg_extensions as ext on tok.extid=ext.id' .
           " where (ext.id=$extid)";

    if (!$is_vendor)
        $sql .= ' and (ext.public=1)';

    $sql .= ' order by tok.tokenname';

    $query = do_dbquery($sql);
    if ($query == false)
        return;  // uh...?
    while ( ($row = db_fetch_array($query)) != false )
        $tokens[] = $row;
    db_free_result($query);

    $entrypoints = array();
    $sql = 'select * from alextreg_entrypoints as ent' .
           ' left outer join alextreg_extensions as ext on ent.extid=ext.id' .
           " where (ext.id=$extid)";

    if (!$is_vendor)
        $sql .= ' and (ext.public=1)';

    $sql .= ' order by ent.entrypointname';

    $query = do_dbquery($sql);
    if ($query == false)
        return;  // uh...?
    while ( ($row = db_fetch_array($query)) != false )
        $entrypoints[] = $row;
    db_free_result($query);

    if ($is_vendor)
    {
        $form = get_form_tag();

        echo "<p>\n";
        echo "<table border='1'><tr><td><b>Vendor:</b></td></tr><tr><td>\n";

        $toggle = (($public) ? 'n' : 'y');
        $is = ($public) ? 'is' : 'is not';
        echo "$form\n";
        echo "This extension $is publically visible.\n";
        echo "<input type='hidden' name='extid' value='$extid'>\n";
        echo "<input type='hidden' name='extname' value='$htmlextname'>\n";
        echo "<input type='hidden' name='newval' value='$toggle'>\n";
        echo "<input type='hidden' name='operation' value='op_showhideext'>\n";
        echo "<input type='submit' name='form_submit' value='Toggle'>\n";
        echo "</form>\n";

        echo "$form\n";
        echo "Add a new token named <input type='text' name='tokname'>\n";
        echo "with the value <input type='text' name='tokval'>.\n";
        echo "<input type='hidden' name='extid' value='$extid'>\n";
        echo "<input type='hidden' name='extname' value='$htmlextname'>\n";
        echo "<input type='hidden' name='operation' value='op_addtoken'>\n";
        echo "<input type='submit' name='form_submit' value='Go!'>\n";
        echo "</form>\n";

        echo "$form\n";
        echo "Add a new entry point named <input type='text' name='entrypointname'>\n";
        echo "<input type='hidden' name='extid' value='$extid'>\n";
        echo "<input type='hidden' name='extname' value='$htmlextname'>\n";
        echo "<input type='hidden' name='operation' value='op_addentrypoint'>\n";
        echo "<input type='submit' name='form_submit' value='Go!'>\n";
        echo "</form>\n";

        echo "$form\n";
        echo "I'd like to rename this extension to\n";
        echo "<input type='text' name='newval' value=''>.\n";
        echo "<input type='hidden' name='extid' value='$extid'>\n";
        echo "<input type='hidden' name='extname' value='$htmlextname'>\n";
        echo "<input type='hidden' name='operation' value='op_renameext'>\n";
        echo "<input type='submit' name='form_submit' value='Go!'>\n";
        echo "</form>\n";

        echo "$form\n";
        echo "I'd like to delete this extension.\n";
        echo "<input type='hidden' name='extid' value='$extid'>\n";
        echo "<input type='hidden' name='extname' value='$htmlextname'>\n";
        echo "<input type='hidden' name='operation' value='op_delext'>\n";
        echo "<input type='submit' name='form_submit' value='Go!'>\n";
        echo "</form>\n";

        if (count($tokens))
        {
            echo "$form\n";
            echo "I want to change the\n";
            echo "<select name='operation' size='1'>\n";
            echo "  <option value='op_renametok'>name</option>\n";
            echo "  <option value='op_revaluetok'>value</option>\n";
            echo "</select>\n";
            echo "of the token named\n";
            echo "<select name='tokname' size='1'>\n";
            echo "  <option value=''>...</option>\n";
            foreach ($tokens as $row)
            {
                $name = htmlentities($row['tokenname'], ENT_QUOTES);
                echo "  <option value='$name'>$name</option>\n";
            } // foreach
            echo "</select>\n";
            echo "to <input type='text' name='newval' value=''>.\n";
            echo "<input type='hidden' name='extid' value='$extid'>\n";
            echo "<input type='hidden' name='extname' value='$htmlextname'>\n";
            echo "<input type='submit' name='form_submit' value='Go!'>\n";
            echo "</form>\n";

            echo "$form\n";
            echo "I want to delete the token named\n";
            echo "<select name='tokname' size='1'>\n";
            echo "  <option value=''>...</option>\n";
            foreach ($tokens as $row)
            {
                $name = htmlentities($row['tokenname'], ENT_QUOTES);
                echo "  <option value='$name'>$name</option>\n";
            } // foreach
            echo "</select>\n";
            echo "<input type='hidden' name='extid' value='$extid'>\n";
            echo "<input type='hidden' name='extname' value='$htmlextname'>\n";
            echo "<input type='hidden' name='operation' value='op_deltok'>\n";
            echo "<input type='submit' name='form_submit' value='Go!'>\n";
            echo "</form>\n";
        } // if

        if (count($entrypoints))
        {
            echo "$form\n";
            echo "I want to change the name of the entry point named\n";
            echo "<select name='entname' size='1'>\n";
            echo "  <option value=''>...</option>\n";
            foreach ($entrypoints as $row)
            {
                $name = htmlentities($row['entrypointname'], ENT_QUOTES);
                echo "  <option value='$name'>$name</option>\n";
            } // foreach
            echo "</select>\n";
            echo "to <input type='text' name='newval' value=''>.\n";
            echo "<input type='hidden' name='extid' value='$extid'>\n";
            echo "<input type='hidden' name='extname' value='$htmlextname'>\n";
            echo "<input type='hidden' name='operation' value='op_renameent'>\n";
            echo "<input type='submit' name='form_submit' value='Go!'>\n";
            echo "</form>\n";

            echo "$form\n";
            echo "I want to delete the entry point named\n";
            echo "<select name='entname' size='1'>\n";
            echo "  <option value=''>...</option>\n";
            foreach ($entrypoints as $row)
            {
                $name = htmlentities($row['entrypointname'], ENT_QUOTES);
                echo "  <option value='$name'>$name</option>\n";
            } // foreach
            echo "</select>\n";
            echo "<input type='hidden' name='extid' value='$extid'>\n";
            echo "<input type='hidden' name='extname' value='$htmlextname'>\n";
            echo "<input type='hidden' name='operation' value='op_delent'>\n";
            echo "<input type='submit' name='form_submit' value='Go!'>\n";
            echo "</form>\n";
        } // if

        echo "</td></tr></table>\n";
    } // if

    echo "<p>Tokens:\n<ul>\n";

    if (count($tokens) == 0)
        echo "  <li> (no tokens.)\n";
    else
    {
        foreach ($tokens as $row)
        {
            $hex = sprintf("0x%X", $row['tokenval']);  // !!! FIXME: faster way to do this?
            echo "  <li> ${row['tokenname']} ($hex)";
            //echo " added ${row['entrydate']},";
            //echo " last modified ${row['lastedit']}";
            echo "\n";
        } // foreach
    } // else
    echo "</ul>\n";

    echo "<p>Entry points:\n<ul>\n";
    if (count($entrypoints) == 0)
        echo "  <li> (no entry points.)\n";
    else
    {
        foreach ($entrypoints as $row)
        {
            echo "  <li> ${row['entrypointname']}";
            //echo " added ${row['entrydate']},";
            //echo " last modified ${row['lastedit']}";
            echo "\n";
        } // foreach
    } // else

    echo "</ul>\n";

    echo "<hr>\n";
} // show_one_extension


$operations['op_findall'] = 'op_findall';
function op_findall()
{
    if (!get_input_string('wanttype', 'Database field type', $wanttype)) return;
    write_debug("called op_findall($wanttype)");
    do_find($wanttype);
} // op_findall


function do_showext($extname)
{
    $sqlextname = db_escape_string($extname);
    $sql = "select * from alextreg_extensions" .
           " where extname='$sqlextname'";

    if (!is_authorized_vendor())
        $sql .= " and (public=1)";

    $query = do_dbquery($sql);
    if ($query == false)
        return;  // error output is handled in database.php ...
    else if (db_num_rows($query) == 0)
        write_error('No such extension.');
    else
    {
        // just in case there's more than one for some reason...
        while ( ($row = db_fetch_array($query)) != false )
            show_one_extension($row);
    } // else

    db_free_result($query);
} // do_showext


$operations['op_showext'] = 'op_showext';
function op_showext()
{
    if (!get_input_string('extname', 'extension name', $extname)) return;
    do_showext($extname);
} // op_showext


function render_search_ui()
{
    $form = get_form_tag();

    print <<<EOF

<p>
Where do you want to go today?

<p>
$form
  I want a list of all known
  <select name="wanttype" size="1">
    <option selected value="extension">extensions</option>
    <option value="tokenname">tokens</option>
    <option value="entrypoint">entry points</option>
  </select>.
  <input type="hidden" name="operation" value="op_findall">
  <input type="submit" name="form_submit" value="Go!">
</form>

<p>
...or...

<p>
$form
  I want
  <select name="wanttype" size="1">
    <option selected value="extension">an extension</option>
    <option value="tokenname">a token name</option>
    <option value="tokenvalue">a token value</option>
    <option value="entrypoint">an entry point</option>
    <option value="anything">anything</option>
  </select>
  named <input type="text" name="wantname" value="">.
  <input type="hidden" name="operation" value="op_findone">
  <input type="submit" name="form_submit" value="Go!">
</form>

EOF;

} // render_search_ui

?>