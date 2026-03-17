<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtCsaf;

final class SecurityTxtCsafWrongFile extends SecurityTxtSpecViolation
{

	public function __construct(string $uri)
	{
		parent::__construct(
			func_get_args(),
			'The file with the Common Security Advisory Framework (CSAF) metadata currently located at %s must be called %s',
			[$uri, SecurityTxtCsaf::METADATA_FILENAME],
			null,
			null,
			'Rename the file to %s',
			[SecurityTxtCsaf::METADATA_FILENAME],
			null,
			specUrl: 'https://docs.oasis-open.org/csaf/csaf/v2.0/os/csaf-v2.0-os.html#717-requirement-7-provider-metadatajson',
		);
	}

}
