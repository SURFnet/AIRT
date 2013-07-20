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
require_once LIBDIR.'/network.plib';

if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
   airt_profile('Refusing client from '.$_SERVER['REMOTE_ADDR']);
	die('Access denied.');
}
airt_profile('SOAP: Accepted incoming connection');

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
	   airt_profile('Figuring out where to send incoming rquest');

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
                        'template'=>'string'),
         'out' => array('confirmation' => 'string')
      );
   }


   function getXMLIncidentData($action)  {
      if ($action == 'getAll') {
         require_once('export.php');
         return exportOpenIncidents();
      }
   }

   /* Add log information to existing incident
    *
    * $incidentid (numeric) = Incident to add to
    * $logging (string) = Logging to be added
    *
    * Returns a string containing a descriptive error message
    * in case of failure, or the empty string in case of success.
    */
   function addLogging($incidentid, $logging, $template) {
      $userid = IMPORTUSER;

      if (!is_string($logging)) {
         return 'Invalid data type (logging)';
      }
      if (!is_numeric($incidentid)) {
         return 'Invalid data type (incidentid)';
      }
      if (!is_string($template)) {
         return 'Invalid data type ($template)';
      }
      if (($incident = getIncident($incidentid)) == false) {
         return 'No such incident ($incidentid)';
      }
      $_SESSION['userid'] = $userid;
      Setup::getOption('inqueuesep', $inqueuesep, true);
      if (empty($inqueuesep)) {
          $inqueuesep = "\n";
      }
      $inqueuesep = date(str_replace('\n', "\n", $inqueuesep));
      $logging = $incident['logging'].$inqueuesep.$logging;

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
         airt_profile('Could not find constituency in XML');
         return '';
      }
      $constituency = $res->item(0)->textContent;
      if (!constituencyExists($constituency)) {
         airt_profile('New constituency');
         if ((addConstituency($constituency, $constituency, $error)) ===
            false) {
            airt_profile('Could not add constituency');
            return _('Error adding constituency');
         }
         airt_profile('Constituency added');
      } else {
         airt_profile('Existing constituency');
      }
      foreach (getConstituencies() as $id=>$con) {
         if ($con['label'] == $constituency || $con['name'] == $constituency) {
            $conid = $id;
            break;
         }
      }
      if (!isset($conid)) {
         airt_profile('Unable to determine constituency');
         return _('Unable to determine constituency');
      }
      airt_profile('Constituency id: '.$conid);

      $contactlist = $xpath->query('//airt/contactData/contact');
      $contacts = array();
      foreach ($contactlist as $contact) {
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
         $contacts[] = array(
            'name'=>$name,
            'email'=>$email,
            'phone'=>$phone
         );
      }

      $networks = array();
      $networklist = $xpath->query('//airt/contactData/network');
      foreach ($networklist as $network) {
         $address = $xpath->query('address', $network);
         if ($address->length == 0) {
            airt_profile('Address not found');
            continue;
         } else {
            $address = $address->item(0)->textContent;
         }
         $netmask = $xpath->query('netmask', $network);
         if ($netmask->length == 0) {
            airt_profile('Netmask not found');
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
      
      // only add network if it does not yet exist
      foreach ($networks as $network) {
         airt_profile('Begin processing network '.
            "$network[address]/$network[netmask]");
         if (networkExists($network['address'], $network['netmask'])==true) {
            airt_profile('Found existing network');
            if (updateNetwork(array(
               'network'=>$network['address'],
               'netmask'=>$network['netmask'],
               'label'=>'net-'.$network['address'],
               'name'=>'Network '.$network['address'].'/'.$network['netmask'],
               'constituency'=>$conid
            ), $error) === false) {
               airt_profile('Unable to update network:'.$error);
               return 'Failed to update network';
            }
            airt_profile('Network updated');
         } else {
            airt_profile('Adding new network');
            if (addNetwork(array(
               'network'=>$network['address'],
               'netmask'=>$network['netmask'],
               'label'=>'net-'.$network['address'],
               'name'=>'Network '.$network['address'].'/'.$network['netmask'],
               'constituency'=>$conid
            ), $error) === false) {
               airt_profile('Unable to add network:'.$error);
               return 'Failed to add network';
            }
            airt_profile('Network added');
         }
         airt_profile('End processing network');
      }

      foreach ($contacts as $contact) {
         airt_profile('Begin processing contact '.$contact['email']);
         $email = strtolower($contact['email']);
         if (($user = getUserByEmail($email)) == false) {
            airt_profile('User not found');
            addUser(array(
               'email'=>$contact['email'],
               'phone'=>$contact['phone'],
               'lastname'=>$contact['name']
            ));
            if (($user = getUserByEmail($email)) == false) {
               airt_profile('Unable to add user');
               return _('Unable to add user');
            }
            airt_profile('User added');
         } else {
            airt_profile('Existing user');
         }
         foreach ($user as $key=>$value) {
            airt_profile("$key=$value");
         }
         $userid = $user['id'];
         if (assignUser($userid, $conid, $error) === false) {
            airt_profile("Unable to assign user $userid:$conid:$error");
            return _('Unable to assign user');
         } 
         airt_profile('User assigned');
      }
      return 'SUCCESS';
   }

	function importIncidentData($xml) {
		airt_profile('Web service method importIncidentData');
		return AIRT_importIncidentData($xml);
	}
} // end of class IncidentHandling


function genRandom() {
   $ticketid = '';

   $string = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
   for($i=0;$i<=64;$i++) {
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
   $ass->setAttribute('IssueInstant', $ticket_details['issuetime']);

   $conditions = $ass->appendChild($doc->createElementNS(
      'urn:oasis:names:tc:SAML:1.0:assertion', 'saml:Conditions'));
   $conditions->setAttribute('NotBefore', $ticket_details['issuetime']);
   $conditions->setAttribute('NotAfter', $ticket_details['exptime']);

   $authentication_statement = $ass->appendChild($doc->createElementNS(
      'urn:oasis:names:tc:SAML:1.0:assertion', 'saml:AuthenticationStatement'));
   $authentication_statement->setAttribute('AuthenticationMethod', 'urn:oasis:names:tc:SAML:1.0:am:password');
   $authentication_statement->setAttribute('AuthenticationInstant', $ticket_details['issuetime']);

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
