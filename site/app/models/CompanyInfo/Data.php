<?php
declare(strict_types = 1);

namespace MichalSpacekCz\CompanyInfo;

/**
 * Company data.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Data
{
	/** @var integer */
	public $status;

	/** @var string */
	public $statusMessage;

	/** @var string */
	public $companyId;

	/** @var string */
	public $companyTaxId;

	/** @var string */
	public $company;

	/** @var string */
	public $street;

	/** @var string */
	public $houseNumber;

	/** @var string */
	public $streetNumber;

	/** @var string */
	public $streetFull;

	/** @var string */
	public $city;

	/** @var string */
	public $zip;

	/** @var string */
	public $country;
}
