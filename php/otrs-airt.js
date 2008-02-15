/* vim:syntax=javascript shiftwidth=3 tabstop=3
 *
 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2006   Tilburg University, The Netherlands

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
 * otrs-airt.js -- AJAX scripts for AIRT-OTRS integration
 *
 * $Id$
 */
var req=new Array();

function loadXMLDoc(ticketno) {
   // Input can be added to the url
   // TODO: make this a config option
   var url = "/airt/otrs.php?action=get&tn="+ticketno;

   // branch for native XMLHttpRequest object
   if (window.XMLHttpRequest != null) {
      req[ticketno] = new XMLHttpRequest();
   }
   // branch for IE/Windows ActiveX version 
   else if (window.ActiveXObject) {
      req[ticketno] = new ActiveXObject("Microsoft.XMLHTTP");
   }

   if (req[ticketno] != null) {
      req[ticketno].open("GET", url, false);
      req[ticketno].send(null);
      processReqChange(ticketno);
   }
}

function getElementById(id) {
   return document.getElementById(id);
}

function processReqChange(ticketno) {
   var response;
   var baseurl;
   var incidentlist;
   var out = '- none';
   var i;
   var label;
   var incidentid;
   var incident;
   var status;
   var tn;
	var airt_output;
	var selectbox;

   res = req[ticketno].responseXML;
   if (res == null) {
      out = "AIRT unavailable (log in first?)";
   } else {
      response = res.documentElement;
      if (response.tagName=='airt') {
         baseurl = response.getAttribute('baseurl');
         incidentlist = response.getElementsByTagName('incident');
         selectbox = getElementById('incidentstatus_'+ticketno);
         for (i=0; i<incidentlist.length; i++) {
            if (i==0) { 
               out=''; 
            }
            incident = incidentlist.item(i);
            incidentid = incident.getAttribute('id');
            label = incident.getAttribute('label');
            status = incident.getAttribute('status');
				t = incident.getAttribute('ticketno');
				if (t != null && t == ticketno) {
					out +=  '- <a href="'+baseurl+'/incident.php?action=details&incidentid='+incidentid+
							  '">'+label+'</a><br/>';
				}
				if (t == null && label != "") {
               selectbox.options[i+1] = new Option(label + "  " + status,incidentid,false,false);
				}
         }
      } else {
         out = 'Unexpected response from server';
      }
   }

   try {
	   var label = "airt_output_"+ticketno;
      airt_output = getElementById(label);
      if (airt_output != null) {
         airt_output.innerHTML = out;
      }
   } catch (e) {
      // do nothing (make IE happy)
   }
} // function ProcessReqChange
