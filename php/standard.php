<?php
/* $Id$ 
 * standard.php - Standard messages
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
 * $Id$
 */
require_once 'config.plib';
require_once LIBDIR."/airt.plib";
require_once LIBDIR."/user.plib";
require_once LIBDIR."/history.plib";
require_once LIBDIR."/incident.plib";
require_once LIBDIR."/export.plib";
require_once 'Mail.php';
require_once 'Mail/mime.php';

if (array_key_exists("action", $_REQUEST)) $action=$_REQUEST["action"];
else $action = "list";

$FILES = array();

function loadAllowedFiles() {
	$f = array();
    $dh = @opendir(STATEDIR."/templates")
    or die ("Unable to open directory with standard messages.");

    while ($file = readdir($dh))
    {
        // skip dot files
        if (ereg("^[.]", $file)) continue;
		array_push($f, $file);
	}
	closedir($dh);

	return $f;
}


/* a filename is allowed if it exists in the $FILES global array. Note that we
 * use a global here, but consider it to be a read-only datastructure. If the
 * $FILES array has not been initialized, access will be refused.
 *
 * Returns: true is the file may be read;
 * Returns: false when it may not be read;
 */
function valid_read($name) {
	global $FILES;

	if (!isset($FILES)) return false;
	if (!in_array($name, $FILES)) return false;

	return true;
}


/* A filename may be written if it only consists of [a-zA-Z_-].
 * NOTE: do not include period or any kind of directory limiters (i.e. / on
 * real OSses)
 */
function valid_write($name) {
	if (ereg('^[a-zA-Z0-9_-]+$', $name)) return true;
	else return false;
}


/** 
 * Read a standard message from the filesystem
 * $str = the name of the file to be read
 * Returns the file as one large buffer on success, or false on failure.
 */
function read_standard_message($str)
{
	if (!valid_read($str)) return false;

    $filename = STATEDIR."/templates/$str";
    if (($f = fopen($filename, "r")) == false) return false;
    

	set_magic_quotes_runtime(0);
    $msg = fread($f, filesize($filename));
    fclose($f);

    return $msg;
} // read_standard_message


/**
 * Retrieve the subject line from a message
 * $msg = the buffer containing the message
 * return false on failure, or the subject line on success
 */
function get_subject($msg) {
    $match = ereg("@SUBJECT@(.*)@ENDSUBJECT@", $msg, $regs);
    if (!$match) return false;

    return $regs[1];
} // get_subject


/**
 * List all standard messages. Returns the number of messages
 */
function list_standard_messages() {
    $dir = STATEDIR."/templates";
    $dh = @opendir($dir)
    or die ("Unable to open directory with standard messages.");

    echo "<table>";
    $count=0;
    while ($file = readdir($dh))
    {
		if (!valid_read($file)) continue;

        $msg = read_standard_message($file);
        $subject = get_subject($msg);
        printf("<tr bgcolor=%s>
            <td><a href=\"%s?action=prepare&filename=%s\">prepare</a></td>
            <td>%s</td>
			<td>%s</td>
            <td><a href=\"%s?action=edit&filename=%s\">edit</a></td>
            <td><a onclick=\"return confirm('Are you sure that you want ".
            "to delete this message?')\"
            href=\"%s?action=delete&filename=%s\">delete</a></td>
            </tr>",
            $count++%2==0?"#DDDDDD":"#FFFFFF",
            $_SERVER['PHP_SELF'], urlencode($file),
            $file, $subject,
            $_SERVER['PHP_SELF'], urlencode($file),
            $_SERVER['PHP_SELF'], urlencode($file)
            );
    }
    echo "</table>";

    closedir($dh);
    return $count;
} // list_standard_messages


/**
 * Show a message, without processing it.
 */
function show_message($name) {
    if (($message = read_standard_message($name)) == false) {
        printf("Unable to read message.");
        return false;
    }
    printf("%s", replace_vars($message));
} // show_message


function save_standard_message($filename, $msg) {
    if ($filename == "" || $msg == "" || !valid_write($filename)) return false;

    $filename = STATEDIR."/templates/$filename";
    if (($f = fopen($filename, "w")) == false) return false;

	set_magic_quotes_runtime(0);
    fwrite($f, $msg);
    fclose($f);

    return true;
} // save_standard_message


function prepare_message($filename) {
	pageHeader("Send standard message.");
	if (!valid_read($filename)) {
		echo "Invalid message template.";
		return;
	}

	// get vars
	$cert = getUserByUserId($_SESSION["userid"]);
	if (MAILFROM == '') $from = $cert["email"];
	else $from = replace_vars(MAILFROM);

	if (REPLYTO == '') $replyto = $cert["email"];
	else $replyto = replace_vars(REPLYTO);

	// load message and replace all standard variables
	$msg = replace_vars(read_standard_message($filename));

	// extract subject
	$subject = get_subject($msg);

	// remove first line from standard message (which is the subject)
	$m = explode("\n", $msg);
	unset($m[0]);
	$msg = implode("\n", $m);

	// to
	$to = $_SESSION["current_email"];


	echo <<<EOF
<FORM action="$_SERVER[PHP_SELF]" method="POST">
<TABLE WIDTH="80">
<TR>
	<TD>To:</TD>
	<TD><INPUT TYPE="text" size="50" name="to" value="$to"></TD>
</TR>
<TR>
	<TD>Subject:</TD>
	<TD><INPUT TYPE="text" size="50" name="subject" value="$subject"></TD>
</TR>
<TR>
	<TD>From:</TD>
	<TD><INPUT TYPE="text" size="50" name="from" value="$from"></TD>
</TR>
<TR>
	<TD>Reply-To:</TD>
	<TD><INPUT TYPE="text" size="50" name="replyto" value="$replyto"></TD>
</TR>
</TABLE>
<TEXTAREA name="msg" cols="80" rows="30">$msg</TEXTAREA>
<P>
<INPUT TYPE="hidden" name="action" value="send">
<INPUT TYPE="reset"  value="Reset">
<INPUT TYPE="submit" value="Send">
<INPUT TYPE="checkbox" name="sendxml"> Check to attach incident data in AIRT
format.
</FORM>
EOF;
	pageFooter();
} // prepare_message


function print_variables_info() {
    echo <<<EOF
<table cellpadding="2">
<tr>
    <td nowrap>@SUBJECT@ .. @ENDSUBJECT@</td>
    <td>Delimits the subject line of the message</td>
</tr>
<tr>
    <td>@HOSTNAME@</td>
    <td>Will be replaced with the currently active hostname</td>
</tr>
<tr>
    <td>@IPADDRESS@</td>
    <td>Will be replaced with the currently active IP address</td>
</tr>
<tr>
    <td>@USERNAME@</td>
    <td>Will be replaced with the name of the current user</td>
</tr>
<tr>
	<td>@USEREMAIL@</td>
	<td>Will be replaced with the email address of the current user</td>
</tr>
<tr>
	<td>@USERINFO@</td>
	<td>Will be replaced with detailed information about the user, if that
	information is available.</td>
</tr>
<tr>
    <td>@YOURNAME@</td>
    <td>Will be replaced with the full name of the logged in incident
    handler</td>
</tr>
<tr>
    <td>@YOURNFIRSTAME@</td>
    <td>Will be replaced with the first name of the logged in incident
    handler</td>
</tr>
<tr>
    <td>@INCIDENTID@</td>
    <td>Will be replaced with the current incident id</td>
</tr>
</table>
EOF;
}


function replace_vars($msg) {
    $out = $msg;

	$out = ereg_replace("@IPADDRESS@", $_SESSION["active_ip"], $out);

	$out = ereg_replace("@HOSTNAME@", 
		@gethostbyaddr($_SESSION["active_ip"]), $out);

	if (array_key_exists('current_name', $_SESSION)) {
		$out = ereg_replace("@USERNAME@", $_SESSION["current_name"], $out);
	}

	if (array_key_exists('current_email', $_SESSION)) {
		$out = ereg_replace("@USEREMAIL@", $_SESSION["current_email"], $out);
	}
	
	if (array_key_exists('current_info', $_SESSION)) {
		$out = ereg_replace("@USERINFO@", $_SESSION["current_info"], $out);
	}

	$u = getUserByUserId($_SESSION["userid"]);
	$name = sprintf("%s %s", $u["firstname"], $u["lastname"]);
	$out = ereg_replace("@YOURNAME@", $name, $out);
	$out = ereg_replace("@YOURFIRSTNAME@", $u["firstname"], $out);

	$out = ereg_replace("@INCIDENTID@",
		normalize_incidentid($_SESSION["incidentid"]), $out);

    return $out ;
} // replace_vars

/*************************************************************************
 * BODY
 *************************************************************************/
$FILES = loadAllowedFiles();

switch ($action) {
    // -------------------------------------------------------------------
    case "list":
        pageHeader("Available standard messages");

        echo "<h2>Messages</H2>";
        if (list_standard_messages() == 0)
            printf("<I>No standard messages available.</I>");
        echo <<<EOF
<P>
<a href="$_SERVER[PHP_SELF]?action=new">Create a new message</a>
EOF;

        pageFooter();
        break;

    // -------------------------------------------------------------------
    case "edit":
		$msg = '';
        if (array_key_exists("filename", $_REQUEST))
            $filename=$_REQUEST["filename"];
        else die("Missing parameter.");


        pageHeader("Edit standard message");

        if (($msg = read_standard_message($filename)) == false) {
            printf("Message not available.");
        }
        else {
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
        if (array_key_exists("filename", $_REQUEST))
            $filename=$_REQUEST["filename"];
        else die("Missing parameter.");

        if (array_key_exists("message", $_REQUEST))
            $message=$_REQUEST["message"];
        else die("Missing parameter.");

		$message = strip_tags($message);
		$message = stripslashes($message);
		
		if (!valid_write($filename)) die ("Invalid filename.");

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
        if (array_key_exists("filename", $_REQUEST))
            $filename=$_REQUEST["filename"];
        else die("Missing parameter.");

		if (valid_write($filename)) 
			unlink(STATEDIR."/templates/$filename");
        Header("Location: $_SERVER[PHP_SELF]");
        break;

    // -------------------------------------------------------------------
    case "prepare":
        if (array_key_exists("filename", $_REQUEST))
            $filename=$_REQUEST["filename"];
        else die("Missing parameter.");

		prepare_message($filename);
		pageFooter();
        break;

    // -------------------------------------------------------------------
	case "send":
		if (array_key_exists("from", $_POST)) $from=$_POST["from"];
		else die("Missing parameter 1.");
		if (array_key_exists("to", $_POST)) $to=$_POST["to"];
		else die("Missing parameter 2.");
		if (array_key_exists("replyto", $_POST)) $replyto=$_POST["replyto"];
		else die("Missing parameter 3.");
		if (array_key_exists("subject", $_POST)) $subject=$_POST["subject"];
		else die("Missing parameter 4.");
		if (array_key_exists("msg", $_POST)) $msg=$_POST["msg"];
		else die("Missing parameter 5.");
		if (array_key_exists("sendxml", $_POST)) $attach=$_POST["sendxml"];
		else $attach="off";

		/* prevent sending bogus stuff */
		if (trim($to) == '') die('Empty recipient?');
		if (trim($msg) == '') die('Empty message body?');
	
		/* clean off html and stuff (only unformatted mail) */
		$msg = strip_tags($msg);
		$msg = stripslashes($msg);

		/* create the headers for the message */
		$hdrs = array(
			'From'     => $from,
			'Subject'  => $subject,
			'Reply-To' => $replyto,
			'To'       => $to
		);
		
		/* get a new mime class instance and set it up */
		$mime = new Mail_mime("\r\n");
		$mime->setTxtBody($msg);
		if ($attach == "on") {
			$attachment = exportIncident(array($_SESSION["incidentid"]));
			$mime->addAttachment($attachment, 'application/xml',
				normalize_incidentid($_SESSION["incidentid"]).'.xml',
				false);
		}
		$body = $mime->get();
		$hdrs = $mime->headers($hdrs);


		/* set up mail recipient */
		if (defined('MAILCC')) {
			$mailto = array($to, MAILCC);
			$hdrs["Cc"] = MAILCC;
		}
		else $mailto = array($to);

		/* set up envelope sender */
		$envfrom="-f".MAILENVFROM;
		
		/* send via Mail class */
		$params = array(
			'sendmail_params' => $envfrom
		);
		$mail = &Mail::factory('sendmail', $params);
		if (! $mail->send($mailto, $hdrs, $body)) 
			die("Error sending message!");

		addIncidentComment(sprintf("Email sent to %s: %s",
			$to, $subject));
		Header("Location: $_SERVER[PHP_SELF]");
		break;


    // -------------------------------------------------------------------
    default:
        die("Unknown action: $action");
} // switch
?>
