-- $Id$
-- This file contains all changes applied to the database schema since the
-- last official release. The airtschema.sql file should always contain the
-- database schema that results from applying all changes below.
-- When we release, this 00-CHANGES.sql file will be copied to a new file
-- "airtschema-newRel.sql" and emptied. This should be done
-- for EACH release, even if there are no changes to the database schema,
-- because the VERSIONS table needs to be updated in all casesA
CREATE TABLE mailbox (
    id    integer,      -- pull from generic_sequence
    messageid varchar,  -- Message-Id header
    sender    varchar,  -- From header (not envelope from)
    recipient varchar,  -- To header
    date      numeric,
    subject   varchar,  -- Subject header
    body      varchar,  -- Everything not header (no mime parsing yet)
    status    varchar,
    PRIMARY KEY (id)
);

ALTER TABLE import_queue 
   ADD COLUMN metatype VARCHAR;
UPDATE import_queue 
   SET metatype='incident' 
   WHERE metatype IS NULL;


UPDATE versions SET value='----version----' WHERE key='airtversion';
-- Needs manual update with the AIRT_VERSION string of the release.
-- Cannot rely on .in expansion as it needs to stay fixed in history.
