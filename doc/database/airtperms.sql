-- $Id$
-- $URL$
-- when using airt_dba to create the tables, but airt to connect,
-- run the following script after creating the tables

grant select,insert,update,delete on address_roles to airt;
grant select,insert,update,delete on authentication_tickets to airt;
grant select,insert,update,delete on constituencies to airt;
grant select,insert,update,delete on constituency_contacts to airt;
grant select,insert,update,delete on export_queue to airt;
grant select,insert,update,delete on external_incidentids to airt;
grant select,insert,update,delete on import_queue to airt;
grant select,insert,update,delete on importqueue_templates to airt;
grant select,insert,update,delete on incident_addresses to airt;
grant select,insert,update,delete on incident_comments to airt;
grant select,insert,update,delete on incident_states to airt;
grant select,insert,update,delete on incident_status to airt;
grant select,insert,update,delete on incident_types to airt;
grant select,insert,update,delete on incident_users to airt;
grant select,insert,update,delete on incidents to airt;
grant select,insert,update,delete on mailtemplates to airt;
grant select,insert,update,delete on networks to airt;
grant select,insert,update,delete on permission to airt;
grant select,insert,update,delete on role_assignments to airt;
grant select,insert,update,delete on role_permissions to airt;
grant select,insert,update,delete on roles to airt;
grant select,insert,update,delete on urls to airt;
grant select,insert,update,delete on user_comments to airt;
grant select,insert,update,delete on users to airt;

