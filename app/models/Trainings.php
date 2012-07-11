<?php
namespace MichalSpacekCz;

/**
 * Trainings model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Trainings extends BaseModel
{


	public function getUpcoming()
	{
		return $this->database->fetchAll('SELECT
				t.action,
				t.name,
				d.tentative,
				d.start
			FROM training_dates d
				JOIN trainings t ON d.key_training = t.id_training
			WHERE
				d.end > NOW()
			ORDER BY t.id_training'
		);
	}


	public function get($name)
	{
		return $this->database->fetch('SELECT
			t.action,
			d.id_date AS dateId,
			t.name,
			t.description,
			t.content,
			t.prerequisites,
			t.audience,
			d.start,
			d.end,
			d.tentative,
			t.original_href AS originalHref,
			t.capacity,
			t.services,
			t.price,
			t.student_discount AS studentDiscount,
			t.materials,
			v.href AS venueHref,
			v.name AS venueName,
			v.address AS venueAddress,
			v.description AS venueDescription
			FROM training_dates d
				JOIN trainings t ON d.key_training = t.id_training
				JOIN training_venues v ON d.key_venue = v.id_venue
			WHERE t.action = ?
				AND d.end > NOW()
			LIMIT 1',
			$name
		);
	}


	public function getPastTrainings($name)
	{
		return $this->database->fetchPairs('SELECT
			d.id_date AS dateId,
			d.start
			FROM training_dates d
				JOIN trainings t ON d.key_training = t.id_training
			WHERE t.action = ?
				AND d.end < NOW()
			ORDER BY
				start DESC',
			$name
		);
	}


}