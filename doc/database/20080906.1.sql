-- $Id: 00-CHANGES.sql 1269 2008-09-01 00:06:08Z kees $
-- This file contains all changes applied to the database schema since the
-- last official release. The airtschema.sql file should always contain the
-- database schema that results from applying all changes below.
-- When we release, this 00-CHANGES.sql file will be copied to a new file
-- "airtschema-newRel.sql" and emptied. This should be done
-- for EACH release, even if there are no changes to the database schema,
-- because the VERSIONS table needs to be updated in all cases.
create table user_capabilities (
   id        integer not null,
   userid    integer not null,
   captype   varchar not null,
   capvalue  integer not null, -- do not use boolean here for cross-db compat
   primary key (id),
   foreign key (userid) references users(id)
);

create table mailtemplate_capabilities (
   id        integer not null,
   template  varchar not null,
   captype   varchar not null,
   capvalue  integer not null, -- do not use boolean here for cross-db compat
   primary key (id),
   foreign key (template) references mailtemplates(name)
);

alter table incident_users
add column mailtemplate_override char(80);
alter table incident_users
add foreign key (mailtemplate_override) foreign key mailtemplates(name);

UPDATE versions SET value='20080906.1' WHERE key='airtversion';
-- Needs manual update with the AIRT_VERSION string of the release.
-- Cannot rely on .in expansion as it needs to stay fixed in history.
