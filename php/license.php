<?php
    $public = 1;
	require_once '@ETCPATH/airt.cfg';
	require_once LIBDIR.'/airt.plib';
    pageHeader("GNU General Public License");
    echo "<PRE>";
    include "../COPYING";
    echo "</PRE>";
    pageFooter();
?>
