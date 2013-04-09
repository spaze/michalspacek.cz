CREATE OR REPLACE VIEW upcoming_training_applications AS
SELECT
	a.id_application AS id,
	a.name AS attendee,
	a.email AS email,
	a.company,
	t.name AS training,
	DATE_FORMAT(d.start,'%e. %c. %Y') AS start,
	v.city,
	s.status AS status,
	DATE_FORMAT(a.status_time,'%e. %c. %Y') AS status_time,
	a.invoice_id AS invoice_id,
	a.price AS price,
	a.paid AS paid,
	a.note,
	a.access_token
FROM
	training_dates d
	JOIN trainings t ON d.key_training = t.id_training
	JOIN training_applications a ON a.key_date = d.id_date
	JOIN training_application_status s ON s.id_status = a.key_status
	JOIN training_venues v ON v.id_venue = d.key_venue
WHERE
	-- this ain't nice but as there can only be one column then we have to use one string
	BINARY CONCAT_WS('/', t.action, v.id_venue, d.start) IN (
		SELECT
			BINARY CONCAT_WS('/', t2.action, d2.key_venue, MIN(d2.start))
		FROM
			trainings t2
			JOIN training_dates d2 ON t2.id_training = d2.key_training
			JOIN training_date_status s2 ON d2.key_status = s2.id_status
		WHERE
			d2.public
			AND t2.action = t.action
			AND d2.end > NOW()
			AND s2.status IN ('TENTATIVE', 'CONFIRMED')
		GROUP BY
			t2.action, d2.key_venue
	)
	AND s.status IN ('CREATED', 'TENTATIVE', 'INVITED', 'SIGNED_UP', 'INVOICE_SENT', 'NOTIFIED', 'IMPORTED')
ORDER BY d.start, t.id_training;
