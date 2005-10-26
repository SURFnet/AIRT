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
require_once ETCDIR.'/webservice.cfg';
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
      
      $userid = airt_authenticate($username,$password);
      if ($userid == -1) {
         $error = 'Permission denied';
         return $error;
         exit;
      }

      // generate a new random id
      $ticketid = genRandom();
      
      // put it in the db
      $creationid = CreateTicket($userid,$ticketid);
      
      // get the issue time
      $res = db_query(sprintf("
         SELECT  created
         FROM    authentication_tickets
         WHERE   id=%s",
         db_masq_null($creationid)))
      or die;

      while ($row = db_fetch_next($res)) {
         $issuetime = $row['created'];
      }
      // format it to XML-standards
      $issuetime = substr($issuetime,0,10)."T".substr($issuetime,11,8);
      $exptime = date('Y-m-d\TH:i:s',mktime(substr($issuetime,11,2),
         substr($issuetime,14,2), substr($issuetime,17,2),
         substr($issuetime,5,2), substr($issuetime,8,2),
         substr($issuetime,0,4)) + TICKET_EXP);

      $ticket_details = array();
      $ticket_details['creationid'] = $creationid;
      $ticket_details['assertionid'] = WS_ENDPOINT.'?'.$creationid;
      $ticket_details['ticketid'] = $ticketid;
      $ticket_details['issuetime'] = $issuetime;
      $ticket_details['exptime'] = $exptime;
      
      // generate a SAML-ticket
      $saml_ticket = generateSAMLTicket($ticket_details);
      
      // send it back to the user
      return $saml_ticket;
   }

   function getXMLIncidentData($action)  {
      if ($action == 'getAll') {
         require_once('export.php');
         return exportOpenIncidents();
      }
   }

   function importIncidentData($importXML) {
      $dom = $state = $status = $type = $incidentid = $address = $ip = $addressrole ='';
      
      // first check the validity of the XML
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

      // check the credentials
      $message_ident_el = $root->get_elements_by_tagname('messageIdentification');
      if (sizeof($message_ident_el) == 0) {
         $error = 'Empty messageIdentification element';
         return $error;
         exit;
      }
      if (sizeof($message_ident_el) > 0) {
         $ticket_el = $message_ident_el[0]->get_elements_by_tagname('TicketID');
         if (sizeof($ticket_el) == 0) {
            $error = 'Key element is empty';
            return $error;
            exit;
         }
         if (sizeof($ticket_el) > 0) {
            $ticketid = $ticket_el[0]->get_content();
            $_SESSION['userid'] = CheckCredentials($ticketid);
            if(!isset($_SESSION['userid'])) {
               $error = 'You are not authorized';
               return $error;
               exit;
            }
         }
         
      }
      foreach($root->get_elements_by_tagname('incident') as $incident_element) {
         $i = 0;

         if (sizeof($incident_element) > 0) {

            $state = getIncidentStateDefault();
            if($state == null) {
               // set the lowest id to the default state
               // fetch the lowest result
               $res = db_query(q("UPDATE incident_states SET
                  isdefault=true where id in (SELECT min(id) FROM
                  incident_states)"));
               db_free_result($res);
               $state = getIncidentStateDefault();
            }
            $status = getIncidentStatusDefault();
            if($status == null) {
               // set default status
               $res  = db_query(q("UPDATE incident_status SET
                  isdefault=true where id in (SELECT min(id) FROM
                  incident_status)"));
               db_free_result($res);
               $status = getIncidentStatusDefault();
            }
            $type = getIncidentTypeDefault();
            if($type == null) {
               // set default type
               $res  = db_query(q("UPDATE incident_types SET
                  isdefault=true where id in (SELECT min(id) FROM
                  incident_types)"));
               db_free_result($res);
               $type = getIncidentTypeDefault();
            }
            // generate an incident id
            $incidentid[$i] = createIncident($state,$status,$type,$logging);

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
                     // default ip
                     if ($ip == null) 
                        $ip = '127.0.0.1';
                  }
                  $hostname_element = $technicalInformation->get_elements_by_tagname('hostname');
                  if (sizeof($hostname_element) > 0) {
                     $hostname = $hostname_element[0]->get_content();
                     // default hostname
                     if ($hostname == null) 
                        $hostname = 'localhost';
                  }
                  $time_dns_resolving_element = $technicalInformation->get_elements_by_tagname('time_dns_resolving');
                  if (sizeof($time_dns_resolving_element) > 0) {
                     $time_dns_resolving = $time_dns_resolving_element[0]->get_content();
                     // default time_dns_resolving
                     if ($time_dns_resolving == null) 
                        $time_dns_resolving = time();
                  }
                  $incident_time_element = $technicalInformation->get_elements_by_tagname('incident_time');
                  if (sizeof($incident_time_element) > 0) {
                     $incident_time = $incident_time_element[0]->get_content();
                     // default incident_time
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

function CreateTicket($userid,$ticketid) {
   $res = db_query(
      "SELECT  nextval('authentication_tickets_sequence') as creationid")
      or die("Unable to execute query");

   while ($row = db_fetch_next($res)) {
      $creationid = $row['creationid'];
   }
      
   $res = db_query(sprintf("insert into authentication_tickets (id,
   userid, created, expiration, ticketid) VALUES
   (%s, %s, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP + interval
   '".TICKET_EXP." seconds', %s)",
      db_masq_null($creationid),
      db_masq_null($userid),
      db_masq_null($ticketid)))
   or die('Unable to create ticket');

   return $creationid;
}

function genRandom() {
   $ticketid = '';

   $string = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
   for($i=0;$i<=2999;$i++) {
      $ticketid .= substr($string,mt_rand(0,strlen($string)-1),1);
   }
   return $ticketid;
}

function generateSAMLTicket($ticket_details) {
   // predefine variables
   $issuer = 'AIRT';
   $digest = '';

   $doc = domxml_new_doc('1.0');
   $root = $doc->create_element('Assertions');
   $root->add_namespace('urn:oasis:names:tc:SAML:1.0:assertion', '');
   $root->add_namespace('http://www.w3.org/2000/09/xmldsig#', 'ds');
   $root->set_attribute('MajorVersion', '1');
   $root->set_attribute('MinorVersion', '0');
   $root->set_attribute('AssertionID', $ticket_details['assertionid']);
   $root->set_attribute('Issuer', $issuer);
   $root->set_attribute('IssueInstant', $ticket_details['issue_time']);
   $doc->append_child($root);

   $conditions = $doc->create_element('Conditions');
   $conditions->set_attribute('NotBefore', $ticket_details['issuetime']);
   $conditions->set_attribute('NotAfter', $ticket_details['exptime']);

   $authentication_statement = $doc->create_element('AuthenticationStatement');
   $authentication_statement->set_attribute('AuthenticationMethod', 'urn:oasis:names:tc:SAML:1.0:am:password');
   $authentication_statement->set_attribute('AuthenticationInstant', $ticket_details['issue_time']);

   $subject = $doc->create_element('Subject');

   $name_identifier = $doc->create_element('NameIdentifier');
   $name_identifier->set_attribute('NameQualifier', $issuer);
   $name_identifier->set_content(INCIDENTID_PREFIX);

   $subject_confirmation = $doc->create_element('SubjectConfirmation');

   $confirmation_method = $doc->create_element('ConfirmationMethod');
   $confirmation_method->set_content('urn:oasis:names:tc:SAML:1.0:cm:holder-of-key');

   $key_info = $doc->create_element('ds:KeyInfo');

   $key_name = $doc->create_element('ds:KeyName');
   $key_name->set_content('AIRTKey');

   $key_value = $doc->create_element('ds:KeyValue');
   $key_value->set_content($ticket_details['ticketid']);

   $signature = $doc->create_element('ds:Signature');
   $signature->set_content($digest);

   // Build structure
   $root->append_child($conditions);
   $key_info->append_child($key_name);
   $key_info->append_child($key_value);
   $subject_confirmation->append_child($confirmation_method);
   $subject_confirmation->append_child($key_info);
   $subject->append_child($name_identifier);
   $subject->append_child($subject_confirmation);
   $authentication_statement->append_child($subject);
   $root->append_child($authentication_statement);
   $root->append_child($signature);
   $content = $doc->dump_mem();
   return $content;

}

function CheckCredentials($ticketid) {
   $res = db_query(sprintf('
      SELECT   userid 
      FROM     authentication_tickets where ticketid=%s 
      AND      CURRENT_TIMESTAMP > created
      AND      CURRENT_TIMESTAMP < expiration', 
         db_masq_null($ticketid)))
   or die;

   while ($row = db_fetch_next($res)) {
      $userid = $row['userid'];
   }
   return $userid;
}


?>
