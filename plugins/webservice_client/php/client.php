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
      $params        = array('importXML' => $import_array);
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
