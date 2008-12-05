-- $Id$
-- This file contains all changes applied to the database schema since the
-- last official release. The airtschema.sql file should always contain the
-- database schema that results from applying all changes below.
-- When we release, this 00-CHANGES.sql file will be copied to a new file
-- "airtschema-newRel.sql" and emptied. This should be done
-- for EACH release, even if there are no changes to the database schema,
-- because the VERSIONS table needs to be updated in all casesA
CREATE TABLE incident_mail (
    id integer,
    messageid integer,
    incidentid integer,
    PRIMARY KEY (id),
    FOREIGN KEY (messageid) REFERENCES mailbox(id),
    FOREIGN KEY (incidentid) REFERENCES incidents(id)
);
ALTER TABLE mailbox ADD COLUMN raw varchar;
ALTER TABLE users ADD COLUMN x509name VARCHAR;

CREATE TABLE settings (
   key varchar,
   value varchar,
   PRIMARY KEY (key)
);

INSERT INTO settings (key, value) VALUES ('archiveage', '20');

GRANT ALL ON incident_mail TO airt;
GRANT ALL ON settings TO airt;

UPDATE versions SET value='----version----' WHERE key='airtversion';
-- Needs manual update with the AIRT_VERSION string of the release.
-- Cannot rely on .in expansion as it needs to stay fixed in history.
