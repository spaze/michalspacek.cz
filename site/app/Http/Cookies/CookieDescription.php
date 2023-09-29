<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Cookies;

use Nette\Utils\Html;

class CookieDescription
{

	public function __construct(
		private readonly string $name,
		private readonly bool $internal,
		private readonly Html $description,
		private readonly ?int $validDays,
	) {
	}


	public function getName(): string
	{
		return $this->name;
	}


	public function isInternal(): bool
	{
		return $this->internal;
	}


	public function getDescription(): Html
	{
		return $this->description;
	}


	public function getValidDays(): ?int
	{
		return $this->validDays;
	}

}
