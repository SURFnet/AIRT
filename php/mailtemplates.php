<?php
/* vim: syntax=php tabstop=3 shiftwidth=3
 * $Id$ 
 * mailtemplates.php - Standard messages
 *
 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2004,2005   Tilburg University, The Netherlands

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
require_once LIBDIR."/mailtemplates.plib";

function listTemplates($recipients=array(), $incidentids=array()) {
  pageHeader(_('Available mail templates'));
  $show_prepare = true;
  print format_templates($recipients, $incidentids);
  print t('<P><a href="%url?action=new">'.
      _('Create a new message').'</a></P>'.LF,
      array('%url'=>$_SERVER['PHP_SELF']));

  pageFooter();
}

$action = strip_tags(fetchFrom('REQUEST', 'action', '%s'));
defaultTo($action, 'list');
$action = strip_tags($action);

switch ($action) {
   // -------------------------------------------------------------------
   case "list":
      /* $to is a comma-separated list of recipient email addresses, or
       * an array contain email addresses
       */
      $recipients = fetchFrom('REQUEST', 'to');
      if (empty($recipients)) {
         $recipients = array();
      }
      if (!is_array($recipients)) {
         $recipients = explode(',', $recipients);
      }

      /* $incidentids is a comma-separated list of incident ids, or
       * an array contain incident ids
       */
      $incidentids = fetchFrom('REQUEST', 'incidentids');
      if (empty($incidentids)) {
         $incidentid = fetchFrom('REQUEST', 'incidentid', '%d');
         if (empty($incidentid)) {
            $sess = fetchFrom('SESSION', 'incidentid');
	    if (empty($sess)) {
                $incidentids = array();
            } else {
	       $incidentids = array($sess);
            }
         } else {
            $incidentids = array($incidentid);
         }
      }
      if (!is_array($incidentids)) {
         $incidentids = explode(',', $incidentids);
      }
      $incidentids = array_filter($incidentids, 'is_numeric');
      listTemplates($recipients, $incidentids);
      break;

   // -------------------------------------------------------------------
   case "edit":
      $msg = '';
      $template = strip_tags(fetchFrom('REQUEST', 'template', '%s'));
      if (empty($template)) {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      pageHeader(_('Edit mail template'));

      if (($msg = get_template($template)) == false) {
         printf(_('Template not available.'));
      } else {
         print _('Update the template and press the "Save!" button to save it. The first
line of the message will be used as the subject. You may use the following
special variables in the template:').'<p>'.LF;
      print_variables_info();
      $update = array();
      get_template_actions($template, $update);
      print '<P>';
      print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">'.LF;
      // note: potential danger here; html and php tags are NOT scrubbed
      print '<textarea wrap name="message" cols="75" rows="15">'.$msg.
         '</textarea>'.LF;
      print '<P>'.LF;
      print _('Automatically change settings after mail based on this template is sent:').'<P>'.LF;
      print '<table cellpadding="3">'.LF;
      print '<tr>'.LF;
      print '   <td>'._('Type').'</td>'.LF;
      print '   <td>'.LF;
      print getIncidentTypeSelection("update[type]", $update['type'],
         array(-1=>_('Do not update')));
      print '   </td>'.LF;
      print '</tr>'.LF;
      print '<tr>'.LF;
      print '   <td>'._('Status').'</td>'.LF;
      print '   <td>'.LF;
      print getIncidentStatusSelection("update[status]", $update['status'],
         array(-1=>_('Do not update')));
      print '   </td>'.LF;
      print '</tr>'.LF;
      print '<tr>'.LF;
      print '   <td>'._('State').'</td>'.LF;
      print '   <td>'.LF;
      print getIncidentStateSelection("update[state]", $update['state'],
         array(-1=>_('Do not update')));
      print '   </td>'.LF;
      print '</tr>'.LF;
      print '</table>'.LF;
      print ''.LF;
      print ''.LF;
      print '<input type="hidden" name="action" value="save">'.LF;
      print '<input type="hidden" name="template" value="'.$template.'">'.LF;
      print '<input type="submit" value="Save">'.LF;
      print '<input type="reset" value="Cancel">'.LF;
      print '</form>'.LF;
   }
   pageFooter();

   break;

   // -------------------------------------------------------------------
   case "save":
      $template = strip_tags(fetchFrom('REQUEST', 'template', '%s'));
      if (empty($template)) {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         reload();
         return;
      }
      $message = fetchFrom('REQUEST', 'message', '%s');
      if (empty($message)) {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         reload();
         return;
      }
      $update = fetchFrom('REQUEST', 'update', '%s');
      if (empty($update)) {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         reload();
         return;
      }

      $message = strip_tags($message);
      $message = stripslashes($message);

      if (save_template($template, $message, $update)) {
         airt_error('ERR_FUNC', 'mailtemplates.php:'.__LINE__);
      }
      listTemplates();

      break;

   // -------------------------------------------------------------------
   case "new":

      pageHeader(_('New mail template'));
      print _('Enter your new template in the text field below. Use the following variables in your text body:');
      print '<P>'.LF;
      $update = array('state'=>-1, 'status'=>-1, 'type'=>-1);
      print_variables_info();
      print '<P>'.LF;
      print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">'.LF;
      print _('Template name').': <input type="text" size="40" name="template">'.LF;
      print '<P>'.LF;
      print _('Message').':<BR>'.LF;
      print '<textarea wrap name="message" cols="75" rows="20"></textarea>'.LF;
      print '<P>'._('Automatically change settings after mail based on this template is sent:').'<P>'.LF;
      print '<table cellpadding="3">'.LF;
      print '<tr>'.LF;
      print '   <td>'._('Type').'</td>'.LF;
      print '   <td>'.LF;
      print getIncidentTypeSelection("update[type]", $update['type'],
         array(-1=>_('Do not update')));
      print '   </td>'.LF;
      print '</tr>'.LF;
      print '<tr>'.LF;
      print '   <td>'._('Status').'</td>'.LF;
      print '   <td>'.LF;
      print getIncidentStatusSelection("update[status]", $update['status'],
         array(-1=>_('Do not update')));
      print '   </td>'.LF;
      print '</tr>'.LF;
      print '<tr>'.LF;
      print '   <td>'._('State').'</td>'.LF;
      print '   <td>'.LF;
      print getIncidentStateSelection("update[state]", $update['state'],
         array(-1=>_('Do not update')));
      print '   </td>'.LF;
      print '</tr>'.LF;
      print '</table>'.LF;
      print '<input type="hidden" name="action" value="save">'.LF;
      print '<input type="submit" value="'._('Save!').'">'.LF;
      print '<input type="reset" value="'._('Cancel!').'">'.LF;
      print '</form>'.LF;

      break;

   // -------------------------------------------------------------------
  case "delete":
     $template = strip_tags(fetchFrom('REQUEST', 'template', '%s'));
     if (empty($template)) {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         reload();
         return;
      }
      if (delete_template($template)) {
         airt_error('ERR_FUNC', 'mailtemplates.php'.__LINE__);
      }
      listTemplates();

      break;

   // -------------------------------------------------------------------
   case 'prepare':
      $template = strip_tags(fetchFrom('REQUEST', 'template'));
      if (empty($template)) {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         reload();
         return;
      }

      /* $incidentids will contain a comma-separated list of incident ids to
       * work on
       */
      $incidentids = strip_tags(fetchFrom('REQUEST', 'incidentids'));
      if (empty($incidentids)) {
         $incidentid = fetchFrom('REQUEST', 'incidentid', '%d');
         if (empty($incidentid)) {
	         $incidentid = $_SESSION['incidentid'];
	         if(empty($incidentid)) {
               die(_('No incident to work on.'));
            }
         }
         $incidentids = array($incidentid);
      } else {
         $incidentids = explode(',', $incidentids);
      }

      /* to contains the user ids of the recipient email addresss.
       */
      $to = fetchFrom('REQUEST', 'to');
      if (empty($to)) {
         $to = array();
      } else {
         $to = explode(',', $to);
      }
      $to = array_filter($to, 'is_numeric');
      prepare_message($template, $incidentids, $to);
      pageFooter();

      break;

   // -------------------------------------------------------------------
   case _('Skip and prepare next'):
      $incidentids = array_filter(explode(',', fetchFrom('POST', 'incidentids')), 'is_numeric');
      $template = strip_tags(fetchFrom('POST', 'template'));
      reload("$_SERVER[PHP_SELF]?action=prepare&template=".
         urlencode($template)."&incidentids=".implode(',',$incidentids));
      break;


   // -------------------------------------------------------------------
   case 'send':
   case _('Send'):
   case _('Send and prepare next'):
      $from = fetchFrom('POST', 'from');
      if (empty($from)) {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         reload();
         return;
      }
      $to = strip_tags(fetchFrom('POST', 'to'));
      if (empty($to)) {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         airt_error(_('No receiver specified (to: field is missing'));
         reload();
         return;
      }
      $subject = strip_tags(fetchFrom('POST', 'subject'));
      if (empty($subject)) {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         reload();
         return;
      }
      $msg = fetchFrom('POST', 'msg');
      if (empty($msg)) {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         reload();
         return;
      }
      $sign = strip_tags(fetchFrom('POST', 'sign'));
      if (empty($sign)) {
         $sign = 'off';
      }
      $incidentid = fetchFrom('POST', 'incidentid', '%d');
      if (empty($incidentid)) {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         reload();
         return;
      }

      $incidentids = fetchFrom('POST', 'incidentids');
      // do not strip_tags here; it will break the email address
      $replyto = fetchFrom('POST', 'replyto');
      defaultTo($replyTo, '');
      $template = strip_tags(fetchFrom('POST', 'template'));

      /* prevent sending bogus stuff */
      if (trim($to) == '') {
         die(_('Empty recipient?'));
      }
      if (trim($msg) == '') {
         die(_('Empty message body?'));
      }

      /* clean off html and stuff (only unformatted mail) */
      $msg = stripslashes($msg);
      $msg = str_replace("\r", '', $msg);

      /* prepare the intial state of the message */
      $hdrs = array(
         'From'     => $from,
         'Subject'  => $subject,
         'To'       => $to,
         'X-Mailer' => 'AIRT $Revision$ http://www.airt.nl',
         'MIME-Version' => '1.0',
      );
      if ($replyto != '') {
          $hdrs['Reply-To'] = $replyto;
      }

      /* set up mail recipient */
      if (defined('MAILCC')) {
         $mailto = array($to, MAILCC);
         $hdrs["Cc"] = MAILCC;
      } else {
         $mailto = array($to);
      }

      /* set up envelope sender */
      $envfrom="-f".MAILENVFROM;

      /* will send via Mail class */
      $mail_params = array(
         'sendmail_args' => $envfrom
      );

      $msg_params = array();
      $msg_params['content_type'] = 'multipart/mixed';
      $msg_params['disposition'] = 'inline';


      $attachcount=0;

      if ($sign == 'off') {
         $msg_params['content_type'] = 'text/plain';
         unset($msg_params['disposition']);
         $mime = new Mail_mimePart($msg, $msg_params);
         $m = $mime->encode();
         $body = $m['body'];
      } else {
         // pgp signed messages are described in RFC 2015
         $msg_params['content_type'] = 'multipart/signed; micalg=pgp-sha1; protocol="application/pgp-signature"';
         $mime = new Mail_mimePart('This is an OpenPGP/MIME signed message (RFC2440 and 3156)', $msg_params);

         // MIME encoding requires CR/LF; see RFC2015
         $msg = explode("\n",$msg);
         $msg = implode("\r\n",$msg);

         $body_params = array();
         $body_params['content_type'] = 'text/plain';
         $body_params['disposition'] = 'inline';
         $body_params['charset'] = 'ISO-8859-1';
         $mime->addsubpart($msg, $body_params);

         /* message signature */
         $sig_params = array();
         $sig_params['content_type'] = 'application/pgp-signature';
         $sig_params['description'] = _('Digital Signature');
         $mime->addsubpart('@AIRT-SIGNATURE@', $sig_params);
         $m = $mime->encode();

         // now generate the signature and replace placeholder
         // 1. Extract the delimiter string
         $disp = explode(';', $m['headers']['Content-Type']);
         if (preg_match('/="(.*)"$/', $disp[3], $match) > 0) {
            $delimiter = $match[1];
         } else {
            $delimiter = 'XXX'; // this means MIME is broken!
         }

         // 2. Extract the main body part
         $body = $m['body'];
         $msg = split('--'.$delimiter, $body);
         $msgbody = implode("\r\n", array_slice(explode("\r\n", $msg[1]), 1));
         $msgbody = substr($msgbody, 0, -1);

         // 3. Sign the main body part and capture the signature
         // create mime-body and remove delimiting lines. RFC 2015 requires
         // that the message is signed, including its MIME headers

         /* write msg to temp file */
         $fname = tempnam('/tmp', 'airt_');
         $f = fopen($fname, 'w');
         fwrite($f, $msgbody, strlen($msgbody));
         fclose($f);

         // 4. update the footer

         /* invoke gpg */
         $cmd = sprintf("%s %s --homedir %s --default-key %s %s",
            GPG_BIN, GPG_OPTIONS, GPG_HOMEDIR, GPG_KEYID, $fname);
         exec($cmd);
         if (($sig = file_get_contents("$fname.asc")) == false) {
            die(_('Unable to read signed message.'));
         }

         /* clean up */
         /*
         unlink($fname);
         unlink("$fname.asc");
         */
         $body = preg_replace("/@AIRT-SIGNATURE@/", $sig, $body);
      }

      $mail = &Mail::factory('smtp', $mail_params);
      $hdrs = array_merge($hdrs, $m['headers']);
      if (! $mail->send($mailto, $hdrs, $body)) {
         die(_("Error sending message!"));
      }

      addIncidentComment(array(
         'comment'=>sprintf(_("Email sent to %s: %s"),
            $to, $subject),
         'incidentid'=>$incidentid));
      generateEvent('postsendmail', array(
         'incidentid'=>$incidentid,
         'sender'=>htmlentities($from),
         'recipient'=>$to,
         'subject'=>$subject));

      /* check for default actions on template */
      $actions = array();
      if ($template == _('Use preferred template')) {
         $t = getPreferredMailTemplateName($incidentid);
      } else {
         $t = $template;
      }

      if (!empty($t) && get_template_actions($t, $actions)) {
         if ($actions['type'] == -1) {
            $actions['type'] = '';
         } else {
            addIncidentComment(array(
               'comment'=>sprintf(_('Type updated to %s'),
                  getIncidentTypeLabelByID($actions['type'])),
               'incidentid'=>$incidentid));
         }
         if ($actions['status'] == -1) {
            $actions['status'] = '';
         } else {
            addIncidentComment(array(
               'comment'=>sprintf(_('Status updated to %s'),
                  getIncidentStatusLabelByID($actions['status'])),
               'incidentid'=>$incidentid));
         }
         if ($actions['state'] == -1) {
            $actions['state'] = '';
         } else {
            addIncidentComment(array(
               'comment'=>sprintf(_('State updated to %s'),
                  getIncidentStateLabelByID($actions['state'])),
               'incidentid'=>$incidentid));
         }
         $incident = getIncident($incidentid);
         $actions['template'] = $incident['template'];
         $actions['desc'] = $incident['desc'];
         updateIncident($incidentid, $actions);
      }
      if ($action == _('Send and prepare next') && isset($incidentids) &&
         isset($template)) {
         reload("$_SERVER[PHP_SELF]?action=prepare&template=$template&incidentids=".
            urlencode($incidentids));
      } else {
         reload('incident.php');
      }

      break;

   // -------------------------------------------------------------------
   default:
      die(_('Unknown action: '. $action));
} // switch
?>
