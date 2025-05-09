<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fields;

use DateTimeImmutable;
use JsonSerializable;
use Override;

final readonly class SecurityTxtExpires implements SecurityTxtFieldValue, JsonSerializable
{

	/**
	 * @internal
	 */
	public const string FORMAT = DATE_RFC3339;


	/**
	 * @internal Use Spaze\SecurityTxt\Fields\SecurityTxtExpiresFactory::create()
	 */
	public function __construct(
		private DateTimeImmutable $dateTime,
		private bool $isExpired,
		private int $inDays,
	) {
	}


	#[Override]
	public function getField(): SecurityTxtField
	{
		return SecurityTxtField::Expires;
	}


	#[Override]
	public function getValue(): string
	{
		return $this->dateTime->format(SecurityTxtExpires::FORMAT);
	}


	public function isExpired(): bool
	{
		return $this->isExpired;
	}


	public function inDays(): int
	{
		return $this->inDays;
	}


	public function getDateTime(): DateTimeImmutable
	{
		return $this->dateTime;
	}


	/**
	 * @return array<string, mixed>
	 */
	#[Override]
	public function jsonSerialize(): array
	{
		return [
			'dateTime' => $this->getDateTime()->format(DATE_RFC3339),
			'isExpired' => $this->isExpired(),
			'inDays' => $this->inDays(),
		];
	}

}
