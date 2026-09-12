<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtCanonicalUriMismatch extends SecurityTxtSpecViolation
{

	/**
	 * @param list<string> $canonicalUris
	 */
	public function __construct(string $uri, array $canonicalUris)
	{
		$count = count($canonicalUris);
		if ($count === 0) {
			$messageFormat = 'The file was fetched from %s but lists no %s field at all';
			$howToFixFormat = 'Add a new %s field with the URI %s';
		} elseif ($count === 1) {
			$messageFormat = 'The file was fetched from %s but the %s field (%s) does not list this URI';
			$howToFixFormat = 'Add a new %s field with the URI %s, or ensure the file is fetched from the listed canonical URI';
		} else {
			$messageFormat = 'The file was fetched from %s but none of the %s fields (' . $this->getRepeatedFormat('%s', $count) . ') list this URI';
			$howToFixFormat = 'Add a new %s field with the URI %s, or ensure the file is fetched from one of the listed canonical URIs';
		}
		parent::__construct(
			func_get_args(),
			$messageFormat,
			[self::asUrl($uri), SecurityTxtField::Canonical->value, ...array_map(self::asUrl(...), $canonicalUris)],
			'draft-foudil-securitytxt-05',
			null,
			$howToFixFormat,
			[SecurityTxtField::Canonical->value, self::asUrl($uri)],
			'2.5.2',
		);
	}

}
