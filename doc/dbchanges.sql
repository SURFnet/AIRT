! $Id$
! This file contains all changed applied to the database schema since the
! last official release. The airtschema.sql file should always contain the
! database schema that results from applying all changes below.
! When we release, this dbchanges.sql file will be copied to a new file
! "from-prevRel-to-newRel.sql" and emptied.

alter table incident_types add column descr varchar(80);
alter table incident_states add column descr varchar(80);
alter table incident_status add column descr varchar(80);

update incident_types
  set descr='Incident is being handled.'
  where label='open';
update incident_types
  set descr='Incident handling has been terminated.'
  where label='closed';
update incident_types
  set descr='Incident handling has stalled.'
  where label='stalled';

alter table incident_types add column isdefault boolean;
alter table incident_states add column isdefault boolean;
alter table incident_status add column isdefault boolean;

update incident_types set isdefault=false;
update incident_status set isdefault=false;
update incident_states set isdefault=false;
