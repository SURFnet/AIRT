-- $Id$
-- This file contains all changes applied to the database schema since the
-- last official release. The airtschema.sql file should always contain the
-- database schema that results from applying all changes below.
-- When we release, this 00-CHANGES.sql file will be copied to a new file
-- "airtschema-from-prevRel-to-newRel.sql" and emptied.
CREATE TABLE import_queue (
  id        integer,
  created   timestamp    not null,
  status    varchar(16)  not null default 'open',
  sender    varchar(50)  not null,
  type      varchar(50)  not null, 
  summary   varchar(100) not null, 
  primary key (id)
);
CREATE SEQUENCE importqueue_sequence;
ALTER TABLE urls ADD COLUMN navbar_position INTEGER;
ALTER TABLE urls ADD COLUMN menu_position INTEGER;
INSERT INTO urls (id, url, label, createdby, created, menu_position, navbar_position) VALUES (8, 'index.php', 'Main menu', 1, '2005-09-14 09:11:28', NULL, 1);
INSERT INTO urls (id, url, label, createdby, created, menu_position, navbar_position) VALUES (5, 'search.php', 'IP Address lookup', 1, '2005-09-14 08:18:13', 3, NULL);
INSERT INTO urls (id, url, label, createdby, created, menu_position, navbar_position) VALUES (6, 'standard.php', 'Mail templates', 1, '2005-09-14 08:18:22', 4, 5);
INSERT INTO urls (id, url, label, createdby, created, menu_position, navbar_position) VALUES (9, 'logout.php', 'Logout', 1, '2005-09-14 09:11:36', 6, 6);
INSERT INTO urls (id, url, label, createdby, created, menu_position, navbar_position) VALUES (4, 'incident.php', 'Incident management', 1, '2005-09-14 08:18:04', 2, NULL);
INSERT INTO urls (id, url, label, createdby, created, menu_position, navbar_position) VALUES (7, 'maintenance.php', 'Edit settings', 1, '2005-09-14 08:18:31', 5, NULL);
INSERT INTO urls (id, url, label, createdby, created, menu_position, navbar_position) VALUES (10, 'incident.php', 'Incidents', 1, '2005-09-14 09:18:30', NULL, 4);
INSERT INTO urls (id, url, label, createdby, created, menu_position, navbar_position) VALUES (11, 'search.php', 'Search', 1, '2005-09-14 09:18:38', NULL, 3);

