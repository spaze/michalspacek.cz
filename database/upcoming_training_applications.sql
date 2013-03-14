CREATE OR REPLACE VIEW upcoming_training_applications AS
SELECT
	a.id_application AS id,
	a.name AS attendee,
	a.email AS email,
	t.name AS training,
	DATE_FORMAT(d.start,'%e. %c. %Y') AS start,
	s.status AS application_status,
	DATE_FORMAT(a.status_time,'%e. %c. %Y') AS application_status_time,
	a.invoice_id AS invoice_id,
	a.price AS price,
	a.paid AS paid,
	CONCAT('http://www.michalspacek.cz/skoleni/',
		CASE t.action
			WHEN 'uvodDoPhp' THEN 'uvod-do-php'
			WHEN 'programovaniVPhp5' THEN 'programovani-v-php5'
			WHEN 'bezpecnostPhpAplikaci' THEN 'bezpecnost-php-aplikaci'
			WHEN 'vykonnostWebovychAplikaci' THEN 'vykonnost-webovych-aplikaci'
			ELSE NULL
		END,
		'/prihlaska/',
		a.access_token) AS application_href
FROM
	training_dates d
	JOIN trainings t ON d.key_training = t.id_training
	JOIN training_applications a ON a.key_date = d.id_date
	JOIN training_application_status s ON s.id_status = a.key_status
	JOIN training_venues v ON v.id_venue = d.key_venue
WHERE
	d.start = (
		SELECT
			MIN(d2.start)
		FROM
			training_dates d2
		WHERE
			d2.end > NOW()
			AND d.key_training = d2.key_training
			AND d2.public
		GROUP BY d2.key_training
	)
	AND s.status IN ('CREATED', 'TENTATIVE', 'INVITED', 'SIGNED_UP', 'INVOICE_SENT', 'NOTIFIED', 'IMPORTED')
ORDER BY d.start, t.id_training;
