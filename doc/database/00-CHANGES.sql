-- $Id$
-- This file contains all changes applied to the database schema since the
-- last official release. The airtschema.sql file should always contain the
-- database schema that results from applying all changes below.
-- When we release, this 00-CHANGES.sql file will be copied to a new file
-- "airtschema-newRel.sql" and emptied. This should be done
-- for EACH release, even if there are no changes to the database schema,
-- because the VERSIONS table needs to be updated in all cases.
ALTER TABLE import_queue ADD COLUMN cache_mailtemplate VARCHAR(80);
ALTER TABLE import_queue ADD COLUMN filter VARCHAR(80);
ALTER TABLE import_queue ADD COLUMN version VARCHAR(80);

UPDATE versions SET value='----version string----' WHERE key='airtversion';
-- Needs manual update with the AIRT_VERSION string of the release.
-- Cannot rely on .in expansion as it needs to stay fixed in history.
