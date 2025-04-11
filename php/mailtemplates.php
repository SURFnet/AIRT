<?php
/* vim: syntax=php tabstop=3 shiftwidth=3
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

$action = strip_tags(fetchFrom('REQUEST', 'action', '%s'));
defaultTo($action, 'list');
$action = strip_tags($action);

airt_profile('mailtemplates.php SESSION='.print_r($_SESSION,true));
airt_profile('mailtemplates.php REQUEST='.print_r($_REQUEST,true));

switch ($action) {
   // -------------------------------------------------------------------
   case "list":
      listTemplates(true);
      break;

   // -------------------------------------------------------------------
   case "edit":
      $msg = '';
      $template = strip_tags(fetchFrom('REQUEST', 'template', '%s'));
      if (empty($template)) {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         reload();
         return;
      }
      pageHeader(_('Edit mail template'), array(
         'menu'=>'mail',
         'submenu'=>'templates'));

      if (($msg = get_template($template)) == false) {
         printf(_('Template not available.'));
      } else {
         print _('Update the template and press the "Save!" button to save it. The first
line of the message will be used as the subject. You may use the following
special variables in the template:').'<p>'.LF;
      print_variables_info();
      $update = array();
      get_template_actions($template, $update);
      if (getMailtemplateCapabilities($template, $caps, $err) === false) {
         airt_msg($err);
         reload();
      }
      print '<P>';
      print '<form action="'.BASEURL.'/mailtemplates.php" method="POST">'.LF;
      // note: potential danger here; html and php tags are NOT scrubbed
      print '<textarea wrap name="message" cols="75" rows="15">'.$msg.
         '</textarea>'.LF;
      print '<P>'.LF;
      print _('Automatically change settings after mail based on this template is sent:').'<P>'.LF;
      print '<table>'.LF;
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
      print '<tr>'.LF;
      print '   <td>'._('Severity').'</td>'.LF;
      print '   <td>'.LF;
      print getIncidentSeveritySelection("update[severity]", $update['severity'],
         array(-1=>_('Do not update')));
      print '   </td>'.LF;
      print '</tr>'.LF;
      print '</table>'.LF;
      print '<p/>'.LF;
      print _('Select capabilities provided by this template:').'<P/>'.LF;
      print '<input type="hidden" name="update[capability]">'.LF;
      foreach ($AIRT_MAIL_CAPABILITIES as $c) {
         if (array_key_exists($c, $caps) && $caps[$c] == 1) {
            $checked = 'CHECKED';
         } else {
            $checked = '';
         }
         echo '<input type="checkbox" '.$checked.' name="capability['.$c.
            ']"> '.$c.'<br/>'.LF;
      }
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
      $capability = fetchFrom('REQUEST', 'capability');
      if (empty($capability)) {
         $capability = array();
      }
      $update['capability'] = array();
      foreach ($capability as $key=>$value) {
         $update['capability'][$key] = $value;
      }

      $message = strip_tags($message);
      // $message = stripslashes($message);

      if (save_template($template, $message, $update)) {
         airt_error('ERR_FUNC', 'mailtemplates.php:'.__LINE__);
      }
      listTemplates(true);

      break;

   // -------------------------------------------------------------------
   case "new":

      pageHeader(_('New mail template'), array(
         'menu'=>'mail',
         'submenu'=>'templates'));
      print _('Enter your new template in the text field below. Use the following variables in your text body:');
      print '<P>'.LF;
      $update = array('state'=>-1, 'status'=>-1, 'type'=>-1, 'severity'=>-1);
      print_variables_info();
      print '<P>'.LF;
      print '<form action="'.BASEURL.'/mailtemplates.php" method="POST">'.LF;
      print _('Template name').': <input type="text" size="40" name="template">'.LF;
      print '<P>'.LF;
      print _('Message').':<BR>'.LF;
      print '<textarea wrap name="message" cols="75" rows="20"></textarea>'.LF;
      print '<P>'._('Automatically change settings after mail based on this template is sent:').'<P>'.LF;
      print '<table>'.LF;
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
      print '<tr>'.LF;
      print '   <td>'._('Severity').'</td>'.LF;
      print '   <td>'.LF;
      print getIncidentSeveritySelection("update[severity]", $update['severity'],
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
      listTemplates(true);

      break;

   // -------------------------------------------------------------------
   case 'prepare':
      airt_profile('prepare');

      if (array_key_exists('template', $_GET)) {
          $global_template = strip_tags(fetchFrom('GET', 'template'));
      } else if (array_key_exists('template', $_SESSION)) {
          $global_template = strip_tags(fetchFrom('SESSION', 'template'));
      }

      defaultTo($global_template, '');
	   
      airt_profile('template='.$global_template);

      /* $incidentids will contain a comma-separated list of incident ids to
       * work on
       */
      $incidentids = strip_tags(fetchFrom('SESSION', 'incidentids'));
      if (empty($incidentids)) {
         $incidentid = fetchFrom('REQUEST', 'incidentid', '%d');
         if (empty($incidentid)) {
            if (array_key_exists('incidentid', $_SESSION)) {
               $incidentid = $_SESSION['incidentid'];
            }
	         if(empty($incidentid)) {
               airt_msg(_('No incident to work on.'));
               reload();
            }
         }
         $incidentids = array($incidentid);
      } else {
         $incidentids = explode(',', $incidentids);
      }

      // ensure only numric incident ids survive
      foreach ($incidentids as $index=>$id) {
          if (!is_numeric($id)) {
              unset($incidentids[$index]);
          }
      }

      /* to contains the user ids of the recipient email addresss.
       * if this array is set, only email to the email addresses
       * explicitly mentioned in this comma-separated array will
       * be processed
       */
      $to = fetchFrom('REQUEST', 'to');
      if (empty($to)) {
         $to = array();
      } else {
         $to = explode(',', $to);
      }
      foreach ($to as $key=>$value) {
          if (!is_numeric($value)) {
              unset($to[$key]);
          }
      }

      /* Fetch override */
      $override = fetchFrom('REQUEST', 'override', '%d');
      defaultTo($override, 0);
       
      $autosend=fetchFrom('SESSION', 'autosend');
      if (array_search($autosend, array('yes', 'no')) === FALSE) 
          $autosend = 'no'; // whitelist enforcement
      defaultTo($autosend, 'no');

      prepare_message($global_template, $override, 
			$incidentids, $autosend, $to);

      break;

   // -------------------------------------------------------------------
   case _('Skip and prepare next'):
      $incidentids = array_filter(explode(',', fetchFrom('POST', 'incidentids')), 'is_numeric');
      $override = fetchFrom('REQUEST', 'override', '%d');
      defaultTo($override, 0);
      $template = strip_tags(fetchFrom('POST', 'template'));
      reload(BASEURL.'/mailtemplates.php?action=prepare'.
         '&template='.urlencode($template).
         '&override='.urlencode($override).
         '&incidentids='.implode(',',$incidentids));
      break;


   // -------------------------------------------------------------------
   case 'send':
   case _('Send'):
   case _('Send and prepare next'):
		airt_profile('Send');
      $from = fetchFrom('POST', 'from');
      if (empty($from)) {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         reload();
         return;
      }
		airt_profile('from='.$from);

      $incidentid = fetchFrom('POST', 'incidentid', '%d');
      if (empty($incidentid)) {
         airt_error('PARAM_MISSING', 'mailtemplates.php:'.__LINE__);
         reload();
         return;
      }
		airt_profile('incidentid='.$incidentid);

		$override = fetchFrom('REQUEST', 'override', '%d');
		defaultTo($override, 0);

		if (!array_key_exists('skipped', $_SESSION)) {
			$_SESSION['skipped'] = array();
			airt_profile('Created new session variable skipped.');
		} 
      /* comma separated list of receipients. */
      $to = strip_tags(fetchFrom('POST', 'to'));
      if (empty($to)) {
			$_SESSION['skipped'][$incidentid] = 1;
			airt_profile('Empty To. Skipping');
      }
      $subject = strip_tags(fetchFrom('POST', 'subject'));
      if (empty($subject)) {
			$_SESSION['skipped'][$incidentid] = 1;
			airt_profile('Empty Subject. Skipping');
      }
      $msg = fetchFrom('POST', 'msg');
      if (empty($msg)) {
			$_SESSION['skipped'][$incidentid] = 1;
			airt_profile('Empty message. Skipping');
      }
      $sign = strip_tags(fetchFrom('POST', 'sign'));
      if (empty($sign)) {
         $sign = 'off';
      }

      $incidentids = fetchFrom('SESSION', 'incidentids');
      // do not strip_tags here; it will break the email address
      $replyto = fetchFrom('POST', 'replyto');
      defaultTo($replyTo, '');
      $template = strip_tags(fetchFrom('SESSION', 'template'));

      $autosend = fetchFrom('SESSION', 'autosend');
      defaultTo($autosend, 'no');
      if (array_search($autosend, array('yes', 'no')) === FALSE) {
         $autosend = 'no';
      }

      /* prevent sending bogus stuff */
      if (trim($to) == '') {
			$_SESSION['skipped'][$incidentid] = 1;
      }
      $to = explode(',', $to);
      foreach ($to as $key=>$value) {
          if (empty($value)) {
              unset($to[$key]);
          }
      }
      $to = implode(',', $to);

      if (trim($msg) == '') {
			$_SESSION['skipped'][$incidentid] = 1;
      }


		if (! array_key_exists($incidentid, $_SESSION['skipped'])) {
			airt_profile('Processing mail.');
			/* clean off html and stuff (only unformatted mail) */
			// $msg = stripslashes($msg);
			$msg = str_replace("\r", '', $msg);

			/* prepare the intial state of the message */
			$hdrs = array(
				'From'     => $from,
				'Subject'  => $subject,
				'To'       => $to,
				'X-Mailer' => 'AIRT $Revision: 1556 $ http://www.airt.nl',
				'MIME-Version' => '1.0',
			);
			if ($replyto != '') {
				 $hdrs['Reply-To'] = $replyto;
			}

			/* set up mail recipient */
			Setup::getOption('mailcc', $mailcc, true);
			if (!empty($mailcc)) {
				$mailto = array($to, $mailcc);
				$hdrs["Cc"] = $mailcc;
			} else {
				$mailto = array($to);
			}

			Setup::getOption('mailenvfrom', $mailenvfrom, true);
			/* set up envelope sender */
			$envfrom="-f".$mailenvfrom;

			/* will send via Mail class */
			$mail_params = array(
				'sendmail_args' => $envfrom,
				'sendmail_path' => SENDMAIL,
			);

			$msg_params = array();
			$msg_params['content_type'] = 'multipart/mixed';
			$msg_params['disposition'] = 'inline';


			$attachcount=0;

			if ($sign == 'off') {
				airt_profile('Not PGP signing');
				$msg_params['content_type'] = 'text/plain';
				unset($msg_params['disposition']);
				$mime = new Mail_mimePart($msg, $msg_params);
				$m = $mime->encode();
				$body = $m['body'];
			} else {
				airt_profile('PGP signing');
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
				$body_params['encoding'] = 'quoted-printable';
				$mime->addsubpart($msg, $body_params);

				/* message signature */
				$sig_params = array();
				$sig_params['content_type'] = 'application/pgp-signature';
				$sig_params['description'] = _('Digital Signature');
				$sig_params['disposition'] = 'attachment';
				$sig_params['dfilename'] = 'signature.asc';
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
				$msg = preg_split('/[-][-]'.$delimiter.'/', $body);
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
				exec($cmd, $output);
				if (($sig = file_get_contents("$fname.asc")) == false) {
					die(_('Unable to read signed message. GPG said: ' . implode("\n", $output)));
				}

				/* clean up */
				unlink($fname);
				unlink("$fname.asc");
				$body = preg_replace("/@AIRT-SIGNATURE@/", $sig, $body);
			}

			$mail = Mail::factory('sendmail', $mail_params);
			if ($mail !== TRUE && PEAR::isError($mail)) {
				 airt_msg(_("Error creating mail relay!"). ' - '.$mail->getMessage());
				 exit(reload());
			}
			$hdrs = array_merge($hdrs, $m['headers']);
			// Convert newlines to local newline style
			// before passing it to 'sendmail'
			$body = preg_replace('~(*BSR_ANYCRLF)\R~', LF , $body);
			$send = $mail->send($mailto, $hdrs, $body);
			airt_profile('Attempting to send.');
			if ($send !== TRUE && PEAR::isError($send)) {
				 airt_profile('Failed sending');
				 airt_msg(_("Error sendmail mail!"). ' - '.$send->getMessage());
				 exit(reload());
			} else {
			    airt_profile('Successfully handed off to sendmail.');
			}

			addIncidentComment(array(
				'comment'=>sprintf(_("Email (from template %s) sent to %s: %s"),
					$template, $to, $subject),
				'incidentid'=>$incidentid));
			generateEvent('postsendmail', array(
				'incidentid'=>$incidentid,
				'sender'=>htmlentities($from),
				'recipient'=>$to,
				'subject'=>$subject));

			airt_profile('Processing template actions.');
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
				if ($actions['severity'] == -1) {
					$actions['severity'] = '';
				} else {
					addIncidentComment(array(
						'comment'=>sprintf(_('Severity updated to %s'),
							getIncidentSeverityLabelByID($actions['severity'])),
						'incidentid'=>$incidentid));
				}
				$incident = getIncident($incidentid);
				$actions['template'] = $incident['template'];
				$actions['desc'] = $incident['desc'];
				airt_profile('Updating incident data');
				updateIncident($incidentid, $actions);
			}
		} else { // if $skipped 
			airt_profile('Skipped.');
		}
      if ($action == _('Send and prepare next') && isset($incidentids) &&
         isset($template)) {
         reload(BASEURL.'/mailtemplates.php?action=prepare'.
            '&override='.urlencode($override));
      } else {
			$n = sizeof(array_keys($_SESSION['skipped']));
			if ($n > 0) {
				pageHeader(_('Error sending email'), array(
					'menu'=>'mail',
					'submenu'=>'templates'
				));
				echo '<div class="block">'.LF;
				echo '<h3>'._('Unsent email').'</h3>'.LF;
				echo '<p/>'.LF;
				echo _('Email for the following incidents failed to send due to errors.').LF;
				echo '<p/>'.LF;
				echo '<ul>'.LF;
				foreach (array_keys($_SESSION['skipped']) as $key) {
					printf(t('<li><a href="%u/incident.php?action=details&incidentid=%d">%l</a></li>'.LF, array(
						'%u'=>BASEURL,
						'%d'=>$key,
						'%l'=>normalize_incidentid($key))));
				}
				echo '</ul>'.LF;
				echo '</div>'.LF;
				printf("<a href=\"incident.php\">"._('Continue').LF);
				pageFooter();
				unset($_SESSION['skipped']);
			} else {			
         	reload('incident.php');
			}
      }

      break;

   // -------------------------------------------------------------------
   case 'deactivate':
      $template = fetchFrom('REQUEST', 'template', '%s');
      if (empty($template)) {
          airt_msg(_('Missing or invalid parameter in mailtemplates.php').':'.__LINE__);
          reload();
          exit;
      }
      if (deactivateMailtemplate($template, $error) == false) {
          airt_msg(t(_('Unable to delete mailtemplate %id: %reason'), array(
             '%id'=>strip_tags($template),
             '%error'=>htmlentities($error))));
          reload();
          exit;
      }
      reload();
      break;

   case 'activate':
      $template = fetchFrom('REQUEST', 'template', '%s');
      if (empty($template)) {
          airt_msg(_('Missing or invalid parameter in mailtemplates.php').':'.__LINE__);
          reload();
          exit;
      }
      if (activateMailtemplate($template, $error) == false) {
          airt_msg(t(_('Unable to delete mailtemplate %id: %reason'), array(
             '%id'=>strip_tags($template),
             '%error'=>htmlentities($error))));
          reload();
          exit;
      }
      reload();
      break;

   case 'inactive':
      pageHeader(_('Inactive mail templates'), array(
        'menu'=>'mail',
        'submenu'=>'Inactive'));
      $out = '';
      $out .= '<div class="block">'.LF;
      $out .= t('<h3>%l</h3>'.LF, array('%l'=>_('Deactivated templates')));
      $res = db_query("SELECT name FROM mailtemplates WHERE status='inactive'
      ORDER BY name");
      $row = db_fetch_next($res);
      if (db_num_rows($res) == 0) {
          $out .= _('No inactive mail templates.');
      } else {
          $out .= '<table class="horizontal">'.LF;
          while ($row !== false) {
              $out .= '<tr>'.LF;
              $out .= t('<td>%t</td>'.LF, array('%t'=>$row['name']));
              $out .= t('<td><a href="%u?action=activate&template=%t">%l</a></td>'.LF,
                array('%u'=>$_SERVER['PHP_SELF'],
                      '%t'=>urlencode($row['name']),
                      '%l'=>_('activate')));
              $out .= '</tr>'.LF;
              $row = db_fetch_next($res);
          }
          $out .= '</table>'.LF;
      }
      $out .= '</div>'.LF;
      print $out;
      pageFooter();
      break;

   default:
      die(_('Unknown action: '. $action));
} // switch
