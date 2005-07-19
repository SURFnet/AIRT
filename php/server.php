<?php
require_once('SOAP/Server.php');
require_once('SOAP/Disco.php');

class ViewIncident {
     var $__dispatch_map = array();

     function ViewIncident() {
         // Define the signature of the dispatch map on the Web services method

         // Necessary for WSDL creation
         $this->__dispatch_map['GetIncidentData'] = array('in' =>
         array('action' => 'string' ), 'out' => array('airtXML' =>
         'string'), );
     }

     function GetIncidentData($action)  {
        if ($action == 'getAll') {
           # this XML-document isn't generated automatically yet
           $exported_incidents = "
<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<airt:airt xmlns:airt=\"http://infolab.uvt.nl/airt\">
  <airt:messageIdentification>
    <airt:message_time>1121623586</airt:message_time>
    <airt:sender_details>
      <airt:webservice_location>/home/sebas/local/share/airt/lib/export.plib</airt:webservice_location>
      <airt:sender_name>The Administrator</airt:sender_name>
      <airt:constituency></airt:constituency>
      <airt:email>airt@example.com</airt:email>
      <airt:telephone>0134663395</airt:telephone>
      <airt:version></airt:version>
    </airt:sender_details>
  </airt:messageIdentification>
  <airt:incident>
    <airt:ticketInformation>
      <airt:ticket_number>
        <airt:prefix>Example-CERT#</airt:prefix>
        <airt:reference>1</airt:reference>
      </airt:ticket_number>
      <airt:history>
        <airt:history_item>
          <airt:history_id>1</airt:history_id>
          <airt:ticket_updater>The Administrator</airt:ticket_updater>
          <airt:ticket_update_time>2005-06-22 13:50:23.820154</airt:ticket_update_time>
          <airt:update_action>Incident created</airt:update_action>
        </airt:history_item>
        <airt:history_item>
          <airt:history_id>2</airt:history_id>
          <airt:ticket_updater>The Administrator</airt:ticket_updater>
          <airt:ticket_update_time>2005-06-22 13:50:23.820154</airt:ticket_update_time>
          <airt:update_action>state=Request for inspection, status=open, type=Active hacking</airt:update_action>
        </airt:history_item>
        <airt:history_item>
          <airt:history_id>3</airt:history_id>
          <airt:ticket_updater>The Administrator</airt:ticket_updater>
          <airt:ticket_update_time>2005-06-22 13:50:23.820154</airt:ticket_update_time>
          <airt:update_action>IP address 137.56.0.67 added to incident.</airt:update_action>
        </airt:history_item>
        <airt:history_item>
          <airt:history_id>4</airt:history_id>
          <airt:ticket_updater>The Administrator</airt:ticket_updater>
          <airt:ticket_update_time>2005-06-24 13:52:31.574577</airt:ticket_update_time>
          <airt:update_action>Details of IP address 137.56.0.67 updated; const=default</airt:update_action>
        </airt:history_item>
        <airt:history_item>
          <airt:history_id>5</airt:history_id>
          <airt:ticket_updater>The Administrator</airt:ticket_updater>
          <airt:ticket_update_time>2005-06-24 13:53:24.740676</airt:ticket_update_time>
          <airt:update_action>User sebas@uvt.nl added to incident.</airt:update_action>
        </airt:history_item>
        <airt:history_item>
          <airt:history_id>6</airt:history_id>
          <airt:ticket_updater>The Administrator</airt:ticket_updater>
          <airt:ticket_update_time>2005-06-24 13:53:34.592224</airt:ticket_update_time>
          <airt:update_action>asdf</airt:update_action>
        </airt:history_item>
        <airt:history_item>
          <airt:history_id>10</airt:history_id>
          <airt:ticket_updater>The Administrator</airt:ticket_updater>
          <airt:ticket_update_time>2005-06-24 15:19:50.256872</airt:ticket_update_time>
          <airt:update_action>User sebas@uvt.nl added to incident.</airt:update_action>
        </airt:history_item>
        <airt:history_item>
          <airt:history_id>11</airt:history_id>
          <airt:ticket_updater>The Administrator</airt:ticket_updater>
          <airt:ticket_update_time>2005-06-24 15:19:53.121005</airt:ticket_update_time>
          <airt:update_action>User sebas@uvt.nl removed from incident.</airt:update_action>
        </airt:history_item>
        <airt:history_item>
          <airt:history_id>27</airt:history_id>
          <airt:ticket_updater>The Administrator</airt:ticket_updater>
          <airt:ticket_update_time>2005-07-06 11:38:28.540942</airt:ticket_update_time>
          <airt:update_action>Details of IP address 137.56.0.67 updated; const=uvt</airt:update_action>
        </airt:history_item>
        <airt:history_item>
          <airt:history_id>28</airt:history_id>
          <airt:ticket_updater>The Administrator</airt:ticket_updater>
          <airt:ticket_update_time>2005-07-06 12:44:20.812944</airt:ticket_update_time>
          <airt:update_action>IP address 127.0.0.1 added to incident.</airt:update_action>
        </airt:history_item>
      </airt:history>
      <airt:creator>The Administrator</airt:creator>
      <airt:created>2005-06-22 13:50:23.820154</airt:created>
      <airt:incident_status>stalled</airt:incident_status>
      <airt:incident_type>Active hacking</airt:incident_type>
      <airt:comment></airt:comment>
    </airt:ticketInformation>
    <airt:technicalInformation>
      <airt:technical_item>
        <airt:technical_id>1</airt:technical_id>
        <airt:constituency>tilburg university</airt:constituency>
        <airt:dest_ip></airt:dest_ip>
        <airt:dest_port></airt:dest_port>
        <airt:dest_hostname></airt:dest_hostname>
        <airt:source_ip>137.56.0.67</airt:source_ip>
        <airt:source_port></airt:source_port>
        <airt:source_hostname>stuwww.uvt.nl</airt:source_hostname>
        <airt:source_mac_address></airt:source_mac_address>
        <airt:source_owner>
          <airt:employee_number></airt:employee_number>
          <airt:email_address/>
          <airt:name></airt:name>
          <airt:region></airt:region>
          <airt:role></airt:role>
        </airt:source_owner>
        <airt:number_attempts></airt:number_attempts>
        <airt:protocol></airt:protocol>
        <airt:incident_time></airt:incident_time>
        <airt:time_dns_resolving></airt:time_dns_resolving>
        <airt:logging></airt:logging>
        <airt:added>2005-06-22 13:50:23.820154</airt:added>
        <airt:addedby>The Administrator</airt:addedby>
      </airt:technical_item>
      <airt:technical_item>
        <airt:technical_id>8</airt:technical_id>
        <airt:constituency>default</airt:constituency>
        <airt:dest_ip></airt:dest_ip>
        <airt:dest_port></airt:dest_port>
        <airt:dest_hostname></airt:dest_hostname>
        <airt:source_ip>127.0.0.1</airt:source_ip>
        <airt:source_port></airt:source_port>
        <airt:source_hostname>localhost</airt:source_hostname>
        <airt:source_mac_address></airt:source_mac_address>
        <airt:source_owner>
          <airt:employee_number></airt:employee_number>
          <airt:email_address/>
          <airt:name></airt:name>
          <airt:region></airt:region>
          <airt:role></airt:role>
        </airt:source_owner>
        <airt:number_attempts></airt:number_attempts>
        <airt:protocol></airt:protocol>
        <airt:incident_time></airt:incident_time>
        <airt:time_dns_resolving></airt:time_dns_resolving>
        <airt:logging></airt:logging>
        <airt:added>2005-07-06 12:44:20.773771</airt:added>
        <airt:addedby>The Administrator</airt:addedby>
      </airt:technical_item>
    </airt:technicalInformation>
  </airt:incident>
  <airt:incident>
    <airt:ticketInformation>
      <airt:ticket_number>
        <airt:prefix>Example-CERT#</airt:prefix>
        <airt:reference>2</airt:reference>
      </airt:ticket_number>
      <airt:history>
        <airt:history_item>
          <airt:history_id>7</airt:history_id>
          <airt:ticket_updater>The Administrator</airt:ticket_updater>
          <airt:ticket_update_time>2005-06-24 13:57:04.174197</airt:ticket_update_time>
          <airt:update_action>Incident created</airt:update_action>
        </airt:history_item>
        <airt:history_item>
          <airt:history_id>8</airt:history_id>
          <airt:ticket_updater>The Administrator</airt:ticket_updater>
          <airt:ticket_update_time>2005-06-24 13:57:04.174197</airt:ticket_update_time>
          <airt:update_action>state=Request for inspection, status=open, type=Active hacking</airt:update_action>
        </airt:history_item>
        <airt:history_item>
          <airt:history_id>9</airt:history_id>
          <airt:ticket_updater>The Administrator</airt:ticket_updater>
          <airt:ticket_update_time>2005-06-24 13:57:04.174197</airt:ticket_update_time>
          <airt:update_action>IP address 137.56.0.67 added to incident.</airt:update_action>
        </airt:history_item>
      </airt:history>
      <airt:creator>The Administrator</airt:creator>
      <airt:created>2005-06-24 13:57:04.174197</airt:created>
      <airt:incident_status>stalled</airt:incident_status>
      <airt:incident_type>Active hacking</airt:incident_type>
      <airt:comment></airt:comment>
    </airt:ticketInformation>
    <airt:technicalInformation>
      <airt:technical_item>
        <airt:technical_id>2</airt:technical_id>
        <airt:constituency>default</airt:constituency>
        <airt:dest_ip></airt:dest_ip>
        <airt:dest_port></airt:dest_port>
        <airt:dest_hostname></airt:dest_hostname>
        <airt:source_ip>137.56.0.67</airt:source_ip>
        <airt:source_port></airt:source_port>
        <airt:source_hostname>stuwww.uvt.nl</airt:source_hostname>
        <airt:source_mac_address></airt:source_mac_address>
        <airt:source_owner>
          <airt:employee_number></airt:employee_number>
          <airt:email_address/>
          <airt:name></airt:name>
          <airt:region></airt:region>
          <airt:role></airt:role>
        </airt:source_owner>
        <airt:number_attempts></airt:number_attempts>
        <airt:protocol></airt:protocol>
        <airt:incident_time></airt:incident_time>
        <airt:time_dns_resolving></airt:time_dns_resolving>
        <airt:logging></airt:logging>
        <airt:added>2005-06-24 13:57:04.174197</airt:added>
        <airt:addedby>The Administrator</airt:addedby>
      </airt:technical_item>
    </airt:technicalInformation>
  </airt:incident>
  <airt:incident>
    <airt:ticketInformation>
      <airt:ticket_number>
        <airt:prefix>Example-CERT#</airt:prefix>
        <airt:reference>3</airt:reference>
      </airt:ticket_number>
      <airt:history>
        <airt:history_item>
          <airt:history_id>12</airt:history_id>
          <airt:ticket_updater>The Administrator</airt:ticket_updater>
          <airt:ticket_update_time>2005-06-24 15:24:23.19437</airt:ticket_update_time>
          <airt:update_action>Incident created</airt:update_action>
        </airt:history_item>
        <airt:history_item>
          <airt:history_id>13</airt:history_id>
          <airt:ticket_updater>The Administrator</airt:ticket_updater>
          <airt:ticket_update_time>2005-06-24 15:24:23.19437</airt:ticket_update_time>
          <airt:update_action>state=Request for inspection, status=open, type=Active hacking</airt:update_action>
        </airt:history_item>
        <airt:history_item>
          <airt:history_id>14</airt:history_id>
          <airt:ticket_updater>The Administrator</airt:ticket_updater>
          <airt:ticket_update_time>2005-06-24 15:24:23.19437</airt:ticket_update_time>
          <airt:update_action>IP address 137.56.0.67 added to incident.</airt:update_action>
        </airt:history_item>
      </airt:history>
      <airt:creator>The Administrator</airt:creator>
      <airt:created>2005-06-24 15:24:23.19437</airt:created>
      <airt:incident_status>stalled</airt:incident_status>
      <airt:incident_type>Active hacking</airt:incident_type>
      <airt:comment></airt:comment>
    </airt:ticketInformation>
    <airt:technicalInformation>
      <airt:technical_item>
        <airt:technical_id>3</airt:technical_id>
        <airt:constituency>tilburg university</airt:constituency>
        <airt:dest_ip></airt:dest_ip>
        <airt:dest_port></airt:dest_port>
        <airt:dest_hostname></airt:dest_hostname>
        <airt:source_ip>137.56.0.67</airt:source_ip>
        <airt:source_port></airt:source_port>
        <airt:source_hostname>stuwww.uvt.nl</airt:source_hostname>
        <airt:source_mac_address></airt:source_mac_address>
        <airt:source_owner>
          <airt:employee_number></airt:employee_number>
          <airt:email_address/>
          <airt:name></airt:name>
          <airt:region></airt:region>
          <airt:role></airt:role>
        </airt:source_owner>
        <airt:number_attempts></airt:number_attempts>
        <airt:protocol></airt:protocol>
        <airt:incident_time></airt:incident_time>
        <airt:time_dns_resolving></airt:time_dns_resolving>
        <airt:logging></airt:logging>
        <airt:added>2005-06-24 15:24:23.19437</airt:added>
        <airt:addedby>The Administrator</airt:addedby>
      </airt:technical_item>
    </airt:technicalInformation>
  </airt:incident>
  <airt:incident>
    <airt:ticketInformation>
      <airt:ticket_number>
        <airt:prefix>Example-CERT#</airt:prefix>
        <airt:reference>6</airt:reference>
      </airt:ticket_number>
      <airt:history>
        <airt:history_item>
          <airt:history_id>21</airt:history_id>
          <airt:ticket_updater>The Administrator</airt:ticket_updater>
          <airt:ticket_update_time>2005-06-24 16:18:15.050539</airt:ticket_update_time>
          <airt:update_action>Incident created</airt:update_action>
        </airt:history_item>
        <airt:history_item>
          <airt:history_id>22</airt:history_id>
          <airt:ticket_updater>The Administrator</airt:ticket_updater>
          <airt:ticket_update_time>2005-06-24 16:18:15.050539</airt:ticket_update_time>
          <airt:update_action>state=Request for inspection, status=open, type=Active hacking</airt:update_action>
        </airt:history_item>
        <airt:history_item>
          <airt:history_id>23</airt:history_id>
          <airt:ticket_updater>The Administrator</airt:ticket_updater>
          <airt:ticket_update_time>2005-06-24 16:18:15.050539</airt:ticket_update_time>
          <airt:update_action>IP address 137.56.0.67 added to incident.</airt:update_action>
        </airt:history_item>
      </airt:history>
      <airt:creator>The Administrator</airt:creator>
      <airt:created>2005-06-24 16:18:15.050539</airt:created>
      <airt:incident_status>stalled</airt:incident_status>
      <airt:incident_type>Active hacking</airt:incident_type>
      <airt:comment></airt:comment>
    </airt:ticketInformation>
    <airt:technicalInformation>
      <airt:technical_item>
        <airt:technical_id>6</airt:technical_id>
        <airt:constituency>tilburg university</airt:constituency>
        <airt:dest_ip></airt:dest_ip>
        <airt:dest_port></airt:dest_port>
        <airt:dest_hostname></airt:dest_hostname>
        <airt:source_ip>137.56.0.67</airt:source_ip>
        <airt:source_port></airt:source_port>
        <airt:source_hostname>stuwww.uvt.nl</airt:source_hostname>
        <airt:source_mac_address></airt:source_mac_address>
        <airt:source_owner>
          <airt:employee_number></airt:employee_number>
          <airt:email_address/>
          <airt:name></airt:name>
          <airt:region></airt:region>
          <airt:role></airt:role>
        </airt:source_owner>
        <airt:number_attempts></airt:number_attempts>
        <airt:protocol></airt:protocol>
        <airt:incident_time></airt:incident_time>
        <airt:time_dns_resolving></airt:time_dns_resolving>
        <airt:logging></airt:logging>
        <airt:added>2005-06-24 16:18:15.050539</airt:added>
        <airt:addedby>The Administrator</airt:addedby>
      </airt:technical_item>
    </airt:technicalInformation>
  </airt:incident>
  <airt:incident>
    <airt:ticketInformation>
      <airt:ticket_number>
        <airt:prefix>Example-CERT#</airt:prefix>
        <airt:reference>7</airt:reference>
      </airt:ticket_number>
      <airt:history>
        <airt:history_item>
          <airt:history_id>24</airt:history_id>
          <airt:ticket_updater>The Administrator</airt:ticket_updater>
          <airt:ticket_update_time>2005-06-24 16:18:39.421639</airt:ticket_update_time>
          <airt:update_action>Incident created</airt:update_action>
        </airt:history_item>
        <airt:history_item>
          <airt:history_id>25</airt:history_id>
          <airt:ticket_updater>The Administrator</airt:ticket_updater>
          <airt:ticket_update_time>2005-06-24 16:18:39.421639</airt:ticket_update_time>
          <airt:update_action>state=Request for inspection, status=open, type=Active hacking</airt:update_action>
        </airt:history_item>
        <airt:history_item>
          <airt:history_id>26</airt:history_id>
          <airt:ticket_updater>The Administrator</airt:ticket_updater>
          <airt:ticket_update_time>2005-06-24 16:18:39.421639</airt:ticket_update_time>
          <airt:update_action>IP address 137.56.0.67 added to incident.</airt:update_action>
        </airt:history_item>
      </airt:history>
      <airt:creator>The Administrator</airt:creator>
      <airt:created>2005-06-24 16:18:39.421639</airt:created>
      <airt:incident_status>stalled</airt:incident_status>
      <airt:incident_type>Active hacking</airt:incident_type>
      <airt:comment></airt:comment>
    </airt:ticketInformation>
    <airt:technicalInformation>
      <airt:technical_item>
        <airt:technical_id>7</airt:technical_id>
        <airt:constituency>tilburg university</airt:constituency>
        <airt:dest_ip></airt:dest_ip>
        <airt:dest_port></airt:dest_port>
        <airt:dest_hostname></airt:dest_hostname>
        <airt:source_ip>137.56.0.67</airt:source_ip>
        <airt:source_port></airt:source_port>
        <airt:source_hostname>stuwww.uvt.nl</airt:source_hostname>
        <airt:source_mac_address></airt:source_mac_address>
        <airt:source_owner>
          <airt:employee_number></airt:employee_number>
          <airt:email_address/>
          <airt:name></airt:name>
          <airt:region></airt:region>
          <airt:role></airt:role>
        </airt:source_owner>
        <airt:number_attempts></airt:number_attempts>
        <airt:protocol></airt:protocol>
        <airt:incident_time></airt:incident_time>
        <airt:time_dns_resolving></airt:time_dns_resolving>
        <airt:logging></airt:logging>
        <airt:added>2005-06-24 16:18:39.421639</airt:added>
        <airt:addedby>The Administrator</airt:addedby>
      </airt:technical_item>
    </airt:technicalInformation>
  </airt:incident>
</airt:airt>
";


          return $exported_incidents;
        }
     }
}

$server       = new SOAP_Server();
$webservice   = new ViewIncident();
$server->addObjectMap($webservice,'http://schemas.xmlsoap.org/soap/envelope/');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD']=='POST') {
     $server->service($HTTP_RAW_POST_DATA);
}

else {
     // Create the DISCO server
     $disco = new SOAP_DISCO_Server($server,'Incident');
     header("Content-type: text/xml");
     if (isset($_SERVER['QUERY_STRING']) && strcasecmp($_SERVER['QUERY_STRING'],'wsdl') == 0) {
         echo $disco->getWSDL();
     }
     else {
         echo $disco->getDISCO();
     }
}

exit;

?>
