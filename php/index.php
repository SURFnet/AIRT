<?php
/* vim: syntax=php tabstop=3 shiftwidth=3
 * $Id$ 
 * index.php - Index page for UvT-CERT
 *
 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2004,2005	Tilburg University, The Netherlands

 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * index.php - AIR console
 * $Id$
 */
require_once 'config.plib';
require_once LIBDIR."/airt.plib";
require_once LIBDIR."/database.plib";

pageHeader("AIRT Control Center");

$out = t('<small>Welcome %username. Your last login was at %lastdb from %hostname.</small>', array(
   '%username'=>$_SESSION['username'],
   '%lastdb'=>$_SESSION['lastdb'],
   '%hostname'=>$_SESSION['hostnamelast']));

$out .= "<HR/>\n";
print $out;
generateEvent('mainmenutop');

/*
$out  = t("<strong>Main tasks</strong>\n");
$out .= t("<p><a href=\"incident.php\">Incident management</a></p>\n");
$out .= t("<p><a href=\"search.php\">IP Address lookup</a></p>\n");
$out .= t("<a href=\"mailtemplates.php\">Mail templates</a></p>\n");
$out .= t("<a href=\"maintenance.php\">Edit settings</a></p>\n");
*/

$out='';
$res = db_query(q("
      SELECT url, label
      FROM   urls
      WHERE  NOT menu_position IS NULL
      ORDER BY menu_position"))
or die("Unable to query database.");

while ($row = db_fetch_next($res)) {
   $url = $row['url'];
   $description = $row['label'];
   $out .= t("<p><a href=\"%url\">%description</a></p>", array(
      '%url'=>$url, 
      '%description'=>$description));
}

print $out;
generateEvent('mainmenubottom');
pageFooter();
?>
