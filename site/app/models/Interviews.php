<?php
namespace MichalSpacekCz;

/**
 * Interviews model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Interviews
{

	/** @var \Nette\Database\Connection */
	protected $database;

	/** @var \Bare\Formatter\Texy */
	protected $texyFormatter;


	public function __construct(\Nette\Database\Connection $connection, \Bare\Next\Formatter\Texy $texyFormatter)
	{
		$this->database = $connection;
		$this->texyFormatter = $texyFormatter;
	}


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
		$result = $this->database->fetch(
			'SELECT
				action,
				title,
				description,
				date,
				href,
				audio_href AS audioHref,
				video_href AS videoHref,
				video_embed AS videoEmbed,
				source_name AS sourceName,
				source_href AS sourceHref
			FROM interviews
			WHERE action = ?',
			$name
		);

		if ($result) {
			$result['description'] = $this->texyFormatter->format($result['description']);
		}

		return $result;
	}


}