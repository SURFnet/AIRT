-- $Id: 00-CHANGES.sql 1381 2008-12-05 19:11:07Z kees $
-- This file contains all changes applied to the database schema since the
-- last official release. The airtschema.sql file should always contain the
-- database schema that results from applying all changes below.
-- When we release, this 00-CHANGES.sql file will be copied to a new file
-- "airtschema-newRel.sql" and emptied. This should be done
-- for EACH release, even if there are no changes to the database schema,
-- because the VERSIONS table needs to be updated in all casesA

INSERT INTO settings (key, value) VALUES ('archiveage', '20');
UPDATE versions SET value='20081219.1' WHERE key='airtversion';

