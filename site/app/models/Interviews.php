<?php
namespace MichalSpacekCz;

/**
 * Interviews model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Interviews extends BaseModel
{


	public function getAll($limit = null)
	{
		$query = 'SELECT
				action,
				title,
				date,
				href,
				video_href AS videoHref,
				audio_href AS audioHref,
				source_name AS sourceName,
				source_href AS sourceHref
			FROM interviews
			ORDER BY date DESC';

		if ($limit !== null) {
			$this->database->getSupplementalDriver()->applyLimit($query, $limit, null);
		}

		return $this->database->fetchAll($query);
	}


	public function get($name)
	{
		return $this->database->fetch(
			'SELECT
				action,
				title,
				date,
				href,
				video_href AS videoHref,
				audio_href AS audioHref,
				source_name AS sourceName,
				source_href AS sourceHref
			FROM interviews
			WHERE action = ?',
			$name
		);
	}


}