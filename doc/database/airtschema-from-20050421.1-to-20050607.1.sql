! $Id$
! In CVS at $Source$
! Database structure changes for upgrades 
! from version 20050421.1 to version 20050607.1
alter table incident_types add column descr varchar(80);
alter table incident_states add column descr varchar(80);
alter table incident_status add column descr varchar(80);

alter table incident_types add column isdefault boolean;
alter table incident_states add column isdefault boolean;
alter table incident_status add column isdefault boolean;

update incident_types set isdefault=false;
update incident_status set isdefault=false;
update incident_states set isdefault=false;
