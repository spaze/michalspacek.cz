<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt;

use Spaze\SecurityTxt\Exceptions\SecurityTxtError;
use Spaze\SecurityTxt\Fields\SecurityTxtContact;
use Spaze\SecurityTxt\Fields\SecurityTxtField;
use Spaze\SecurityTxt\Violations\SecurityTxtAcknowledgmentsNotHttps;
use Spaze\SecurityTxt\Violations\SecurityTxtAcknowledgmentsNotUri;
use Spaze\SecurityTxt\Violations\SecurityTxtCanonicalNotHttps;
use Spaze\SecurityTxt\Violations\SecurityTxtCanonicalNotUri;
use Spaze\SecurityTxt\Violations\SecurityTxtContactNotHttps;
use Spaze\SecurityTxt\Violations\SecurityTxtContactNotUri;
use Spaze\SecurityTxt\Violations\SecurityTxtCsafNotHttps;
use Spaze\SecurityTxt\Violations\SecurityTxtCsafNotUri;
use Spaze\SecurityTxt\Violations\SecurityTxtEncryptionNotHttps;
use Spaze\SecurityTxt\Violations\SecurityTxtEncryptionNotUri;
use Spaze\SecurityTxt\Violations\SecurityTxtFileLocationNotHttps;
use Spaze\SecurityTxt\Violations\SecurityTxtFileLocationNotUri;
use Spaze\SecurityTxt\Violations\SecurityTxtHiringNotHttps;
use Spaze\SecurityTxt\Violations\SecurityTxtHiringNotUri;
use Spaze\SecurityTxt\Violations\SecurityTxtNotUriReason;
use Spaze\SecurityTxt\Violations\SecurityTxtPolicyNotHttps;
use Spaze\SecurityTxt\Violations\SecurityTxtPolicyNotUri;
use Uri\Rfc3986\Uri;
use Uri\WhatWg\Url;

/**
 * Whether a value written in a `security.txt` is a URI, and why not when it is not, worked out together so the rule and the reason cannot disagree. RFC 9116 says a value
 * follows the URI syntax described in RFC 3986, so that decides, once the characters RFC 3987 adds for an IRI are percent-encoded as its Section 3.1 maps them, which makes
 * `https://háčky.example/` one. On top of that an HTTP or HTTPS URI needs a host and no user name, which RFC 9110 requires, and any URI has to be one the WHATWG parser this
 * library reads URLs with can parse, which a port of 99999 is not.
 */
final class SecurityTxtUriValidator
{

	/**
	 * The scheme RFC 3986 Section 3.1 describes
	 */
	private const string SCHEME = '[a-z][a-z0-9+.-]*';

	/**
	 * The scheme, `//`, and the user name and password up to the last `@` before the host, where RFC 3986 and the WHATWG parser both end them
	 */
	private const string BEFORE_HOST = self::SCHEME . '://(?:[^/?#]*@)?';

	/**
	 * The schemes RFC 9116 uses, a word before a colon that isn't one of them is more likely a label, like `Email: security@example.com`
	 */
	private const string KNOWN_SCHEMES = 'https?|mailto|tel|openpgp4fpr|dns';

	private const string CONTROL = '(?:[\x00-\x1f\x7f]|\xc2[\x80-\x9f])';

	/**
	 * An OpenPGP key fingerprint, the spaces in it as GnuPG prints it, grouped by four, two in the middle
	 */
	private const string FINGERPRINT = '(?:[0-9a-f]{4} {0,2}){9}[0-9a-f]{4}';

	/**
	 * A phone number, with the hyphens, dots and spaces people write in one, and a digit, which RFC 3966 requires
	 */
	private const string PHONE = '\+?[\-. ]*+[0-9][0-9\-. ]*+';

	/**
	 * A hostname label, letters, digits and hyphens of any script, starting with a letter or a digit
	 */
	private const string LABEL = '[\p{L}\p{Nd}][\p{L}\p{M}\p{Nd}-]*+';

	/**
	 * A hostname of two labels or more, then the port, the path, the query, the fragment or nothing
	 */
	private const string HOSTNAME = '(?:' . self::LABEL . '\.)++' . self::LABEL . '(?:[/?#:]|$)';

	/**
	 * What a value or a word may be wrapped in, and isn't part of it
	 */
	private const string WRAPPERS = '<>()[]"\'';


	/**
	 * @param class-string<SecurityTxtAcknowledgmentsNotUri|SecurityTxtCanonicalNotUri|SecurityTxtContactNotUri|SecurityTxtCsafNotUri|SecurityTxtEncryptionNotUri|SecurityTxtHiringNotUri|SecurityTxtPolicyNotUri|SecurityTxtFileLocationNotUri> $notUriError
	 * @param class-string<SecurityTxtAcknowledgmentsNotHttps|SecurityTxtCanonicalNotHttps|SecurityTxtContactNotHttps|SecurityTxtCsafNotHttps|SecurityTxtEncryptionNotHttps|SecurityTxtHiringNotHttps|SecurityTxtPolicyNotHttps|SecurityTxtFileLocationNotHttps> $notHttpsError
	 * @param bool $fetched The URL a file was fetched from is not written in the file but serialized by the parser, which leaves `|` or `%zz` in a path as they were, so it is refused only when the parser cannot read it at all
	 * @param string|null $fileLocation The URL the file was fetched from, which a path is suggested on
	 * @throws SecurityTxtError
	 */
	public function validate(string $uri, string $notUriError, string $notHttpsError, ?SecurityTxtField $field = null, bool $fetched = false, ?string $fileLocation = null): void
	{
		$result = $fetched ? $this->checkFetched($uri) : $this->check($uri, $field, $fileLocation);
		if (!$result instanceof Url) {
			throw new SecurityTxtError(new $notUriError($uri, ...$result));
		}
		if ($result->getScheme() === 'http') {
			throw new SecurityTxtError(new $notHttpsError($uri));
		}
	}


	/**
	 * The URL a value is, or failing that the one suggested for it, which is where the value points once the suggestion is taken
	 */
	public function getUrl(string $uri, ?SecurityTxtField $field = null, ?string $fileLocation = null): ?Url
	{
		$result = $this->check($uri, $field, $fileLocation);
		if ($result instanceof Url) {
			return $result;
		}
		return isset($result['correctValue']) ? Url::parse($result['correctValue']) : null;
	}


	/**
	 * The URL a value is, or why it is not one and what to use instead, named as the violation's parameters. Without `$explain` only whether it is one, all a suggestion
	 * needs, so working out a suggestion never works out another one.
	 *
	 * @return Url|array{reason: SecurityTxtNotUriReason, correctValue?: string|null, character?: string}
	 */
	private function check(string $uri, ?SecurityTxtField $field, ?string $fileLocation = null, bool $explain = true): Url|array
	{
		// A value that isn't UTF-8 is not an IRI, and fails here
		$iri = preg_replace_callback('/[^\x00-\x7f]/u', fn(array $matches): string => $this->isUcschar($matches[0]) ? rawurlencode($matches[0]) : $matches[0], $uri);
		$rfc3986 = $iri === null ? null : Uri::parse($iri);
		$scheme = $rfc3986?->getScheme();
		if ($iri === null || $rfc3986 === null || $scheme === null) {
			return $explain ? $this->explainNotRfc3986($uri, $iri ?? $uri, $field, $fileLocation) : ['reason' => SecurityTxtNotUriReason::NotRfc3986];
		}
		if (in_array($scheme, ['http', 'https'], true)) {
			if (in_array($rfc3986->getRawHost(), [null, ''], true)) {
				return ['reason' => SecurityTxtNotUriReason::HostMissing];
			}
			$userInfo = $rfc3986->getRawUserInfo();
			if ($userInfo !== null) {
				// Cut out of the value as written, a URL serialized by a parser would spell the rest the way the parser read it
				$withoutUserInfo = preg_replace('~^(' . self::SCHEME . '://)[^/?#]*@~i', '$1', $uri, 1);
				return [
					'reason' => $userInfo === '' ? SecurityTxtNotUriReason::EmptyUserInfo : SecurityTxtNotUriReason::UserInfo,
					'correctValue' => $explain ? $this->suggest($withoutUserInfo) : null,
				];
			}
		}
		// RFC 6068 puts the addresses before the query, each with something before its one @ and after it, or none there and a `to` field in the query instead.
		// Not after a `//`, which RFC 3986 reads as a host, so `mailto://security@example.com` would be said to have no address
		if ($scheme === 'mailto' && $rfc3986->getRawHost() === null) {
			$to = $rfc3986->getRawPath();
			if ($to === '' && preg_match('/(?:^|&)to=[^&]/i', $rfc3986->getRawQuery() ?? '') !== 1) {
				return ['reason' => SecurityTxtNotUriReason::EmailAddressMissing];
			}
			if ($to !== '' && preg_match('/^[^@,]++@[^@,]++(?:,[^@,]++@[^@,]++)*+$/D', $to) !== 1) {
				return ['reason' => SecurityTxtNotUriReason::NotEmailAddress];
			}
		}
		$url = Url::parse($uri);
		if ($url === null) {
			if ($rfc3986->getPort() > 65535) {
				return ['reason' => SecurityTxtNotUriReason::PortOutOfRange];
			}
			return ['reason' => in_array($rfc3986->getRawHost(), [null, ''], true) ? SecurityTxtNotUriReason::HostMissing : SecurityTxtNotUriReason::InvalidHost];
		}
		return $url;
	}


	/**
	 * Why RFC 3986 refused a value, or read it as a relative reference, which is not a URI either for having no scheme, the first reason that holds. Worked out from the
	 * value, because the parser says only that it is malformed, but only for a value it refused, so whatever reason this gives, the value does break RFC 3986.
	 *
	 * @param string $iri The value with the characters RFC 3987 adds for an IRI percent-encoded, so what is left that is not ASCII is not allowed even there
	 * @return array{reason: SecurityTxtNotUriReason, correctValue?: string|null, character?: string}
	 */
	private function explainNotRfc3986(string $uri, string $iri, ?SecurityTxtField $field, ?string $fileLocation): array
	{
		if ($uri === '') {
			return ['reason' => SecurityTxtNotUriReason::Empty];
		}
		if (preg_match('/^(?:<([^<>]+)>|\(([^()]+)\)|\[([^\[\]]+)\]|"([^"]+)"|\'([^\']+)\')$/D', $uri, $matches) === 1) {
			$reason = match (count($matches)) {
				2 => SecurityTxtNotUriReason::AngleBrackets,
				3 => SecurityTxtNotUriReason::Parentheses,
				4 => SecurityTxtNotUriReason::SquareBrackets,
				default => SecurityTxtNotUriReason::Quotes,
			};
			return ['reason' => $reason, 'correctValue' => $this->suggest($matches[count($matches) - 1])];
		}
		// Words separated by spaces, commas or control characters, of which a scheme and `//`, a scheme RFC 9116 uses and more, or any scheme and an `@`, `sip:security@example.com`,
		// is a URI, not `ext:12` or `example.com:8080`
		$words = preg_split('/(?:[\s,]|' . self::CONTROL . ')++/', $uri, -1, PREG_SPLIT_NO_EMPTY);
		$words = $words === false ? [] : $words;
		$isUri = fn(string $word): bool => preg_match('~^(?:' . self::SCHEME . '://|(?:' . self::KNOWN_SCHEMES . '):.|' . self::SCHEME . ':[^@]++@)~i', trim($word, self::WRAPPERS)) === 1;
		$uris = [];
		foreach ($words as $position => $word) {
			$next = $words[$position + 1] ?? null;
			// And so is one of those schemes and a space before what is neither a URI nor an address, `tel: +420-123-456-789`, the address after `mailto: ` is the contact
			if (
				$isUri($word)
				|| (
					preg_match('/^(?:' . self::KNOWN_SCHEMES . '):$/iD', trim($word, self::WRAPPERS)) === 1
					&& $next !== null
					&& !$isUri($next)
					&& filter_var(trim($next, self::WRAPPERS . ';:'), FILTER_VALIDATE_EMAIL) === false
				)
			) {
				$uris[] = $position;
			}
		}
		// A value that starts with a scheme RFC 9116 uses is one contact up to a comma or a semicolon and a space, even with a space in it, `mailto:Security Team <security@example.com>`
		// or `mailto: security@example.com`
		$list = $uri;
		if (preg_match('/^(?:' . self::KNOWN_SCHEMES . '):/i', $uri) === 1) {
			$list = preg_match('/[,;]\s/', $uri, $separator, PREG_OFFSET_CAPTURE) === 1 ? substr($uri, $separator[0][1]) : '';
			if ($list !== '' && ($uris[0] ?? null) !== 0) {
				array_unshift($uris, 0);
			}
		}
		// An address in a Contact is one more contact, and only spaces and commas separate them, a control character inside one is no separator
		$emails = [];
		$phones = 0;
		if ($field === SecurityTxtField::Contact) {
			$emailWords = preg_split('/[\s,]++/', $list, -1, PREG_SPLIT_NO_EMPTY);
			foreach ($emailWords === false ? [] : $emailWords as $position => $word) {
				if (filter_var(trim($word, self::WRAPPERS . ';:'), FILTER_VALIDATE_EMAIL) !== false) {
					$emails[$position] = $word;
				}
			}
			// And so is a phone number between commas or semicolons, seven digits or more with hyphens, dots, spaces or brackets between them, `+44 (0)20 7946 0958`,
			// after a label too, `Phone: +1-201-555-0123`, but not after `tel: `, which is one of the URIs already, nor a comma or a semicolon in a URI
			$items = preg_replace_callback('/\S++/', fn(array $word): string => $isUri($word[0]) ? preg_replace('/[,;](?=\S)/', '_', $word[0]) ?? '' : $word[0], $list) ?? '';
			foreach (explode(',', str_replace(';', ',', $items)) as $item) {
				if (
					preg_match('~^\s*+(?:(\p{L}[\p{L}\p{N}+./-]*+):\s*+)?\+?\(?[0-9](?:(?:\)[\- ]?|[\- ]?\(|[\-. ]++)?+[0-9]){6,}+\s*+$~iuD', $item, $label) === 1
					&& preg_match('/^(?:' . self::KNOWN_SCHEMES . ')$/iD', $label[1] ?? '') !== 1
				) {
					$phones++;
				}
			}
		}
		if (count($uris) + count($emails) + $phones > 1) {
			return ['reason' => match (true) {
				$emails === [] && $phones === 0 => SecurityTxtNotUriReason::MoreThanOneUri,
				$uris === [] && $phones === 0 => SecurityTxtNotUriReason::MoreThanOneEmailAddress,
				default => SecurityTxtNotUriReason::MoreThanOneContact,
			}];
		}
		$last = count($words) - 1;
		if ($last > 0 && count($uris) === 1 && $uris[0] === $last) {
			// Unless the URI's scheme could have started in the word before, `htt ps://`
			if (
				preg_match('/^(?:' . self::KNOWN_SCHEMES . '):/i', trim($words[$last], self::WRAPPERS)) === 1
				|| preg_match('/[^a-z0-9+.-]$/iD', $words[$last - 1]) === 1
			) {
				// And without the full stop or a bracket of the sentence around it, which would be part of the path
				$sentence = preg_match('/[.,;:!?]$/D', $words[$last]) === 1 || substr_count($words[$last], ')') !== substr_count($words[$last], '(') || substr_count($words[$last], ']') !== substr_count($words[$last], '[');
				return ['reason' => SecurityTxtNotUriReason::TextBeforeUri, 'correctValue' => $sentence ? null : $this->suggest($words[$last])];
			}
		}
		if (count($emails) === 1 && count($words) > 1) {
			$position = array_key_first($emails);
			$email = trim($emails[$position], self::WRAPPERS . ';:');
			// Only an address set apart, in brackets or after a label, `security team@example.com` is not `team@example.com`
			if ($email !== $emails[$position] || str_ends_with($emailWords[$position - 1] ?? '', ':')) {
				return ['reason' => SecurityTxtNotUriReason::EmailAddressInText, 'correctValue' => $this->suggest(SecurityTxtContact::email($email)->getUri())];
			}
		}
		if (
			count($words) > 1
			&& preg_match('/^(' . self::SCHEME . '):$/iD', $words[0], $label) === 1
			&& preg_match('/^(?:' . self::KNOWN_SCHEMES . ')$/iD', $label[1]) !== 1
		) {
			return ['reason' => SecurityTxtNotUriReason::Label, 'correctValue' => $this->suggest(ltrim(substr($uri, strlen($words[0]))))];
		}
		if ($this->isSchemeMissing($uri)) {
			return $this->explainSchemeMissing($uri, $field, $fileLocation);
		}
		[$host, $port] = $this->getHostAndPort($uri);
		if (str_contains($uri, ' ')) {
			if (preg_match('/^tel:( *+)(' . self::PHONE . ')$/iD', $uri, $matches) === 1 && str_contains($matches[2], ' ')) {
				return [
					'reason' => $matches[1] === '' ? SecurityTxtNotUriReason::SpaceInPhoneNumber : SecurityTxtNotUriReason::SpaceAfterSchemeAndInPhoneNumber,
					'correctValue' => $this->suggest(SecurityTxtContact::phone($matches[2])->getUri()),
				];
			}
			if (preg_match('/^openpgp4fpr: *(' . self::FINGERPRINT . ')$/iD', $uri, $matches) === 1 && str_contains($matches[1], ' ')) {
				return ['reason' => SecurityTxtNotUriReason::SpaceInFingerprint, 'correctValue' => $this->suggest('openpgp4fpr:' . str_replace(' ', '', $matches[1]))];
			}
			if (preg_match('/^(' . self::KNOWN_SCHEMES . '): +(\S+)$/iD', $uri, $matches) === 1) {
				$correctValue = "{$matches[1]}:{$matches[2]}";
				if (strtolower($matches[1]) === 'mailto') {
					// An address encoded, `mailto:foo#bar@example.com` would mail `foo`, but none when the address may be encoded already, or the URI would mail no address
					$address = substr($matches[2], 0, strcspn($matches[2], '?#'));
					$correctValue = match (true) {
						preg_match('/%[0-9a-f]{2}/i', $address) === 1 => null,
						filter_var($matches[2], FILTER_VALIDATE_EMAIL) !== false => SecurityTxtContact::email($matches[2])->getUri(),
						!str_contains($address, '@') => null,
						default => $correctValue,
					};
				}
				return ['reason' => SecurityTxtNotUriReason::SpaceAfterScheme, 'correctValue' => $this->suggest($correctValue)];
			}
			// In the host or the port only if the path or something else follows them, with nothing after the host, a space may as well end the URI and start a note
			$authority = preg_match('~^' . self::SCHEME . '://[^/?#]*+[/?#]~i', $uri) === 1;
			return ['reason' => match (true) {
				$authority && str_contains($host ?? '', ' ') => SecurityTxtNotUriReason::SpaceInHost,
				$authority && str_contains($port ?? '', ' ') => SecurityTxtNotUriReason::SpaceInPort,
				default => SecurityTxtNotUriReason::Space,
			}];
		}
		if (preg_match('/' . self::CONTROL . '/', $uri) === 1) {
			// Unless that would join what the control characters separate, a word to a word or one host label to another
			$joins = preg_match('~(?<=[^:/?#@])(?<!' . self::CONTROL . ')' . self::CONTROL . '++(?=[^:/?#@])~', $uri) === 1;
			return ['reason' => SecurityTxtNotUriReason::ControlCharacter, 'correctValue' => $joins ? null : $this->suggest(preg_replace('/' . self::CONTROL . '/', '', $uri))];
		}
		$percent = '/%(?![0-9a-f]{2})/i';
		if (preg_match($percent, $uri) === 1) {
			if (preg_match($percent, $host ?? '') === 1) {
				return ['reason' => SecurityTxtNotUriReason::StrayPercentInHost];
			}
			return ['reason' => SecurityTxtNotUriReason::StrayPercent, 'correctValue' => $this->suggest(preg_replace($percent, '%25', $uri))];
		}
		if (str_contains($uri, '\\')) {
			return ['reason' => SecurityTxtNotUriReason::Backslash];
		}
		if ($host !== null && str_starts_with($host, '[') && !str_contains($host, ']')) {
			return ['reason' => SecurityTxtNotUriReason::Ipv6Unclosed];
		}
		// What follows the last colon is the port only when the host is in square brackets or has no colon, in `2001:db8::ff` it is part of the address
		[$knownHost, $knownPort] = str_starts_with($host ?? '', '[') || !str_contains($host ?? '', ':') ? [$host, $port] : ["{$host}:{$port}", null];
		// A `[` and a `]` are allowed only around an IPv6 address, and a `#` only once
		$rest = preg_replace('~^(' . self::BEFORE_HOST . ')\[[^/?#\]]*\]~i', '$1', $iri) ?? $iri;
		$fragment = strpos($rest, '#');
		if (
			preg_match("~[^a-z0-9._\\~:/?#@!$&'()*+,;=%-]|\\[|\\]~i", $rest, $matches) === 1
			|| (
				$fragment !== false
				&& preg_match('/#/', $rest, $matches, 0, $fragment + 1) === 1
			)
		) {
			return match (true) {
				str_contains($knownPort ?? '', $matches[0]) => ['reason' => SecurityTxtNotUriReason::PortNotNumber],
				preg_match('/^[\x21-\x7e]$/D', $matches[0]) !== 1 => ['reason' => SecurityTxtNotUriReason::UnencodedCharacter],
				// Percent-encoded it would still not be a host, an IPv6 address was left out of the search
				!str_starts_with($knownHost ?? '', '[') && str_contains($knownHost ?? '', $matches[0]) => ['reason' => SecurityTxtNotUriReason::CharacterInHost, 'character' => $matches[0]],
				default => ['reason' => SecurityTxtNotUriReason::Character, 'character' => $matches[0]],
			};
		}
		if ($host !== null && !str_starts_with($host, '[') && substr_count("{$host}:{$port}", ':') > 1) {
			$bracketed = preg_replace('~^(' . self::BEFORE_HOST . ')([^/?#]*)~i', '$1[$2]', $uri, 1);
			$withPort = $port !== null && preg_match('/^[0-9]{2,}$/D', $port) === 1 ? preg_replace('~^(' . self::BEFORE_HOST . ')([^/?#]*):~i', '$1[$2]:', $uri, 1) : null;
			// Which of them are URIs, `http` ones too, the scheme is not what is wrong with the value
			$uris = array_values(array_filter([$bracketed, $withPort], fn(?string $value): bool => $value !== null && $this->check($value, null, explain: false) instanceof Url));
			if ($uris !== []) {
				// Not when both are, `2001:db8::1:8080` is one address, or another one and a port
				return ['reason' => SecurityTxtNotUriReason::Ipv6WithoutBrackets, 'correctValue' => count($uris) === 1 ? $this->suggest($uris[0]) : null];
			}
		}
		if ($knownPort !== null && preg_match('/^[0-9]*$/D', $knownPort) !== 1) {
			return ['reason' => SecurityTxtNotUriReason::PortNotNumber];
		}
		return ['reason' => SecurityTxtNotUriReason::NotRfc3986];
	}


	/**
	 * @return array{reason: SecurityTxtNotUriReason, correctValue?: string|null}
	 */
	private function explainSchemeMissing(string $uri, ?SecurityTxtField $field, ?string $fileLocation): array
	{
		// An absolute path, which resolves the same at either location RFC 9116 puts the file, a relative one doesn't, against the scheme and host the file came from
		if (
			$fileLocation !== null
			&& preg_match('~^/(?!/)~', $uri) === 1
			&& preg_match('~^' . self::SCHEME . '://[^/?#]*~i', $fileLocation, $origin) === 1
		) {
			$correctValue = $this->suggest($origin[0] . $uri);
			if ($correctValue !== null) {
				return ['reason' => SecurityTxtNotUriReason::SchemeMissingPath, 'correctValue' => $correctValue];
			}
		}
		if ($field === SecurityTxtField::Encryption && preg_match('/^' . self::FINGERPRINT . '$/iD', $uri) === 1) {
			return [
				'reason' => str_contains($uri, ' ') ? SecurityTxtNotUriReason::SchemeMissingFingerprintWithSpaces : SecurityTxtNotUriReason::SchemeMissingFingerprint,
				'correctValue' => $this->suggest('openpgp4fpr:' . str_replace(' ', '', $uri)),
			];
		}
		if ($field === SecurityTxtField::Contact) {
			[$reason, $correctValue] = match (true) {
				filter_var($uri, FILTER_VALIDATE_EMAIL) !== false => [SecurityTxtNotUriReason::SchemeMissingEmail, SecurityTxtContact::email($uri)->getUri()],
				preg_match('/^' . self::PHONE . '$/D', $uri) === 1 => [
					str_contains($uri, ' ') ? SecurityTxtNotUriReason::SchemeMissingPhoneWithSpaces : SecurityTxtNotUriReason::SchemeMissingPhone,
					SecurityTxtContact::phone($uri)->getUri(),
				],
				// Without a comma, more likely between two contacts, or a full stop or the like at the end, more likely of a sentence
				preg_match('~^' . self::HOSTNAME . '~uD', $uri) === 1 && preg_match('/,|[.;:!?]$/D', $uri) !== 1 => [SecurityTxtNotUriReason::SchemeMissingHostname, "https://{$uri}"],
				default => [SecurityTxtNotUriReason::SchemeMissing, null],
			};
			// Prose with an @ in it does not become a URI by putting a scheme in front, and the hint saying it would goes with the suggestion
			$correctValue = $this->suggest($correctValue);
			if ($correctValue !== null) {
				return ['reason' => $reason, 'correctValue' => $correctValue];
			}
		}
		return ['reason' => SecurityTxtNotUriReason::SchemeMissing];
	}


	/**
	 * The host and the port as written, each null when the value has no authority, or the authority no port. An IPv6 address ends at its `]`.
	 * Of a `mailto:` URI with no authority, the domains of its addresses joined by a `,`, which is in none of them, and no port, but not a domain literal like `[192.0.2.1]`,
	 * its `[` doesn't start an IPv6 address.
	 *
	 * @return array{string|null, string|null}
	 */
	private function getHostAndPort(string $uri): array
	{
		if (preg_match('~^mailto:(?!//)([^?#]*)~i', $uri, $matches) === 1 && preg_match_all('~@([^@,]*+)(?=,|$)~D', $matches[1], $domains) > 0) {
			return [implode(',', array_filter($domains[1], fn(string $domain): bool => !str_starts_with($domain, '['))), null];
		}
		if (preg_match('~^' . self::BEFORE_HOST . '([^/?#]*)~i', $uri, $matches) !== 1) {
			return [null, null];
		}
		$authority = $matches[1];
		$end = str_starts_with($authority, '[') ? strpos($authority, ']') : false;
		if ($end !== false) {
			$rest = substr($authority, $end + 1);
			return [substr($authority, 0, $end + 1), str_starts_with($rest, ':') ? substr($rest, 1) : null];
		}
		$colon = strrpos($authority, ':');
		return $colon === false ? [$authority, null] : [substr($authority, 0, $colon), substr($authority, $colon + 1)];
	}


	/**
	 * @return Url|array{reason: SecurityTxtNotUriReason}
	 */
	private function checkFetched(string $uri): Url|array
	{
		return Url::parse($uri) ?? ['reason' => $this->isSchemeMissing($uri) ? SecurityTxtNotUriReason::SchemeMissing : SecurityTxtNotUriReason::NotUrl];
	}


	/**
	 * A value to use instead, but only one that passes the checks the value failed, this one and the one after it refusing `http`.
	 */
	private function suggest(?string $value): ?string
	{
		if ($value === null) {
			return null;
		}
		$result = $this->check($value, null, explain: false);
		return $result instanceof Url && $result->getScheme() !== 'http' ? $value : null;
	}


	/**
	 * Whether a character is one RFC 3987 adds for an IRI, its `ucschar`, leaving out the private use characters it also allows in a query, no `security.txt` needs them.
	 * Compared as a number, not as a character class in a pattern: PCRE2 10.46 fails to match `例` against the class with all of the ranges below.
	 */
	private function isUcschar(string $character): bool
	{
		$codePoint = match (strlen($character)) {
			2 => (ord($character[0]) & 0x1f) << 6 | ord($character[1]) & 0x3f,
			3 => (ord($character[0]) & 0x0f) << 12 | (ord($character[1]) & 0x3f) << 6 | ord($character[2]) & 0x3f,
			4 => (ord($character[0]) & 0x07) << 18 | (ord($character[1]) & 0x3f) << 12 | (ord($character[2]) & 0x3f) << 6 | ord($character[3]) & 0x3f,
			default => 0,
		};
		return ($codePoint >= 0xa0 && $codePoint <= 0xd7ff)
			|| ($codePoint >= 0xf900 && $codePoint <= 0xfdcf)
			|| ($codePoint >= 0xfdf0 && $codePoint <= 0xffef)
			// From %x10000-1FFFD to %xD0000-DFFFD, and %xE1000-EFFFD
			|| ($codePoint >= 0x10000 && $codePoint <= 0xefffd && ($codePoint & 0xffff) <= 0xfffd && ($codePoint < 0xe0000 || $codePoint >= 0xe1000));
	}


	private function isSchemeMissing(string $uri): bool
	{
		return preg_match('/^' . self::SCHEME . ':/i', $uri) !== 1;
	}

}
