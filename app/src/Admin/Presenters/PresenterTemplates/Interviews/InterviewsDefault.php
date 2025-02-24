<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters\PresenterTemplates\Interviews;

use MichalSpacekCz\Interviews\Interview;

final class InterviewsDefault
{

	/**
	 * @param list<Interview> $interviews
	 */
	public function __construct(
		public string $pageTitle,
		public array $interviews,
	) {
	}

}
