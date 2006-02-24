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
  pageHeader("Available mail templates");

   print format_templates();
   print t("<P><a href=\"%url?action=new\">Create a new message</a></P>\n", 
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

      pageHeader("Edit mail template");

      if (($msg = get_template($template)) == false) {
         printf("Template not available.");
      } else {
         echo <<<EOF
Update the template and press the 'Save!' button to save it. The first
line of the message will be used as the subject. You may use the following
special variables in the template:

<P>
EOF;
      print_variables_info();
      echo <<<EOF
<P>

<form action="$_SERVER[PHP_SELF]" method="POST">
<textarea wrap name="message" cols=75 rows=20>$msg</textarea>
<P>
<input type="hidden" name="action" value="save">
<input type="hidden" name="template" value="$template">
<input type="submit" value="Save">
<input type="reset" value="Cancel">
</form>
EOF;
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

      $message = strip_tags($message);
      $message = stripslashes($message);

      if (save_template($template, $message)) {
         airt_error('ERR_FUNC', 'mailtemplates.php:'.__LINE__);
      }
      listTemplates();
      break;

   // -------------------------------------------------------------------
   case "new":
      pageHeader("New mail template");
      echo <<<EOF
Enter your new template in the text field below. Use the following variables
in your text body:
<P>
EOF;
      print_variables_info();
      echo <<<EOF
<P>
<form action="$_SERVER[PHP_SELF]" method="POST">
File name: <input type="text" size="40" name="template">
<P>
Message:<BR>
<textarea wrap name="message" cols=75 rows=20></textarea>
<P>
<input type="hidden" name="action" value="save">
<input type="submit" value="Save!">
<input type="reset" value="Cancel!">
</form>
EOF;
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
   case "prepare":
      if (array_key_exists("template", $_REQUEST)) {
         $template=$_REQUEST["template"];
      } else {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      if (array_key_exists('agenda', $_REQUEST)) {
         $agenda = explode(',',$_REQUEST['agenda']);
      } elseif (array_key_exists('incidentid', $_REQUEST)) {
         $agenda = array($_REQUEST['incidentid']);
      } else {
         $agenda = array($_SESSION['incidentid']);
      }
      prepare_message($template, $agenda);
      pageFooter();
      break;

   // -------------------------------------------------------------------
   case 'send':
   case 'Send':
   case 'Send and prepare next':
      if (array_key_exists("from", $_POST)) {
         $from=$_POST["from"];
      } else {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      if (array_key_exists("to", $_POST)) {
         $to=$_POST["to"];
      } else {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      if (array_key_exists("replyto", $_POST)) {
         $replyto=$_POST["replyto"];
      } else {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      if (array_key_exists("subject", $_POST)) {
         $subject=$_POST["subject"];
      } else {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      if (array_key_exists("msg", $_POST)) {
         $msg=$_POST["msg"];
      } else {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      if (array_key_exists('sign', $_POST)) {
         $sign = $_POST['sign'];
      } else {
         $sign = 'off';
      }
      if (array_key_exists('incidentid', $_POST)) {
         $incidentid = $_POST['incidentid'];
      } else {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         return;
      }
      if (array_key_exists('agenda', $_POST)) {
         $agenda = $_POST['agenda'];
      }
      if (array_key_exists('template', $_POST)) {
         $template = $_POST['template'];
      }

      /* prevent sending bogus stuff */
      if (trim($to) == '') {
         die('Empty recipient?');
      }
      if (trim($msg) == '') {
         die('Empty message body?');
      }

      /* clean off html and stuff (only unformatted mail) */
      $msg = strip_tags($msg);
      $msg = stripslashes($msg);
      $msg = str_replace("\r", '', $msg);

      /* prepare the intial state of the message */
      $hdrs = array(
         'From'     => $from,
         'Subject'  => $subject,
         'To'       => $to,
         'X-Mailer' => 'AIRT $Revision$ http://www.sourceforge.net/projects/airt'
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
            die('Unable to read signed message.');
         }

         /* clean up */
         unlink($fname);
         unlink("$fname.asc");

         /* message signature */
         $sig_params = array();
         $sig_params['content_type'] = 'application/pgp-signature';
         $sig_params['disposition'] = 'inline';
         $sig_params['description'] = 'Digital signature';
         $sig_params['dfilename'] = 'signature.asc';
         $mime->addsubpart($sig, $sig_params);
      }
      $m = $mime->encode();

      $mail = &Mail::factory('smtp', $mail_params);
      $hdrs = array_merge($hdrs, $m['headers']);
      if (! $mail->send($mailto, $hdrs, $m['body'])) {
         die("Error sending message!");
      }
      addIncidentComment(sprintf("Email sent to %s: %s",
         $to, $subject), $incidentid);
      generateEvent('postsendmail', array(
         'incidentid'=>$incidentid,
         'sender'=>$from,
         'recipient'=>$to,
         'subject'=>$subject));

      if ($action == 'Send and prepare next' && isset($agenda) &&
         isset($template)) {
         Header("Location: $_SERVER[PHP_SELF]?action=prepare&template=$template&agenda=$agenda");
      } else {
         Header("Location: $_SERVER[PHP_SELF]");
      }
      break;


   // -------------------------------------------------------------------
   default:
      die("Unknown action: $action");
} // switch
?>
