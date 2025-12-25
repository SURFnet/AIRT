<?php
   $public = 1;
	 require_once 'config.plib';
   require_once LIBDIR.'/airt.plib';
   pageHeader("GNU General Public License");
   echo "<pre>";
   include "/usr/share/common-licenses/GPL";
   echo "</pre>";
   pageFooter();
