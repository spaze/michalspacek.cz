<?php
namespace MichalSpacekCz;

/**
 * Training applications model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TrainingApplications extends BaseModel
{


	public function getByStatus($status)
	{
		return $this->database->fetchAll(
			'SELECT
				a.name,
				a.email,
				t.name AS trainingName,
				t.action AS trainingAction,
				a.access_token AS accessToken
			FROM
				training_applications a
				JOIN training_dates d ON a.key_date = d.id_date
				JOIN trainings t ON d.key_training = t.id_training
				JOIN training_application_status s ON a.key_status = s.id_status
			WHERE
				s.status = ?',
			$status
		);
	}


	public function getByDate($dateId)
	{
		return $this->database->fetchAll(
			'SELECT
				a.id_application AS id,
				a.name,
				a.email,
				s.status
			FROM
				training_applications a
				JOIN training_application_status s ON a.key_status = s.id_status
			WHERE
				key_date = ?',
			$dateId
		);
	}


}
