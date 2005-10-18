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
$public = 1;
require_once 'SOAP/Server.php';
require_once 'SOAP/Disco.php';
require_once 'config.plib';
require_once LIBDIR.'/authentication.plib';
require_once LIBDIR.'/incident.plib';

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

exit;

class IncidentHandling {
   var $__dispatch_map = array();

   function IncidentHandling () {
      // Define the signature of the dispatch map on the Web services method
      // Necessary for WSDL creation
      $this->__dispatch_map['RequestAuthentication'] = array('in' =>
         array('auth_request' => 'string'), 
         'out' => array('AuthenticationTicket' => 'string'), );
      $this->__dispatch_map['getXMLIncidentData'] = array('in' => array('action' => 'string'), 
         'out' => array('airtXML' => 'string'), );
      $this->__dispatch_map['importIncidentData'] = array('in' => array('importXML' => 'string'), 
         'out' => array('confirmation' => 'string'), );
   }

   function RequestAuthentication($auth_request) {
      $dom = '';

      if (!$dom = domxml_open_mem($auth_request,DOMXML_LOAD_PARSING + DOMXML_LOAD_DONT_KEEP_BLANKS,$error)) {
         $error = 'Could not parse XML document';
         return $error;
         exit;
      }
      $root = $dom->document_element();

      if (sizeof($root) == 0) {
         $error = 'XML does not contain any elements';
         return $error;
         exit;
      }
      
      $username_element = $root->get_elements_by_tagname('username');
      $password_element = $root->get_elements_by_tagname('password');
      if (sizeof($username_element) == 0 || sizeof($password_element) == 0) {
         $error = 'Username element or password element is empty';
         return $error;
         exit;
      }
      
      $username = $username_element[0]->get_content();
      $password = $password_element[0]->get_content();
      
      # now authenticate to db
      $user_id = airt_authenticate($username,$password);
      if ($user_id == -1) {
         $error = "Permission denied";
         return $error;
         exit;
      }
      
   }

   function getXMLIncidentData($action)  {
      if ($action == 'getAll') {
         require_once('export.php');
         return exportOpenIncidents();
      }
   }

   function importIncidentData($importXML) {
      $dom = $state = $status = $type = $incidentid = $address = $ip = $addressrole ='';
      
      if (!$dom = domxml_open_mem($importXML,DOMXML_LOAD_PARSING + DOMXML_LOAD_DONT_KEEP_BLANKS,$error)) {
         $error = 'Could not parse XML document';
         return $error;
         exit;
      }
      $root = $dom->document_element();

      if (sizeof($root) == 0) {
         $error = 'XML does not contain any elements';
         return $error;
         exit;
      }

      $_SESSION['userid'] = 1;
      $public = 1;

      foreach($root->get_elements_by_tagname('incident') as $incident_element) {
         $i = 0;

         if (sizeof($incident_element) > 0) {

            $state = getIncidentStateDefault();
            if($state == null) {
               # set the lowest id to the default state
               # fetch the lowest result
               $res = db_query(q("UPDATE incident_states SET
                  isdefault=true where id in (SELECT min(id) FROM
                  incident_states)"));
               db_free_result($res);
               $state = getIncidentStateDefault();
            }
            $status = getIncidentStatusDefault();
            if($status == null) {
               # set default status
               $res  = db_query(q("UPDATE incident_status SET
                  isdefault=true where id in (SELECT min(id) FROM
                  incident_status)"));
               db_free_result($res);
               $status = getIncidentStatusDefault();
            }
            $type = getIncidentTypeDefault();
            if($type == null) {
               # set default type
               $res  = db_query(q("UPDATE incident_types SET
                  isdefault=true where id in (SELECT min(id) FROM
                  incident_types)"));
               db_free_result($res);
               $type = getIncidentTypeDefault();
            }
            # generate an incident id
            $incidentid[$i] = createIncident($state,$status,$type);

            foreach($incident_element->get_elements_by_tagname('ticketInformation') as $ticketInformation) {
               if (sizeof($ticketInformation) > 0) {
                  $prefix_element = $ticketInformation->get_elements_by_tagname('prefix');
                  if (sizeof($prefix_element) > 0) {
                     $prefix = $prefix_element[0]->get_content();
                     if ($prefix == null) 
                        $prefix = '#UNKNOWN';
                  }
                  $reference_element = $ticketInformation->get_elements_by_tagname('reference');
                  if (sizeof($reference_element) > 0) {
                     $reference = $reference_element[0]->get_content();
                     if ($reference == null)
                        $reference = '0';
                  }
               }
            }

            foreach($incident_element->get_elements_by_tagname('technicalInformation') as $technicalInformation) {
               if (sizeof($technicalInformation) > 0) {
                  $ip_element = $technicalInformation->get_elements_by_tagname('ip');
                  if (sizeof($ip_element) > 0) {
                     $ip = $ip_element[0]->get_content();
                     # default ip
                     if ($ip == null) 
                        $ip = '127.0.0.1';
                  }
                  $hostname_element = $technicalInformation->get_elements_by_tagname('hostname');
                  if (sizeof($hostname_element) > 0) {
                     $hostname = $hostname_element[0]->get_content();
                     # default hostname
                     if ($hostname == null) 
                        $hostname = 'localhost';
                  }
                  $time_dns_resolving_element = $technicalInformation->get_elements_by_tagname('time_dns_resolving');
                  if (sizeof($time_dns_resolving_element) > 0) {
                     $time_dns_resolving = $time_dns_resolving_element[0]->get_content();
                     # default time_dns_resolving
                     if ($time_dns_resolving == null) 
                        $time_dns_resolving = time();
                  }
                  $incident_time_element = $technicalInformation->get_elements_by_tagname('incident_time');
                  if (sizeof($incident_time_element) > 0) {
                     $incident_time = $incident_time_element[0]->get_content();
                     # default incident_time
                     if ($incident_time == null)
                        $incident_time = time();
                  }
                  $logging_element = $technicalInformation->get_elements_by_tagname('logging');
                  if (sizeof($logging_element) > 0) {
                     $logging = $logging_element[0]->get_content();
                  }
               }
               $address = $ip;
               $addressrole = '0';

               addIPtoIncident($address,$incidentid[$i],$addressrole);
            }
         }
      }

      if ($error == null) {
         $error = 'Import successful. Imported incident with id ';
         foreach ($incidentid as $i => $id)
            $id_list .= "$id, ";
         $id_list = rtrim($id_list,', ');
         $error .= $id_list.'.';
         return $error;
      }
   }
}

/*
function getAuthenticationTicket() {
   $rid = '';

   $fileName = STATEDIR."/importqueue/rid";
   $ticketFile = @fopen($fileName,'r');
   if (!$ticketFile) {
      $error = 'Could not open '.$fileName.' for reading';
      echo $error;
      return FALSE;
   }
   else {
      $rid  = fread($ticketFile,1024);
      return $rid;
   }
   @fclose($ticketFile);
}

function writeAuthenticationTicket() {
   $rand = '';

   $fileName = STATEDIR."/importqueue/rid";
   $ticketFile = @fopen($fileName,'w');
   if (!$ticketFile) {
      $error = 'Could not open '.$fileName.' for writing';
      echo $error;
      return FALSE;
      exit;
   }
   $rid = md5(mt_rand(48,50));
   $content = $rid;
   if (flock($ticketFile,LOCK_EX)) {
      if (!@fwrite($ticketFile,$content)) {
         $error = 'Could not write to '.$ticketFile;
         @fclose($ticketFile);
      }
      flock($ticketFile,LOCK_UN);
   }
   else {
      $error = 'Could not lock '.$ticketFile.' for writing';
   }
   @fclose($ticketFile);
}
*/
?>
