<?php
/* $Id$
 * login.php - allows users to log in to this site. 
 *
 * LIBERTY: INCIDENT RESPONSE SUPPORT FOR END-USERS
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
include "../lib/liberty.plib";
include "../lib/pgsql.plib";

if (array_key_exists("action", $_REQUEST)) $action=$_REQUEST[action];
else $action = "none";

$SELF = "login.php";

switch ($action) 
{
    case "none":
        pageHeader("UvT-CERT Login page");
        echo <<<EOF
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
        break;


    case "check":
        if (array_key_exists("login", $_REQUEST))
            $login = $_REQUEST["login"];
        else die("Missing required field.");

        // password must be HTTP post
        if (array_key_exists("password", $_POST))
            $password = $_POST["password"];
        else die("Missing required field.");

        $conn = db_connect(RTNAME, RTUSER, RTPASSWD)
        or die("Unable to connect to database.".db_errormsg());

        $query = sprintf("
            SELECT u.id
            FROM   users u, groups g, groupmembers m
            WHERE  u.name = '%s'
            AND    u.password = '%s'
            AND    u.id = m.memberid
            AND    m.groupid = g.id
            AND    g.name = '%s'", 
                $login, 
                md5($password, true), 
                CERTGROUP);
printf("$query");
        $res = db_query($conn, $query)
        or die("Unable to query database: ".db_errormsg());


        if (db_num_rows($res) == 0)
        {
            pageHeader("Permission denied.");
            printf("Username and/or password incorrect.");
            pageFooter();
            exit;
        }

        $filename="/var/lib/cert/bad_login_$login.txt";
        /* check for lockout */
        if (file_exists($filename))
        {
            $f = fopen($filename, "r");
            $count = 0;
            while (!feof($f))
            {
                $line = fgets($f);
                if ($line=="") continue;
                $last = $line;
                $count++;
            }
            fclose($f);
            $now=Date("U");
            $delta=$now-$last;
            if ($count > 3 && $delta < 900)
            {
                pageHeader("Access denied.");
                printf("Your account has been blocked due to subsequent
                invalid logins.");
                printf("<P><a href=\"%s\">Try again</a>", $SELF);
                exit();
            }
        }

        /* process login */
        if (array_key_exists($login, $USERNAMES) &&
            $USERNAMES[$login] == $password) 
        {
            $f = fopen("/var/lib/cert/last_$login.txt","w");
            fputs($f, sprintf(
                "Welcome %s. Your last login was at %s from %s.\n",
                $login, Date("r"), gethostbyaddr($_SERVER["REMOTE_ADDR"])));
            fclose($f);
            
            if (file_exists($filename)) unlink($filename);
            session_start();
            $_SESSION[username] = $login;
            Header("Location: index.php");
            return;
        }
        else 
        {
            $f = fopen($filename,"a");
            fputs($f, date("U")."\n");
            fclose($f);
            pageHeader("Access denied.");
            printf("Invalid username or password.");
            printf("<P><a href=\"%s\">Try again</a>", $SELF);
        }
            
        break;

    default:
        die("Unknown action.");
} // switch
?>
