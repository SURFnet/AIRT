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
    (id, lastname, login, password) 
    VALUES
    (nextval('users_sequence'), 'Administrator', 'admin', 'admin');
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
INSERT INTO constituencies
		(id,label,name) 
		VALUES
		(-1, 'default', 'default');
INSERT INTO networks
		(id, network, netmask, label, constituency) 
		VALUES
		( -1, '0.0.0.0', '0.0.0.0', 'Default network', -1);
INSERT INTO incident_status
		(id, label)
		VALUES
		(1, 'open');
INSERT INTO incident_status
		(id, label)
		VALUES
		(2, 'closed');
INSERT INTO incident_status
		(id, label)
		VALUES
		(3, 'stalled');

end transaction;
