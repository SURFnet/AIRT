-- $Id: 00-CHANGES.sql 1481 2009-07-14 13:58:09Z kees $
-- This file contains all changes applied to the database schema since the
-- last official release. The airtschema.sql file should always contain the
-- database schema that results from applying all changes below.
-- When we release, this 00-CHANGES.sql file will be copied to a new file
-- "airtschema-newRel.sql" and emptied. This should be done
-- for EACH release, even if there are no changes to the database schema,
-- because the VERSIONS table needs to be updated in all casesA
alter table mailtemplates add status varchar(16);
insert into settings values ('defaultlanguage', 'en_US.utf8');
insert into settings values ('instancename', 'AIRT development instance');
insert into settings values ('mailfrom', 'AIRT developers @YOURFIRSTNAME@');
insert into settings values ('mailenvfrom', 'info@leune.com');
insert into settings values ('mailcc', NULL);
insert into settings values ('replyto', NULL);
insert into settings values ('pagesize', '50');
insert into settings values ('session_timeout', '3600');
insert into settings values ('archiveage', '432000');
insert into settings values ('correlationage', '172800');
insert into settings values ('x509client', '1');


UPDATE versions SET value='20090718.1' WHERE key='airtversion';
-- Needs manual update with the AIRT_VERSION string of the release.
-- Cannot rely on .in expansion as it needs to stay fixed in history.
