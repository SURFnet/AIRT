<?php
/* $Id$
 * login.php - allows users to log in to this site. 
 *
 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2004	Kees Leune <kees@uvt.nl>

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
$public=1;
include "../lib/airt.plib";
include "../lib/rt.plib";

function check_SSL()
{
    if (isset($_SERVER["SSL_CLIENT_CERT"]))
    {
        $data = openssl_x509_parse($_SERVER["SSL_CLIENT_CERT"]);
        if ($data["issuer"]["CN"] != "UvT-CA")
            die("Invalid certificate authority");

        $now = time();
        if ($now < $data["validFrom_time_t"] ||
            $now > $data["validTo_time_t"])
            die("Certificate expired");

        $subject = $data["subject"]["CN"];
        if ($data["subject"]["OU"] == "UvT-CERT")
            return $subject;
    }
    return false;
}

if (array_key_exists("action", $_REQUEST)) $action=$_REQUEST[action];
else $action = "none";

$SELF = "login.php";

switch ($action) 
{
    case "none":
        pageHeader("AIR login page");
        echo <<<EOF
<P>
<form action="$SELF" method="POST">
<table>
<tr>
    <td>Login</td>
    <td><input type="text"  name="login" size="25"></td>
</tr>

<tr>
    <td>Password</td>
    <td><input type="password"  name="password" size="25"></td>
</tr>
</table>

<P>
<input type="hidden" name="action" value="check">
<input type="submit" value="Login">
</form>
EOF;
        if (check_SSL() != false)
        {
            printf("<P><a href=\"$SELF?action=ssl\">Log in via SSL client
            certificate</a> [<a 
            href=\"$SELF?action=sslinfo\">SSL info</a>]");
        }

echo <<<EOF

<P>
<HR>
<P>
<BLOCKQUOTE><small>
    AIR version pre-0.1, Copyright (C) 2004  Kees Leune 
    &lt;<a href="mailto:kees@uvt.nl">kees@uvt.nl</a>&gt;<BR>
    AIR comes with ABSOLUTELY NO WARRANTY; for details 
    <a href="license.php">click here</a>.<BR>
    This is free software, and you are welcome to redistribute it
    under certain conditions; type `show c' for details.
</small></BLOCKQUOTE>
EOF;
        break;


    case "check":
        if (array_key_exists("login", $_REQUEST))
            $login = $_REQUEST["login"];
        else die("Missing required field.");

        // password must be HTTP post
        if (array_key_exists("password", $_POST))
            $password = $_POST["password"];
        else die("Missing required field.");

        $userid = RT_checkLogin($login, $password);
        if ($userid == -1)
        {
            pageHeader("Permission denied.");
            printf("Username and/or password incorrect.");
            pageFooter();
            exit;
        }

        $f = fopen("/var/lib/cert/last_$login.txt","w");
        fputs($f, sprintf(
            "Welcome %s. Your last login was at %s from %s.\n",
            $login, Date("r"), gethostbyaddr($_SERVER["REMOTE_ADDR"])));
        fclose($f);

        session_start();
        $_SESSION["username"] = $login;
        $_SESSION["userid"]   = $userid;
        $_SESSION["ip"]       = $_SERVER["REMOTE_ADDR"];
        $_SESSION["last"]     = time();

        Header("Location: index.php");
            
        break;

        case "ssl";
            $name = check_SSL();
            if ($name == false)
            {
                Location("Header: $SELF");
                exit;
            }
            switch ($name)
            {
                case "Kees Leune":
                    $username="kees";
                    $userid="26";
                    break;
                case "Teun Nijssen":
                    $username="teun";
                    break;
                default:
                    Location("Header: $SELF");
                    exit;
            }

            session_start();
            $_SESSION["username"] = $username;
            $_SESSION["userid"]   = $userid;
            $_SESSION["ip"]       = $_SERVER["REMOTE_ADDR"];
            $_SESSION["last"]     = time();

            Header("Location: index.php");
            break;


    default:
        die("Unknown action.");
} // switch
?>
