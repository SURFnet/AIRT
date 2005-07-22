-- $Id$
-- This file contains all changes applied to the database schema since the
-- last official release. The airtschema.sql file should always contain the
-- database schema that results from applying all changes below.
-- When we release, this 00-CHANGES.sql file will be copied to a new file
-- "airtschema-from-prevRel-to-newRel.sql" and emptied.
begin transaction;
create table address_roles (
   id integer,
   label varchar(50) not null,
   descr varchar(80),
   isdefault boolean not null,
   primary key (id)
);
alter table incident_addresses
   add column addressrole integer;
alter table incident_addresses
   add foreign key (addressrole) references address_roles(id);
create sequence address_roles_sequence;
create unique index address_roles_label on address_roles(upper(label));
insert into address_roles (id, label) 
values
(nextval('address_roles_sequence'), 'Victim');
insert into address_roles (id, label) 
values
(nextval('address_roles_sequence'), 'Target');
end transaction
