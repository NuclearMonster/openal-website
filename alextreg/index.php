<?php include ("../header.php") ?>

<?php
require_once 'operations.php';
//require_once 'headerandfooter.php';
require_once 'icculux.php'
require_once 'listandsearch.php';

icculux();
if (do_operation())
    echo "<p>Back to <a href='${_SERVER['PHP_SELF']}'>search page</a>.\n";
else
    render_search_ui();
render_footer();
?>

<?php include ("../footer.php") ?>