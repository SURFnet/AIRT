var req;

function loadXMLDoc(ticketno) {
   // Input can be added to the url
   // TODO: make this a config option
   var url = "/airt/otrs.php?action=get&tn="+ticketno;

   // branch for native XMLHttpRequest object
   if (window.XMLHttpRequest) {
      req = new XMLHttpRequest();
   }
   // branch for IE/Windows ActiveX version 
   else if (window.ActiveXObject) {
      req = new ActiveXObject("Microsoft.XMLHTTP");
   }

   if (req != null) {
      req.onreadystatechange = processReqChange;
      req.open("GET", url, true);
      req.send(null);
   }
}

function getElementById(id) {
   return document.getElementById(id);
}

function processReqChange() {
   var response;
   var baseurl;
   var incidentlist;
   var out = '- none';
   var i;
   var label;
   var incidentid;
   var incident;
   var status;

   // only if req shows "complete"
   if (req.readyState == 4) {
      // only if "OK"
      if (req.status == 200) {
         res = req.responseXML;
         if (res == null) {
	    out = "AIRT unavailable (log in first?)";
	 } else {
            response = res.documentElement;
            if (response.tagName=='airt') {
               baseurl = response.getAttribute('baseurl');
               incidentlist = response.getElementsByTagName('incident');
               for (i=0; i<incidentlist.length; i++) {
	          if (i==0) { out=''; }
                  incident = incidentlist.item(i);
                  incidentid = incident.getAttribute('id');
                  label = incident.getAttribute('label');
                  out +=  '- <a href="'+baseurl+'/incident.php?action=details&incidentid='+incidentid+
                         '">'+label+'</a><br/>';
               }
            } else {
	       out = 'Unexpected response from server';
	    }
	 }

         try {
	    airt_output = getElementById("airt_output");
	 } catch (e) {
	 }
	 if (airt_output != null) {
            airt_output.innerHTML = out;
         } 
      } else {
         airt_output = getElementById('airt_output');
         if (airt_output != null) {
            airt_output.innerHTML = "AIRT unavailable";
         }
      }
   }
}
