ALTER TABLE blocks DROP CONSTRAINT "$2", ADD CONSTRAINT blocks_incident_fk
FOREIGN KEY(incident) references incidents(id) ON DELETE CASCADE;

ALTER TABLE external_incidentids DROP CONSTRAINT "$1", ADD CONSTRAINT
ei_incidentid_fkey FOREIGN KEY(incidentid) references incidents(id) ON DELETE
CASCADE;

ALTER TABLE incident_addresses DROP CONSTRAINT "$1", ADD CONSTRAINT
ia_incident_fk FOREIGN KEY(incident) references incidents(id) ON DELETE
CASCADE;

ALTER TABLE incident_attachments DROP CONSTRAINT
"incident_attachments_incident_fkey", ADD CONSTRAINT ia_incident_fk FOREIGN
KEY(incident) references incidents(id) ON DELETE CASCADE;

ALTER TABLE incident_comments DROP CONSTRAINT "$1", ADD CONSTRAINT
ic_incident_fk FOREIGN KEY(incident) references incidents(id) ON DELETE
CASCADE;

ALTER TABLE incident_mail DROP CONSTRAINT "incident_mail_incidentid_fkey", ADD
CONSTRAINT incident_mail_incidentid_fkey FOREIGN KEY(incidentid) references
incidents(id) ON DELETE CASCADE;

ALTER TABLE incident_users DROP CONSTRAINT "$1", ADD CONSTRAINT iu_incident_fk
FOREIGN KEY(incidentid) references incidents(id) ON DELETE CASCADE;
