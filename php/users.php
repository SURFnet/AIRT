<?php
/*
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
 * users.php -- manage users
 * 
 * $Id$
 */
 require_once 'config.plib';
 require_once LIBDIR.'/airt.plib';
 require_once LIBDIR.'/database.plib';
 require_once LIBDIR.'/user.plib';
 
 $SELF = "users.php";

 if (array_key_exists("action", $_REQUEST)) $action=$_REQUEST["action"];
 else $action = "list";

 function show_form($id="")
 {
    $lastname = $firstname = $email = $phone = $login = "";
    $action = "add";
    $submit = "Add!";

    if ($id != "")
    {
        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $res = db_query($conn, "
        SELECT lastname, firstname, email, phone, login, userid, password
        FROM   users
        WHERE  id = '$id'")
        or die("Unable to query database.");

        if (db_num_rows($res) > 0)
        {
            $row = db_fetch_next($res);
            $lastname = $row["lastname"];
            $firstname = $row["firstname"];
            $email = $row["email"];
            $phone = $row["phone"];
            $login = $row["login"];
			$userid = $row["userid"];
            $action = "update";
            $submit = "Update!";
        }
        db_close($conn);
    }
    echo <<<EOF
<form action="$SELF" method="POST">
<input type="hidden" name="action" value="$action">
<input type="hidden" name="id" value="$id">
<table>
<tr>
    <td>Login</td>
    <td><input type="text" size="30" name="login" value="$login"></td>
</tr>
<tr>
    <td>Organizational user id</td>
    <td><input type="text" size="30" name="userid" value="$userid"></td>
</tr>
<tr>
    <td>Last name</td>
    <td><input type="text" size="30" name="lastname" value="$lastname"></td>
</tr>
<tr>
    <td>First name</td>
    <td><input type="text" size="30" name="firstname" value="$firstname"></td>
</tr>
<tr>
    <td>Email address</td>
    <td><input type="text" size="30" name="email" value="$email"></td>
</tr>
<tr>
    <td>Phone number</td>
    <td><input type="text" size="30" name="phone" value="$phone"></td>
</tr>
<tr>
    <td>Password</td>
    <td><input type="password" size="30" name="password"></td>
</tr>
<tr>
    <td>Confirm password</td>
    <td><input type="password" size="30" name="password2"></td>
</tr>
</table>
<p>
<input type="submit" value="$submit">
</form>
EOF;
 }

 switch ($action)
 {
    // --------------------------------------------------------------
    case "list":
        pageHeader("AIRT users");
        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
        or die ("Unable to connect to database.");

        $res = db_query($conn, "
            SELECT id, login, lastname, firstname, email, phone,
                   userid
            FROM   users
            ORDER BY login")
        or die("Unable to query database.");
        
        echo <<<EOF
<table width="100%" cellpadding=3>
<tr>
    <th>Login</th>
	<th>User id</th>
    <th>Last name</th>
    <th>First name</th>
    <th>Email</th>
    <th>Phone</th>
</tr>
EOF;
        $count=0;
        while ($row = db_fetch_next($res))
        {
			$id = $row["id"];
            $login = $row["login"];
            $lastname = $row["lastname"];
            $firstname = $row["firstname"];
            $email = $row["email"];
            $phone = $row["phone"];
            $userid = $row["userid"];

            printf("
<tr bgcolor='%s'>
    <td>%s</td>
    <td>%s</td>
    <td>%s</td>
    <td>%s</td>
    <td><a href='mailto:%s'>%s</a></td>
    <td>%s</td>
    <td><a href='$SELF?action=edit&id=%s'>edit</a></td>
    <td><a 
       onclick=\"return confirm('Are you sure that you want to delete %s?')\"
       href='$SELF?action=delete&id=%s'>delete</a></td>
</tr>",
            ($count++%2==0?"#FFFFFF":"#DDDDDD"),
            $login, $userid, $lastname, $firstname, $email, $email, $phone,
            $id, $login, $id);
        }
        db_free_result($res);
        db_close($conn);
        echo <<<EOF
</table>

<P>

<h3>New user</h3>
EOF;
        show_form();
        pageFooter();
        break;

    // --------------------------------------------------------------
    case "add":
    case "update":
        if (array_key_exists("login", $_POST)) $login=$_POST["login"];
        else die("Missing information (1).");

        if (array_key_exists("lastname", $_POST)) $lastname=$_POST["lastname"];
        else die("Missing information (2).");
        
        if (array_key_exists("firstname", $_POST)) 
            $firstname=$_POST["firstname"];
        else die("Missing information (3).");

        if (array_key_exists("email", $_POST)) $email=strtolower($_POST["email"]);
        else die("Missing information (4).");
        
        if (array_key_exists("phone", $_POST)) $phone=$_POST["phone"];
        else die("Missing information (5).");
        
        if (array_key_exists("password", $_POST)) $password=$_POST["password"];
        else $password="";
        
        if (array_key_exists("password2", $_POST))
            $password2=$_POST["password2"];
        else $password2="";

        if (array_key_exists("userid", $_POST)) $userid=$_POST["userid"];
        else $userid="";

        if (array_key_exists("id", $_POST)) $id=$_POST["id"];
        else $id="";

        // ========= ADD ==========
        if ($action == "add")
        {
            if ($password != $password2)
            {
                pageHeader("Error");
                echo <<<EOF
The passwords that you provided do not match.<P>
Please use your browser's back button to correct the problem and 
resend the form.
EOF;
                pageFooter();
                exit;
            }
            $conn = db_connect(DBDB, DBUSER, DBPASSWD)
            or die("Unable to connect to database.");

            $res = db_query($conn,
                "SELECT id
                 FROM   users
                 WHERE  login='$login'")
            or die("Unable to query database.");

            if (db_num_rows($res) > 0)
            {
                pageHeader("Error");
                echo <<<EOF
Login <em>$login</em> is already in use.<P>
Please use your browser's back button to correct the problem and 
resend the form.
EOF;
                pageFooter();
                exit;
            }

            db_free_result($res);

			addUser(array(
				"lastname" => $lastname,
				"firstname" => $firstname,
				"email" => $email,
				"phone" => $phone,
				"login" => $login,
				"userid" => $userid,
				"password" => $password
			));

            db_close($conn);
            Header("Location: $SELF");
        }

        // ========== UPDATE ===========
        else if ($action == "update")
        {
            if ($id=="") die("Missing information(A)");
            if ($password != "")
            {
                if ($password != $password2)
                {
                    pageHeader("Error");
                    echo <<<EOF
The passwords that you provided do not match.<P>
Please use your browser's back button to correct the problem and 
resend the form.
EOF;
                    pageFooter();
                    exit;
                }
			}

            $query = sprintf("
                UPDATE users
                SET    lastname=%s,
                       firstname=%s,
                       email=%s,
                       phone=%s,
                       login=%s,
                       userid=%s",
                    db_masq_null($lastname),
                    db_masq_null($firstname),
                    db_masq_null($email),
                    db_masq_null($phone),
					db_masq_null($login),
					db_masq_null($userid),
                    $id);
			if ($password != "") {
                $query=sprintf("
                    %s, 
                    password=%s", 
                        $query,
                        db_masq_null($password)
				);
            }
            $query = sprintf("
                %s
                WHERE id=%s",
                    $query,
                    $id);

            $conn = db_connect(DBDB, DBUSER, DBPASSWD)
            or die("Unable to connect to database.");

            $res = db_query($conn, $query)
            or die("Unable to execute query 1");

            db_close($conn);
            Header("Location: $SELF");
        }

        break;

    // --------------------------------------------------------------
    case "delete":
        if (array_key_exists("id", $_GET)) $id=$_GET["id"];
        else $id="";

        $conn = db_connect(DBDB, DBUSER, DBPASSWD)
        or die("Unable to connect to database.");

        $res = db_query($conn,
            "DELETE FROM users
             WHERE  id = $id");
        if (!$res) {
			pageHeader("Error removing user.");
			echo <<<EOF
<p>Unable to remove this user from the database.</p>

<p>The most likely cause for this failure is that the user is associated with
one or more incidents.</p>

<p><a href="$SELF">continue...</a></p>
EOF;
			pageFooter();
			exit;
		}

        db_close($conn);
        Header("Location: $SELF");

        break;

    // --------------------------------------------------------------
    case "edit":
        if (array_key_exists("id", $_GET)) $id=$_GET["id"];
        else die("Missing information (1).");

        pageHeader("Edit user information");
        show_form($id);
        pageFooter();
        break;

    // --------------------------------------------------------------
    default:
        die("Unknown action: $action");
} // switch
?>
 
