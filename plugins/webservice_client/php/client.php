<?php
/* $Id$ 
 * index.php - Index page for UvT-CERT
 *
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
 * index.php - AIR console
 * $Id$
 */

switch($_POST[method]) {
   case null:
      ShowClientForm();
   break;

   case 'getData':
      require('SOAP/Client.php');
      $endpoint      = 'https://similarius.uvt.nl/~sebas/airt/server.php';
      $airt_client   = new SOAP_Client($endpoint);
      $method        = 'getXMLIncidentData';
      $params        = array('action' => 'getAll');
      $ans           = $airt_client->call($method, $params);
      Header("Content-Type: application/xml");
      print_r($ans);
   break;

   case 'import':
      require('SOAP/Client.php');
      $endpoint      = 'https://similarius.uvt.nl/~sebas/airt/server.php';
      $airt_client   = new SOAP_Client($endpoint);
      $method        = 'importIncidentData';
      $xml_incident  = '
<airt>
   <messageIdentification>
      <message_time>1122033423</message_time>
	 <sender_details>
	    <webservice_location>/home/sebas/local/share/airt/lib/export.plib</webservice_location>
	    <sender_name>Henk</sender_name>
	    <constituency>UvT-CERT</constituency>
	    <email>sebas@uvt.nl</email>
	    <telephone>2432</telephone>
	    <version>1.4</version>
         </sender_details>
    </messageIdentification>
      <incident>
         <ticketInformation>
	    <ticket_number>
               <prefix>Example-CERT#</prefix>
               <reference>1</reference>
            </ticket_number>
            <history>
	       <history_item>
                  <history_id>1</history_id>
                  <ticket_updater>The Administrator</ticket_updater>
                  <ticket_update_time>2005-06-22 13:50:23.820154</ticket_update_time>
                  <update_action>Incident created</update_action>
                  </history_item>
	       <history_item>
                  <history_id>2</history_id>
                  <ticket_updater>The Administrator</ticket_updater>
                  <ticket_update_time>2005-06-22 13:50:23.820154</ticket_update_time>
	          <update_action>state=Request for inspection, status=open, type=Active hacking</update_action>
               </history_item>
	       <history_item>
                  <history_id>4</history_id>
                  <ticket_updater>The Administrator</ticket_updater>
                  <ticket_update_time>2005-06-24 13:52:31.574577</ticket_update_time>
	          <update_action>Details of IP address 137.56.0.67 updated; const=default</update_action>
               </history_item>
            </history>
            <creator>The Administrator</creator>
            <created>2005-06-22 13:50:23.820154</created>
            <incident_status>1</incident_status>
            <incident_state>1</incident_state>
            <incident_type>1</incident_type>
            <comment></comment>
         </ticketInformation>
	 <technicalInformation>
	    <technical_item>
               <technical_id>1</technical_id>
               <constituency>1</constituency>
               <ip>137.56.0.67</ip>
               <port>12</port>
               <hostname>bla.bla.nl</hostname>
               <mac_address>hex</mac_address>
               <addressrole>1</addressrole>
	       <source_owner>
                  <employee_number></employee_number>
                  <email_address></email_address>
                  <name></name>
                  <region></region>
                  <role></role>
               </source_owner>
               <number_attempts></number_attempts>
               <protocol></protocol>
               <incident_time></incident_time>
               <time_dns_resolving></time_dns_resolving>
               <logging></logging>
               <added>2005-06-22 13:50:23.820154</added>
               <addedby>The Administrator</addedby>
            </technical_item>
         </technicalInformation>
      </incident>
</airt>';
      $params        = array('importXML' => $xml_incident);
      $ans           = $airt_client->call($method, $params);
#      Header("Content-Type: application/xml");
      print_r($ans);
   break;
}

function ShowClientForm() {
   echo "<TABLE>";
   echo "<FORM METHOD='POST'>";
   echo "<TR><TD>Export all open incidents</TD><TD><INPUT TYPE='submit' VALUE='export'></TD>";
   echo "<INPUT TYPE='hidden' NAME='method' VALUE='getData'>";
   echo "</FORM>";

   echo "<FORM METHOD='POST'>";
   echo "<TR><TD>Import incident</TD><TD><INPUT TYPE='submit' VALUE='import'></TD></TR>";
   echo "<INPUT TYPE='hidden' NAME='method' VALUE='import'>";
   echo "</FORM>";
   echo "</TABLE>";
}


?>
