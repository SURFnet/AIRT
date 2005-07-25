<?php
/* vim:syntax=php shiftwidth=3 tabstop=3
 *
 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2004   Tilburg University, The Netherlands

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

require_once('SOAP/Server.php');
require_once('SOAP/Disco.php');


class IncidentHandling {
   var $__dispatch_map = array();

   function IncidentHandling () {
      // Define the signature of the dispatch map on the Web services method

      // Necessary for WSDL creation
      $this->__dispatch_map['getXMLIncidentData'] = array('in' => array('action' => 'string'), 
         'out' => array('airtXML' => 'string'), );
      $this->__dispatch_map['importIncidentData'] = array('in' => array('importXML' => 'string'), 
         'out' => array('confirmation' => 'string'), );
   }

   function getXMLIncidentData($action)  {
      if ($action == 'getAll') {
         $public  = 1;
         require_once('export.php');
         return(exportOpenIncidents());
      }
   }

   function importIncidentData($importXML) {
      $public  = 1;
      require_once 'config.plib';
      require_once LIBDIR.'/incident.plib';
      
      $doc                             = domxml_open_mem($importXML,DOMXML_LOAD_PARSING,$error);
      $message_time_array              = $doc->get_elements_by_tagname('message_time');
      foreach($message_time_array as $message_time) {
         $message_time                 = $message_time->get_content();
      }
      #TODO: temporarily unknown howto get userid
      $userid                          = 1;
      $_SESSION[userid]                = $userid;

      $incident_nodes                  = $doc->get_elements_by_tagname('incident');
      foreach($incident_nodes as $incident_node) {
         $incident_id_array            = $incident_node->get_elements_by_tagname('reference');
         $insert_array['incidentid']   = $incident_id_array[0]->get_content();

         $incident_status_array        = $incident_node->get_elements_by_tagname('incident_status');
         $insert_array['status']       = $incident_status_array[0]->get_content();

         $incident_state_array         = $incident_node->get_elements_by_tagname('incident_state');
         $insert_array['state']        = $incident_state_array[0]->get_content();

         $incident_type_array          = $incident_node->get_elements_by_tagname('incident_type');
         $insert_array['type']         = $incident_type_array[0]->get_content();

         $incidentid                   = createIncident($userid,$insert_array['state'],$insert_array['status'],$insert_array['type']);
         
         $incident_addresses_nodes     = $incident_node->get_elements_by_tagname('technicalInformation');
         foreach($incident_addresses_nodes as $incident_address_node) {
            $ip_array                           = $incident_address_node->get_elements_by_tagname('ip');
            $insert_address_array['ip']         = $ip_array[0]->get_content();

            $hostname_array                     = $incident_address_node->get_elements_by_tagname('hostname');
            $insert_address_array['hostname']   = $hostname_array[0]->get_content();
            
            $constituency_array                 = $incident_address_node->get_elements_by_tagname('constituency');
            $insert_address_array['constituency'] = $constituency_array[0]->get_content();

            $addressrole_array                  = $incident_address_node->get_elements_by_tagname('addressrole');
            $insert_address_array['addressrole'] = $addressrole_array[0]->get_content();
            
            addIPtoIncident($insert_address_array['ip'],$incidentid,$insert_address_array['addressrole']=0);
            unset($insert_address_array);
         }

         unset($insert_array);
      }
   }
}

$server       = new SOAP_Server();
$webservice   = new IncidentHandling();
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
   } else {
      echo $disco->getDISCO();
   }
}
/*
function insertIncident($message_time,$user_id,$insert_array) {
   #TODO: translations of type, status and state from int to text
   $public     = 1;
   require_once 'config.plib';
   require_once LIBDIR.'/airt.plib';
   require_once LIBDIR.'/database.plib';
   require_once LIBDIR.'/history.plib';
   require_once LIBDIR.'/incident.plib';

   #get incidentid. necessary for several db instructions
   $res        = db_query("select nextval('incidents_sequence') as incidentid")
                     or die("Unable to execute query 2.");
   $row        = db_fetch_next($res);
   $incidentid = $row["incidentid"];
   db_free_result($res);

   #insert incident
   $query      = sprintf(
      "insert into incidents
       (id, created, creator, updated, updatedby, state, status, type)
       values (%s, CURRENT_TIMESTAMP, %s, CURRENT_TIMESTAMP, %s, '%s', '%s', '%s')",
                $incidentid,
                $user_id,
                $user_id,
                $insert_array[state]  == "" ? 'NULL' : $insert_array['state'],
                $insert_array[status] == "" ? 'NULL' : $insert_array['status'],
                $insert_array[type]   == "" ? 'NULL' : $insert_array['type']);

   $res        = db_query($query) or die ("Unable to execute query");
   db_free_result($res);

   $_SESSION['incidentid'] = $incidentid;
   $_SESSION['userid']     = $user_id;

   addIncidentComment("Incident created", "", "");
   addIncidentComment(sprintf("state=%s, status=%s, type=%s",
      getIncidentStateLabelByID($insert_array['state']),
      getIncidentStatusLabelByID($insert_array['status']),
      getIncidentTypeLabelById($insert_array['type'])), "", "");
   
   return($incidentid);
}

function insertIncidentAddresses($incidentid,$message_time,$user_id,$insert_address_array) {
   $public     = 1;
   require_once 'config.plib';
   require_once LIBDIR.'/airt.plib';
   require_once LIBDIR.'/database.plib';
   require_once LIBDIR.'/history.plib';
   require_once LIBDIR.'/incident.plib';
         

   $res = db_query("select nextval('incident_addresses_sequence') as iaid")
            or die("Unable to execute query 4.");
   $row = db_fetch_next($res);
   $iaid = $row["iaid"];
   db_free_result($res);

   $query = sprintf(
         "insert into incident_addresses
         (id, incident, ip, hostname, addressrole, constituency, added, addedby)
         values
         (%s, %s, %s, %s, %s, %s, CURRENT_TIMESTAMP, %s)",
             $iaid,
             $incidentid,
             db_masq_null($insert_address_array['ip']),
             db_masq_null($insert_address_array['hostname']),
             $insert_address_array['addressrole'],
             db_masq_null($insert_address_array['constituency']),
             $user_id);
   $res  = db_query($query) or die("Unable to execute query");
   db_free_result($res);

   $_SESSION['incidentid'] = $incidentid;
   $_SESSION['userid']     = $user_id;
   
   addIncidentComment(sprintf("IP address %s added to
      incident.",$insert_address_array['ip']), "", "");
   
   return($iaid);
}

*/

exit;

?>
