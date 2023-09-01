<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Presenters\PresenterTemplates\PasswordsStorages;

use MichalSpacekCz\Pulse\Passwords\StorageRegistry;

class PasswordsStoragesDefaultTemplate
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
