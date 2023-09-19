<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

class OpenSslX509ParseResult
{

	public function __construct(
		private readonly string $commonName,
		private readonly int $validFromTimeT,
		private readonly int $validToTimeT,
		private readonly string $serialNumberHex,
	) {
	}


	public function getCommonName(): string
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
