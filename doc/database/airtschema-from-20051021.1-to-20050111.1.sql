-- $Id$
-- This file contains all changes applied to the database schema since the
-- last official release. The airtschema.sql file should always contain the
-- database schema that results from applying all changes below.
-- When we release, this 00-CHANGES.sql file will be copied to a new file
-- "airtschema-from-prevRel-to-newRel.sql" and emptied. This should be done
-- for EACH release, even if there are no changes to the database schema,
-- because the VERSIONS table needs to be updated in all cases.

-- VERSIONS table tracks all relevant versions of the components.
CREATE TABLE versions (
   key   varchar(16) not null,
   value varchar(16) not null,
   primary key (key)
);

-- MAILTEMPLATES table contains, eh, the, eh, mail templates.
CREATE TABLE mailtemplates (
   name      varchar(80) not null,
   body      text        not null,
   createdby integer     not null,
   created   timestamp   not null,
   updatedby integer,
   updated   timestamp,
   primary key (name),
   foreign key (createdby) references users(id),
   foreign key (updatedby) references users(id)
);

-- First release: insert the version record. Needs one-time manual tweaking.
INSERT INTO versions (key, value) VALUES ('airtversion','20051101.1');

-- When table and record has been established (next release):
UPDATE versions SET value='20051101.1' WHERE key='airtversion';
-- Needs manual update with the AIRT_VERSION string of the release.
-- Cannot rely on .in expansion as it needs to stay fixed in history.

ALTER TABLE authentication_tickets ADD expiration timestamp;

ALTER table authentication_tickets ALTER COLUMN expiration SET NOT NULL;

-- export queue does not need to be initialised, just created.
CREATE TABLE export_queue (
  id        integer,
  task      varchar(32)   not null,
  params    varchar(256),
  created   timestamp     not null,
  scheduled timestamp,
  started   timestamp,
  ended     timestamp,
  result    varchar(256),
  primary key (id)
);
CREATE SEQUENCE exportqueue_sequence;

