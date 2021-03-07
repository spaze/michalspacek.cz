<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Presenters\PasswordsStoragesTemplates;

use MichalSpacekCz\Pulse\Passwords\StorageRegistry;
use Nette\Bridges\ApplicationLatte\Template;

class PasswordsStoragesDefaultTemplate extends Template
{

	public bool $isDetail;

	public string $pageTitle;

	public StorageRegistry $data;

	/** @var string[] */
	public array $ratingGuide;

	public bool $openSearchSort;

	public string $canonicalLink;

}
