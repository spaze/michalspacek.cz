<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

final readonly class OpenSslX509ParseResult
{

	public function __construct(
		private ?string $commonName,
		private int $validFromTimeT,
		private int $validToTimeT,
		private string $serialNumberHex,
	) {
	}


	public function getCommonName(): ?string
	{
		return $this->commonName;
	}


	public function getValidFromTimeT(): int
	{
		return $this->validFromTimeT;
	}


	public function getValidToTimeT(): int
	{
		return $this->validToTimeT;
	}


	public function getSerialNumberHex(): string
	{
		return $this->serialNumberHex;
	}

}
