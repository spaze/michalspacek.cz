<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Interviews;

use MichalSpacekCz\Interviews\Interview;

final class DefaultTemplateParameters
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
