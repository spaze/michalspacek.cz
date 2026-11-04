<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Violations;

use Spaze\SecurityTxt\Fields\SecurityTxtField;

final class SecurityTxtCanonicalUriMismatch extends SecurityTxtSpecViolation
{

	/**
	 * @param list<string> $canonicalUris
	 */
	public function __construct(string $uri, array $canonicalUris, SecurityTxtCanonicalUriMismatchReason $reason = SecurityTxtCanonicalUriMismatchReason::Unspecified)
	{
		$count = count($canonicalUris);
		$howToFixValues = [SecurityTxtField::Canonical->value, self::asUrl($uri)];
		$seeAlsoSections = [];
		if ($count === 0) {
			$messageFormat = 'The file was fetched from %s but lists no %s field at all';
			$howToFixFormat = 'Add a new %s field with the URI %s';
		} else {
			$messageFormat = $count === 1
				? "The file was fetched from %s but the %s field (%s) doesn't list this URI, so the contents of the file shouldn't be trusted"
				: 'The file was fetched from %s but none of the %s fields (' . $this->getRepeatedFormat('%s', $count) . ") list this URI, so the contents of the file shouldn't be trusted";
			if ($reason === SecurityTxtCanonicalUriMismatchReason::WellKnownListed) {
				$howToFixFormat = 'Serve the file under the %s path, which the %s field lists, and redirect %s to %s';
				$howToFixValues = ['/.well-known/', SecurityTxtField::Canonical->value, '/security.txt', '/.well-known/security.txt'];
				$seeAlsoSections = ['3'];
			} elseif ($reason === SecurityTxtCanonicalUriMismatchReason::TopLevelListed) {
				$howToFixFormat = 'Add a new %s field with the URI %s, as the file under the %s path is the one used';
				$howToFixValues = [...$howToFixValues, '/.well-known/'];
				$seeAlsoSections = ['3'];
			} elseif ($reason === SecurityTxtCanonicalUriMismatchReason::SameHost || $reason === SecurityTxtCanonicalUriMismatchReason::NotUri) {
				$howToFixFormat = match (true) {
					$count === 1 => 'Change the %s field to %s, the URI the file was fetched from',
					$reason === SecurityTxtCanonicalUriMismatchReason::SameHost => 'Change the %s field naming this host to %s, the URI the file was fetched from',
					default => "Change the %s field that isn't a URI to %s, the URI the file was fetched from",
				};
			} else {
				$howToFixFormat = $count === 1
					? 'Add a new %s field with the URI %s, or ensure the file is fetched from the listed canonical URI'
					: 'Add a new %s field with the URI %s, or ensure the file is fetched from one of the listed canonical URIs';
			}
		}
		parent::__construct(
			func_get_args(),
			$messageFormat,
			[self::asUrl($uri), SecurityTxtField::Canonical->value, ...array_map(self::asUrl(...), $canonicalUris)],
			'draft-foudil-securitytxt-05',
			null,
			$howToFixFormat,
			$howToFixValues,
			'2.5.2',
			$seeAlsoSections,
		);
	}

}
