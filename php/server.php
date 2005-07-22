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
      $doc                 = domxml_open_mem($importXML,DOMXML_LOAD_PARSING,$error);
      $ws_location_array   = $doc->get_elements_by_tagname("webservice_location");
      foreach($ws_location_array as $ws_location) {
         return $ws_location->get_content();
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

exit;

?>
