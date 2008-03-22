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

pageHeader(_('AIRT Control Center'));

$out = t('<small>'._('Welcome %username. Your last login was at %lastdb from %hostname.').'</small>', array(
   '%username'=>strip_tags($_SESSION['username']),
   '%lastdb'=>strip_tags($_SESSION['lastdb']),
   '%hostname'=>strip_tags($_SESSION['hostnamelast'])));

$out .= "<HR/>\n";
print $out;
generateEvent('mainmenutop');

$out='';
$res = db_query(q("
      SELECT url, label
      FROM   urls
      WHERE  NOT menu_position IS NULL
      ORDER BY menu_position"))
or die(_('Unable to query database.'));

while ($row = db_fetch_next($res)) {
   $url = $row['url'];
   $description = $row['label'];
   $out .= t("<p><a href=\"%url\">%description</a></p>", array(
      '%url'=>$url, 
      '%description'=>strip_tags($description)));
}

print $out;
generateEvent('mainmenubottom');
pageFooter();
?>
