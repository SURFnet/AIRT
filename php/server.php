<?php
require_once('SOAP/Server.php');
require_once('SOAP/Disco.php');


class IncidentHandling {
   var $__dispatch_map = array();

   function IncidentHandling () {
      // Define the signature of the dispatch map on the Web services method

      // Necessary for WSDL creation
      $this->__dispatch_map['getXMLIncidentData'] = array('in' =>
      array('action' => 'string' ), 'out' => array('airtXML' =>
      'string'), );
   }

   function getXMLIncidentData($action)  {
      if ($action == 'getAll') {
         # this XML-document isn't generated automatically yet
#         DEFINE('DEBUG',true);
         $public  = 1;
         require_once('export.php');
#         return "BLA";
         return(exportOpenIncidents());
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
