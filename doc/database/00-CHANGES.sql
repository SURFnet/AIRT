-- $Id$
-- This file contains all changes applied to the database schema since the
-- last official release. The airtschema.sql file should always contain the
-- database schema that results from applying all changes below.
-- When we release, this 00-CHANGES.sql file will be copied to a new file
-- "airtschema-from-prevRel-to-newRel.sql" and emptied.
ALTER TABLE incidents ADD COLUMN logging text;

begin transaction;
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
end transaction;

CREATE TABLE authentication_tickets (
    id integer not null,
    userid varchar(100) not null,
    created timestamp not null,
    ticketid varchar(3000) not null,
    primary key (id),
    foreign key (userid)   references users(id)
);

CREATE SEQUENCE authentication_tickets_sequence;

ALTER TABLE import_queue ADD COLUMN content text not null;

