/* vim: syntax=java tabstop=3 shiftwidth=3
 * $Id$ 
 * AIRT: APPLICATION FOR INCIDENT RESPONSE TEAMS
 * Copyright (C) 2004,2005	Tilburg University, The Netherlands

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
package airt.client;

import org.apache.axis.client.Call;
import org.apache.axis.client.Service;
import org.apache.axis.encoding.XMLType;

import javax.xml.namespace.QName;
import javax.xml.rpc.ParameterMode;

public class airt_client {
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

