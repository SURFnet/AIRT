<?php
/* $Id$ 
 *
 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2004	Tilburg University, The Netherlands

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
 * mainentance.php - Maintenance page for AIRT
 * $Id$
 */
require_once '/etc/airt/airt.cfg';
require_once LIBDIR."/airt.plib";

pageHeader("AIRT Maintenance Center");
?>


<hr>

<b>User management</b>

<P>

<a href="users.php">Edit users</a>

<P>

<a href="roles.php">Edit roles</a> 

<P>

<a href="roleassignments.php">Manage role assignments</a>




<HR>

<b>Incident management</b>

<P>

<a href="incident_states.php">Edit incident states</a>

<P>

<a href="incident_status.php">Edit incident statuses</a>

<P>

<a href="incident_types.php">Edit incident types</a> 

<P>

<a href="standard.php">Edit standard messages</a> TODO


<HR>

<b>Network management</b>

<P>

<a href="constituencies.php">Edit constituencies</a>

<P>

<a href="constituency_contacts.php">Edit constituency contacts</a> TODO




<HR>

<b>Appearance</b>

<P>

<a href="links.php">Edit main menu links</a>


<hr>

<?php
    pageFooter();
?>
