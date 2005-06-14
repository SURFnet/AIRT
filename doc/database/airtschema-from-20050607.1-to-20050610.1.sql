! $Id$
! In CVS at $Source$
! Database bootstrap changes for upgrades 
! from version 20050607.1 to version 20050610.1

update incident_status
  set descr='Incident is being handled.'
  where label='open';
update incident_status
  set descr='Incident handling has been terminated.'
  where label='closed';
update incident_status
  set descr='Incident handling has stalled.'
  where label='stalled';

