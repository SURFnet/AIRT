/*
 * LIBERTY: INCIDENT RESPONSE SUPPORT FOR END-USERS
 * Copyright (C) 2004	Kees Leune <kees@uvt.nl>

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
 * libertyschema.sql - Additional tables
 */

drop table URLs;
drop table constituencies;
drop table incidents;

create table constituencies
(
    id            integer,
    name          varchar(30),
    description   varchar(255),
    contact_email varchar(100),
    contact_name  varchar(100),
    contact_phone varchar(100),
    created     timestamp,
    createdby   integer,

    primary key (id),
);

create table URLs
(
    id          integer,
    url         varchar(300),
    description varchar(300),
    created     timestamp,
    createdby   integer,

    primary key (id)
);

create table incidents 
(
    id                  varchar(30),
    ip                  varchar(30),
    status              varchar(30),
    state               varchar(30), 
    category            varchar(30),
    user_name           varchar(30),
    user_email          varchar(30),
    constituency        integer,
    rtid                integer,
    created             timestamp,
    creator             integer,
    closed              timestamp,
    lastupdatedby       integer,
    lastupdated         timestamp,

    primary key (id),
    foreign key (constituency) references constituencies (id),
);

create sequence constituencies_seq;
create sequence incidentid_seq;
create sequence URLs_seq;

create index incidents_ip_index on incidents (ip);
create index incidents_status_index on incidents (status);
create index incidents_constituency on incidents (constituency);
