/* $Id$ 
 */
package airt.client;

import java.awt.*;
import java.net.*;
import java.io.*;
import java.awt.event.*;
import java.applet.*;
import javax.swing.*;
import javax.swing.tree.*;
import javax.swing.event.TreeSelectionListener;
import javax.swing.event.TreeSelectionEvent;

import org.apache.axis.AxisFault;
import org.apache.axis.client.Call;
import org.apache.axis.client.Service;
import org.apache.axis.utils.Options;
import org.apache.axis.encoding.ser.*;
import org.apache.axis.encoding.XMLType;
import org.apache.axis.types.*;

import javax.xml.namespace.QName;
import javax.xml.rpc.ParameterMode;

public class airtclient {
  public static void main(String args[]){
    try {
      org.apache.axis.client.Service service= new Service();
      Call call    = (Call) service.createCall();
      call.setTargetEndpointAddress( new java.net.URL("http://similarius.uvt.nl/~sebas/airt/server.php") );
      //call.setOperationName( new QName("IncidentService", "GetIncidentData"));
      call.setOperationName( new QName("GetIncidentData"));
      call.addParameter( "arg1", XMLType.XSD_STRING, ParameterMode.IN );
      call.setReturnType( org.apache.axis.encoding.XMLType.XSD_STRING);
      String ga = new String("getAll");
      String result = (String) call.invoke( new Object[] {ga} );
      System.out.println(result);
      }
    catch (Exception ev){
      ev.printStackTrace();
      }
    }
  }
