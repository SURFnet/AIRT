-- $Id$
-- This file contains all changes applied to the database schema since the
-- last official release. The airtschema.sql file should always contain the
-- database schema that results from applying all changes below.
-- When we release, this 00-CHANGES.sql file will be copied to a new file
-- "airtschema-from-prevRel-to-newRel.sql" and emptied.

-- Add NOT NULL constraints to incident_types and enforce UNIQUE.
ALTER TABLE incident_types ALTER COLUMN label SET NOT NULL;
CREATE UNIQUE INDEX incident_types_label ON incident_types(upper(label));
ALTER TABLE incident_types ALTER COLUMN isdefault SET NOT NULL;

-- Add NOT NULL constraints to incident_states and enforce UNIQUE.
ALTER TABLE incident_states ALTER COLUMN label SET NOT NULL;
CREATE UNIQUE INDEX incident_states_label ON incident_states(upper(label));
ALTER TABLE incident_states ALTER COLUMN isdefault SET NOT NULL;

-- Add NOT NULL constraints to incident_status and enforce UNIQUE.
ALTER TABLE incident_status ALTER COLUMN label SET NOT NULL;
CREATE UNIQUE INDEX incident_status_label ON incident_status(upper(label));
ALTER TABLE incident_status ALTER COLUMN isdefault SET NOT NULL;

-- Add NOT NULL constraints to constituencies and enforce UNIQUE.
ALTER TABLE constituencies ALTER COLUMN label SET NOT NULL;
CREATE UNIQUE INDEX constituencies_label ON constituencies(upper(label));

-- Add NOT NULL constraints to roles and enforce UNIQUE.
ALTER TABLE roles ALTER COLUMN label SET NOT NULL;
CREATE UNIQUE INDEX roles_label ON roles(upper(label));

-- Add NOT NULL constraints to users and enforce UNIQUE.
UPDATE users
  SET email = 'airt@example.com'
  WHERE email IS NULL;
ALTER TABLE users ALTER COLUMN email SET NOT NULL;
CREATE UNIQUE INDEX users_email ON users(upper(email));

-- Add NOT NULL constraints to incidents.
ALTER TABLE incidents ALTER COLUMN created SET NOT NULL;
ALTER TABLE incidents ALTER COLUMN creator SET NOT NULL;
ALTER TABLE incidents ALTER COLUMN state SET NOT NULL;
ALTER TABLE incidents ALTER COLUMN status SET NOT NULL;
ALTER TABLE incidents ALTER COLUMN type SET NOT NULL;

-- Add NOT NULL constraints to incident_addresses and add a missing
-- foreign key.
ALTER TABLE incident_addresses ALTER COLUMN incident SET NOT NULL;
ALTER TABLE incident_addresses ALTER COLUMN ip SET NOT NULL;
ALTER TABLE incident_addresses ALTER COLUMN hostname SET NOT NULL;
ALTER TABLE incident_addresses ALTER COLUMN constituency SET NOT NULL;
ALTER TABLE incident_addresses ALTER COLUMN added SET NOT NULL;
ALTER TABLE incident_addresses ALTER COLUMN addedby SET NOT NULL;
ALTER TABLE incident_addresses
  ADD FOREIGN KEY (constituency) REFERENCES constituencies(id);

-- Add NOT NULL constraints to incident_users.
ALTER TABLE incident_users ALTER COLUMN incidentid SET NOT NULL;
ALTER TABLE incident_users ALTER COLUMN userid SET NOT NULL;
ALTER TABLE incident_users ALTER COLUMN added SET NOT NULL;
ALTER TABLE incident_users ALTER COLUMN addedby SET NOT NULL;

-- Add NOT NULL constraints to role_assignments.
ALTER TABLE role_assignments ALTER COLUMN role SET NOT NULL;
ALTER TABLE role_assignments ALTER COLUMN userid SET NOT NULL;

-- Add NOT NULL constraints to constituency_contacts.
ALTER TABLE constituency_contacts ALTER COLUMN constituency SET NOT NULL;
ALTER TABLE constituency_contacts ALTER COLUMN userid SET NOT NULL;

-- Add NOT NULL constraints to networks.
ALTER TABLE networks ALTER COLUMN network SET NOT NULL;
ALTER TABLE networks ALTER COLUMN label SET NOT NULL;
ALTER TABLE networks ALTER COLUMN constituency SET NOT NULL;

-- Add NOT NULL constraints to incident_comments.
ALTER TABLE incident_comments ALTER COLUMN incident SET NOT NULL;
ALTER TABLE incident_comments ALTER COLUMN comment SET NOT NULL;
ALTER TABLE incident_comments ALTER COLUMN added SET NOT NULL;
ALTER TABLE incident_comments ALTER COLUMN addedby SET NOT NULL;

-- Add NOT NULL constraints to user_comments.
ALTER TABLE user_comments ALTER COLUMN userid SET NOT NULL;
ALTER TABLE user_comments ALTER COLUMN comment SET NOT NULL;
ALTER TABLE user_comments ALTER COLUMN added SET NOT NULL;
ALTER TABLE user_comments ALTER COLUMN addedby SET NOT NULL;

-- Add NOT NULL constraints to urls and enforce UNIQUE.
ALTER TABLE urls ALTER COLUMN url SET NOT NULL;
ALTER TABLE urls ALTER COLUMN label SET NOT NULL;
ALTER TABLE urls ALTER COLUMN createdby SET NOT NULL;
ALTER TABLE urls ALTER COLUMN created SET NOT NULL;
CREATE UNIQUE INDEX urls_label ON urls(upper(label));

-- Add NOT NULL constraints to permissions and enforce UNIQUE.
ALTER TABLE permissions ALTER COLUMN label SET NOT NULL;
CREATE UNIQUE INDEX permissions_label ON permissions(upper(label));

-- Add NOT NULL constraints to role_permissions.
ALTER TABLE role_permissions ALTER COLUMN role SET NOT NULL;
ALTER TABLE role_permissions ALTER COLUMN permission SET NOT NULL;

-- Add NOT NULL constraints to blocks.
ALTER TABLE blocks ALTER COLUMN ip SET NOT NULL;
ALTER TABLE blocks ALTER COLUMN lastupdated SET NOT NULL;
ALTER TABLE blocks ALTER COLUMN lastupdatedby SET NOT NULL;
ALTER TABLE blocks ALTER COLUMN incident SET NOT NULL;

-- Add updated[by] fields to incident_addresses.
ALTER TABLE incident_addresses
  ADD COLUMN updated timestamp;
ALTER TABLE incident_addresses
  ADD COLUMN updatedby integer;
ALTER TABLE incident_addresses
  ADD FOREIGN KEY (updatedby) REFERENCES users(id);

-- Add last and hostnamelast to users
ALTER TABLE users ADD COLUMN last timestamp;
ALTER TABLE users ADD COLUMN hostnamelast varchar(128);
