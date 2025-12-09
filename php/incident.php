<?php
/* vim:syntax=php shiftwidth=3 tabstop=3
 *
 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2006   Tilburg University, The Netherlands

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
 * Incidents.php - incident management interface
 */

require_once 'config.plib';
require_once LIBDIR.'/airt.plib';
require_once LIBDIR.'/database.plib';
require_once LIBDIR.'/constituency.plib';
require_once LIBDIR.'/incident.plib';
require_once LIBDIR.'/history.plib';
require_once LIBDIR.'/user.plib';
require_once LIBDIR.'/mailtemplates.plib';

function filterIsOn($v) {
    return (strtolower($v) == 'on');
}

$action = strip_tags(fetchFrom('REQUEST','action'));
defaultTo($action,'list');

switch ($action) {

  //--------------------------------------------------------------------
  case _('Compose'):
  case 'prepare':
  case _('Auto send'):
  case _('prepare'):
     // Send bulk mail for the selected incidents.
     $massincidents = fetchFrom('REQUEST','massincidents[]');
     if (empty($massincidents)) {
        // Nothing selected, show list again.
        reload();
     }
     $massincidents = array_keys($massincidents);
     if (is_array($massincidents) && sizeof($massincidents) >= 1) {
        // filter out non-numeric elements
         foreach ($massincidents as $key=>$value) {
             if (!is_numeric($value)) {
                 unset($massincidents[$key]);
             }
         }
	 }
     $incidentids = implode(',', $massincidents);

     $template = strip_tags(fetchFrom('REQUEST','template'));
     defaultTo($template,_('Do not send mail'));
     if ($template==_('Do not send mail')) {
        // No template selected, show list again.
        reload();
     }
     if ($action == _('Auto send'))
        $autosend='yes';
     else
        $autosend='no';
	  $_SESSION['incidentids'] = $incidentids;
	  $_SESSION['autosend'] = $autosend;
	  $_SESSION['template'] = $template;
     reload("mailtemplates.php?action=prepare");
     break;

  //--------------------------------------------------------------------
  case _('Show Details'):
  case 'details':
    incidentDetails();
    break;

    //---------------------------------------------------------------
    case _('Create New Incident'):
    case _('New incident'):
    case 'new':
      newIncident(false);
      pageFooter();
      break;
    //--------------------------------------------------------------------
    case 'bulkform':
       newIncident(true);
       pageFooter();
       break;

    case 'add':
       addIncident();
       break;

    case "addbulk":
      addBulkIncidents();
      break;

    case 'list':
       listIncidents();
       break;

   case 'stall':
       stallIncident();
       break;

  case 'reopen':
       reopenIncident();
       break;

   case 'close':
       closeIncident();
       break;

   //--------------------------------------------------------------------
   case 'addip':
      $incidentid = fetchFrom('SESSION','incidentid', '%d');
      if ($incidentid=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $ip = strip_tags(fetchFrom('POST','ip'));
      if ($ip=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }
      // only resolve hostname to IP address
      if (preg_match('/([0-9]\.){4}/', $ip) == 0) {
          $ip = gethostbyname($ip);
      }

      $addressrole = fetchFrom('POST','addressrole', '%d');
      if ($addressrole=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      generateEvent('addiptoincident', array(
         'incidentid' => $incidentid,
         'ip'         => $ip,
         'addressrole'=> $addressrole
      ));

      if (trim($ip) != '') {
         addIPToIncident(array(
				'ip'=>trim($ip),
				'incidentid' => $incidentid,
			   'addressrole' => $addressrole));
         addIncidentComment(array(
            'comment'=>t(_('IP address %ip added to incident with role %role'),
               array(
                  '%ip'=>$ip,
                  '%role'=>getAddressRoleByID($addressrole)
               )),
            'incidentid'=>$incidentid
         ));
      }

      reload(sprintf('%s?action=details&incidentid=%s',
         BASEURL.'/incident.php',
         urlencode($incidentid)));
      break;

    //--------------------------------------------------------------------
   case 'editip':
      $incidentid = fetchFrom('SESSION','incidentid', '%d');
      if ($incidentid=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $ip = strip_tags(fetchFrom('REQUEST','ip'));
      if ($ip=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      pageHeader(_('IP address details'), array(
         'menu'=>'incidents',
         'submenu'=>'incidents'));
      printf(editIPform($incidentid,$ip));
      pageFooter();
      break;

    //--------------------------------------------------------------------
   case 'updateip':
      $id = fetchFrom('POST','id', '%d');
      if ($id=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $constituency = fetchFrom('POST','constituency', '%d');
      if ($constituency=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $ip = strip_tags(fetchFrom('POST','ip'));
      if ($ip=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $incidentid = fetchFrom('POST','incidentid', '%d');
      if ($incidentid=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $addressrole = fetchFrom('POST','addressrole', '%d');
      if ($addressrole=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      // Update the IP details.
      updateIPofIncident($id, $constituency, $addressrole);

      // Fetch all constituencies and address roles for name lookup.
      $constituencies = getConstituencies();
      $constLabel = $constituencies[$constituency]['label'];

      $addressroles = getAddressRoles();
      $addressRoleLabel = $addressroles[$addressrole];

      // Generate comment and event.
      addIncidentComment(array(
         'comment'=>t( _('Details of IP address %ip updated; const=%const, addressrole=%role'),
            array(
               '%ip'=>$ip,
               '%const'=>$constLabel,
               '%role'=>$addressRoleLabel
            )
         ),
         'incidentid'=>$incidentid
      ));
      generateEvent('updateipdetails', array(
         'incidentid' => $incidentid,
         'ip'         => $ip,
         'constituency' => $constituency,
         'addressrole' => $addressrole
      ));

      reload(sprintf('%s?action=details&incidentid=%s',
         BASEURL.'/incident.php',
         urlencode($incidentid)));

    //--------------------------------------------------------------------
   case 'deleteip':
      $incidentid = fetchFrom('REQUEST', 'incidentid', '%d');
      if (empty($incidentid)) {
         $incidentid = fetchFrom('SESSION','incidentid', '%d');
      }
      if ($incidentid=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $ip = strip_tags(fetchFrom('GET','ip'));
      if ($ip=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $addressrole = fetchFrom('GET','addressrole', '%d');
      if ($addressrole=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      removeIpFromIncident($ip, $incidentid, $addressrole);
      addIncidentComment(array(
         'comment'=>t(_('IP address %address (%role) removed from incident.'),
            array('%address'=>$ip,
               '%role'=>getAddressRolebyID($addressrole)
            )
         ),
         'incidentid'=>$incidentid
      ));

      generateEvent('removeipfromincident', array(
         'incidentid' => $incidentid,
         'ip'         => $ip,
         'addressrole'=> $addressrole
      ));
      reload(sprintf('%s?action=details&incidentid=%s',
         BASEURL.'/incident.php',
         urlencode($incidentid)));
      break;

    //--------------------------------------------------------------------
   case 'adduser':
      $email = strip_tags(fetchFrom('REQUEST','email'));
      if ($email=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }
      $template = fetchFrom('REQUEST', 'template');
      defaultTo($template, '');

      $add = strip_tags(fetchFrom('REQUEST','addifmissing'));
      defaultTo($add,'off');

      $incidentid = fetchFrom('SESSION','incidentid', '%d');
      if ($incidentid=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $id = getUserByEmail($email);
      if (!$id) {
         addUser(['email'=>strtolower(trim($email))]);
         $id = getUserByEmail(strtolower(trim($email)));
      }

      $user = getUserByUserID($id['id']);
      addUserToIncident($id['id'], $incidentid, $template);
      addIncidentComment(array(
         'comment'=>sprintf(_('User %s added to incident.'), $user['email']),
         'incidentid'=>$incidentid
      ));

      reload(sprintf('%s?action=details&incidentid=%s',
         BASEURL.'/incident.php',
         urlencode($incidentid)));

      break;
   //--------------------------------------------------------------------
   case 'deluser':
      $incidentid = fetchFrom('SESSION','incidentid', '%d');
      if ($incidentid=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $userid = fetchFrom('GET','userid', '%d');
      if ($userid=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      removeUserFromIncident($userid, $incidentid);
      $user = getUserByUserID($userid);
      addIncidentComment(array(
         'comment'=>sprintf(_('User %s removed from incident.'),
            $user['email']),
         'incidentid'=>$incidentid
      ));

      reload(sprintf('%s?action=details&incidentid=%s',
         BASEURL.'/incident.php',
         urlencode($incidentid)));
      break;

   //--------------------------------------------------------------------
   case 'addcomment':
      $comment = strip_tags(fetchFrom('REQUEST','comment'));
      if (empty($comment)) {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }
      $incidentid = fetchFrom('REQUEST', 'incidentid', '%d');
      if (empty($incidentid)) {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }
      if (!is_numeric($incidentid)) {
         airt_error('PARAM_MISC', _('Incorrect parameter type in ').__LINE__);
         reload();
      }

      addIncidentComment(array(
         'comment'=>$comment,
         'incidentid'=>$incidentid,
      ));
      generateEvent('incidentcommentadd', array(
         'comment'=>$comment,
         'incidentid'=>$incidentid,
      ));

      touchIncident($incidentid);
      reload(sprintf('%s?action=details&incidentid=%d',
        BASEURL.'/incident.php',
        $incidentid));
      break;

    //--------------------------------------------------------------------
   case _('Update'):
   case _('update'):
      $incidentid = fetchFrom('SESSION','incidentid', '%d');
      if ($incidentid=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $severity = fetchFrom('POST','severity', '%d');
      $state = fetchFrom('POST','state', '%d');
      if ($state=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $status = fetchFrom('POST','status', '%d');
      if ($status=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $type = fetchFrom('POST','type', '%d');
      if ($type=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $subtype = strip_tags(trim(fetchFrom('POST', 'subtype')));

      $desc = strip_tags(trim(fetchFrom('POST', 'desc')));
      $logging = trim(fetchFrom('POST','logging'));
      $template = trim(strip_tags(fetchFrom('POST','template')));
      if ($template == -1) { $template = ''; }
      $date_day = trim(fetchFrom('POST', 'date_day', '%d'));
      $date_month = trim(fetchFrom('POST', 'date_month', '%d'));
      $date_year = trim(fetchFrom('POST', 'date_year', '%d'));
      $date_hour = trim(fetchFrom('POST', 'date_hour', '%d'));
      $date_minute = trim(fetchFrom('POST', 'date_minute', '%d'));
      $date_second = trim(fetchFrom('POST', 'date_second', '%d'));
      $date = strtotime(sprintf('%04d-%02d-%04d %02d:%02d:%02d',
         $date_year, $date_month, $date_day,
         $date_hour, $date_minute, $date_second));
      generateEvent('incidentupdate', array(
         'incidentid'=>$incidentid,
         'state'=>$state,
         'status'=>$status,
         'type'=>$type,
         'date'=>$date,
         'desc'=>$desc
      ));

      updateIncident($incidentid,array(
         'state'=>$state,
         'status'=>$status,
         'severity'=>$severity,
         'type'=>$type,
         'date'=>$date,
         'logging'=>$logging,
         'template'=>$template,
         'subtype'=> $subtype,
         'desc'=>$desc
         ));

      $SEVERITIES = getIncidentSeverities();
      addIncidentComment(array(
         'comment'=>sprintf(_(
            'Incident updated: severity=%s, state=%s, status=%s, type=%s, desc=%s'),
            $SEVERITIES[$severity],
            getIncidentStateLabelByID($state),
            getIncidentStatusLabelByID($status),
            getIncidentTypeLabelByID($type),
            $desc),
         'incidentid'=>$incidentid
      ));

      airt_msg(_('Incident updated.'));
      reload(t('%url?action=details&incidentid=%id', array(
         '%url'=>BASEURL.'/incident.php',
         '%id'=>$incidentid
      )));
      break;

   //--------------------------------------------------------------------
   case _('Update selected incidents'):
   case 'massupdate':
      // massincidents may be absent, this is how HTML checkboxes work.
      $massIncidents = fetchFrom('POST', 'massincidents');
      if ($massIncidents == '') {
         // Nothing checked, nothing to do; disregard command.
         airt_msg(_('No incidents selected to work on.'));
         reload();
      }
      $massIncidents = array_keys(array_filter($massIncidents, 'filterIsOn'));
      $massState = fetchFrom('POST', 'massstate', '%d');
      if ($massState == 'null') {
         $massState = '';
      }
      $massStatus = fetchFrom('POST', 'massstatus', '%d');
      if ($massStatus=='null') {
         $massStatus = '';
      }
      $massType = fetchFrom('POST', 'masstype', '%d');
      if ($massType=='null') {
         $massType = '';
      }

      updateIncidentList($massIncidents, array(
         'state'=>$massState,
         'status'=>$massStatus,
         'type'=>$massType));

      reload();
      break;

   //--------------------------------------------------------------------
   case _('Mail'):
      $recipients = fetchFrom('REQUEST', 'to');
      if (!is_array($recipients)) {
         $recipients = explode(',', $recipients);
      }
      foreach ($recipients as $key=>$value) {
          if (!is_numeric($value)) {
              unset($recipients[$key]);
          }
      }

      $incidentid = fetchFrom('REQUEST', 'incidentid', '%d');
      if (empty($incidentid)) {
         die(_('Invalid parameter value in ').__LINE__);
      }
      if (empty($recipients)) {
         airt_msg(_(
           'USER ERROR: Must select one or more recipients for mail.'));
         reload(BASEURL.'/incident.php?action=details&incidentid='.
            urlencode($incidentid));
         return;
      }

      $template = fetchFrom('REQUEST', 'template');
      if (empty($template)) {
          airt_msg(t(_('Missing template in %c:%l'),
          array('%c='=>'incident.php', '%l'=>__LINE__)));
          reload(BASEURL.'/incident.php?action=details&incidentid='.
             urlencode($incidentid));
          return;
      }

      // strip out all template that have no matching users
      foreach($template as $user=>$t) {
          if (array_search($user, $recipients) === FALSE) {
              unset($template[$user]);
          }
      }

      reload('mailtemplates.php?action=prepare&'.
         'template='. urlencode(implode(',', $template)).
         '&to='. urlencode(implode(',', $recipients)).
         '&incidentid='.$incidentid);
      break;

   //--------------------------------------------------------------------
   case _('Remove'):
      $incidentid = fetchFrom('REQUEST', 'incidentid', '%d');
      if (empty($incidentid)) {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
         return;
      }
      $recipients = fetchFrom('REQUEST', 'to');
      if (empty($recipients)) {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload(BASEURL.'/incident.php?action=details&incidentid='.
            urlencode($incidentid));
         return;
      }
      if (!is_array($recipients)) {
         $recipients = explode(',', $recipients);
      }
      foreach ($recipients as $key=>$value) {
          if (!is_numeric($value)) {
              unset($recipients[$key]);
          }
      }
      foreach ($recipients as $userid) {
         if (!is_numeric($userid)) {
            // should not happen
            die(_('Invalid parameter type in ').__LINE__);
         }
         $user = getUserByUserId($userid);
         removeUserFromIncident($userid, $incidentid);
         addIncidentComment(array(
            'comment'=>sprintf(_('User %s removed from incident.'),
               $user["email"]),
            'incidentid'=>$incidentid
         ));
      }
      reload($_SERVER['HTTP_REFERER']);
      break;

   //--------------------------------------------------------------------
   case 'delete_extid':
      $incidentid = fetchFrom('REQUEST', 'incidentid', '%d');
      $extid = htmlentities(fetchFrom('REQUEST', 'extid', '%s'));
      if ($incidentid == '') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
         return;
      }
      if ($extid == '') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
         return;
      }
      deleteExternalIncidentIDs($incidentid, $extid);
      reload(BASEURL.'/incident.php?action=details&incidentid='.
         urlencode($incidentid));
      break;

   //--------------------------------------------------------------------
   case _('Add external identifier'):
   case 'add_extid':
      $incidentid = fetchFrom('REQUEST', 'incidentid', '%d');
      $extid = htmlentities(trim(fetchFrom('REQUEST', 'extid', '%s')));
      if ($incidentid == '') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
         return;
      }
      if ($extid == '') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
         return;
      }
      addExternalIncidentIDs($incidentid, $extid);
      reload(BASEURL.'/incident.php?action=details&incidentid='.
         urlencode($incidentid));
      break;

   //--------------------------------------------------------------------

   case 'upload':
      $incidentid = fetchFrom('REQUEST', 'incidentid', '%d');
      defaultTo($incidentid, 0);
      if ($incidentid == 0) {
         airt_error('PARAM_FORMAT', 'incident.php:'.__LINE__);
         reload();
         break;
      }
      if (receiveUpload($incidentid, $error) == false) {
         echo "Upload failed: $error";
      } else {
         airt_msg(_('File successfully uploaded'));
         reload(t('%url?action=details&incidentid=%id', array(
            '%url' => BASEURL.'/incident.php',
            '%id' => $incidentid
         )));
      }
      break;

   case 'download':
      $id = fetchFrom('REQUEST', 'attachment', '%d');
      defaultTo($id, 0);
      if ($id == 0) {
         airt_error('PARAM_FORMAT', 'incident.php:'.__LINE__);
         reload();
         break;
      }
      if (fetchAttachment($id, $attachment, $error) == false) {
         airt_error('ERR_FUNC', $error);
         reload();
         break;
      }
      Header('Content-Type: '.$attachment['content_type']);
      Header('Content-disposition: attachment;
           filename="'.$attachment['filename'].'"');
      echo $attachment['content_body'];
      break;

   case 'rmattach':
      $id = fetchFrom('REQUEST', 'attachment', '%d');
      defaultTo($id, 0);
      if ($id == 0) {
         airt_error('PARAM_FORMAT', 'incident.php:'.__LINE__);
         reload();
         break;
      }
      if (deleteAttachment($id, $error) == false) {
         airt_error('ERR_FUNC', $error);
         reload();
         break;
      }
      airt_msg('Attachment successfully deleted.');
      reload($_SERVER['HTTP_REFERER']);
      break;

   case _('Update overrides'):
      $incidentid = fetchFrom('REQUEST', 'incidentid', '%d');
      defaultTo($incidentid, -1);

      if ($incidentid == -1) {
         airt_msg(_('Missing or invalid parameter (incidentid) in line ').
            __LINE__);
         reload();
         break;
      }

      $templates = fetchFrom('REQUEST', 'template');
      $userids=array();
      foreach (array_keys($templates) as $value) {
          if (is_numeric($value)) {
              $userids[] = $value;
          }
      }

      if (!is_array($templates)) {
         airt_msg(_('Missing or invalid parameter (template) in line ').
            __LINE__);
         reload();
         break;
      }
      foreach ($templates as $key=>$value) {
          $templates[$key] = strip_tags($value);
      }

      foreach ($userids as $userid) {
         defaultTo($userid, -1);
         $template = $templates[$userid];
         defaultTo($template, '');

         if ($userid == -1) {
            airt_msg(_('Missing or invalid parameter (userid) in line ').
               __LINE__);
            reload();
            break;
         }

         setMailtemplateOverride($incidentid, $userid, $template);
         airt_msg(_('Mail template override updated.').' ');
      }

      reload(BASEURL.'/incident.php?action=details&incidentid='.
         urlencode($incidentid));
      break;

   case 'unlink':
      $msgid = fetchFrom('REQUEST', 'msgid', '%d');
      defaultTo($msgid, -1);
      if ($msgid == -1) {
         airt_msg(_('Invalid parameter type in').' mailbox.plib:'.__LINE__);
         reload();
         return;
      }
      $incidentid = fetchFrom('REQUEST', 'incidentid', '%d');
      defaultTo($incidentid, -1);
      if ($incidentid == -1) {
         airt_msg(_('Invalid parameter type in').' mailbox.plib:'.__LINE__);
         reload();
         return;
      }
      if (removeEmailFromIncident($incidentid, $msgid, $error) == false) {
         airt_msg($error);
      }
      airt_msg(_('Message removed from incident.'));
      reload(BASEURL.'/incident.php?action=details&incidentid='.$incidentid);

      break;

   //--------------------------------------------------------------------
   default:
      die(_('Unknown action').' '.htmlentities($action));
}
