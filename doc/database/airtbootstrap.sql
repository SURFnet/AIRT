-- AIRT: APPLICATION FOR INCIDENT RESPONSE
-- Copyright (C) 2004   Tilburg University, The Netherlands

-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.

-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.

-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA


-- $Id$
-- In CVS at $Source$

-- 
-- Preload administrator account and administrator role
--

begin transaction;

INSERT INTO users
    (id, lastname, email, login, password) 
    VALUES
    (nextval('users_sequence'), 'Administrator', 'airt@example.com', 'admin', 
    'd033e22ae348aeb5660fc2140aec35850c4da997');
INSERT INTO roles
    (id, label)
    VALUES
    (nextval('roles_sequence'), 'Administrator');
INSERT INTO permissions
    (id, label)
    VALUES
    (nextval('permissions_sequence'), 'administrator');
INSERT INTO role_assignments
    (id, role, userid)
    VALUES
    (nextval('role_assignments_sequence'), 1, 1);
INSERT INTO role_permissions
    (id, role, permission)
    VALUES
    (nextval('role_assignments_sequence'), 1, 1);
INSERT INTO users
    (id, lastname, email, login, password)
    VALUES
    (nextval('users_sequence'), 'Webservice', 'airt-ws@example.com', 'webservice',
    '62e91bc649621eed1004de8936987fc853df0eb7');
INSERT INTO roles
    (id, label)
    VALUES
    (nextval('roles_sequence'), 'Webservice');
INSERT INTO role_assignments
    (id, role, userid)
    VALUES
    (nextval('role_assignments_sequence'), currval('roles_sequence'),
    currval('users_sequence'));
INSERT INTO constituencies
    (id,label,name) 
    VALUES
    (-1, 'default', 'default');
INSERT INTO networks
    (id, network, netmask, label, constituency) 
    VALUES
    ( -1, '0.0.0.0', '0.0.0.0', 'Default network', -1);
INSERT INTO incident_status
    (id, label,descr,isdefault)
    VALUES
      (nextval('incident_status_sequence'),
      'open',
      'Incident is being handled.',
      TRUE);
INSERT INTO incident_types
    (id,label,descr,isdefault)
    VALUES
      (nextval('incident_types_sequence'),
      'Compromised',
      'Host has been comprimised.',
      TRUE);
INSERT INTO incident_states
    (id,label,descr,isdefault)
    VALUES
      (nextval('incident_states_sequence'),
      'Inspectionrequest',
      'Inspection requested.',
      TRUE);
INSERT INTO incident_status
    (id, label,descr,isdefault)
    VALUES
      (nextval('incident_status_sequence'),
      'closed',
      'Incident handling has been terminated.',
      FALSE);
INSERT INTO incident_status
    (id, label,descr,isdefault)
    VALUES
      (nextval('incident_status_sequence'),
      'stalled',
      'Incident handling has stalled.',
      FALSE);
INSERT INTO address_roles
   (id, label, isdefault)
   VALUES
   (0, 'Unknown', false);
INSERT INTO address_roles
   (id, label, isdefault)
   VALUES
   (nextval('address_roles_sequence'), 'Victim', false);
INSERT INTO address_roles (id, label, isdefault)
   VALUES
   (nextval('address_roles_sequence'), 'Target', false);
INSERT INTO urls (id, url, label, createdby, created, menu_position, navbar_position) VALUES (8, 'index.php', 'Main menu', 1, '2005-09-14 09:11:28', NULL, 1);
INSERT INTO urls (id, url, label, createdby, created, menu_position, navbar_position) VALUES (5, 'search.php', 'IP Address lookup', 1, '2005-09-14 08:18:13', 3, NULL);
INSERT INTO urls (id, url, label, createdby, created, menu_position,
navbar_position) VALUES (6, 'mailtemplates.php', 'Mail templates', 1, '2005-09-14 08:18:22', 4, 5);
INSERT INTO urls (id, url, label, createdby, created, menu_position, navbar_position) VALUES (9, 'logout.php', 'Logout', 1, '2005-09-14 09:11:36', 6, 6);
INSERT INTO urls (id, url, label, createdby, created, menu_position, navbar_position) VALUES (4, 'incident.php', 'Incident management', 1, '2005-09-14 08:18:04', 2, NULL);
INSERT INTO urls (id, url, label, createdby, created, menu_position, navbar_position) VALUES (7, 'maintenance.php', 'Edit settings', 1, '2005-09-14 08:18:31', 5, NULL);
INSERT INTO urls (id, url, label, createdby, created, menu_position, navbar_position) VALUES (10, 'incident.php', 'Incidents', 1, '2005-09-14 09:18:30', NULL, 4);
INSERT INTO urls (id, url, label, createdby, created, menu_position, navbar_position) VALUES (11, 'search.php', 'Search', 1, '2005-09-14 09:18:38', NULL, 3);
INSERT INTO urls (id, url, label, createdby, created, menu_position,
navbar_position) VALUES (12, 'importqueue/queue.php', 'Import Queue', 1,
'2005-10-20 13:18:38', NULL, 3);


end transaction;
