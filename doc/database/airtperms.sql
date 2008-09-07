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
grant select,insert,update,delete on permissions to airt;
grant select,insert,update,delete on role_assignments to airt;
grant select,insert,update,delete on role_permissions to airt;
grant select,insert,update,delete on roles to airt;
grant select,insert,update,delete on urls to airt;
grant select,insert,update,delete on user_comments to airt;
grant select,insert,update,delete on users to airt;
grant select on versions to airt;

grant select,update on address_roles_sequence to airt;
grant select,update on authentication_tickets_sequence to airt;
grant select,update on blocks_sequence to airt;
grant select,update on constituencies_sequence to airt;
grant select,update on constituency_contacts_sequence to airt;
grant select,update on exportqueue_sequence to airt;
grant select,update on importfilter_templates_sequence to airt;
grant select,update on importqueue_sequence to airt;
grant select,update on importqueue_templates_sequence to airt;
grant select,update on incident_addresses_sequence to airt;
grant select,update on incident_comments_sequence to airt;
grant select,update on incident_states_sequence to airt;
grant select,update on incident_status_sequence to airt;
grant select,update on incident_types_sequence to airt;
grant select,update on incident_users_sequence to airt;
grant select,update on incidents_sequence to airt;
grant select,update on ipaddresses_sequence to airt;
grant select,update on networks_sequence to airt;
grant select,update on permissions_sequence to airt;
grant select,update on role_assignments_sequence to airt;
grant select,update on role_permissions_sequence to airt;
grant select,update on roles_sequence to airt;
grant select,update on urls_sequence to airt;
grant select,update on user_comments_sequence to airt;
grant select,update on users_sequence to airt;
grant select,insert,update,delete on incident_attachments to airt;
grant select,update on generic_sequence to airt;
grant select,update,insert,delete on mailtemplate_capabilities to airt;
grant select,update,insert,delete on user_capabilities to airt;
