<?php
    require_once '/etc/airt/airt.cfg';
    require_once LIBDIR.'/airt.plib';

    pageHeader("Debug info");

    $activeip = $_SESSION["active_ip"];
    $activeid = $_SESSION["active_incidentid"];
    $activeticket = $_SESSION["active_ticketid"];
    $sip = $_SESSION["ip"];
    $slast = Date("r", $_SESSION["last"]);
    $expire = Date("r", $_SESSION["last"] + SESSION_TIMEOUT);
    $uid = $_SESSION["userid"];
    $uname = $_SESSION["username"];

echo <<<EOF
<PRE>
Session data:

    User name   : $uname
    User id     : $uid
    Session IP  : $sip
    Session last: $slast
    Session ends: $expire

Active info:

    Active Incident: $activeid
    Active IP      : $activeip
    Active Ticket  : $activeticket
</PRE>
EOF;

    pageFooter();
?>
