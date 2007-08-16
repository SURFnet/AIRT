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

if (array_key_exists('action', $_REQUEST)) {
   $action=$_REQUEST['action'];
} else {
   $action = "list";
}

function listTemplates() {
  pageHeader(_('Available mail templates'));

   print format_templates();
   print t('<P><a href="%url?action=new">'.
      _('Create a new message').'</a></P>'.LF,
      array('%url'=>$_SERVER['PHP_SELF']));
   // If a current_email parameter has been passed along, put it in the
   // session for later use by "prepare".
   if (array_key_exists('current_email', $_REQUEST)) {
      $_SESSION['current_email'] = $_REQUEST['current_email'];
   }

   pageFooter();
}

switch ($action) {
   // -------------------------------------------------------------------
   case "list":
      listTemplates();
      break;

   // -------------------------------------------------------------------
   case "edit":
      $msg = '';
      if (array_key_exists("template", $_REQUEST)) {
         $template=$_REQUEST["template"];
      } else {
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
      print '<textarea wrap name="message" cols=75 rows=20>'.$msg.'</textarea>'.LF;
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
      if (array_key_exists("template", $_REQUEST)) {
         $template=$_REQUEST["template"];
      } else {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      if (array_key_exists("message", $_REQUEST)) {
            $message=$_REQUEST["message"];
      } else {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      if (array_key_exists("update", $_REQUEST)) {
            $update=$_REQUEST["update"];
      } else {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
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
      print '<textarea wrap name="message" cols=75 rows=20></textarea>'.LF;
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
      if (array_key_exists("template", $_REQUEST)) {
         $template=$_REQUEST["template"];
      } else {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      if (delete_template($template)) {
         airt_error('ERR_FUNC', 'mailtemplates.php'.__LINE__);
      }
      listTemplates();

      break;

   // -------------------------------------------------------------------
   case 'prepare':
      $template = fetchFrom('REQUEST', 'template');
      if (empty($template)) {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         reload();
         return;
      }
      if (array_key_exists('agenda', $_REQUEST)) {
         $agenda = explode(',',$_REQUEST['agenda']);
      } elseif (array_key_exists('incidentid', $_REQUEST)) {
         $agenda = array($_REQUEST['incidentid']);
      } else {
         if (array_key_exists('incidentid', $_SESSION)) {
            $agenda = array($_SESSION['incidentid']);
         } else {
            echo "No active incident.";
            break;
         }
      }
      if (array_key_exists('to', $_REQUEST)) {
         $to = explode(',',$_REQUEST['to']);
      } else {
         $to = array();
      }
      prepare_message($template, $agenda, $to);
      pageFooter();

      break;

   // -------------------------------------------------------------------
   case _('Skip and prepare next'):
      if (array_key_exists('agenda', $_POST)) {
         $agenda = $_POST['agenda'];
      }
      if (array_key_exists('template', $_POST)) {
         $template = $_POST['template'];
      }
      Header("Location: $_SERVER[PHP_SELF]?action=prepare&template=".
         urlencode($template)."&agenda=".urlencode($agenda));
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
      $to = fetchFrom('POST', 'to');
      if (empty($to)) {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         airt_error(_('No receiver specified (to: field is missing'));
         reload();
         return;
      }
      $subject = fetchFrom('POST', 'subject');
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
      $sign = fetchFrom('POST', 'sign');
      if (empty($sign)) {
         $sign = 'off';
      }
      $incidentid = fetchFrom('POST', 'incidentid');
      if (empty($incidentid)) {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         reload();
         return;
      }

      $agenda = fetchFrom('POST', 'agenda');
      $replyto = fetchFrom('POST', 'replyto');
      $template = fetchFrom('POST', 'template');

      /* prevent sending bogus stuff */
      if (trim($to) == '') {
         die(_('Empty recipient?'));
      }
      if (trim($msg) == '') {
         die(_('Empty message body?'));
      }

      /* clean off html and stuff (only unformatted mail) */
      // $msg = strip_tags($msg);
      $msg = stripslashes($msg);
      $msg = str_replace("\r", '', $msg);

      /* prepare the intial state of the message */
      $hdrs = array(
         'From'     => $from,
         'Subject'  => $subject,
         'To'       => $to,
         'X-Mailer' => 'AIRT $Revision$ http://www.airt.nl'
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

      $body_params = array();
      $body_params['content_type'] = 'text/plain';
      $body_params['disposition'] = 'inline';
      $body_params['charset'] = 'us-ascii';

      $attachcount=0;

      if ($sign == 'off') {
         $msg_params['content_type'] = 'text/plain';
         unset($msg_params['disposition']);
         $mime = new Mail_mimePart($msg, $msg_params);
      } else  {
         // pgp signed messages are described in RFC 2015
         $msg_params['content_type'] = 'multipart/signed; micalg=pgp-sha1; protocol="application/pgp-signature"';
         $mime = new Mail_mimePart('', $msg_params);

         // MIME encoding requires CR/LF
         $msg = explode("\n",$msg);
         $msg = implode("\r\n",$msg);
         $mime->addsubpart($msg, $body_params);

         // create mime-body and remove delimiting lines. RFC 2015 requires
         // that the message is signed, including its MIME headers
         $tmpmime=$mime;
         $m = $tmpmime->encode();
         unset($tmpmime);
         $m = explode("\r\n", $m['body']);
         $m = array_slice($m, 1, -2);
         $m = implode("\r\n", $m);

         /* write msg to temp file */
         $fname = tempnam('/tmp', 'airt_');
         $f = fopen($fname, 'w');
         fwrite($f, $m, strlen($m));
         fclose($f);

         /* invoke gpg */
         $cmd = sprintf("%s %s --homedir %s --default-key %s %s",
            GPG_BIN, GPG_OPTIONS, GPG_HOMEDIR, GPG_KEYID, $fname);
         exec($cmd);
         if (($sig = file_get_contents("$fname.asc")) == false) {
            die(_('Unable to read signed message.'));
         }

         /* clean up */
         unlink($fname);
         unlink("$fname.asc");

         /* message signature */
         $sig_params = array();
         $sig_params['content_type'] = 'application/pgp-signature';
         $sig_params['disposition'] = 'inline';
         $sig_params['description'] = _('Digital signature');
         $sig_params['dfilename'] = 'signature.asc';
         $mime->addsubpart($sig, $sig_params);
      }
      $m = $mime->encode();

      $mail = &Mail::factory('smtp', $mail_params);
      $hdrs = array_merge($hdrs, $m['headers']);
      if (! $mail->send($mailto, $hdrs, $m['body'])) {
         die(_("Error sending message!"));
      }
      addIncidentComment(sprintf(_("Email sent to %s: %s"),
         $to, $subject), $incidentid);
      generateEvent('postsendmail', array(
         'incidentid'=>$incidentid,
         'sender'=>$from,
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
            addIncidentComment(sprintf(_('Type updated to %s'),
               getIncidentTypeLabelByID($actions['type'])));
         }
         if ($actions['status'] == -1) {
            $actions['status'] = '';
         } else {
            addIncidentComment(sprintf(_('Status updated to %s'),
               getIncidentStatusLabelByID($actions['status'])));
         }
         if ($actions['state'] == -1) {
            $actions['state'] = '';
         } else {
            addIncidentComment(sprintf(_('State updated to %s'),
               getIncidentStateLabelByID($actions['state'])));
         }
         updateIncident($incidentid, $actions['state'], $actions['status'],
            $actions['type']);
      }
      if ($action == _('Send and prepare next') && isset($agenda) &&
         isset($template)) {
         Header("Location: $_SERVER[PHP_SELF]?action=prepare&template=$template&agenda=$agenda");
      } else {
         reload('incident.php');
      }

      break;

   // -------------------------------------------------------------------
   default:
      die(_('Unknown action: '. $action));
} // switch
?>
