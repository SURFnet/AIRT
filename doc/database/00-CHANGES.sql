-- $Id$
-- This file contains all changes applied to the database schema since the
-- last official release. The airtschema.sql file should always contain the
-- database schema that results from applying all changes below.
-- When we release, this 00-CHANGES.sql file will be copied to a new file
-- "airtschema-from-prevRel-to-newRel.sql" and emptied.

-- VERSIONS table tracks all relevant versions of the components.
CREATE TABLE versions (
  key   varchar(16) not null,
  value varchar(16) not null,
  primary key (key)
)

-- First release: insert the record. Needs one-time manual tweaking.
INSERT INTO versions (key, value) 
  VALUES ('airtschema','---schemastring---');

-- When table and record has been established (next release):
UPDATE versions SET value='---schemastring---' WHERE key='airtschema';
-- Needs manual update with the AIRT_SCHEMA_VERSION string of the release.
-- Cannot rely on .in expansion as it needs to stay fixed in history.
