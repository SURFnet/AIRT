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

pageHeader(_('Settings'), array(
   'menu'=>'settings'));
print '<div class="column">'.LF;
print '    <div class="block">'.LF;
print '       <h3>'._('Configuration options').'</h3>'.LF;
print t('       <a href="%u/config.php">%l</a>', array(
   '%u'=>BASEURL,
   '%l'=>_('Configuration screen')
));
print '    </div>'.LF;
print '    <div class="block">'.LF;
print '        <h3>'._('Edit tools menu').'</h3>'.LF;
print '        <p>';
print _('The Tools-menu can be used to access sources of information directly from any screen of AIRT. You might want to add a link to your IP-Address lookup and Network statistics application to this list.');
print '</p>'.LF;
print '        <p>'.LF;
print _('All of the links in the Tools Menu will open in a new window.');
print '</p>'.LF;
print t('        <a href="%u/links.php">%l</a>', array(
   '%u'=>BASEURL,
   '%l'=>_('Edit Tools menu')));
print '    </div>'.LF;
print '    <div class="block">'.LF;
print '        <h3>'._('AIRT User management').'</h3>'.LF;
print '        <P>'.LF;
print t('        <a href="%u/users.php">%l</a>'.LF, array(
   '%u'=>BASEURL,
   '%l'=>_('Edit users')));
print '    </div>'.LF;
print '</div>'.LF;
print '<div class="column">'.LF;
print '    <div class="block">'.LF;
print '        <h3>'._('Incident handling management').'</h3>'.LF;
print t('        <p><a href="%u/incident_states.php">%l</a><br/>'.LF, array(
   '%u'=>BASEURL,
   '%l'=>_('Edit incident states')));
print _('Edit the states an incident can be in.').'</p>'.LF;
print '</p>'.LF;
print t('        <p><a href="%u/incident_types.php">%l</a><br />'.LF, array(
   '%u'=>BASEURL,
   '%l'=>_('Edit incident types')));
print _('Edit what kind of incidents are identified on your network.').'</p>'.LF;
print t('         <p><a href="%u/incident_status.php">%l</a><br />'.LF, array(
   '%u'=>BASEURL,   
   '%l'=>_('Edit incident statuses')));
print '<em>';
print _('This is probably not a function anymore in a future version');
print '</em></p>'.LF;
print t('<p><a href="%u/mailtemplates.php">%l</a><br />', array(
   '%u'=>BASEURL,
   '%l'=>_('Edit standard messages')));
print '        </p>'.LF;
print '    </div>'.LF;
print '    <div class="block">'.LF;
print '<h3>'._('Import queue').'</h3>'.LF;
print t('<a href="%u/importqueue.php?action=preftempl">%l</a><br/>'.LF, array(
   '%u'=>BASEURL,
   '%l'=>_('Edit preferred templates')));
print _('Set preferred templates for import filters').'<p/>'.LF;
print '    </div>'.LF;
print '</div>'.LF;

pageFooter();
?>
