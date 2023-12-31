<?php
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

use JsonSerializable;
use Override;

readonly class CompanyInfoDetails implements JsonSerializable
{

	public function __construct(
		private int $status,
		private string $statusMessage,
		private string $companyId = '',
		private string $companyTaxId = '',
		private string $company = '',
		private ?string $streetAndNumber = null,
		private ?string $city = null,
		private ?string $zip = null,
		private ?string $country = null,
	) {
	}


	public function getStatus(): int
	{
		return $this->status;
	}


	/**
	 * @return array<string, int|string|null>
	 */
	#[Override]
	public function jsonSerialize(): array
	{
		return array_filter([
			'status' => $this->status,
			'statusMessage' => $this->statusMessage,
			'companyId' => $this->companyId,
			'companyTaxId' => $this->companyTaxId,
			'company' => $this->company,
			'street' => $this->streetAndNumber,
			'city' => $this->city,
			'zip' => $this->zip,
			'country' => $this->country,
		]);
	}

}
