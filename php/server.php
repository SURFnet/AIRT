<?php
/* vim:syntax=php shiftwidth=3 tabstop=3
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

require_once('SOAP/Server.php');
require_once('SOAP/Disco.php');

# TODO: bring all require_onces to the top of the file. You might include
# unnessarry files, but it is more clear what the depedencies are.

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
         # TODO: return is an operator, not a function. You do not need the ()
         return(exportOpenIncidents());
      }
   }

   # TODO: implement sensible error handling; exit or return are not sufficient
   function importIncidentData($importXML) {
      // FIXME temporary hack to use userid
      // temporarily set userid to 1 if necessary
      session_register('user_id');
      if($_SESSION['userid'] == null) {
         $_SESSION['userid'] = '1';
         $set_userid_tmp = true;
      }
      $public  = 1;
      require_once 'config.plib';
      require_once LIBDIR.'/incident.plib';

      if (!$dom = domxml_open_mem($importXML,DOMXML_LOAD_PARSING + DOMXML_LOAD_DONT_KEEP_BLANKS,$error)) {
         return 1;
         exit;
      }
      $root = $dom->document_element();

      if (sizeof($root) == 0) {
         exit;
      }
      foreach($root->get_elements_by_tagname('incident') as $incident_element) {
         if (sizeof($incident_element) > 0) {

            # TODO: set default state, status, type
            $state = getIncidentStateDefault();
            if($state == null) {
               return 1;
               exit;
            }
            $status = getIncidentStatusDefault();
            if($status == null) {
               return 1;
               exit;
            }
            $type = getIncidentTypeDefault();
            if($type == null) {
               return 1;
               exit;
            }
            # generate an incident id
            $incidentid = createIncident($state,$status,$type);

            # TODO: defaults for prefix, reference ; if they are not part of
            # the XML. they will be uninitialised in the current code
            foreach($incident_element->get_elements_by_tagname('ticketInformation') as $ticketInformation) {
               if (sizeof($ticketInformation) > 0) {
                  $prefix_element = $ticketInformation->get_elements_by_tagname('prefix');
                  if (sizeof($prefix_element) > 0) {
                     $prefix = $prefix_element[0]->get_content();
                  }
                  $reference_element = $ticketInformation->get_elements_by_tagname('reference');
                  if (sizeof($reference_element) > 0) {
                     $reference = $reference_element[0]->get_content();
                  }
               }
            }
            # TODO: defaults for $ip, $hostname, etc.
            foreach($incident_element->get_elements_by_tagname('technicalInformation') as $technicalInformation) {
               if (sizeof($technicalInformation) > 0) {
                  $ip_element = $technicalInformation->get_elements_by_tagname('ip');
                  if (sizeof($ip_element) > 0) {
                     $ip = $ip_element[0]->get_content();
                  }
                  $hostname_element = $technicalInformation->get_elements_by_tagname('hostname');
                  if (sizeof($hostname_element) > 0) {
                     $hostname = $hostname_element[0]->get_content();
                  }
                  $time_dns_resolving_element = $technicalInformation->get_elements_by_tagname('time_dns_resolving');
                  if (sizeof($time_dns_resolving_element) > 0) {
                     $time_dns_resolving = $time_dns_resolving_element[0]->get_content();
                  }
                  $incident_time_element = $technicalInformation->get_elements_by_tagname('incident_time');
                  if (sizeof($incident_time_element) > 0) {
                     $incident_time = $incident_time_element[0]->get_content();
                  }
                  $logging_element = $technicalInformation->get_elements_by_tagname('logging');
                  if (sizeof($logging_element) > 0) {
                     $logging = $logging_element[0]->get_content();
                  }
               }
               $address = $ip;
               $addressrole = '0';

               # TODO: make sure you db_escape_string these things before you
               # shoot them into the database (to prevent XSS). Probably better
               # to do that in addIPtoIncident than here though.
               addIPtoIncident($address,$incidentid,$addressrole);
            }
         }
      }
      // FIXME temporary hack to use userid
      if($set_userid_tmp == true) {
         unset($_SESSION['userid']);
      }
   }

   # TODO: sensible return output?
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
