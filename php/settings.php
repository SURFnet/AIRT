<?php
/* $Id$ 
 *
 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2004.2005	Tilburg University, The Netherlands

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
require_once 'config.plib';
require_once LIBDIR."/airt.plib";

pageHeader(_('AIRT Maintenance Center'));
print '<hr>'.LF;
print '<b>'._('User management').'</b>'.LF;
print '<P>'.LF;
print '<a href="users.php">'._('Edit users').'</a>'.LF;
print '<HR>'.LF;
print '<b>'._('Incident management').'</b>'.LF;
print '<P>'.LF;
print '<a href="incident_states.php">'._('Edit incident states').'</a>'.LF;
print '<P>'.LF;
print '<a href="incident_status.php">'._('Edit incident statuses').'</a>'.LF;
print '<P>'.LF;
print '<a href="incident_types.php">'._('Edit incident types').'</a>'.LF;
print '<P>'.LF;
print '<a href="mailtemplates.php">'._('Edit standard messages').'</a>'.LF;
print '<HR>'.LF;
print '<b>'._('Network management').'</b>'.LF;
print '<P>'.LF;
print '<a href="networks.php">'._('Edit networks').'</a>'.LF;
print '<P>'.LF;
print '<a href="constituencies.php">'._('Edit constituencies').'</a>'.LF;
print '<P>'.LF;
print '<a href="constituency_contacts.php">'._('Edit constituency contacts').'</a>'.LF;
print '<HR>'.LF;
print '<b>'._('Appearance').'</b>'.LF;
print '<P>'.LF;
print '<a href="links.php">'._('Edit main menu links').'</a>'.LF;
print '<hr>'.LF;
pageFooter();
?>
