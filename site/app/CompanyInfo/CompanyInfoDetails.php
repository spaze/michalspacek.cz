<?php
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

use JsonSerializable;
use Override;

class CompanyInfoDetails implements JsonSerializable
{

	public function __construct(
		private readonly int $status,
		private readonly string $statusMessage,
		private readonly string $companyId = '',
		private readonly string $companyTaxId = '',
		private readonly string $company = '',
		private readonly ?string $streetAndNumber = null,
		private readonly ?string $city = null,
		private readonly ?string $zip = null,
		private readonly ?string $country = null,
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
