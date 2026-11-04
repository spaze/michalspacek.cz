<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Validator\Validators;

use Override;
use Spaze\SecurityTxt\Exceptions\SecurityTxtWarning;
use Spaze\SecurityTxt\SecurityTxt;
use Spaze\SecurityTxt\Violations\SecurityTxtCanonicalUriMismatch;
use Spaze\SecurityTxt\Violations\SecurityTxtCanonicalUriMismatchReason;
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
		$canonicalUrls = array_values(array_filter(array_map(Url::parse(...), $canonicalUris)));
		if ($this->lists($canonicalUrls, $url)) {
			return;
		}
		$reason = $this->getReason($canonicalUrls, $url, count($canonicalUrls) < count($canonicalUris));
		// With the reason only when there's one, so a stored violation carries nothing more than before
		throw new SecurityTxtWarning($reason !== SecurityTxtCanonicalUriMismatchReason::Unspecified
			? new SecurityTxtCanonicalUriMismatch($uri, $canonicalUris, $reason)
			: new SecurityTxtCanonicalUriMismatch($uri, $canonicalUris));
	}


	/**
	 * @param list<Url> $canonicalUrls
	 */
	private function lists(array $canonicalUrls, Url $url): bool
	{
		foreach ($canonicalUrls as $canonicalUrl) {
			if ($url->equals($canonicalUrl, UriComparisonMode::IncludeFragment)) {
				return true;
			}
		}
		return false;
	}


	/**
	 * Only for a file fetched from one of the two places RFC 9116 section 3 puts it, not one a redirect took elsewhere
	 *
	 * @param list<Url> $canonicalUrls
	 */
	private function getReason(array $canonicalUrls, Url $url, bool $notUri): SecurityTxtCanonicalUriMismatchReason
	{
		if ($url->getQuery() !== null || $url->getFragment() !== null) {
			return SecurityTxtCanonicalUriMismatchReason::Unspecified;
		}
		if ($url->getPath() === '/security.txt') {
			return $this->lists($canonicalUrls, $url->withPath('/.well-known/security.txt')) ? SecurityTxtCanonicalUriMismatchReason::WellKnownListed : SecurityTxtCanonicalUriMismatchReason::Unspecified;
		}
		if ($url->getPath() !== '/.well-known/security.txt') {
			return SecurityTxtCanonicalUriMismatchReason::Unspecified;
		}
		if ($this->lists($canonicalUrls, $url->withPath('/security.txt'))) {
			return SecurityTxtCanonicalUriMismatchReason::TopLevelListed;
		}
		foreach ($canonicalUrls as $canonicalUrl) {
			if ($canonicalUrl->getAsciiHost() === $url->getAsciiHost()) {
				return SecurityTxtCanonicalUriMismatchReason::SameHost;
			}
		}
		return $notUri ? SecurityTxtCanonicalUriMismatchReason::NotUri : SecurityTxtCanonicalUriMismatchReason::Unspecified;
	}

}
