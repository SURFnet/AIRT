
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
    foreign key (state)     references ticket_states(id),
    foreign key (status)    references incident_status(id),
    foreign key (type)      references incident_types(id)
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
    user        integer,
    primary key (id),
    foreign key (role) references roles(id),
    foreign key (user) references users(id)
);

CREATE TABLE ipaddresses (
    id          integer,
    address     varchar(128),
    hostname    varchar(128),
    constituency integer,
    client      integer,
    primary key (id)
    foreign key (constituency) references constituencies(id),
    foreign key (client)       references users(id)
);

CREATE TABLE constituency_contacts (
    id          integer,
    constituency integer,
    user        integer,
    primary key (id),
    foreign key (constituency) references constituencies(id),
    foreign key (user)         references users(id)
);

CREATE TABLE networks (
    id          integer,
    network     varchar(128),
    netmask     varchar(128)
    constituency integer,
    primary key (id),
    foreign key (constituency) references constituencies(id)
);

CREATE TABLE credentials (
    id          integer,
    user        integer,
    login       varchar(64),
    password    varchar(64),
    ou          varchar(64),
    ca          varchar(64),
    primary key (id),
    foreign key (user) references users(id)
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
    foreign key (address) references ipaddress(id),
    foreign key (addedby) references users(id)
);

CREATE TABLE user_comments ( 
    id          integer,
    user        integer,
    comment     varchar(240),
    added       timestamp,
    addedby     integer,
    primary key (id),
    foreign key (address) references users(id),
    foreign key (addedby) references users(id)
);

-- EOF --
