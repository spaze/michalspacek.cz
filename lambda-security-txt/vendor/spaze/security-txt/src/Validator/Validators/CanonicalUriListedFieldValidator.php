<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Validator\Validators;

use Override;
use Spaze\SecurityTxt\Exceptions\SecurityTxtWarning;
use Spaze\SecurityTxt\SecurityTxt;
use Spaze\SecurityTxt\Violations\SecurityTxtCanonicalUriMismatch;
use Uri\UriComparisonMode;
use Uri\WhatWg\Url;

final class CanonicalUriListedFieldValidator implements FieldValidator
{

	#[Override]
	public function validate(SecurityTxt $securityTxt): void
	{
		$uri = $securityTxt->getFileLocation();
		if ($uri === null) {
			return;
		}

		$canonicals = $securityTxt->getCanonical();
		if ($canonicals === []) {
			return;
		}

		$url = Url::parse($uri);
		if ($url === null || $url->getScheme() !== 'https') {
			// Asking whether the field lists where the file is only means something once that is a place: a location reported by its own violation would otherwise be told to
			// list itself, which for an http one is advice the Canonical field would refuse, and two locations that name nothing would count as naming the same nothing
			return;
		}

		$canonicalUris = array_map(fn($canonical): string => $canonical->getUri(), $canonicals);
		foreach ($canonicalUris as $canonicalUri) {
			$canonicalUrl = Url::parse($canonicalUri);
			if ($canonicalUrl !== null && $url->equals($canonicalUrl, UriComparisonMode::IncludeFragment)) {
				return;
			}
		}
		throw new SecurityTxtWarning(new SecurityTxtCanonicalUriMismatch($uri, $canonicalUris));
	}

}
