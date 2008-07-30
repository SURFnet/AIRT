-- $Id: 00-CHANGES.sql 1258 2008-05-29 00:59:48Z kees $
-- This file contains all changes applied to the database schema since the
-- last official release. The airtschema.sql file should always contain the
-- database schema that results from applying all changes below.
-- When we release, this 00-CHANGES.sql file will be copied to a new file
-- "airtschema-newRel.sql" and emptied. This should be done
-- for EACH release, even if there are no changes to the database schema,
-- because the VERSIONS table needs to be updated in all cases.
create table incident_attachments (
   id integer,
   content_type text not null,
   content_body bytea not null,
   incident integer,
   filename text,
   primary key (id),
   foreign key (incident) references incidents(id)
);
grant select,insert,update,delete on incident_attachments to airt;
create sequence generic_sequence;
grant select,update on generic_sequence to airt;


UPDATE versions SET value='20080730.1' WHERE key='airtversion';
-- Needs manual update with the AIRT_VERSION string of the release.
-- Cannot rely on .in expansion as it needs to stay fixed in history.
