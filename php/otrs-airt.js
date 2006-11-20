var req;

function loadXMLDoc(ticketno) {
   // Input can be added to the url
   // TODO: make this a config option
   var url = "http://192.168.81.130/airt/otrs.php?action=get&ticketno="+ticketno;

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

function processReqChange() {
   // only if req shows "complete"
   if (req.readyState == 4) {
      // only if "OK"
      if (req.status == 200) {
         response = req.responseXML.documentElement;
         tnelement = response.getElementsByTagName('incidentno');
	 if (tnelement.length >= 1) {
	    incidentnr=tnelement.item(0).firstChild.data;
	 }
         textfield = document.getElementById('incidentnr');
         textfield.value=incidentnr;
	 /*
         htmlelement = response.getElementsByTagName('html');
	 if (htmlelement.length >= 1) {
	    html=htmlelement.item(0).firstChild.data;
	    document.writeln(html);
	 }
	 */
      }
   }
}
