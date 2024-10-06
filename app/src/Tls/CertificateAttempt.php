<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

readonly class CertificateAttempt
{

	public function __construct(
		private string $commonName,
		private ?string $commonNameExt,
	) {
	}


	public function getCommonName(): string
	{
		return $this->commonName;
	}


	public function getCommonNameExt(): ?string
	{
		return $this->commonNameExt;
	}

}
