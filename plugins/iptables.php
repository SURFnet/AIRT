<?php
require_once "lib/uvtcert.plib";

if (array_key_exists("action", $_REQUEST)) $action=$_REQUEST["action"];
else $action="list";

$SELF="iptables.php";

function show_overview()
{
    global $SELF;

    pageHeader("IPTables results");

    $f = fopen("/var/log/kern.log", "r");
    $ips = array();
    $ports = array();
    printf("Parsing log file...<P>");
    flush();
    $first="";
    while (!feof($f))
    {       
        $line = fgets($f);
        if ($first=="") 
        {
            $first=$line;
            printf("Log starts: <B>%s</B><P>", substr($line, 0, 15));
        }
        if (ereg("IN=eth0 OUT=.*SRC=([0-9.]+).*DPT=([0-9]+)", $line, $results))
        {
            if (array_key_exists($results[1], $ips)) $ips[$results[1]]++;
            else $ips[$results[1]] = 1;

            if (array_key_exists($results[2], $ports)) $ports[$results[2]]++;
            else $ports[$results[2]] = 1;
        }
    }
    fclose($f);

    printf("<h2>Summary</h2>
        <P>
        <B>%s</B> uniq ip's, <B>%s</B> uniq ports\n", 
        count($ips), count($ports));

    arsort($ports, SORT_NUMERIC);
    $max = count($ports) >  5 ? 5 : count($ports);
    $count = 0;

    printf("<h2>Top 5 popular ports</h2>
        <P>
        <TABLE cellpadding=4>");
    echo "<tr>
        <th>Details</th>
        <th>Portno</th>
        <th>Distinct no. of probes</th>
        </tr>";
    $count = 0;
    foreach ($ports as $port => $amount)
    {
        printf("<tr bgcolor='%s'>
            <td><a href=\"$SELF?action=portdetails&port=%s\">details</a></td>
            <td>%s</td>
            <td>%s</td>
            </tr>\n", 
                $count++ % 2 == 0 ? "#DDDDDD" : "#FFFFFF",
                urlencode($port), $port, $amount);
        if ($count >= $max) break;
    }
    echo "</TABLE>";


    arsort($ips, SORT_NUMERIC);
    $max = count($ips) >  5 ? 5 : count($ips);
    $count = 0;

    printf("<h2>Top 5 popular source addresses</h2>
        <P>
        <TABLE cellpadding=4>");
    echo "<tr>
        <th>Details</th>
        <th>IP address</th>
        <th>Host name</th>
        <th>Distinct no. of probes</th>
        </tr>";
    $count = 0;
    foreach ($ips as $ip => $amount)
    {
        printf("<tr bgcolor='%s'>
            <td><a href=\"$SELF?action=ipdetails&ip=%s\">details</a></td>
            <td>%s</td>
            <td>%s</td>
            <td>%s</td>
            </tr>\n", 
                $count++ % 2 == 0 ? "#DDDDDD" : "#FFFFFF",
                $ip, urlencode($ip), gethostbyaddr($ip), $amount);
        if ($count >= $max) break;
    }
    echo "</TABLE>";
    pageFooter();
}

function ipdetails($ip)
{
    pageHeader("Details for ip address $ip");
    $f = fopen("/var/log/kern.log", "r");
    $count = 0;
    while (!feof($f))
    {
        $in = fgets($f);
        if (ereg(sprintf("SRC=%s.*DPT=([0-9]+)", $ip), $in, $regs))
        {
            printf("<div style=\"background-color:%s\">%s<P>
                    To port: <B>%s</B></div><P>", 
                $count++ % 2 == 0 ? "#DDDDDD" : "#FFFFFF",
                $in,
                $regs[1]);
        }
    }
    fclose($f);
    pageFooter();
    
}

function portdetails($port)
{
    pageHeader("Details for ip address $ip");
    $f = fopen("/var/log/kern.log", "r");
    $count = 0;
    while (!feof($f))
    {
        $in = fgets($f);
        if (ereg(sprintf("SRC=([0-9.]+).*DPT=%s", $ip), $in, $regs))
        {
            printf("<div style=\"background-color:%s\">%s<P>
                    From IP: <B>%s</B></div><P>", 
                $count++ % 2 == 0 ? "#DDDDDD" : "#FFFFFF",
                $in,
                $regs[1]);
        }
    }
    fclose($f);
    pageFooter();
    
}


switch ($action)
{
    case "list":
        show_overview();
        break;
    case "ipdetails":
        if (array_key_exists("ip", $_REQUEST)) $ip=$_REQUEST["ip"];
        else die("Missing parameter.");

        ipdetails($ip);
        break;
    case "portdetails":
        if (array_key_exists("port", $_REQUEST)) $port=$_REQUEST["port"];
        else die("Missing parameter.");

        portdetails($port);
        break;
    default:
        die("Unknown action.");
}

?>
