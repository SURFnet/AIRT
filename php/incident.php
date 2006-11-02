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
 * $Id$
 */

require_once 'config.plib';
require_once LIBDIR.'/airt.plib';
require_once LIBDIR.'/database.plib';
require_once LIBDIR.'/constituency.plib';
require_once LIBDIR.'/incident.plib';
require_once LIBDIR.'/history.plib';
require_once LIBDIR.'/user.plib';
require_once LIBDIR.'/mailtemplates.plib';


$action = fetchFrom('REQUEST','action');
defaultTo($action,'list');

switch ($action) {

  //--------------------------------------------------------------------
  case _('Prepare'):
     // Send bulk mail for the selected incidents.
     $massincidents = fetchFrom('REQUEST','massincidents[]');
     if (empty($massincidents)) {
        // Nothing selected, show list again.
        reload();
     }
     $agenda = implode(',', $massincidents);

     $template = fetchFrom('REQUEST','template');
     defaultTo($template,_('Do not send mail'));
     if ($template==_('Do not send mail')) {
        // No template selected, show list again.
        reload();
     }

     reload("mailtemplates.php?action=prepare&".
               "template=$template&agenda=$agenda");
     break;

  //--------------------------------------------------------------------
  case _('Show Details'):
  case 'details':
    $incidentid = fetchFrom('REQUEST','incidentid');
    if ($incidentid=='') {
       airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
       reload();
    }

    print updateCheckboxes();

    /* Prevent cross site scripting in incidentid. */
# TODO This thing still does not cope well with non-integers. It breaks some
# SQL statement down the line.
    $norm_incidentid = normalize_incidentid($incidentid);
    $incidentid = decode_incidentid($norm_incidentid);
    if (!getIncident($incidentid)) {
      pageHeader(_('Invalid incident'));
      printf(_('Requested incident (%s) does not exist.'),
             $norm_incidentid);
      pageFooter();
      exit;
    }
    $_SESSION['incidentid'] = $incidentid;

    pageHeader(_('Incident details: ').$norm_incidentid);
    $output = '<div class="externalids" width="100%">';
    $output .= t('(<a href="%url?action=edit_extid&'.
                 'incidentid=%incidentid">'._('Edit').'</a>) ',
            array(
       '%url'=>$_SERVER['PHP_SELF'], '%incidentid'=>urlencode($incidentid)));
    $output .= _('External identifiers').': ';
	 $e = array();
	 foreach (getExternalIncidentIDs($incidentid) as $i) {
	    if (substr($i, 0, 1) != '_') {
		    $e[] = $i;
		 }
	 }
    $output .= implode(',', $e);
    $output .= '</div><!-- externalids -->'.LF;
    $output .= '<div class="tickets" width="100%">'.LF;
    $output .= t('(<a href="%url?action=edit_ticket&incidentid=%incidentid">'.
      _('Edit').'</a>) ', array('%url'=>$_SERVER['PHP_SELF'], 
      '%incidentid'=>$incidentid));
    $output .= _('Ticket number(s)').': ';
    foreach (getTicketNumbers($incidentid) as $tn) {
       $out = array();
# TODO: make base url configurable
putenv('PERL5LIB=/opt/otrs');
       $cmd = LIBDIR.'/otrs/tn-redirect.pl http://192.168.81.129/otrs '.$tn;
       $out = exec($cmd, $out, $res);
       $output .= t('<a href="%url">%tn</a>&nbsp; ', array('%url'=>$out,
          '%tn'=>$tn));
    }
    $output .= '</div><!-- tickets -->'.LF;
    $output .= formatEditForm();
    $output .= '<hr/>'.LF;
    $output .= '<h3>'._('History').'</h3>'.LF;
    generateEvent('historyshowpre', array('incidentid'=>$incidentid));
    $output .= formatIncidentHistory($incidentid);
    generateEvent('historyshowpost', array('incidentid'=>$incidentid));

    $output .= '<p/>'.LF;
    $output .= '<form action="'.$_SERVER['PHP_SELF'].'" method="post">'.LF;
    $output .= '<input type="hidden" name="action" value="addcomment">'.LF;
    $output .= '<table bgcolor="#DDDDDD" border=0 cellpadding=2>'.LF;
    $output .= '<tr>'.LF;
    $output .= '  <td>'._('New comment').': </td>'.LF;
    $output .= '  <td><input type="text" size=45 name="comment"></td>'.LF;
    $output .= '  <td><input type="submit" value="'._('Add').'"></td>'.LF;
    $output .= '</tr>'.LF;
    $output .= '</table>'.LF;
    $output .= '</form>'.LF;

    print $output;
    break;

    //---------------------------------------------------------------
    case _('Create New Incident'):
    case _('New incident'):
    case 'new':
      PageHeader(_('New Incident'));
      $check = false;
      $output = updateCheckboxes();
      $output .= '<form name="jsform" action="'.$_SERVER['PHP_SELF'].
                 '" method="POST">'.LF;

      $output .= formatIncidentForm($check);

      $output .= '<input type="submit" name="action" value="'._('Add').'">'.LF;
      $output .= t('<input type="checkbox" name="sendmail" %checked>'.LF,
         array('%checked'=>($check==false)?'':'CHECKED'));
      $output .= _('Check to prepare mail.').LF;
      $output .= '</form>'.LF;
      print $output;
      break;
    //--------------------------------------------------------------------
    case _('Create Many Incidents'):
      PageHeader("Bulk incidents");
      $check = false;
      $output = updateCheckboxes();
      $output .= "<form name=\"jsform\" action=\"$_SERVER[PHP_SELF]\" method=\"POST\">\n";

      $output .= formatIncidentBulkForm($check);

      $output .= "<input type=\"submit\" name=\"action\" value=\"Addbulk\">\n";

      $output .= "</form>\n";
      print $output;
      break;


    //---------------------------------------------------------------------
    case "Addbulk":
      $addresses = $constituency = $type = $state = $status = $email =
		   $addressrole = $logging = '';

      if (array_key_exists("addressrole", $_POST)) {
         $addressrole=$_POST["addressrole"];
      }

      if (array_key_exists("constituency", $_POST)) {
        $constituency=$_POST["constituency"];
      }
      if (array_key_exists("type", $_POST)) {
        $type=$_POST["type"];
      }
      if (array_key_exists("state", $_POST)) {
        $state=$_POST["state"];
      }
      if (array_key_exists("status", $_POST)) {
        $status=$_POST["status"];
      }
      if (array_key_exists("logging", $_POST)) {
        $logging=trim($_POST["logging"]);
      }
      $date_day = trim(fetchFrom('POST', 'date_day', '%d'));
      $date_month = trim(fetchFrom('POST', 'date_month', '%d'));
      $date_year = trim(fetchFrom('POST', 'date_year', '%d'));
      $date_hour = trim(fetchFrom('POST', 'date_hour', '%d'));
      $date_minute = trim(fetchFrom('POST', 'date_minute', '%d'));
      $date_second = trim(fetchFrom('POST', 'date_second', '%d'));
      $date = strtotime(sprintf('%04d-%02d-%04d %02d:%02d:%02d',
         $date_year, $date_month, $date_day,
         $date_hour, $date_minute, $date_second));

      if (array_key_exists("addresses", $_POST)) {
         $addresses=$_POST["addresses"];      
         $addresslist = split("\r?\n",$addresses);

         foreach($addresslist as $address) {

            // make sure we have an IP address here
            $address = @gethostbyname($address);
            if($address) {
               $incidentid = createIncident($state,$status,$type,$date,$logging);
               if ($address!='') {
                  addIPtoIncident($address,$incidentid,$addressrole);
               }
	    }
         }
      }
      reload();
      break;

    //---------------------------------------------------------------------
    case "Add":
      // Create a new incident from edit form.
      $address = $constituency = $type = $state = $status = $email =
		  $addressrole = $logging = '';
      $addifmissing = $sendmail = 'off';

      $addressrole  = fetchFrom('POST','addressrole');
      $address      = fetchFrom('POST','address');
      $address = @gethostbyname($address);
      $constituency = fetchFrom('POST','constituency');
      $type         = fetchFrom('POST','type');
      $state        = fetchFrom('POST','state');
      $status       = fetchFrom('POST','status');
      $sendmail     = fetchFrom('POST','sendmail');
      $email        = fetchFrom('POST','email');
      if ($email!='') {
# NOTE: this may be a list of addresss; looks not good.
         $_SESSION['current_email'] = trim(strtolower($email));
      }
      $addif        = fetchFrom('POST','addifmissing');
      $logging      = fetchFrom('POST','logging');

      $date_day = trim(fetchFrom('POST', 'date_day', '%d'));
      $date_month = trim(fetchFrom('POST', 'date_month', '%d'));
      $date_year = trim(fetchFrom('POST', 'date_year', '%d'));
      $date_hour = trim(fetchFrom('POST', 'date_hour', '%d'));
      $date_minute = trim(fetchFrom('POST', 'date_minute', '%d'));
      $date_second = trim(fetchFrom('POST', 'date_second', '%d'));
      $date = strtotime(sprintf('%04d-%02d-%04d %02d:%02d:%02d',
         $date_year, $date_month, $date_day,
         $date_hour, $date_minute, $date_second));

      $incidentid   = createIncident($state,$status,$type,$date,$logging);
      addIPtoIncident($address,$incidentid,$addressrole);

      if ($email != '') {
         foreach (explode(',', $email) as $addr) {
            $addr = trim($addr);
            $user = getUserByEmail($addr);
            if (!$user) {
               if ($addif == 'on') {
                  addUser(array('email'=>$addr));
                  $user = getUserByEmail($addr);
                  addUserToIncident($user['id'], $incidentid);
               } else {
                  pageHeader(_('Unable to add user to incident.'));
                  printf('<p>'.LF.
_('The e-mail address specified in the incident data entry form (%s) is unknown
to AIRT and you chose not to add it to the database.').'</p>'.LF.'<p>'.
_('The incident has been created, however no users have been associated with
it.').'</p>'.LF.'<p><a href="'.$_SERVER['PHP_SELF'].'">'.
_('Continue').'...</a>'.LF,
                     $addr);
                  pageFooter();
                  exit;
               }
           } else addUserToIncident($user['id'], $incidentid);
         }
      }

      if ($sendmail == 'on') {
         reload('mailtemplates.php');
      } else {
         reload();
      }

   //--------------------------------------------------------------------
   case 'toggle':
      // Flip the column of check boxes.
      $toggle = fetchFrom('REQUEST','toggle');
      defaultTo($toggle,0);
      $toggle = ($toggle == 0) ? 1 : 0;
      // Break omitted on purpose.

    //--------------------------------------------------------------------
    case 'list':
      pageHeader(_('Incident Overview'));
      print updateCheckboxes();

      generateEvent('incidentlistpre');
      print formatListOverviewHeader();
      print '<hr/>';
      print formatListOverviewBody();
      print '<hr/>';
      print formatListOverviewFooter();

      generateEvent('incidentlistpost');
      pageFooter();
      break;

   //--------------------------------------------------------------------
   case 'addip':
      $incidentid = fetchFrom('SESSION','incidentid');
      if ($incidentid=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $ip = fetchFrom('POST','ip');
      if ($ip=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }
      $ip = gethostbyname($ip);

      $addressrole = fetchFrom('POST','addressrole');
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
         addIpToIncident(trim($ip), $incidentid, $addressrole);
         addIncidentComment(t(
           _('IP address %ip added to incident with role %role'),
           array(
            '%ip'=>$ip,
            '%role'=>getAddressRoleByID($addressrole))
         ));
      }

      reload(sprintf('%s?action=details&incidentid=%s',
         $_SERVER['PHP_SELF'],
         urlencode($incidentid)));
      break;

    //--------------------------------------------------------------------
   case 'editip':
      $incidentid = fetchFrom('SESSION','incidentid');
      if ($incidentid=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $ip = fetchFrom('REQUEST','ip');
      if ($ip=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      pageHeader(_('IP address details'));
      printf(editIPform($incidentid,$ip));
      pageFooter();
      break;

    //--------------------------------------------------------------------
   case 'updateip':
      $id = fetchFrom('POST','id');
      if ($id=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $constituency = fetchFrom('POST','constituency');
      if ($constituency=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $ip = fetchFrom('POST','ip');
      if ($ip=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $incidentid = fetchFrom('POST','incidentid');
      if ($incidentid=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $addressrole = fetchFrom('POST','addressrole');
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
      addIncidentComment(t(
  _('Details of IP address %ip updated; const=%const, addressrole=%role'),
         array(
           '%ip'=>$ip,
           '%const'=>$constLabel,
           '%role'=>$addressRoleLabel)
      ));
      generateEvent('updateipdetails', array(
         'incidentid' => $incidentid,
         'ip'         => $ip,
         'constituency' => $constituency,
         'addressrole' => $addressrole
      ));

      reload(sprintf('%s?action=details&incidentid=%s',
         $_SERVER['PHP_SELF'],
         urlencode($incidentid)));

    //--------------------------------------------------------------------
   case 'deleteip':
      $incidentid = fetchFrom('SESSION','incidentid');
      if ($incidentid=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $ip = fetchFrom('GET','ip');
      if ($ip=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $addressrole = fetchFrom('GET','addressrole');
      if ($addressrole=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      removeIpFromIncident($ip, $incidentid, $addressrole);
      addIncidentComment(t(
         _('IP address %address (%role) removed from incident.'),
         array(
            '%address'=>$ip,
            '%role'=>getAddressRolebyID($addressrole)
         )));

      generateEvent('removeipfromincident', array(
         'incidentid' => $incidentid,
         'ip'         => $ip,
         'addressrole'=> $addressrole
      ));
      reload(sprintf('%s?action=details&incidentid=%s',
         $_SERVER['PHP_SELF'],
         urlencode($incidentid)));
      break;

    //--------------------------------------------------------------------
   case 'adduser':
      $email = fetchFrom('REQUEST','email');
      if ($email=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $add = fetchFrom('REQUEST','addifmissing');
      defaultTo($add,'off');

      $incidentid = fetchFrom('SESSION','incidentid');
      if ($incidentid=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $id = getUserByEmail($email);
      if (!$id) {
         if ($add == 'on') {
            addUser(array('email'=>$email));
            $id = getUserByEmail($email);
         } else {
            printf(_('Unknown email address. User not added.'));
            exit();
         }
      }

      $user = getUserByUserID($id['id']);
      addUserToIncident($id['id'], $incidentid);
      addIncidentComment(sprintf(_('User %s added to incident.'),
                                 $user['email']));

      reload(sprintf('%s?action=details&incidentid=%s',
         $_SERVER['PHP_SELF'],
         urlencode($incidentid)));

      break;
   //--------------------------------------------------------------------
   case 'deluser':
      $incidentid = fetchFrom('SESSION','incidentid');
      if ($incidentid=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $userid = fetchFrom('GET','userid');
      if ($userid=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      removeUserFromIncident($userid, $incidentid);
      $user = getUserByUserID($userid);
      addIncidentComment(sprintf(_('User %s removed from incident.'), 
         $user['email']));

      reload(sprintf('%s?action=details&incidentid=%s',
         $_SERVER['PHP_SELF'],
         urlencode($incidentid)));
      break;

   //--------------------------------------------------------------------
   case 'addcomment':
      $comment = fetchFrom('REQUEST','comment');
      if ($comment=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      addIncidentComment($comment);
      generateEvent('incidentcommentadd', array(
         'comment'=>$comment,
         'incidentid'=>$_SESSION['incidentid']
      ));

      reload(sprintf('%s?action=details&incidentid=%s',
        $_SERVER['PHP_SELF'],
        $_SESSION['incidentid']));
      break;

    //--------------------------------------------------------------------
   case 'Update':
   case 'update':
      $incidentid = fetchFrom('SESSION','incidentid');
      if ($incidentid=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $state = fetchFrom('POST','state');
      if ($state=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $status = fetchFrom('POST','status');
      if ($status=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $type = fetchFrom('POST','type');
      if ($type=='') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
      }

      $logging = trim(fetchFrom('POST','logging'));
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
         'incidentid' => $incidentid,
         'state' => $state,
         'status' => $status,
         'type' => $type,
         'date' => $date
      ));

      updateIncident($incidentid,$state,$status,$type,$date,$logging);
		/* attempt to close corresponding OTRS tickets, if any*/
		if (getIncidentStatusLabelByID($status) == 'closed') {
			foreach (getTicketNumbers($incidentid) as $tn) {
				putenv('PERL5LIB=/opt/otrs');
				$cmd = LIBDIR.'/otrs/ticketclose.pl '.$tn;
				$out = exec($cmd, $out, $res);
				if ($res == 0) {
					addIncidentComment(_('Closed OTRS ticket ').$tn);
				} else {
				   addIncidentComment(_('Failed to close OTRS ticket ').$tn);
					echo "<PRE>Cmd: $cmd\n";
					echo "Error code: $res";;
					echo "</PRE>";
				}
			}
		}

      addIncidentComment(sprintf(_(
        'Incident updated: state=%s, status=%s type=%s'), 
         getIncidentStateLabelByID($state),
         getIncidentStatusLabelByID($status),
         getIncidentTypeLabelByID($type)));

      reload();
      break;

    //--------------------------------------------------------------------
   case 'showstates':
      generateEvent('pageHeader',
         array('title' => _('Available incident states')));
      $res = db_query('SELECT label, descr
         FROM   incident_states
         ORDER BY label')
      or die(_('Unable to query incident states.'));
      $output = '<script language="JavaScript">'.LF;
      $output .= 'window.resizeTo(800,500);'.LF;
      $output .= '</script>'.LF;
      $output .= '<table>'.LF;
      while ($row = db_fetch_next($res)) {
         $output .= '<tr>'.LF;
         $output .= '  <td>'.strip_tags($row[label]).'</td>'.LF;
         $output .= '  <td>'.strip_tags($row[descr]).'</td>'.LF;
         $output .= '</tr>'.LF;
      }
      $output .= '</table>'.LF;
      print $output;
      break;

   //--------------------------------------------------------------------
   case 'showtypes':
      generateEvent('pageHeader',
         array('title' => _('Available incident types')));
      $res = db_query('SELECT label, descr
         FROM   incident_types
         ORDER BY label')
      or die(_('Unable to query incident types.'));
      $output = '<script language="JavaScript">'.LF;
      $output .= 'window.resizeTo(800,500);'.LF;
      $output .= '</script>';
      $output .= '<table>'.LF;
      while ($row = db_fetch_next($res)) {
         $output .= '<tr>'.LF;
         $output .= '  <td>'.strip_chars($row[label]).'</td>'.LF;
         $output .= '  <td>'.strip_chars($row[descr]).'</td>'.LF;
         $output .= '</tr>'.LF;
      }
      $output .= '</table>'.LF;
      print $output;
      break;

    //--------------------------------------------------------------------
   case 'showstatus':
      generateEvent('pageHeader',
         array('title' => _('Available incident statuses')));

      $res = db_query('
         SELECT label, descr
         FROM   incident_status
         ORDER BY label')
      or die(_('Unable to query incident statuses.'));
      $output = '<script language="JavaScript">'.LF;
      $output .= 'window.resizeTo(800,500);'.LF;
      $output .= '</script>'.LF;
      $output .= '<table>'.LF;
      while ($row = db_fetch_next($res)) {
         $output .= '<tr>\n';
         $output .= '  <td>'.strip_chars($row[label]).'</td>'.LF;
         $output .= '  <td>'.strip_chars($row[descr]).'</td>'.LF;
         $output .= '</tr>'.LF;
      }
      $output .= '</table>'.LF;
      print $output;
      break;

   //--------------------------------------------------------------------
   case 'massupdate':
      // massincidents may be absent, this is how HTML checkboxes work.
      $massIncidents = fetchFrom('POST', 'massincidents');
      if ($massIncidents == '') {
         // Nothing checked, nothing to do; disregard command.
         Header("Location: $_SERVER[PHP_SELF]");
      }
      $massState = fetchFrom('POST', 'massstate');
      if ($massState == 'null') {
         $massState = '';
      }
      $massStatus = fetchFrom('POST', 'massstatus');
      if ($massStatus=='null') {
         $massStatus = '';
      }
      $massType = fetchFrom('POST', 'masstype');
      if ($massType=='null') {
         $massType = '';
      }

      updateIncidentList($massIncidents,$massState,$massStatus,$massType);

      Header("Location: $_SERVER[PHP_SELF]");
      break;

   //--------------------------------------------------------------------
   case 'Mail':
      $agenda = fetchFrom('REQUEST', 'agenda');
      if ($agenda == '') {
         airt_msg(_(
           'USER ERROR: Must select one or more recipients for mail.'));
         Header("Location: $_SERVER[PHP_SELF]?action=details&incidentid=$_SESSION[incidentid]");
         return;
      }
      Header("Location: mailtemplates.php?to=".urlencode(implode(',',$agenda)));
      break;

   //--------------------------------------------------------------------
   case 'Remove':
      $incidentid = fetchFrom('REQUEST', 'incidentid');
      $agenda = fetchFrom('REQUEST', 'agenda');
      if ($incidentid == '') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      if ($agenda == '') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      foreach ($agenda as $userid) {
         $user = getUserByUserId($userid);
         removeUserFromIncident($userid, $incidentid);
         addIncidentComment(sprintf(_('User %s removed from incident.'), 
            $user["email"]));
      }
      Header("Location: $_SERVER[HTTP_REFERER]");
      break;

   //--------------------------------------------------------------------
   case 'edit_extid':
      $incidentid = fetchFrom('REQUEST', 'incidentid');
      if ($incidentid == '') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      formatEditExternalids($incidentid);
      break;

   //--------------------------------------------------------------------
   case 'delete_extid':
      $incidentid = fetchFrom('REQUEST', 'incidentid');
      $extid = fetchFrom('REQUEST', 'extid');
      if ($incidentid == '') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      if ($extid == '') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      deleteExternalIncidentIDs($incidentid, $extid);
      Header("Location: $_SERVER[PHP_SELF]?action=edit_extid&incidentid=".
         urlencode($incidentid));
      break;

   //--------------------------------------------------------------------
   case 'Add external identifier':
   case 'add_extid':
      $incidentid = fetchFrom('REQUEST', 'incidentid');
      $extid = fetchFrom('REQUEST', 'extid');
      if ($incidentid == '') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      if ($extid == '') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      addExternalIncidentIDs($incidentid, $extid);
      Header("Location: $_SERVER[PHP_SELF]?action=edit_extid&incidentid=".
         urlencode($incidentid));
      break;

   //--------------------------------------------------------------------
   case 'edit_ticket':
      $incidentid = fetchFrom('REQUEST', 'incidentid', '%d');
      if ($incidentid == '') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         reload();
         return;
      }
      print formatEditTicket($incidentid);
      break;

   //--------------------------------------------------------------------
   case 'delete_tn':
      $incidentid = fetchFrom('REQUEST', 'incidentid');
      $tn = fetchFrom('REQUEST', 'tn');
      if ($incidentid == '') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      if ($tn == '') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      deleteExternalIncidentIDs($incidentid, '_OTRS'.$tn);
      Header("Location: $_SERVER[PHP_SELF]?action=edit_ticket&incidentid=".
         urlencode($incidentid));
      break;

   //--------------------------------------------------------------------
   case _('Add ticket number'):
   case 'add_tn':
      $incidentid = fetchFrom('REQUEST', 'incidentid');
      $tn = fetchFrom('REQUEST', 'tn');
      if ($incidentid == '') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      if ($tn == '') {
         airt_error('PARAM_MISSING', 'incident.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      addExternalIncidentIDs($incidentid, '_OTRS'.$tn);
      Header("Location: $_SERVER[PHP_SELF]?action=edit_ticket&incidentid=".
         urlencode($incidentid));
      break;

   //--------------------------------------------------------------------
   default:
      die(_('Unknown action'));
}
?>
