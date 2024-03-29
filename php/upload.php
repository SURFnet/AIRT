<?php
require_once 'config.plib';
require_once LIBDIR.'/airt.plib';
require_once LIBDIR.'/database.plib';
require_once LIBDIR.'/importqueue.plib';

$action = strip_tags(fetchFrom('REQUEST','action'));
defaultTo($action,'select');

switch ($action) {
    case 'select':
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
        $out .= '<form enctype="multipart/form-data" method="POST">'.LF;
        $out .= '<table>'.LF;
        $out .= '<tr>'.LF;
        $out .= '<td>'._('File').':</td>'.LF;
        $out .= '<td colspan="2"><input type="file" name="file" required><br/>';
        $out .= '</td>'.LF;
        $out .= '</tr>'.LF;
        $out .= '</table>'.LF;
        $out .= '<input type="hidden" name="action" value="upload"/>'.LF;
        $out .= '<input type="submit" value="'._('Upload file').'"/>'.LF;
        $out .= '</form>'.LF;
        $out .= '</div><!-- block -->'.LF;
        $out .= '</div>'.LF;
        print $out;
        pageFooter();
        break;
    
    case 'upload':
        if (($f = fopen($_FILES['file']['tmp_name'], 'r')) === FALSE) {
            airt_msg(_('Could not open uploaded file'));
            exit(reload());
        }
        $dst = tempnam('/tmp', 'airt_');
        copy($_FILES['file']['tmp_name'], $dst);
        $_SESSION['uploadfile'] = $dst;
        $f = fopen($dst, 'r');
        $line = fgets($f);
        fclose($f);

        /* simple logic: the delimiter resulting the largest number of fields
         * is probably the default */
        $max = 0;
        $fs = '';
        foreach (array('	',',', '|', ';', ':') as $d) {
            $n = sizeof(str_getcsv($line, $d));
            if ($n > $max) { 
                $max = $n;
                $fs = $d;
            }
        }

        /* first match wins */
        $fields=str_getcsv($line, $fs);
        array_walk($fields, create_function('&$val', '$val = strtolower(trim($val));'));
        $iphdr = '';
        foreach (array('ip', 'addr', 'ipaddr', 'ip-addr', 'ip_addr', 'srcip',
        'src', 'src_ip') as $label) {
            if (array_search($label, $fields)!==FALSE) {
                $iphdr=$label;
                break;
            }
        }
        if ($fs == '	') { $fs = '\t';}

        pageHeader(_('File upload'), array(
            'menu'=>'incidents',
            'submenu'=>'upload'));
        $out = '<div>'.LF;
        $out .= '<div class="block">'.LF;
        $out .= _('First line of uploaded file').':'.LF;
        $out .= '<pre class="withscroll">' . htmlspecialchars($line) .LF. '</pre>'.LF;
        $out .= '<form method="POST">'.LF;
        $out .= '<table>'.LF;
        $out .= '<tr>'.LF;
        $out .= '<td>'._('Separator').':</td>'.LF;
        $out .= t('<td><input type="text" size="2" name="sep" required maxlength="1" value="%s"/></td>'.LF, array('%s'=>htmlentities($fs)));
        $out .= '<td>'._('Single character field separator').'</td>'.LF;
        $out .= '</tr>'.LF;
        $out .= '<tr>'.LF;
        $out .= '<td>'._('IP address label').':</td>'.LF;
        $out .= t('<td><input type="text" name="ip_label" required value="%s"/></td>'.LF, array('%s'=>htmlentities($iphdr)));
        $out .= '<td>'._('Column name contain the IP address').'</td>'.LF;
        $out .= '</tr>'.LF;
        $out .= '<tr>'.LF;
        $out .= '<td>'._('CSV filter version identifier').':</td>'.LF;
        $out .= '<td><select name="version">'.LF;
        $out .= '<option value="" SELECTED>'._('None').'</option>'.LF;
        if (importqueueTemplatesGetItems($items, $error) == 0) {
           foreach ($items as $id=>$item) {
              if ($item['filter'] == 'filter_csv') {
                 $out .= sprintf('<option value="%s">%s</option>'.LF,htmlentities($item['version']),  htmlentities($item['version']));
              }
           }
        }

        $out .= '<td>'._('Set to define a preferred template for filter_csv').'</td>';
        $out .= '</tr>'.LF;
        $out .= '</table>'.LF;
        $out .= '<input type="hidden" name="action" value="import"/>'.LF;
        $out .= '<input type="submit" value="'._('Send to import queue').'"/>'.LF;
        $out .= '</form>'.LF;
        $out .= '</div><!-- block -->'.LF;
        $out .= '</div>'.LF;
        print $out;

        break;

    case 'import':
        if (($f = fopen($_SESSION['uploadfile'], 'r')) === FALSE) {
            airt_msg(_('Could not open uploaded file'));
            unset($_SESSION['uploadfile']);
            exit(reload());
        }
        if (($ip_label = fetchFrom('POST', 'ip_label', '%s')) == '') {
            airt_msg(_('Unable to retrieve ip_label'));
            unlink($_SESSION['uploadfile']);
            unset($_SESSION['uploadfile']);
            exit(reload());
        }
        if (($sep = fetchFrom('POST', 'sep', '%s')) == '') {
            airt_msg(_('Unable to retrieve separator'));
            unlink($_SESSION['uploadfile']);
            unset($_SESSION['uploadfile']);
            exit(reload());
        }
        if (($version = fetchFrom('POST', 'version', '%s')) == '') {
            $version = '';
        }
        if ($sep == '\t') { $sep = '	';}
        if (strlen($sep) > 1) {
            airt_msg(_('Delimiter must be one character only'));
            unlink($_SESSION['uploadfile']);
            unset($_SESSION['uploadfile']);
            exit(reload());
        }
        $row = fgetcsv($f, 0, $sep);
        array_walk($row, create_function('&$val', '$val = strtolower(trim($val));'));
        if (($index = array_search(strtolower($ip_label), $row)) === FALSE) {
            airt_msg(_('Index field not found. aborting.'));
            unlink($_SESSION['uploadfile']);
            unset($_SESSION['uploadfile']);
            exit(reload());
        }

        unlink($_SESSION['uploadfile']);
        unset($_SESSION['uploadfile']);
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
            $output .= '      <hostname>';
            $output .= @gethostbyaddr(trim($row[$index]));
            $output .=       '</hostname>'.LF;
            $output .= '      <time_dns_resolving>';
            $output .= Date('Y-m-d H:i:s');
            $output .=       '</time_dns_resolving>'.LF;
            $output .= '      <logging>'.LF;
//            $output .= htmlentities(trim(join($sep, $row)));
            $output .= htmlentities(trim(join($sep, str_replace('\n', "\n", preg_replace( '/[^[:print:]]/', '',$row)))));
            $output .= '      </logging>'.LF;
            $output .= '   </technicalInformation>'.LF;
            $output .= '</incident>'.LF;
            $count++;
        }
        $output .= '</airt>'.LF;
        if (import($output, $error) === FALSE) {
            airt_msg($error);
            exit(reload());
        }
        airt_msg(_('Number of imported entries: ').$count);
        reload('importqueue.php');
        break;

    default:
        die(_('Unknown action').' '.htmlentities($action));
}
