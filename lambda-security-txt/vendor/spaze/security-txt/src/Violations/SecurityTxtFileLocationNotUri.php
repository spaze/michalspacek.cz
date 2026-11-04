<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

final class SecurityTxtFileLocationNotUri extends SecurityTxtSpecViolation
{

	public function __construct(string $uri, SecurityTxtNotUriReason $reason = SecurityTxtNotUriReason::Unspecified, ?string $correctValue = null, ?string $character = null)
	{
		parent::__construct(
			func_get_args(),
			'The location of the file %s ' . $reason->getFormat(),
			[$uri, ...$reason->getValues($character)],
			'draft-foudil-securitytxt-00',
			self::asUrl($correctValue),
			$reason->getHowToFix(),
			$reason->getHowToFixValues($character),
			'3',
		);
	}

}
