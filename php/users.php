<?php
/* vim: syntax=php tabstop=3 shiftwidth=3
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
 *
 * users.php -- manage users
 * 
 * $Id$
 */
 require_once 'config.plib';
 require_once LIBDIR.'/airt.plib';
 require_once LIBDIR.'/database.plib';
 require_once LIBDIR.'/user.plib';

 if (array_key_exists("action", $_REQUEST)) $action=$_REQUEST["action"];
 else $action = "list";

 function show_form($id="") {
    $lastname = $firstname = $email = $phone = $login = $userid = '';
    $action = "add";
    $submit = _("Add!");

    if ($id != "") {
        $res = db_query("
        SELECT lastname, firstname, email, phone, login, userid, password
        FROM   users
        WHERE  id = '$id'")
        or die(_("Unable to query database."));

        if (db_num_rows($res) > 0) {
            $row = db_fetch_next($res);
         if (array_key_exists('lastname', $row))
            $lastname=$row['lastname'];
         if (array_key_exists('firstname', $row))
            $firstname=$row['firstname'];
         if (array_key_exists('email', $row))
            $email=$row['email'];
         if (array_key_exists('phone', $row))
            $phone=$row['phone'];
         if (array_key_exists('login', $row))
            $login=$row['login'];
         if (array_key_exists('userid', $row))
            $userid=$row['userid'];

            $action = "update";
            $submit = _("Update!");
        }
    }
    if (array_key_exists('language', $_SESSION)) {
       $lang = $_SESSION['language'];
    } elseif (defined('DEFAULTLANGUAGE')) {
       $lang = DEFAULTLANGUAGE;
    } else {
       $lang = 'en';
    }
    print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">'.LF;
    print '<input type="hidden" name="action" value="'.$action.'">'.LF;
    print '<input type="hidden" name="id" value="'.$id.'">'.LF;
    print '<table>'.LF;
    print '<tr>'.LF;
    print '    <td>'._('Login').'</td>'.LF;
    print '    <td><input type="text" size="30" name="login" value="'.
       $login.'"></td>'.LF;
    print '</tr>'.LF;
    print '<tr>'.LF;
    print '    <td>'._('Organizational user id').'</td>'.LF;
    print '    <td><input type="text" size="30" name="userid" value="'.
      $userid.'"></td>'.LF;
    print '</tr>'.LF;
    print '<tr>'.LF;
    print '    <td>'._('Last name').'</td>'.LF;
    print '    <td><input type="text" size="30" name="lastname" value="'.
      $lastname.'"></td>'.LF;
    print '</tr>'.LF;
    print '<tr>'.LF;
    print '    <td>'._('First name').'</td>'.LF;
    print '    <td><input type="text" size="30" name="firstname" value="'.
      $firstname.'"></td>'.LF;
    print '</tr>'.LF;
    print '<tr>'.LF;
    print '    <td>'._('Email address').'</td>'.LF;
    print '    <td><input type="text" size="30" name="email" value="'.
      $email.'"></td>'.LF;
    print '</tr>'.LF;
    print '<tr>'.LF;
    print '    <td>'._('Phone number').'</td>'.LF;
    print '    <td><input type="text" size="30" name="phone" value="'.
      $phone.'"></td>'.LF;
    print '</tr>'.LF;
    print '<tr>'.LF;
    print '   <td>'._('Preferred language').'</td>'.LF;
    print '   <td>'.formatAvailableLanguages('language', $lang).'</td>'.LF;
    print '</tr>'.LF;
    print '<tr>'.LF;
    print '    <td>'._('Password').'</td>'.LF;
    print '    <td><input type="password" size="30" name="password"></td>'.LF;
    print '</tr>'.LF;
    print '<tr>'.LF;
    print '    <td>'._('Confirm password').'</td>'.LF;
    print '    <td><input type="password" size="30" name="password2"></td>'.LF;
    print '</tr>'.LF;
    print '</table>'.LF;
    print '<p>'.LF;
    print '<input type="submit" value="'.$submit.'">'.LF;
    print '</form>'.LF;
 }

 switch ($action) {
   // --------------------------------------------------------------
   case "list":
      pageHeader(_("AIRT users"));

      $res = db_query("
            SELECT id, login, lastname, firstname, email, phone,
                   userid
            FROM   users
            ORDER BY login")
      or die(_('Unable to query database.'));

      print '<table width="100%" cellpadding=3>'.LF;
      print '<tr>'.LF;
      print '   <th>'._('Login').'</th>'.LF;
      print '   <th>'._('User id').'</th>'.LF;
      print '   <th>'._('Last name').'</th>'.LF;
      print '   <th>'._('First name').'</th>'.LF;
      print '   <th>'._('Email').'</th>'.LF;
      print '   <th>'._('Phone').'</th>'.LF;
      print '</tr>'.LF;
      $count=0;
      while ($row = db_fetch_next($res)) {
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
    <td><a href='$_SERVER[PHP_SELF]?action=edit&id=%s'>"._('edit')."</a></td>
    <td><a 
       onclick=\"return confirm('"._('Are you sure that you want to delete %s?')."')\"
       href='$_SERVER[PHP_SELF]?action=delete&id=%s'>"._('delete')."</a></td>
</tr>",
            ($count++%2==0?"#FFFFFF":"#DDDDDD"),
            $login, $userid, $lastname, $firstname, $email, $email, $phone,
            $id, $login, $id);
      }
      db_free_result($res);
      print '</table>'.LF;
      print '<P>'.LF;
      print '<h3>'._('New user').'</h3>'.LF;
      show_form();
      pageFooter();
      break;

    // --------------------------------------------------------------
    case "add":
    case "update":
        if (array_key_exists("login", $_POST)) $login=$_POST["login"];
        else die(_("Missing information (1)."));

        if (array_key_exists("lastname", $_POST)) $lastname=$_POST["lastname"];
        else die(_("Missing information (2)."));

        if (array_key_exists("firstname", $_POST)) 
           $firstname=$_POST["firstname"];
        else die(_("Missing information (3)."));

        if (array_key_exists("email", $_POST)) 
           $email=strtolower($_POST["email"]);
        else die(_("Missing information (4)."));

        if (array_key_exists("phone", $_POST)) $phone=$_POST["phone"];
        else die(_("Missing information (5)."));

        if (array_key_exists("password", $_POST)) $password=$_POST["password"];
        else $password="";

        if (array_key_exists("password2", $_POST))
            $password2=$_POST["password2"];
        else $password2="";

        if (array_key_exists("userid", $_POST)) $userid=$_POST["userid"];
        else $userid="";
        
        if (array_key_exists("language", $_POST)) $language=$_POST["language"];
        else $language="";

        if (array_key_exists("id", $_POST)) $id=$_POST["id"];
        else $id="";

        // ========= ADD ==========
        if ($action == "add") {
            if ($password != $password2) {
                pageHeader(_("Error"));
               print _('The passwords that you provided do not match.').LF;
               print '<P>'.LF;
               print _("Please use your browser's back button to correct the problem and resend the form.").LF;
               pageFooter();
               exit;
            }

            $res = db_query(
                "SELECT id
                 FROM   users
                 WHERE  login='$login'")
            or die(_("Unable to query database."));

            if (db_num_rows($res) > 0) {
                pageHeader(_("Error"));
               print _("Login <em>$login</em> is already in use.").'<P>'.LF;
               print _("Please use your browser's back button to correct the problem and resend the form.").LF;
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
            "password" => $password,
            "language"=> $language
            ));

            if ($userid == $_SESSION['userid']) {
               @session_destroy();
            }
            Header("Location: $_SERVER[PHP_SELF]");
        }

        // ========== UPDATE ===========
        else if ($action == "update")
        {
            if ($id=="") die(_("Missing information(A)"));
            if ($password != "")
            {
                if ($password != $password2)
                {
                    pageHeader(_("Error"));
                    print _('The passwords that you provided do not match.').LF;
                    print '<P>'.LF;
                    print _("Please use your browser's back button to correct the problem and resend the form.").LF;
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
                       userid=%s,
                       language=%s",
                    db_masq_null($lastname),
                    db_masq_null($firstname),
                    db_masq_null($email),
                    db_masq_null($phone),
               db_masq_null($login),
               db_masq_null($userid),
               db_masq_null($language),
                    $id);
         if ($password != "") {
                $query=sprintf("
                    %s, 
                    password=%s", 
                        $query,
                        db_masq_null(sha1($password))
            );
         }
         $query = sprintf("
                %s
                WHERE id=%s",
                    $query,
                    $id);

         $res = db_query($query)
         or die(_("Unable to execute query 1"));

         # db_close($conn);
         Header("Location: $_SERVER[PHP_SELF]");
   }

   break;

    // --------------------------------------------------------------
    case "delete":
        if (array_key_exists("id", $_GET)) $id=$_GET["id"];
        else $id="";

        $res = db_query(
            "DELETE FROM users
             WHERE  id = $id");
        if (!$res) {
         pageHeader(_("Error removing user."));
         print '<p>'.LF;
         print _('Unable to remove this user from the database.').'</p>'.LF;
         print '<p>'.LF;
         print _('The most likely cause for this failure is that the user is associated with one or more incidents.').'</p>'.LF;
         print '<p>'.LF;
         print '<a href="'.$_SERVER[PHP_SELF].'">'._('continue').'...</a></p>'.LF;
         pageFooter();
         exit;
      }

      Header("Location: $_SERVER[PHP_SELF]");

      break;

    // --------------------------------------------------------------
    case "edit":
        if (array_key_exists("id", $_GET)) $id=$_GET["id"];
        else die(_("Missing information (1)."));

        pageHeader(_("Edit user information"));
        show_form($id);
        pageFooter();
        break;

    // --------------------------------------------------------------
    default:
        die(_("Unknown action: $action"));
} // switch
?>
