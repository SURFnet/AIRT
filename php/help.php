<?php
/* vim:syntax=php
 * $Id$ 

 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2004	Kees Leune <kees@uvt.nl>

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
 */
require_once 'config.plib';
require_once LIBDIR.'/airt.plib';

if (array_key_exists('topic', $_GET)) $topic = $_GET['topic'];
else die('Missing topic.');

pageHeader("Help information");
switch ($topic) {
	// --------------------------------------------------------------
	case 'search-search':
		echo <<<EOF
<a name="search-search">
<h3>Help information for IP address search.</h3>

<p>Enter the IP address or the host name of the node that you would like more
information on.</p>

<p>Use hotkey <b>ctrl-a</b> to focus the cursor in the input field.</p>
</a>
EOF;
		break;
	
	// --------------------------------------------------------------
	case 'search-info':
		echo <<<EOF
Help information for IP address search results.
EOF;
		break;

	// --------------------------------------------------------------
	case 'incident-adduser':
		echo <<<EOF
<p>Enter user details in this field. AIRT processing logic will attempt to match
this information by a user by trying the following tests.</p>

<ol>
<li>AIRT login</li>
<li>Organizational userid</li>
<li>Email address</li>
</ol>

<p>The results of the first test that returns a match will be used. If no
match is found, you will be redirected to the edit users page.</p>
EOF;
		break;
	// --------------------------------------------------------------
	default:
		echo 'Unknown help topic';
}

	$r = $_SERVER["HTTP_REFERER"];
	echo <<<EOF
	<a href="$r">Back...</a>
EOF;

pageFooter();
?>
