<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Pulse\PasswordsStorages;

use MichalSpacekCz\Pulse\Passwords\Storage\StorageRegistry;

final class PasswordsStoragesDefaultTemplateParameters
{

	/**
	 * @param array<string, string> $ratingGuide
	 */
	public function __construct(
		public bool $isDetail,
		public string $pageTitle,
		public StorageRegistry $data,
		public array $ratingGuide,
		public bool $openSearchSort,
		public ?string $canonicalLink,
	) {
	}

}
