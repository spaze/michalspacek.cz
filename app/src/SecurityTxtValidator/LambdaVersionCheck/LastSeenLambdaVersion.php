<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\LambdaVersionCheck;

use DateTimeImmutable;
use MichalSpacekCz\Application\DependencyVersion;

final readonly class LastSeenLambdaVersion
{

	public function __construct(
		private int $id,
		private DateTimeImmutable $lastCheck,
		private DependencyVersion $version,
	) {
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getLastCheck(): DateTimeImmutable
	{
		return $this->lastCheck;
	}


	public function getVersion(): DependencyVersion
	{
		return $this->version;
	}

}
