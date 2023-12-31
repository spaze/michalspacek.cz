<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Controls;

use Nette\Forms\Controls\TextInput;

readonly class TrainingControlsCompany
{

	public function __construct(
		private TextInput $companyId,
		private TextInput $companyTaxId,
		private TextInput $company,
		private TextInput $street,
		private TextInput $city,
		private TextInput $zip,
	) {
	}


	public function getCompanyId(): TextInput
	{
		return $this->companyId;
	}


	public function getCompanyTaxId(): TextInput
	{
		return $this->companyTaxId;
	}


	public function getCompany(): TextInput
	{
		return $this->company;
	}


	public function getStreet(): TextInput
	{
		return $this->street;
	}


	public function getCity(): TextInput
	{
		return $this->city;
	}


	public function getZip(): TextInput
	{
		return $this->zip;
	}

}
