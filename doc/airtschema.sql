
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
-- In CVS at $CVS$


DROP SEQUENCE incident_types_sequence;
DROP SEQUENCE incident_states_sequence;
DROP SEQUENCE incident_status_sequence;
DROP SEQUENCE constituencies_sequence;
DROP SEQUENCE roles_sequence;
DROP SEQUENCE users_sequence;
DROP SEQUENCE incidents_sequence;
DROP SEQUENCE ipaddresses_sequence;
DROP SEQUENCE incident_addresses_sequence;
DROP SEQUENCE role_assignments_sequence;
DROP SEQUENCE constituency_contacts_sequence;
DROP SEQUENCE networks_sequence;
DROP SEQUENCE credentials_sequence;
DROP SEQUENCE incident_comments_sequence;
DROP SEQUENCE ip_comments_sequence;
DROP SEQUENCE user_comments_sequence;
DROP SEQUENCE urls_sequence;

DROP TABLE incident_types CASCADE; 
DROP TABLE incident_states CASCADE;
DROP TABLE incident_status CASCADE;
DROP TABLE constituencies CASCADE;
DROP TABLE roles CASCADE;
DROP TABLE users CASCADE;
DROP TABLE incidents CASCADE;
DROP TABLE ipaddresses CASCADE;
DROP TABLE incident_addresses CASCADE;
DROP TABLE role_assignments CASCADE;
DROP TABLE constituency_contacts CASCADE;
DROP TABLE networks CASCADE;
DROP TABLE credentials CASCADE;
DROP TABLE incident_comments CASCADE; 
DROP TABLE ip_comments CASCADE; 
DROP TABLE user_comments CASCADE; 
DROP TABLE urls CASCADE;

begin transaction;

CREATE TABLE incident_types ( 
    id          integer,
    label       varchar(50),
    primary key (id)
);

CREATE TABLE incident_states (
    id          integer,
    label       varchar(50),
    primary key (id)
);

CREATE TABLE incident_status (
    id          integer,
    label       varchar(50),
    primary key (id)
);

CREATE TABLE constituencies (
    id          integer,
    label       varchar(50),
    name        varchar(100),
    primary key (id)
);

CREATE TABLE roles (
    id          integer,
    label       varchar(50),
    primary key (id)
);

CREATE TABLE users (
    id          integer,
    lastname    varchar(100),
    firstname   varchar(100),
    email       varchar(100),
    phone       varchar(100),
    primary key (id)
);

CREATE TABLE incidents (
    id          integer,
    created     timestamp,
    creator     integer,
    updated     timestamp,
    updatedby   integer,
    state       integer,
    status      integer,
    type        integer,
    primary key (id),
    foreign key (creator)   references users(id),
    foreign key (updatedby) references users(id),
    foreign key (state)     references incident_states(id),
    foreign key (status)    references incident_status(id),
    foreign key (type)      references incident_types(id)
);

CREATE TABLE ipaddresses (
    id          integer,
    address     varchar(128),
    hostname    varchar(128),
    constituency integer,
    client      integer,
    primary key (id),
    foreign key (constituency) references constituencies(id),
    foreign key (client)       references users(id)
);


CREATE TABLE incident_addresses (
    id          integer,
    incident    integer,
    address     integer,
    added       timestamp,
    addedby     integer,
    primary key (id),
    foreign key (incident) references incidents(id),
    foreign key (address)  references ipaddresses(id),
    foreign key (addedby)  references users(id)
);

CREATE TABLE role_assignments (
    id          integer,
    role        integer,
    userid      integer,
    primary key (id),
    foreign key (role) references roles(id),
    foreign key (userid) references users(id)
);

CREATE TABLE constituency_contacts (
    id           integer,
    constituency integer,
    userid       integer,
    primary key (id),
    foreign key (constituency) references constituencies(id),
    foreign key (userid)       references users(id)
);

CREATE TABLE networks (
    id          integer,
    network     varchar(128),
    netmask     varchar(128),
    constituency integer,
    primary key (id),
    foreign key (constituency) references constituencies(id)
);

CREATE TABLE credentials (
    id          integer,
    userid      integer,
    login       varchar(64),
    password    varchar(64),
    ou          varchar(64),
    ca          varchar(64),
    primary key (id),
    foreign key (userid) references users(id)
);

CREATE TABLE incident_comments ( 
    id          integer,
    incident    integer,
    comment     varchar(240),
    added       timestamp,
    addedby     integer,
    primary key (id),
    foreign key (incident) references incidents(id),
    foreign key (addedby) references users(id)
);

CREATE TABLE ip_comments ( 
    id          integer,
    address     integer,
    comment     varchar(240),
    added       timestamp,
    addedby     integer,
    primary key (id),
    foreign key (address) references ipaddresses(id),
    foreign key (addedby) references users(id)
);

CREATE TABLE user_comments ( 
    id          integer,
    userid      integer,
    comment     varchar(240),
    added       timestamp,
    addedby     integer,
    primary key (id),
    foreign key (userid) references users(id),
    foreign key (addedby) references users(id)
);

CREATE TABLE urls (
    id          integer,
    url         varchar(255),
    label       varchar(255),
    createdby   integer,
    created     timestamp,
    primary key (id),
    foreign key (createdby) references users(id)
);


CREATE SEQUENCE incident_types_sequence;
CREATE SEQUENCE incident_states_sequence;
CREATE SEQUENCE incident_status_sequence;
CREATE SEQUENCE constituencies_sequence;
CREATE SEQUENCE roles_sequence;
CREATE SEQUENCE users_sequence;
CREATE SEQUENCE incidents_sequence;
CREATE SEQUENCE ipaddresses_sequence;
CREATE SEQUENCE incident_addresses_sequence;
CREATE SEQUENCE role_assignments_sequence;
CREATE SEQUENCE constituency_contacts_sequence;
CREATE SEQUENCE networks_sequence;
CREATE SEQUENCE credentials_sequence;
CREATE SEQUENCE incident_comments_sequence;
CREATE SEQUENCE ip_comments_sequence;
CREATE SEQUENCE user_comments_sequence;
CREATE SEQUENCE urls_sequence;

end transaction
