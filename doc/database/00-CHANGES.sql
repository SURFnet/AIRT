-- $Id$
-- This file contains all changes applied to the database schema since the
-- last official release. The airtschema.sql file should always contain the
-- database schema that results from applying all changes below.
-- When we release, this 00-CHANGES.sql file will be copied to a new file
-- "airtschema-newRel.sql" and emptied. This should be done
-- for EACH release, even if there are no changes to the database schema,
-- because the VERSIONS table needs to be updated in all casesA

UPDATE versions SET value='----version----' WHERE key='airtversion';
INSERT into settings (key) values ('bubblesize');
ALTER TABLE users ADD notes TEXT;

-- Needs manual update with the AIRT_VERSION string of the release.
-- Cannot rely on .in expansion as it needs to stay fixed in history.

