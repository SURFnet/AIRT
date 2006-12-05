/* vim:syntax=php shiftwidth=3 tabstop=3
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
 * $Id: incident.php 1016 2006-10-31 12:34:55Z kees $
 */
var req = new Object();

function loadXMLDoc(ticketno) {
   // Input can be added to the url
   // TODO: make this a config option
   var url = "/airt/otrs.php?action=get&tn="+ticketno;

   // branch for native XMLHttpRequest object
   if (window.XMLHttpRequest) {
      req.ticketno = new XMLHttpRequest();
   }
   // branch for IE/Windows ActiveX version 
   else if (window.ActiveXObject) {
      req.ticketno = new ActiveXObject("Microsoft.XMLHTTP");
   }

   if (req.ticketno != null) {
      req.ticketno.onreadystatechange = processReqChange(ticketno);
      req.ticketno.open("GET", url, true);
      req.ticketno.send(null);
   }
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

   // only if req shows "complete"
   alert(ticketno+":"+req.ticketno+":"+req.ticketno.readyState);
   if (req.ticketno.readyState == 4) {
      // only if "OK"
      if (req.ticketno.status == 200) {
         res = req.ticketno.responseXML;
         if (res == null) {
            out = "AIRT unavailable (log in first?)";
         } else {
            response = res.documentElement;
            if (response.tagName=='airt') {
               baseurl = response.getAttribute('baseurl');
               incidentlist = response.getElementsByTagName('incident');
               selectbox = document.getElementById('incidentstatus');
               for (i=0; i<incidentlist.length; i++) {
                  if (i==0) { 
		     out=''; 
		  }
                  incident = incidentlist.item(i);
                  incidentid = incident.getAttribute('id');
                  tn = incident.getAttribute('ticketno');
                  label = incident.getAttribute('label');
                  status = incident.getAttribute('status');
                  out +=  '- <a href="'+baseurl+'/incident.php?action=details&incidentid='+incidentid+
                          '">'+label+'</a><br/>';
                  // selectbox.options[i+1] = new Option(label + "  " + status,label,false,false);
               }
            } else {
               out = 'Unexpected response from server';
            }
         }

         try {
            airt_output = document.getElementById("airt_output_"+tn);
            if (airt_output != null) {
               airt_output.innerHTML = out;
            } 
         } catch (e) {
         }
      } else {
         airt_output = document.getElementById('airt_output');
         if (airt_output != null) {
            airt_output.innerHTML = "AIRT unavailable";
         }
      }
   } // readyState == 4
} // function ProcessReqChange
