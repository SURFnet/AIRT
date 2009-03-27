<?php
/* vim:syntax=php shiftwidth=3 tabstop=3
 * $Id$ 

 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2004,2005   Kees Leune <kees@uvt.nl>

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
require_once LIBDIR.'/airt.plib';
require_once LIBDIR.'/database.plib';
require_once LIBDIR.'/error.plib';
require_once LIBDIR.'/importqueue.plib';

$action = strip_tags(fetchFrom('REQUEST', 'action', '%s'));
defaultTo($action, 'list');


/** Helper function to display the queue.
 */
function showQueue($type='all') {
   pageHeader(_('Import queue'), array(
      'menu'=>'incidents',
      'submenu'=>'importqueue'));
   $out = '<div class="importqueue-overview-header">'.LF;
   $out .= '</div><!-- importqueue-overview-header -->'.LF;
   $out .= '<script language="JavaScript">'.LF;
   $out .= '   function submitMe(a) {'.LF;
   $out .= '      document.forms[1].elements[0].value = a;'.LF;
   $out .= '      document.forms[1].submit();'.LF;
   $out .= '   }'.LF;
   $out .= '</script>'.LF;
   $out .= t('<form action="%u/importqueue.php" method="post">'.LF, array(
      '%u'=>BASEURL));
   $out .= '<input type="hidden" name="action" value=""/>'.LF;
   $out .= queueFormatItems($type);
   $out .= '<div class="importqueue-overview-footer">'.LF;
   $out .= '<p/>'._('With selected: ');
   $out .= t('<input type="submit" onClick="submitMe(\'accept\')" name="action" value="%v">'.LF, array(
      '%v'=>_('Accept')));
   $out .= t('<input type="submit" onClick="submitMe(\'reject\')" name="action" value="%v">'.LF, array(
      '%v'=>_('Reject')));
   $out .= t('<input type="submit" name="action" value="%v">'.LF, array(
      '%v'=>_('Refresh')));
   $out .= '</div><!-- importqueue-overview-footer -->'.LF;
   $out .= '</form>'.LF;
   print $out;
}



switch (strtolower($action)) {
   //----------------------------------------------------------------
   case 'accept':
   case 'reject':
   case _('accept'):
   case _('reject'):
      $error = '';
      
      // no queue elments checked. Process button pushed from empty queue?
      if (!array_key_exists('checked', $_REQUEST)) {
         showQueue();
         break;
      }
      pageHeader(_('Import queue'));

      // interpret all decision and take action if accept or reject
      $tags=array();
      if (array_key_exists('group', $_REQUEST)) {
         $decisions = queueNormalize($_REQUEST['group'], $_REQUEST['checked'],
            strtolower($action));
      } else {
         $decisions = $_REQUEST['checked'];
      }

      foreach ($decisions as $id=>$value) {
         $update = false;
         switch ($value) {
            case 'on':
               if (strtolower($action) == _('accept') ||
                   strtolower($action) == 'accept') {
                  if (array_key_exists('template', $_REQUEST) &&
                      array_key_exists($id, $_REQUEST['template'])) {
                     $template = $_REQUEST['template'][$id];
                  } else {
                     $template = '';
                  }
                  queueElementAccept($id, $template);
               }
               elseif (strtolower($action) == _('reject') ||
                       strtolower($action) == 'reject') {
                  print t(_('Rejecting queue element %id<br/>').LF, array('%id'=>$id));
                  flush();
                  $value = 'rejected';
                  $update = true;
                  if ($update) {
                     if (updateQueueItem($id, 'status', $value, $error)) {
                        airt_error('ERR_QUERY', 'importqueue.php:'.__LINE__, $error);
                        Header("Location: $_SERVER[PHP_SELF]");
                        return;
                     }
                  }
               }
               break;
            default:
               print t(_('Ignoring queue element %id<br/>').LF, array('%id'=>$id));
               flush();
         }
      }

      // show updated queue;
      echo '<a href="incident.php">'._('Done').'.</a><br/>'.LF;
      break;

   // ----------------------------------------------------------------
   case "showdetails":
      if (!array_key_exists('id', $_GET)) {
         airt_error('PARAM_MISSING', 'importqueue.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         exit;
      }
      if (!is_numeric($_GET['id'])) {
         airt_error('PARAM_MISSING', 'importqueue.php:'.__LINE__);
         Header("Location: $_SERVER[PHP_SELF]");
         exit;
      }
      $item = queuePeekItem($_GET['id'], $error);
      if ($item == NULL) {
         airt_error('', 'importqueue.php:'.__LINE__, _('Error fetching queue item'));
         Header("Location: $_SERVER[PHP_SELF]");
         exit;
      }
      pageHeader(_('Queue details for item ').$_GET['id']);
      $out = '<table>'.LF;
      $out .= '<tr>'.LF;
      $out .= '  <td>'._('Status').'</td>'.LF;
      $out .= '  <td>'.$item['status'].'</td>'.LF;
      $out .= '</tr>'.LF;
      $out .= '<tr>'.LF;
      $out .= '  <td>'._('Type').'</td>'.LF;
      $out .= '  <td>'.$item['type'].'</td>'.LF;
      $out .= '</tr>'.LF;
      $out .= '<tr>'.LF;
      $out .= '  <td>'._('Summary').'</td>'.LF;
      $out .= '  <td>'.$item['summary'].'</td>'.LF;
      $out .= '</tr>'.LF;
      $out .= '<tr>'.LF;
      $out .= '  <td colspan="2" align="left" nowrap>'.
              _('Input queue data').'</td>'.LF;
      $out .= '</tr>'.LF;
      $out .= '<tr valign="top">'.LF;
      $out .= t('  <td colspan="2" align="left"><pre>%xml</pre></td>'.LF,
         array('%xml'=>htmlentities($item['content'])));
      $out .= '</tr>'.LF;
      $out .= '</table>'.LF;

      print $out;
      pageFooter();
      break;
   // ----------------------------------------------------------------
   case 'toggle':
      $toggle = fetchFrom('REQUEST','toggle', '%d');
      defaultTo($toggle,0);
      $toggle = ($toggle == 0) ? 1 : 0;
      // break omitted intentionally

   // ----------------------------------------------------------------
   case 'refresh':
   case _('refresh'):
   case 'list':
      $type=strip_tags(fetchFrom('REQUEST', 'type'));
      if (empty($type)) {
          $type = 'all';
      }
      showQueue($type);
      break;

   // ----------------------------------------------------------------
   case 'preftempl':
      pageHeader(_('Preferred mail templates'), array(
         'menu'=>'settings'));
      print importqueueTemplatesFormatItems();
      pageFooter();
      break;

   // ----------------------------------------------------------------
   case _('add preferred template'):
      $filter = strip_tags(fetchFrom('REQUEST', 'filter'));
      defaultTo($filter, '');
      $version = strip_tags(fetchFrom('REQUEST', 'version'));
      defaultTo($version, '');
      $mailtemplate = strip_tags(fetchFrom('REQUEST', 'mailtemplate'));
      defaultTo($mailtemplate, '');

      if ($filter == '' || $version == '' || $mailtemplate == '') {
         airt_error('PARAM_MISSING', 'importqueue.php:'.__LINE__);
         reload($_SERVER['HTTP_REFERER']);
      }

      if (setPreferredMailtemplate($filter, $version, $mailtemplate, $error) > 0) {
         airt_msg('Failed to set preferred template: '.$error);
      }

      reload($_SERVER['PHP_SELF'].'?action=preftempl');

      break;

   // ----------------------------------------------------------------
	case _('remove checked preferred templates'):
	   $check = fetchFrom('REQUEST', 'check');
		defaultTo($check, array());

      if (importqueueTemplatesGetItems($items, $error) > 0) {
			airt_msg('Failed to retrieve preferences: '.$error);
		} else {
			foreach ($check as $id=>$value) {
				if ($value == 'on') {
					if (removePreferredMailtemplate($items[$id]['filter'],
						$items[$id]['version'], $error) > 0) {
						airt_msg('Removal failed: '.$error);
						break;
					}
				}
			}
		}
		reload($_SERVER['PHP_SELF'].'?action=preftempl');
	   break;

   // ----------------------------------------------------------------
   case 'list_filters':
      print '<strong>'._('Available import filters').'</strong><p/>'.LF;
      print '<table>';
      print '<tr>';
      print '  <th>'._('Filter name').'</th>'.LF;
      print '  <th>'._('Version').'</th>'.LF;
      print '</tr>';
      foreach (importqueue_get_filters() as $id=>$f) {
         print '<tr>';
         print '   <td>'.'filter_'.strip_tags($f).'</td>'.LF;
         if (function_exists('filter_'.$f.'_getVersion')) {
            $func = 'filter_'.$f.'_getVersion';
            print '   <td>'.$func().'</td>'.LF;
         }
         print '</tr>'.LF;
      }
      print '</table>'.LF;
      break;

   // ----------------------------------------------------------------
   default:
      airt_error('PARAM_INVALID', 'importqueue.php:'.__LINE__);
      reload();
}
?>
