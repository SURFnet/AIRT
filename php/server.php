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
require_once LIBDIR.'/server.plib';
require_once LIBDIR.'/authentication.plib';
require_once LIBDIR.'/incident.plib';
require_once LIBDIR.'/constituency.plib';
require_once LIBDIR.'/search.plib';
require_once LIBDIR.'/profiler.plib';

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
      $this->__dispatch_map['RequestAuthentication'] = array(
         'in'  => array('auth_request' => 'string'),
         'out' => array('AuthenticationTicket' => 'string')
      );

      $this->__dispatch_map['getXMLIncidentData'] = array(
         'in'  => array('action' => 'string'),
         'out' => array('airtXML' => 'string')
      );

      $this->__dispatch_map['importIncidentData'] = array(
         'in'  => array('importXML' => 'string'),
         'out' => array('confirmation' => 'string')
      );

      $this->__dispatch_map['importContact'] = array(
         'in'  => array('importXML' => 'string'),
         'out' => array('confirmation' => 'string')
      );

      $this->__dispatch_map['addLogging'] = array(
         'in'  => array('incidentid' => 'integer',
                        'logging'=>'string',
                        'template'=>'string',
                        'AuthenticationTicket'=>'string'),
         'out' => array('confirmation' => 'string')
      );
   }


   function RequestAuthentication($auth_request) {
      $dom = '';
      $doc = new DOMDocument();
      airt_profile('RequestAuthentication: parsing request');
      $dom = $doc->loadXML($auth_request);
      if (!$dom) {
         $error = 'Could not parse XML document';
         return $error;
         exit;
      }

      if ($doc->hasChildNodes() == false) {
         $error = 'XML does not contain any elements';
         return $error;
         exit;
      }

      $username_element = $doc->getElementsByTagname('username');
      $password_element = $doc->getElementsByTagname('password');
      if ($username_element->length == 0 || $password_element->length == 0) {
         $error = 'Username element or password element is empty';
         return $error;
         exit;
      }

      $username = $username_element->item(0)->textContent;
      $password = $password_element->item(0)->textContent;

      airt_profile('RequestAuthentication: authenticating user');
      $userid = airt_authenticate($username,$password);
      if ($userid == -1) {
         airt_profile('RequestAuthentication: credentials refused');
         $error = 'Permission denied';
         return $error;
         exit;
      }
      airt_profile('RequestAuthentication: credentials accepted');

      // generate a new random id
      $ticketid = genRandom();

      // put it in the db
      $creationid = CreateTicket($userid,$ticketid);
      $issuetime = getIssueTime($creationid);

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
      $dom = $mailtemplate = $state = $status = $type = $incidentid = $address = $ip = $addressrole ='';

      // first check the validity of the XML
      $doc = new DOMDocument();
      $dom = $doc->loadXML($importXML);
      if (!$dom) {
         $error = 'Could not parse XML document';
         return $error;
         exit;
      }
airt_profile('parsed');

      if ($doc->hasChildNodes() == false) {
         $error = 'XML does not contain any elements';
         return $error;
         exit;
      }

airt_profile('xml ok');

      // check the credentials
      $message_ident_el = $doc->getElementsByTagname('messageIdentification');
      if ($message_ident_el->length == 0) {
         $error = 'Empty messageIdentification element';
         return $error;
         exit;
      }

airt_profile('identification ok');

      if ($message_ident_el->length > 0) {
         $ticket_el = $message_ident_el->item(0)->getElementsByTagname('TicketID');
         if ($ticket_el->length == 0) {
            $error = 'Key element is empty';
            return $error;
            exit;
         } else {
            $ticketid = $ticket_el->item(0)->textContent;
            $userid = CheckCredentials($ticketid);
            if ($userid == -1) {
               return 'Not authorized';
            }
            $_SESSION['userid'] = $userid;
         }
      }

airt_profile('Credentials ok');

      // get defaults
      if (!defined('WS_IMPORT_DEFAULTSTATE')) {
         $state = getIncidentStateDefault();
         if($state == null) {
            setIncidentStateDefault();
            $state = getIncidentStateDefault();
         }
      } else {
         $state = array_search(WS_IMPORT_DEFAULTSTATE, getIncidentStates());
         if ($state == false) {
            $state = getIncidentStateDefault();
         }
      }

airt_profile('State: '.$state);

      $status = getIncidentStatusDefault();
      if($status == null) {
         setIncidentStatusDefault();
         $status = getIncidentStatusDefault();
      }

airt_profile('Status: '.$status);

      $type = getIncidentTypeDefault();
      if($type == null) {
         setIncidentTypeDefault();
         $type = getIncidentTypeDefault();
      }

airt_profile('Type: '.$type);

      // parse incident data
      $incidents = $doc->getElementsByTagname('incident');
      for ($count=0;$count<$incidents->length;$count++) {
         // begin ticketInformation
         $ti = $incidents->item($count)->getElementsByTagName('ticketInformation');
         if ($ti->length > 0) {
            $prefixel = $ti->item(0)->getElementsByTagname('prefix');
            if ($prefixel->length > 0) {
               $prefix = $prefixel->item(0)->textContent;
               if (empty($prefix)) {
                  $prefix = '#UNKNOWN';
               }
            }
            $refel = $ti->item(0)->getElementsByTagname('reference');
            if ($refel->length > 0) {
               $reference = $refel->item(0)->textContent;
               if (empty($reference)) {
                  $reference = '0';
               }
            }
         } // end TicketInformation

         $techinfo = $incidents->item($count)->getElementsByTagname('technicalInformation');
         for ($count2=0; $count2<$techinfo->length; $count2++) {
            $ip_element = $techinfo->item($count2)->getElementsByTagname('ip');
            if ($ip_element->length > 0) {
               $ip = $ip_element->item(0)->textContent;
               // default ip
               if ($ip == null) {
                  $ip = '127.0.0.1';
               }
            }
            $hostname_element = $techinfo->item($count2)->getElementsByTagname('hostname');
            if ($hostname_element->length > 0) {
               $hostname = $hostname_element->item(0)->textContent;
               // default hostname
               if ($hostname == null) {
                  $hostname = 'localhost';
               }
            }

            $time_dns_resolving_element = $techinfo->item($count2)->getElementsByTagname('time_dns_resolving');
            if ($time_dns_resolving_element->length > 0) {
               $time_dns_resolving = $time_dns_resolving_element->item(0)->textContent;
               // default time_dns_resolving
               if ($time_dns_resolving == null) {
                  $time_dns_resolving = time();
               }
            }

            $incident_time_element = $techinfo->item($count2)->getElementsByTagname('incident_time');
            if ($incident_time_element->length > 0) {
               $incident_time = $incident_time_element->item(0)->textContent;
               // default incident_time
               if ($incident_time == null) {
                  $incident_time = time();
               }
            }

            $logging_element = $techinfo->item($count2)->getElementsByTagname('logging');
            if ($logging_element->length > 0) {
               $logging = $logging_element->item(0)->textContent;
            }
airt_profile('Logging: ok');

            $mailtemplate_element = $techinfo->item($count2)->getElementsByTagname('mailtemplate');
            if ($mailtemplate_element->length > 0) {
               $mailtemplate = $mailtemplate_element->item(0)->textContent;
            }
airt_profile('Mailtemplate: ok');

            $address = $ip;
            $addressrole = '0';
         } // end technicalInformation
      } // end incident

airt_profile('Incident data parsed');

      // generate an incident id
      $incidentid[$i] = createIncident(array(
         'state'=>$state,
         'status'=>$status,
         'type'=>$type,
         'logging'=>$logging));
airt_profile('Incident '.$incidentid[$i].' created');

      addIPtoIncident($address,$incidentid[$i],$addressrole);
airt_profile('IP addresses added');

      $networkid = categorize($address);
      $constituencyID = getConstituencyIDbyNetworkID($networkid);
      $contacts = getConstituencyContacts($constituencyID);
      foreach ($contacts as $id=>$data) {
         addUserToIncident($data['userid'], $incidentid[$i]);
      }
airt_profile('Users added');

      if ($mailtemplate != '' && $mailtemplate != _('No preferred template')) {
         setPreferredMailTemplateName($incidentid[$i], $mailtemplate);
         addIncidentComment(array(
            'comment'=>'Import queue set preferred template to: '.$mailtemplate,
            'incidentid'=>$incidentid[$i]));
      }

airt_profile('Template added');

      if ($error == null) {
         airt_profile('Success');
         $error = 'Import successful. Imported incident with id ';
         foreach ($incidentid as $i => $id)
            $id_list .= "$id, ";
         $id_list = rtrim($id_list,', ');
         $error .= $id_list.'.';
         return $error;
      }
   }

   /* Add log information to existing incident
    *
    * $incidentid (numeric) = Incident to add to
    * $logging (string) = Logging to be added
    * $authticket (string) = SAML Authentication ticket for the request
    *
    * Returns a string containing a descriptive error message
    * in case of failure, or the empty string in case of success.
    */
   function addLogging($incidentid, $logging, $template, $authticket) {
      $userid = CheckCredentials($authticket);

      if (!is_string($logging)) {
         return 'Invalid data type (logging)';
      }
      if (!is_numeric($incidentid)) {
         return 'Invalid data type (incidentid)';
      }
      if (!is_string($authticket)) {
         return 'Invalid data type ($authticket)';
      }
      if (!is_string($template)) {
         return 'Invalid data type ($template)';
      }
      $userid = CheckCredentials($authticket);
      if ($userid == -1) {
         return 'Not authorized.';
      }
      if (($incident = getIncident($incidentid)) == false) {
         return 'No such incident ($incidentid)';
      }
      $_SESSION['userid'] = $userid;
      $logging = $incident['logging']."\n".$logging;
      updateIncident($incidentid, array('logging'=>$logging));
      if ($template != '') {
         setPreferredMailTemplateName($incidentid, $template);
         addIncidentComment(array(
            'comment'=>'Import queue set preferred template to: '.$template,
            'incidentid'=>$incidentid));
      }
      return '';
   } // addLogging

   /**
    * Import new contact information. If the constituency already exists,
    * the networks and/or constituency contacts will be created (if 
    * necesarry) and associated with the constituency
    *
    * @param $importXML XML describing networks, constituency
    *        and constituency contacts. 
    * <airt>
    *    <contactData>
    *       <constituency>My Constituency</constituency>
    *       <contact><!-- element may be repeated 0 or more times -->
    *          <name>...</name>
    *          <email>...</email>
    *          <phone>...</phone>
    *       </contact>
    *       <network><!-- element may be repeated 0 or more times -->
    *          <address>...</address>
    *          <netmask>...</netmask>
    *       </network>
    *   </contactData>
    * </airt>
    *       
    *        
    */
   function importContact($importXML) {
      airt_profile('begin importContact');
      airt_profile('xml: '.$importXML);
      $doc = new DOMDocument();
      $doc->loadXML($importXML);
      $xpath = new DOMXPath($doc);

      $res = $xpath->query('//airt/contactData/constituency');
      /* We cannot do anything if we do not know what the constituency is.
       * Only one constituency is allowed per contactData element.
       */
      if ($res->length == 0) {
         airt_profile('Could not find constituency');
         return '';
      }
      $constituency = $res->item(0)->textContent;
      if (constituencyExists($constituency)) {
         $addToExisting = true;
      } else {
         $addToExisting = false;
      }

      $contactlist = $xpath->query('//airt/contactData/contact');
      $contacts = array();
      foreach ($contactlist as $contact) {
         airt_profile('Begin processing contact');
         $name = $xpath->query('name', $contact);
         if ($name->length == 0) {
            $name = '';
         } else {
            $name = $name->item(0)->textContent;
         }
         $email = $xpath->query('email', $contact);
         if ($email->length == 0) {
            $email = '';
         } else {
            $email = $email->item(0)->textContent;
         }
         $phone = $xpath->query('phone', $contact);
         if ($phone->length == 0) {
            $phone = '';
         } else {
            $phone = $phone->item(0)->textContent;
         }
         airt_profile('Z');
         $contacts[] = array(
            'name'=>$name,
            'email'=>$email,
            'phone'=>$phone
         );
         airt_profile('End processing contact');
      }

      $networklist = $xpath->query('//airt/contactData/network');
      if ($network->length == 0) {
         continue;
      }
      foreach ($networklist as $network) {
         $address = $xpath->query('address', $network);
         if ($address->length == 0) {
            continue;
         } else {
            $address = $address->item(0)->textContent;
         }
         $netmask = $xpath->query('netmask', $network);
         if ($netmask->length == 0) {
            continue;
         } else {
            $netmask = $netmask->item(0)->textContent;
         }
         $networks[] = array(
            'address'=>$address,
            'netmask'=>$netmask
         );
      }
      
      $error = '';
      
      // only proceed if constituency does not exist
      if (constituencyExists($constituency)) {
         return 'Constituency exists';
      }

      addConstituency($constituency, $constituency, $error);
      $c = getConstituencies();
      $consid = array_search($constituency, $c);

      // only add network if it does not yet exist
      foreach ($networks as $network) {
         if (networkExists($network['address'], $network['netmask'])) {
            if (updateNetwork(array(
               'network'=>$network['address'],
               'netmask'=>$network['netmask'],
               'label'=>'net-'.$network['address'],
               'name'=>'Network '.$network['address'].'/'.$network['netmask']
            )) === false) {
               return 'Failed to update network';
            }
         } else {
            if (addNetwork(array(
               'network'=>$network['address'],
               'netmask'=>$network['netmask'],
               'label'=>'net-'.$network['address'],
               'name'=>'Network '.$network['address'].'/'.$network['netmask']
            )) === false) {
               return 'Failed to add network';
            }
         }
      }

// XXX
      foreach ($contacts as $contact) {
         $email = strtolower($contact['email']);
         if (($user = getUserByEmail($email)) === false) {
            addUser(array(
               'email'=>$contact['email'],
               'phone'=>$contact['phone'],
               'name'=>$contact['lastname']
            ));
            if (($user == getUserByEmail($email)) === false) {
               return _('Unable to add user');
            }
         }
         $userid = $user['id'];
      }
      return 'SUCCESS';
   }
} // end of class IncidentHandling


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

   $doc = new DOMDocument();
   $ass = $doc->appendChild($doc->createElementNS(
      'urn:oasis:names:tc:SAML:1.0:assertion', 'saml:Assertions'));
   $ass->setAttribute('MajorVersion', '1');
   $ass->setAttribute('MinorVersion', '0');
   $ass->setAttribute('AssertionID', $ticket_details['assertionid']);
   $ass->setAttribute('Issuer', $issuer);
   $ass->setAttribute('IssueInstant', $ticket_details['issue_time']);

   $conditions = $ass->appendChild($doc->createElementNS(
      'urn:oasis:names:tc:SAML:1.0:assertion', 'saml:Conditions'));
   $conditions->setAttribute('NotBefore', $ticket_details['issuetime']);
   $conditions->setAttribute('NotAfter', $ticket_details['exptime']);

   $authentication_statement = $ass->appendChild($doc->createElementNS(
      'urn:oasis:names:tc:SAML:1.0:assertion', 'saml:AuthenticationStatement'));
   $authentication_statement->setAttribute('AuthenticationMethod', 'urn:oasis:names:tc:SAML:1.0:am:password');
   $authentication_statement->setAttribute('AuthenticationInstant', $ticket_details['issue_time']);

   $subject = $authentication_statement->appendChild($doc->createElementNS(
      'urn:oasis:names:tc:SAML:1.0:assertion', 'saml:Subject'));

   $name_identifier = $subject->appendChild($doc->createElementNS(
      'urn:oasis:names:tc:SAML:1.0:assertion', 'saml:NameIdentifier'));
   $name_identifier->setAttribute('NameQualifier', $issuer);
   $name_identifier->appendChild($doc->createTextNode(INCIDENTID_PREFIX));

   $subject_confirmation = $subject->appendChild($doc->createElementNS(
      'urn:oasis:names:tc:SAML:1.0:assertion', 'saml:SubjectConfirmation'));

   $confirmation_method = $subject_confirmation->appendChild($doc->createElementNS(
      'urn:oasis:names:tc:SAML:1.0:assertion', 'saml:ConfirmationMethod'));
   $confirmation_method->appendChild($doc->createTextNode(
      'urn:oasis:names:tc:SAML:1.0:cm:holder-of-key'));

   $key_info = $subject_confirmation->appendChild($doc->createElementNS(
      'http://www.w3.org/2000/09/xmldsig#', 'ds:KeyInfo'));

   $key_name = $key_info->appendChild($doc->createElementNS(
      'http://www.w3.org/2000/09/xmldsig#', 'ds:KeyName'));
   $key_name->appendChild($doc->createTextNode('AIRTKey'));

   $key_value = $key_info->appendChild($doc->createElementNS(
      'http://www.w3.org/2000/09/xmldsig#', 'ds:KeyValue'));
   $key_value->appendChild($doc->createTextNode($ticket_details['ticketid']));

   $signature = $ass->appendChild($doc->createElementNS(
      'http://www.w3.org/2000/09/xmldsig#', 'ds:Signature'));
   $signature->appendChild($doc->createTextNode($digest));

   // Build structure
   $content = $doc->saveXML();
   return $content;

}


?>
