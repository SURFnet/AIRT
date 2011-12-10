<?php
require_once 'config.plib';
require_once LIBDIR.'/airt.plib';
require_once LIBDIR.'/database.plib';
require_once LIBDIR.'/importqueue.plib';

$action = strip_tags(fetchFrom('REQUEST','action'));
defaultTo($action,'upload');

switch ($action) {
    case 'upload':
        pageHeader(_('File upload'), array(
            'menu'=>'incidents',
            'submenu'=>'upload'));
        $out = '<div>'.LF;
        $out .= '<div class="block">'.LF;
        $out .= '<h3>'._('Upload custom CSV').'</h3>'.LF;
        $out .= _('Please select a delimited file.'). ' ';
        $out .= _('The first line of the file must be a header containing column names.').LF;
        $out .= _('One column must contain a single IP address.');
        $out .= '<p/>'.LF;
        $out .= '<form enctype="multipart/form_data" method="POST">'.LF;
        $out .= '<table>'.LF;
        $out .= '<tr>'.LF;
        $out .= '<td>'._('File').':</td>'.LF;
        $out .= '<td colspan="2"><input type="file" name="file"/><br/>';
        $out .= '</td>'.LF;
        $out .= '</tr>'.LF;
        $out .= '<tr>'.LF;
        $out .= '<td>'._('Separator').':</td>'.LF;
        $out .= '<td><input type="text" size="2" name="sep"/></td>'.LF;
        $out .= '<td>'._('Single character field separator').'</td>'.LF;
        $out .= '</tr>'.LF;
        $out .= '<tr>'.LF;
        $out .= '<td>'._('IP address label').':</td>'.LF;
        $out .= '<td><input type="text" name="ip_label"/></td>'.LF;
        $out .= '<td>'._('Column name contain the IP address').'</td>'.LF;
        $out .= '</tr>'.LF;
        $out .= '<tr>'.LF;
        $out .= '<td>'._('CSV filter version identifier').':</td>'.LF;
        $out .= '<td><input type="text" name="version"/></td>'.LF;
        $out .= '<td>'._('Set to define a preferred template for filter_csv').'</td>';
        $out .= '</tr>'.LF;
        $out .= '</table>'.LF;
        $out .= '<input type="hidden" name="action" value="import"/>'.LF;
        $out .= '<input type="submit" value="'._('Send to import queue').'"/>'.LF;
        $out .= '</form>'.LF;
        $out .= '</div><!-- block -->'.LF;
        $out .= '</div>'.LF;
        print $out;
        pageFooter();
        break;

    case 'import':
        if (($f = fopen($_FILES['file']['tmp_name'], 'r')) === FALSE) {
            airt_msg(_('Could not open uploaded file'));
            exit(reload());
        }
        if (($ip_label = fetchFrom('POST', 'ip_label', '%s')) == '') {
            airt_msg(_('Unable to retrieve ip_label'));
            exit(reload());
        }
        if (($sep = fetchFrom('POST', 'sep', '%s')) == '') {
            airt_msg(_('Unable to retrieve separator'));
            exit(reload());
        }
        if (($version = fetchFrom('POST', 'version', '%s')) == '') {
            $version = '';
        }
        if (strlen($delimiter) > 1) {
            airt_msg(_('Delimiter must be one character only'));
            exit(reload());
        }
        $row = fgetcsv($f, 0, $sep);
        if (($index = array_search($ip_label, $row)) === FALSE) {
            airt_msg(_('Index field not found. aborting.'));
            exit(reload());
        }

        $count = 0;
        $output = '<airt>'.LF;
            
        while (($row = fgetcsv($f, 0, $sep)) !== FALSE) {
            $output .= t('<queuedata filter="filter_csv" version="%v" ref="%c">'.LF, 
                array('%c'=>$count, '%v'=>$version));
            $output .= '     <status>open</status>'.LF;
            $output .= '     <sender>Generic CSV</sender>'.LF;
            $output .= '     <type>Generic CSV Report</type>'.LF;
            $output .= t('     <summary>CSV import "%f"</summary>'.LF,
                array('%f'=>htmlentities(trim($row[$index]))));
            $output .= '</queuedata>'.LF;
            $output .= t('  <incident id="%c">'.LF, array('%c'=>$count));
            $output .= '    <ticketInformation>'.LF;
            $output .= '      <prefix>GenericCSV</prefix>'.LF;
            $output .= '    </ticketInformation>'.LF;
            $output .= '    <technicalInformation>'.LF;
            $output .= t('      <ip>%ip</ip>'.LF, array(
                '%ip'=>htmlentities(trim($row[$index]))));
            $output .= '      <logging>'.LF;
            $output .= htmlentities(trim(join($sep, $row)));
            $output .= '      </logging>'.LF;
            $output .= '   </technicalInformation>'.LF;
            $output .= '</incident>'.LF;
            $count++;
        }
        $output .= '</airt>'.LF;
        if (import($output,&$error) === FALSE) {
            airt_msg($error);
            exit(reload());
        }
        reload('importqueue.php');

        break;

    default:
        die(_('Unknown action').' '.htmlentities($action));
}
?>
