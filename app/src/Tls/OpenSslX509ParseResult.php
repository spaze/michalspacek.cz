<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

final readonly class OpenSslX509ParseResult
{

	/**
	 * @param list<string>|null $subjectAlternativeNames
	 */
	public function __construct(
		private ?string $commonName,
		private ?array $subjectAlternativeNames,
		private int $validFromTimeT,
		private int $validToTimeT,
		private string $serialNumberHex,
	) {
	}


	public function getCommonName(): ?string
	{
		return $this->commonName;
	}


	/**
	 * @return list<string>|null
	 */
	public function getSubjectAlternativeNames(): ?array
	{
		return $this->subjectAlternativeNames;
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
