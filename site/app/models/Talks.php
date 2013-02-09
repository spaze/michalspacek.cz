<?php
namespace MichalSpacekCz;

/**
 * Talks model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Talks extends BaseModel
{


	public function getAll($limit = null)
	{
		$query = 'SELECT
				title,
				date,
				href,
				slides_href AS slidesHref,
				video_href AS videoHref,
				event,
				event_href AS eventHref
			FROM talks
			ORDER BY date DESC';

		if ($limit !== null) {
			$this->database->getSupplementalDriver()->applyLimit($query, $limit, null);
		}

		return $this->database->fetchAll($query);
	}


}