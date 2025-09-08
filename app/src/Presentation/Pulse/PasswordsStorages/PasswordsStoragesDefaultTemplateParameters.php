<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Pulse\PasswordsStorages;

use MichalSpacekCz\Pulse\Passwords\Storage\StorageRegistry;

final class PasswordsStoragesDefaultTemplateParameters
{

	public bool $isDetail = false;

	public ?string $pageTitle = null;

	public ?StorageRegistry $data = null;

	/** @var array<string, string> $ratingGuide */
	public array $ratingGuide = [];

	public bool $openSearchSort = false;

	public ?string $canonicalLink = null;

}
