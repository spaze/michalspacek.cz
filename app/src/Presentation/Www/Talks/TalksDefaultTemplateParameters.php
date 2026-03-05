<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\Talks;

use MichalSpacekCz\Talks\Talk;
use Nette\Utils\Html;

final readonly class TalksDefaultTemplateParameters
{

	/**
	 * @param list<Html> $favoriteTalks
	 * @param list<Talk> $upcomingTalks
	 * @param array<int, non-empty-list<Talk>> $talks
	 */
	public function __construct(
		public string $pageTitle,
		public array $favoriteTalks,
		public array $upcomingTalks,
		public array $talks,
	) {
	}

}
