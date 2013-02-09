DROP PROCEDURE IF EXISTS set_training_application_status;

DELIMITER $$
CREATE PROCEDURE set_training_application_status (
	IN id_application INTEGER,
	IN new_status TEXT,
	IN new_status_time DATETIME,
	IN new_status_time_timezone TEXT,
	OUT status INTEGER,
	OUT status_text TEXT,
	OUT old_id_status INTEGER,
	OUT old_status TEXT,
	OUT old_status_time DATETIME,
	OUT old_status_time_timezone TEXT
)
BEGIN
	DECLARE _new_id_status INT;
	SELECT s.id_status FROM training_application_status s WHERE s.status = new_status INTO _new_id_status;

	SELECT
		a.key_status,
		s.status,
		a.status_time,
		a.status_time_timezone
	FROM
		training_applications a
		JOIN training_application_status s ON a.key_status = s.id_status
	WHERE
		a.id_application = id_application
	INTO
		old_id_status,
		old_status,
		old_status_time,
		old_status_time_timezone;

	UPDATE
		training_applications a
	SET
		a.key_status = _new_id_status,
		a.status_time = new_status_time,
		a.status_time_timezone = new_status_time_timezone
	WHERE
		a.id_application = id_application;

	INSERT INTO
		training_application_status_history (
			key_application,
			key_status,
			status_time,
			status_time_timezone
		)
	VALUES (
		id_application,
		old_id_status,
		old_status_time,
		old_status_time_timezone
	);

	SET status = 200;
	SET status_text = 'OK';
END;
$$
DELIMITER ;
