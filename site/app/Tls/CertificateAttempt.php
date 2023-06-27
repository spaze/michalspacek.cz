<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Tls;

class CertificateAttempt
{

	public function __construct(
		private readonly string $commonName,
		private readonly ?string $commonNameExt,
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
