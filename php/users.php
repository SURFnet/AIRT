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
 require_once LIBDIR.'/incident.plib';
 require_once LIBDIR.'/formkey.plib';

 $action=strip_tags(fetchFrom('REQUEST', 'action'));
 defaultTo($action, 'list');
 $formkey = new formKey();
 $formkey->init();

 function show_form($formkey, $id="") {
    $lastname = $firstname = $email = $phone = $login = $userid = '';
    $action = "add";
    $caps = array();
    $cap_iodef='';
    $cap_login='';
    $submit = _("Add!");

    if (array_key_exists('language', $_SESSION)) {
       $language = $_SESSION['language'];
    } elseif (Setup::getOption('defaultlanguage', $defaultlang) === true) {
       $language = $defaultlang;
    } else {
       $language = 'en_US.utf8';
    }
    if ($id != "") {
       if (!is_numeric($id)) {
          die(_('Invalid parameter type ').__LINE__);
       }
       $res = db_query("
          SELECT lastname, firstname, email, phone, login, userid, password,
               language, x509name
        FROM   users
        WHERE  id = $id")
        or die(_("Unable to query database."));

        if (db_num_rows($res) > 0) {
            $row = db_fetch_next($res);
            if (array_key_exists('lastname', $row))
               $lastname=strip_tags($row['lastname']);
            if (array_key_exists('firstname', $row))
               $firstname=strip_tags($row['firstname']);
            if (array_key_exists('email', $row))
               $email=strip_tags($row['email']);
            if (array_key_exists('phone', $row))
               $phone=strip_tags($row['phone']);
            if (array_key_exists('login', $row))
               $login=strip_tags($row['login']);
            if (array_key_exists('userid', $row))
               $userid=strip_tags($row['userid']);
            if (array_key_exists('language', $row))
               $language=strip_tags($row['language']);
            if (array_key_exists('x509name', $row))
               $x509name=strip_tags($row['x509name']);

            $action = "update";
            $submit = _("Update!");
        }
        if (getUserCapabilities($id, $caps, $error) == false) {
           airt_msg(_('Error retrieving user capabilities:'). $error);
           return false;
        } else {
           $cap_iodef = ($caps[AIRT_USER_CAPABILITY_IODEF] == 1) ? 'checked' : '';
           $cap_login = ($caps[AIRT_USER_CAPABILITY_LOGIN] == 1) ? 'checked' : '';
        }
    }
    print '<form action="'.BASEURL.'/users.php" method="POST">'.LF;
    print t('<input type="hidden" name="formkey" value="%key"/>'.LF, array(
       '%key'=>$formkey->get()));
    print '<input type="hidden" name="action" value="'.
       strip_tags($action).'">'.LF;
    print '<input type="hidden" name="id" value="'.
       strip_tags($id).'">'.LF;
    print '<table>'.LF;
    print '<tr>'.LF;
    print '    <td>'._('Login').'</td>'.LF;
    print '    <td><input type="text" size="30" name="login" value="'.
       strip_tags($login).'"></td>'.LF;
    print '</tr>'.LF;
    print '<tr>'.LF;
    print '    <td>'._('X509 Subject').'</td>'.LF;
    print '    <td><input type="text" size="30" name="x509name" value="'.
       strip_tags($x509name).'"></td>'.LF;
    print '</tr>'.LF;
    print '<tr>'.LF;
    print '    <td>'._('Organizational user id').'</td>'.LF;
    print '    <td><input type="text" size="30" name="userid" value="'.
      strip_tags($userid).'"></td>'.LF;
    print '</tr>'.LF;
    print '<tr>'.LF;
    print '    <td>'._('Last name').'</td>'.LF;
    print '    <td><input type="text" size="30" name="lastname" value="'.
      strip_tags($lastname).'"></td>'.LF;
    print '</tr>'.LF;
    print '<tr>'.LF;
    print '    <td>'._('First name').'</td>'.LF;
    print '    <td><input type="text" size="30" name="firstname" value="'.
      strip_tags($firstname).'"></td>'.LF;
    print '</tr>'.LF;
    print '<tr>'.LF;
    print '    <td>'._('Email address').'</td>'.LF;
    print '    <td><input type="text" size="30" name="email" value="'.
      strip_tags($email).'"></td>'.LF;
    print '</tr>'.LF;
    print '<tr>'.LF;
    print '    <td>'._('Phone number').'</td>'.LF;
    print '    <td><input type="text" size="30" name="phone" value="'.
      strip_tags($phone).'"></td>'.LF;
    print '</tr>'.LF;
    print '<tr>'.LF;
    print '   <td>'._('Preferred language').'</td>'.LF;
    print '   <td>'.formatAvailableLanguages('language', $language).'</td>'.LF;
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
    print '<p>'.LF;
    print '<b>User capabilities</b><br/>'.LF;
    print '<input type="checkbox" name="cap_iodef" '.$cap_iodef.'>'.
       _('IODEF capable').'</input><br/>'.LF;
    print '<input type="checkbox" name="cap_login" '.$cap_login.'>'.
       _('Interactive login allowed').'</input><br/>'.LF;
    print '<input type="submit" value="'.strip_tags($submit).'">'.LF;
    print '<p/>'.LF;
    print '</form>'.LF;
 }

 switch ($action) {
   // --------------------------------------------------------------
   case 'list':
   case 'Show':
      pageHeader(_("Users"), array('menu'=>'settings'));

      $interactive = fetchFrom('REQUEST', 'interactive', '%s');
      defaultTo($interactive, 'on');

      $contacts = fetchFrom('REQUEST', 'contacts', '%s');
      defaultTo($contacts, 'on');

      $all = fetchFrom('REQUEST', 'all', '%s');
      defaultTo($all, 'off');

      print t('<form action="%url" method="GET">'.LF, array(
         '%url'=>BASEURL.'/users.php'));
      print _('Display: ').LF;
      print t('<input type="checkbox" name="interactive" %checked>'.LF, array(
         '%checked'=>$interactive == 'on' ? 'CHECKED' : ''));
      print _('Interactive users').' '.LF;
      print t('<input type="checkbox" name="contacts" %checked>'.LF, array(
         '%checked'=>$contacts == 'on' ? 'CHECKED' : ''));
      print _('Constituency contacts').' '.LF;
      print t('<input type="checkbox" name="all" %checked>'.LF, array(
         '%checked'=>$all == 'on' ? 'CHECKED' : ''));
      print _('All contacts').LF;
      print '<input type="submit" name="action" value="Show">'.LF;
      print '</form>';

      $query = q('SELECT id, login, lastname, firstname, email, phone,
                userid FROM users ');
      if ($all != 'on') {
         $mods = array();
         if ($interactive == 'on') {
            $mods[] = 'NOT login IS NULL';
         }
         if ($contacts == 'on') {
            $mods[] = 'id IN (select distinct userid from constituency_contacts)';
         }
         if (sizeof($mods) > 0) {
            $query .= 'WHERE ';
            $query .= implode(' OR ', $mods);
         }
      }
      $query .= ' ORDER  BY login,email';

      if (($res = db_query($query)) === false) {
         die(_('Unable to query database.'));
      }

      print '<table class="horizontal">'.LF;
      print '<tr>'.LF;
      print '   <th>'._('Login').'</th>'.LF;
      print '   <th>'._('User id').'</th>'.LF;
      print '   <th>'._('Last name').'</th>'.LF;
      print '   <th>'._('First name').'</th>'.LF;
      print '   <th>'._('Email').'</th>'.LF;
      print '   <th>'._('Phone').'</th>'.LF;
      print '   <th/>'.LF;
      print '</tr>'.LF;
      $count=0;
      while ($row = db_fetch_next($res)) {
         $id = strip_tags($row["id"]);
         $login = strip_tags($row["login"]);
         $lastname = strip_tags($row["lastname"]);
         $firstname = strip_tags($row["firstname"]);
         $email = strip_tags($row["email"]);
         $phone = strip_tags($row["phone"]);
         $userid = strip_tags($row["userid"]);

         printf("
<tr>
    <td>%s</td>
    <td>%s</td>
    <td>%s</td>
    <td>%s</td>
    <td><a href='mailto:%s'>%s</a></td>
    <td>%s</td>
    <td nowrap><a href='%s?action=edit&id=%s'>"._('edit')."</a>
    <a 
       onclick=\"return confirm('"._('Are you sure that you want to delete %s?')."')\"
       href='%s?action=delete&id=%s&formkey=%s'>"._('delete')."</a></td>
</tr>",
            htmlentities($login), htmlentities($userid), 
            htmlentities($lastname), htmlentities($firstname), 
            urlencode($email), 
            htmlentities($email), htmlentities($phone),
            BASEURL.'/users.php',urlencode($id),
            htmlentities($email), 
            BASEURL.'/users.php', urlencode($id), urlencode($formkey->get()));
      }
      db_free_result($res);
      print '</table>'.LF;

      print '<P>'.LF;
      print '<h3>'._('New user').'</h3>'.LF;
      show_form($formkey);
      pageFooter();
      break;

    // --------------------------------------------------------------
    case "add":
    case "update":
        if ($formkey->validate() == false) {
            print(_('Unable to verify form key'));
            exit(reload());
        }
        $login = strip_tags(fetchFrom('POST', 'login'));
        defaultTo($login, '');
        $x509name = strip_tags(fetchFrom('POST', 'x509name'));
        defaultTo($x509name, '');
        $lastname = strip_tags(fetchFrom('POST', 'lastname'));
        defaultTo($lastname, '');
        $firstname = strip_tags(fetchFrom('POST', 'firstname'));
        defaultTo($firstname, '');
        $email = strtolower(strip_tags(fetchFrom('POST', 'email')));
        if (empty($email)) {
           die(_('Missing information ').__LINE__);
        }
        $phone = strip_tags(fetchFrom('POST', 'phone'));
        defaultTo($phone, '');
        $password = strip_tags(fetchFrom('POST', 'password'));
        defaultTo($password, '');
        $password2 = strip_tags(fetchFrom('POST', 'password2'));
        defaultTo($password2, '');
        $userid = strip_tags(fetchFrom('POST', 'userid'));
        defaultTo($userid, '');
        $language = strip_tags(fetchFrom('POST', 'language'));
        defaultTo($language, '');
        $id = strip_tags(fetchFrom('POST', 'id', '%d'));
        defaultTo($id, '');
        $cap_login = fetchFrom('POST', 'cap_login');
        defaultTo($cap_login, 'off');
        $cap_iodef = fetchFrom('POST', 'cap_iodef');
        defaultTo($cap_iodef, 'off');

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

            db_free_result($res);

            if (addUser(array(
                "lastname" => $lastname,
                "firstname" => $firstname,
                "email" => $email,
                "phone" => $phone,
                "login" => $login,
                "userid" => $userid,
                "password" => $password,
                "language"=> $language,
                "x509name"=> $x509name), $status) == false) {
                airt_msg($status);
                reload();
                exit();
            }
                ;
            $u = getUserByEmail($email);
            if ($cap_iodef == 'on') $cap_iodef = 1;
            else $cap_iodef = 0;
            if ($cap_login == 'on') $cap_login = 1;
            else $cap_login = 0;
            setUserCapabilities($u['id'], array(
                AIRT_USER_CAPABILITY_IODEF => $cap_iodef,
                AIRT_USER_CAPABILITY_LOGIN => $cap_login), $error);

            if ($userid == $_SESSION['userid']) {
               @session_destroy();
            }
            reload();
        }

        // ========== UPDATE ===========
        else if ($action == "update")
        {
            if ($id=="") die(_("Missing information ").__LINE__);
            if ($password != "") {
                if ($password != $password2) {
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
                       language=%s,
                       x509name=%s",
                    db_masq_null($lastname),
                    db_masq_null($firstname),
                    db_masq_null($email),
                    db_masq_null($phone),
               db_masq_null($login),
               db_masq_null($userid),
               db_masq_null($language),
               db_masq_null($x509name));
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

         if ($cap_iodef == 'on') $cap_iodef = 1;
         else $cap_iodef = 0;
         if ($cap_login == 'on') $cap_login = 1;
         else $cap_login = 0;
         setUserCapabilities($id, array(
            AIRT_USER_CAPABILITY_LOGIN=>$cap_login,
            AIRT_USER_CAPABILITY_IODEF=>$cap_iodef), $error);
         reload();
   }

   break;

    // --------------------------------------------------------------
    case "delete":
       $id = fetchFrom('REQUEST', 'id', '%d');
       defaultTo($id, '');
       if (!is_numeric($id)) {
           die(_('Invalid parameter type ').__LINE__);
       }
       if ($formkey->validate() == false) {
           print(_('Unable to verify form key'));
           exit(reload());
       }
       /* cascade down if user is not associated with any existing
        * incidents
        */
       $caps = array();
       if (getUserCapabilities($id, $caps, $error) === false) {
          airt_msg(_('Error fetching user capabilities in').
            ' users.php:'.__LINE__);
          reload();
       }
       if (sizeof($caps) > 0) {
          $incidents = array();
          if (getIncidentsByUserID($id, $incidents, $error) === false) {
             airt_msg(_('Error fetching incidents in:').
               ' users.php'.__LINE__);
             reload();
          }
          if (sizeof($incidents) == 0) {
             $res = db_query(q('delete from user_capabilities
                where userid=%uid', array('%uid'=>$id)));
             if ($res === false) {
                airt_msg(_('Unable to unset user capabilities in').
                   ' users.php:'.__LINE__);
                reload();
             }
             db_free_result($res);
          }
       }

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
         print '<a href="'.BASEURL.'/users.php">'._('continue').'...</a></p>'.LF;
         pageFooter();
         exit;
      }

      reload();

      break;

    // --------------------------------------------------------------
    case "edit":
       $id = fetchFrom('GET', 'id', '%d');
       if (empty($id)) {
          die(_("Missing information ").__LINE__);
       }
       if (!is_numeric($id)) {
           die(_('Invalid parameter type ').__LINE__);
       }
       pageHeader(_("Edit user information"), array(
		    'menu'=>'settings'));
       show_form($formkey, $id);
       pageFooter();
       break;

    // --------------------------------------------------------------
    default:
        die(_("Unknown action: ".strip_tags($action)));
} // switch
?>
