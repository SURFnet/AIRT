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


$FILES = loadAllowedFiles();
if (array_key_exists('action', $_REQUEST)) {
   $action=$_REQUEST['action'];
} else {
   $action = "list";
}

switch ($action) {
   // -------------------------------------------------------------------
   case "list":
     pageHeader("Available standard messages");

      echo "<h2>Messages</H2>";
      if (list_standard_messages() == 0) {
         printf("<I>No standard messages available.</I>");
      }
      echo <<<EOF
<P>
<a href="$_SERVER[PHP_SELF]?action=new">Create a new message</a>
EOF;
      // If a current_email parameter has been passed along, put it in the
      // session for later use by "prepare".
      if (array_key_exists('current_email', $_REQUEST)) {
         $_SESSION['current_email'] = $_REQUEST['current_email'];
      }

      pageFooter();
      break;

   // -------------------------------------------------------------------
   case "edit":
      $msg = '';
      if (array_key_exists("filename", $_REQUEST)) {
         $filename=$_REQUEST["filename"];
      } else {
         die("Missing parameter.");
      }

      pageHeader("Edit standard message");

      if (($msg = read_standard_message($filename)) == false) {
         printf("Message not available.");
      } else {
         echo <<<EOF
Update the message and press the 'Save!' button to save the message. The first
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
<input type="hidden" name="filename" value="$filename">
<input type="submit" value="Save!">
<input type="reset" value="Cancel!">
</form>
EOF;
      }
      pageFooter();

      break;

   // -------------------------------------------------------------------
   case "save":
      if (array_key_exists("filename", $_REQUEST)) {
         $filename=$_REQUEST["filename"];
      } else {
         die("Missing parameter.");
      }
      if (array_key_exists("message", $_REQUEST)) {
            $message=$_REQUEST["message"];
      } else {
         die("Missing parameter.");
      }

      $message = strip_tags($message);
      $message = stripslashes($message);

      if (!valid_write($filename)) {
         die ("Invalid filename.");
      }

      save_standard_message($filename, $message);
      Header("Location: $_SERVER[PHP_SELF]");
      break;

   // -------------------------------------------------------------------
   case "new":
      pageHeader("New standard message");
      echo <<<EOF
Enter your new message in the text field below. Use the following variables
in your text body:
<P>
EOF;
      print_variables_info();
      echo <<<EOF
<P>
<form action="$_SERVER[PHP_SELF]" method="POST">
File name: <input type="text" size="40" name="filename">
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
      if (array_key_exists("filename", $_REQUEST)) {
         $filename=$_REQUEST["filename"];
      } else {
         die("Missing parameter.");
      }

      if (valid_write($filename)) {
         unlink(STATEDIR."/templates/$filename");
      }
      Header("Location: $_SERVER[PHP_SELF]");
      break;

   // -------------------------------------------------------------------
   case "prepare":
      if (array_key_exists("filename", $_REQUEST)) {
         $filename=$_REQUEST["filename"];
      } else {
         die("Missing parameter.");
      }

      prepare_message($filename);
      pageFooter();
      break;

   // -------------------------------------------------------------------
   case "send":
      if (array_key_exists("from", $_POST)) {
         $from=$_POST["from"];
      } else {
         die("Missing parameter 1.");
      }
      if (array_key_exists("to", $_POST)) {
         $to=$_POST["to"];
      } else {
         die("Missing parameter 2.");
      }
      if (array_key_exists("replyto", $_POST)) {
         $replyto=$_POST["replyto"];
      } else {
         die("Missing parameter 3.");
      }
      if (array_key_exists("subject", $_POST)) {
         $subject=$_POST["subject"];
      } else {
         die("Missing parameter 4.");
      }
      if (array_key_exists("msg", $_POST)) {
         $msg=$_POST["msg"];
      } else {
         die("Missing parameter 5.");
      }
      if (array_key_exists("sendxml", $_POST)) {
         $attach=$_POST["sendxml"];
      } else {
         $attach='off';
      }
      if (array_key_exists('sign', $_POST)) {
         $sign = $_POST['sign'];
      } else {
         $sign = 'off';
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
      $msg = str_replace("
", '', $msg);

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
         $to, $subject));
      Header("Location: $_SERVER[PHP_SELF]");
      break;


   // -------------------------------------------------------------------
   default:
      die("Unknown action: $action");
} // switch
?>
