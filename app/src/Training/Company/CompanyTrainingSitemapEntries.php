<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Company;

use MichalSpacekCz\Sitemap\SitemapEntries;
use MichalSpacekCz\Sitemap\SitemapEntry;
use Nette\Database\Explorer;
use Override;

/**
 * Every company training that isn't discontinued or replaced by another, in every language it has, a training and
 * its translations as one entry.
 */
final readonly class CompanyTrainingSitemapEntries implements SitemapEntries
{

	public function __construct(
		private Explorer $database,
	) {
	}


	#[Override]
	public function getEntries(): array
	{
		$rows = $this->database->fetchAll(
			'SELECT
				t.id_training AS id,
				l.language AS locale,
				a.action
			FROM trainings t
				JOIN trainings_company tc ON t.id_training = tc.key_training
				JOIN training_url_actions ta ON t.id_training = ta.key_training
				JOIN url_actions a ON ta.key_url_action = a.id_url_action
				JOIN languages l ON a.key_language = l.id_language
			WHERE
				t.key_successor IS NULL
				AND t.key_discontinued IS NULL
			ORDER BY
				t.order IS NULL, t.order',
		);
		$params = [];
		foreach ($rows as $row) {
			assert(is_int($row->id));
			assert(is_string($row->locale));
			assert(is_string($row->action));
			$params[$row->id][$row->locale] = ['name' => $row->action];
		}
		return array_values(array_map(fn(array $localeParams): SitemapEntry => new SitemapEntry($localeParams), $params));
	}

}
