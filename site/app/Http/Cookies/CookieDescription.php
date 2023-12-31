<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Cookies;

use Nette\Utils\Html;

readonly class CookieDescription
{

	public function __construct(
		private string $name,
		private bool $internal,
		private Html $description,
		private ?int $validDays,
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
