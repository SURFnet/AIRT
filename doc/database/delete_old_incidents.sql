/**
	Run this script in a cronjob/scheduled task or by hand to clean up
	old incidents from the database.
	
	It will	delete any incidents, and related data, which are not "open"
	and have last been updated over 2 years ago, from the tables
	`incidents`, `incidents_`*, `blocks` and `mailbox`.

	Additionally it will clean from the `import_queue` any item that
	has is "open" and is older than six months.

	It does not clean any other tables, such as users, constituents
	or networks.

	You need to have database migration 20180720.1.schema.sql applied.
 */

SELECT 'import_queue' AS table, status, count(*) FROM import_queue GROUP BY status;
DELETE FROM import_queue
	WHERE status != 'open' AND updated < NOW() - INTERVAL '6 months';
SELECT 'import_queue' AS table, status, count(*) FROM import_queue GROUP BY status;

SELECT 'incidents' AS table, count(*) as total, min(id) as oldest, max(id) as newest FROM incidents;
DELETE FROM incidents
	WHERE status != 1 AND updated < NOW() - INTERVAL '2 years';
SELECT 'incidents' AS table, count(*) as total, min(id) as oldest, max(id) as newest FROM incidents;

SELECT 'mailbox' AS table, count(*) as total, to_timestamp(min(date)) as oldest, to_timestamp(max(date)) as newest FROM mailbox;

DELETE FROM mailbox
  USING public.mailbox as mail
    LEFT OUTER JOIN public.incident_mail AS incident_mail ON mail.id = incident_mail.messageid
    WHERE public.mailbox.id = mail.id
      AND CAST(mail.date AS BIGINT) < (CAST(EXTRACT(epoch FROM NOW()) AS BIGINT) - 63113852)
      AND incident_mail.id IS NULL;


SELECT 'mailbox' AS table, count(*) as total, to_timestamp(min(date)) as oldest, to_timestamp(max(date)) as newest FROM mailbox;
