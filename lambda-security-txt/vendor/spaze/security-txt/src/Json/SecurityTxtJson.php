<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Json;

use BackedEnum;
use DateMalformedStringException;
use DateTimeImmutable;
use ReflectionClass;
use ReflectionNamedType;
use Spaze\SecurityTxt\Check\Exceptions\SecurityTxtCannotParseJsonException;
use Spaze\SecurityTxt\Check\SecurityTxtCheckHostResult;
use Spaze\SecurityTxt\Exceptions\SecurityTxtError;
use Spaze\SecurityTxt\Exceptions\SecurityTxtWarning;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotParseHostnameException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtFetcherException;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetchResult;
use Spaze\SecurityTxt\Fetcher\SecurityTxtRedirects;
use Spaze\SecurityTxt\Fields\SecurityTxtAcknowledgments;
use Spaze\SecurityTxt\Fields\SecurityTxtBugBounty;
use Spaze\SecurityTxt\Fields\SecurityTxtCanonical;
use Spaze\SecurityTxt\Fields\SecurityTxtContact;
use Spaze\SecurityTxt\Fields\SecurityTxtCsaf;
use Spaze\SecurityTxt\Fields\SecurityTxtEncryption;
use Spaze\SecurityTxt\Fields\SecurityTxtExpires;
use Spaze\SecurityTxt\Fields\SecurityTxtField;
use Spaze\SecurityTxt\Fields\SecurityTxtHiring;
use Spaze\SecurityTxt\Fields\SecurityTxtPolicy;
use Spaze\SecurityTxt\Fields\SecurityTxtPreferredLanguages;
use Spaze\SecurityTxt\Fields\SecurityTxtUriField;
use Spaze\SecurityTxt\Parser\SecurityTxtSplitLines;
use Spaze\SecurityTxt\SecurityTxt;
use Spaze\SecurityTxt\SecurityTxtHost;
use Spaze\SecurityTxt\SecurityTxtValidationLevel;
use Spaze\SecurityTxt\Signature\SecurityTxtSignatureVerifyResult;
use Spaze\SecurityTxt\Violations\SecurityTxtSpecViolation;
use Throwable;
use Uri\WhatWg\Url;
use ValueError;

final readonly class SecurityTxtJson
{

	/**
	 * What a stored result means, bumped when one stops being readable by the code that read the previous one. Not only the keys: an exception or a violation stores a class
	 * name and the arguments its constructor was called with, bar the `$previous` exception, which is never stored and so leaves a replayed exception unchained. A decoder
	 * replays by calling that constructor again, so renaming a class, reordering a parameter or adding a required one breaks a stored blob while `jsonSerialize()` goes on
	 * writing the same two keys. `SecurityTxtWireContractTest` pins those signatures so such a
	 * change has to be noticed here rather than in a consumer. Not the library version: this says
	 * nothing about which release wrote the blob, only whether this decoder understands its shape. A consumer wanting to know which release wrote it should carry that
	 * alongside itself, the installed version of this package say, because the two answer different questions.
	 *
	 * Bump it only when a stored blob genuinely stops being readable by the previous decoder, never to track a release, and measure that against decoders that shipped: a wire
	 * that never made a release has no stored blobs to protect, so a change there moves nothing. A reader already fails on a break it cannot handle,
	 * so the number costs nothing and turns `fetchResult is not set or not an array` into a sentence naming both versions; bumping it for a change an older reader could have
	 * tolerated is what would turn a benign upgrade into a forced deploy order. What a bump should mean for a decoder that could partly understand a newer blob is issue #107,
	 * and nothing here decides it.
	 */
	public const int FORMAT_VERSION = 1;


	public function __construct(private SecurityTxtSplitLines $splitLines)
	{
	}


	/**
	 * @param list<mixed> $violations
	 * @return list<SecurityTxtSpecViolation>
	 * @throws SecurityTxtCannotParseJsonException
	 */
	public function createViolationsFromJsonValues(array $violations): array
	{
		$objects = [];
		foreach ($violations as $violation) {
			if (!is_array($violation) || !isset($violation['class']) || !is_string($violation['class'])) {
				throw new SecurityTxtCannotParseJsonException('class is missing or not a string');
			} elseif (!class_exists($violation['class'])) {
				throw new SecurityTxtCannotParseJsonException("class {$violation['class']} doesn't exist");
			}
			if (!isset($violation['params']) || !is_array($violation['params'])) {
				throw new SecurityTxtCannotParseJsonException('params is missing or not an array');
			}
			$class = $violation['class'];
			if (!is_subclass_of($class, SecurityTxtSpecViolation::class)) {
				throw new SecurityTxtCannotParseJsonException(sprintf("class %s doesn't extend %s", $class, SecurityTxtSpecViolation::class));
			}
			$objects[] = $this->createObjectFromJsonParams($class, $violation['params']);
		}
		return $objects;
	}


	/**
	 * A chain comes back as the spellings a stored result carries, handed to `SecurityTxtRedirects` rather than to each reader as a bare array, so what a redirect reads as is
	 * decided once, by the chain, and a replayed result says what the one it was made from said. Not read back into `Url` objects here: a URL this library records need not
	 * have a spelling that parses, which is why the chain holds the strings, see `SecurityTxtRedirects`.
	 *
	 * @param array<array-key, mixed> $values
	 * @return array<string, SecurityTxtRedirects>
	 * @throws SecurityTxtCannotParseJsonException
	 */
	public function createRedirectsFromJsonValues(array $values): array
	{
		$redirects = [];
		foreach ($values as $url => $urlRedirects) {
			if (!is_string($url)) {
				throw new SecurityTxtCannotParseJsonException(sprintf('redirects key is of type %s, not a string', get_debug_type($url)));
			}
			if (!is_array($urlRedirects)) {
				throw new SecurityTxtCannotParseJsonException("redirects > {$url} is not an array");
			}
			$urls = [];
			foreach ($urlRedirects as $urlRedirect) {
				if (!is_string($urlRedirect)) {
					throw new SecurityTxtCannotParseJsonException('redirects contains an item which is not a string');
				}
				$urls[] = $urlRedirect;
			}
			$redirects[$url] = new SecurityTxtRedirects(...$urls);
		}
		return $redirects;
	}


	/**
	 * @param array<array-key, mixed> $values
	 * @return SecurityTxt
	 * @throws SecurityTxtCannotParseJsonException
	 */
	public function createSecurityTxtFromJsonValues(array $values): SecurityTxt
	{
		$securityTxt = new SecurityTxt(SecurityTxtValidationLevel::AllowInvalidValuesSilently);
		try {
			if (isset($values['fileLocation'])) {
				if (!is_string($values['fileLocation'])) {
					throw new SecurityTxtCannotParseJsonException('fileLocation is not a string');
				}
				$securityTxt->setFileLocation($values['fileLocation']);
			}
			if (isset($values['fields'])) {
				if (!is_array($values['fields'])) {
					throw new SecurityTxtCannotParseJsonException('fields is not an array');
				}
				foreach ($values['fields'] as $key => $field) {
					if (!is_array($field)) {
						throw new SecurityTxtCannotParseJsonException('fields is not an array of arrays');
					}
					if (count($field) !== 1) {
						throw new SecurityTxtCannotParseJsonException("fields > {$key} must be a single-entry map");
					}
					foreach ($field as $name => $value) {
						if (!is_string($name)) {
							throw new SecurityTxtCannotParseJsonException('field name is not a string');
						}
						if (SecurityTxtField::tryFrom($name) === null) {
							throw new SecurityTxtCannotParseJsonException("fields > {$name} is an unsupported field");
						}
						if ($name === SecurityTxtField::Acknowledgments->value) {
							$this->addSecurityTxtUriField($name, $value, SecurityTxtAcknowledgments::class, $securityTxt->addAcknowledgments(...));
						} elseif ($name === SecurityTxtField::BugBounty->value) {
							if (!is_array($value)) {
								throw new SecurityTxtCannotParseJsonException("fields > {$name} is not an array");
							}
							if (!isset($value['rewards']) || !is_bool($value['rewards'])) {
								throw new SecurityTxtCannotParseJsonException("fields > {$name} > rewards is missing or not a bool");
							}
							$securityTxt->setBugBounty(new SecurityTxtBugBounty($value['rewards']));
						} elseif ($name === SecurityTxtField::Canonical->value) {
							$this->addSecurityTxtUriField($name, $value, SecurityTxtCanonical::class, $securityTxt->addCanonical(...));
						} elseif ($name === SecurityTxtField::Contact->value) {
							$this->addSecurityTxtUriField($name, $value, SecurityTxtContact::class, $securityTxt->addContact(...));
						} elseif ($name === SecurityTxtField::Csaf->value) {
							$this->addSecurityTxtUriField($name, $value, SecurityTxtCsaf::class, $securityTxt->addCsaf(...));
						} elseif ($name === SecurityTxtField::Encryption->value) {
							$this->addSecurityTxtUriField($name, $value, SecurityTxtEncryption::class, $securityTxt->addEncryption(...));
						} elseif ($name === SecurityTxtField::Expires->value) {
							if (!is_array($value)) {
								throw new SecurityTxtCannotParseJsonException("fields > {$name} is not an array");
							} elseif (!isset($value['dateTime']) || !is_string($value['dateTime'])) {
								throw new SecurityTxtCannotParseJsonException("fields > {$name} > dateTime is missing or not a string");
							} elseif (!isset($value['isExpired']) || !is_bool($value['isExpired'])) {
								throw new SecurityTxtCannotParseJsonException("fields > {$name} > isExpired is missing or not a bool");
							} elseif (!isset($value['inDays']) || !is_int($value['inDays'])) {
								throw new SecurityTxtCannotParseJsonException("fields > {$name} > inDays is missing or not an int");
							}
							try {
								$dateTime = new DateTimeImmutable($value['dateTime']);
							} catch (DateMalformedStringException $e) {
								throw new SecurityTxtCannotParseJsonException("fields > {$name} > dateTime is wrong format", $e);
							}
							$securityTxt->setExpires(new SecurityTxtExpires($dateTime, $value['isExpired'], $value['inDays']));
						} elseif ($name === SecurityTxtField::Hiring->value) {
							$this->addSecurityTxtUriField($name, $value, SecurityTxtHiring::class, $securityTxt->addHiring(...));
						} elseif ($name === SecurityTxtField::Policy->value) {
							$this->addSecurityTxtUriField($name, $value, SecurityTxtPolicy::class, $securityTxt->addPolicy(...));
						} elseif ($name === SecurityTxtField::PreferredLanguages->value) {
							if (!is_array($value)) {
								throw new SecurityTxtCannotParseJsonException("fields > {$name} is not an array");
							} elseif (!isset($value['languages']) || !is_array($value['languages'])) {
								throw new SecurityTxtCannotParseJsonException("fields > {$name} > languages is missing or not an array");
							}
							$languages = [];
							foreach ($value['languages'] as $language) {
								if (!is_string($language)) {
									throw new SecurityTxtCannotParseJsonException("fields > {$name} > languages contains an item which is not a string");
								}
								$languages[] = $language;
							}
							$securityTxt->setPreferredLanguages(new SecurityTxtPreferredLanguages($languages));
						}
					}
				}
			}
			if (isset($values['signatureVerifyResult'])) {
				if (!is_array($values['signatureVerifyResult'])) {
					throw new SecurityTxtCannotParseJsonException('signatureVerifyResult is not an array');
				} elseif (
					!isset($values['signatureVerifyResult']['keyFingerprint'])
					|| !is_string($values['signatureVerifyResult']['keyFingerprint'])
				) {
					throw new SecurityTxtCannotParseJsonException('signatureVerifyResult > keyFingerprint is missing or not a string');
				} elseif (
					!isset($values['signatureVerifyResult']['dateTime'])
					|| !is_string($values['signatureVerifyResult']['dateTime'])
				) {
					throw new SecurityTxtCannotParseJsonException('signatureVerifyResult > dateTime is missing or not a string');
				}
				try {
					$dateTime = new DateTimeImmutable($values['signatureVerifyResult']['dateTime']);
				} catch (DateMalformedStringException $e) {
					throw new SecurityTxtCannotParseJsonException('signatureVerifyResult > dateTime is wrong format', $e);
				}
				$securityTxt = $securityTxt->withSignatureVerifyResult(new SecurityTxtSignatureVerifyResult($values['signatureVerifyResult']['keyFingerprint'], $dateTime));
			}
		} catch (SecurityTxtError | SecurityTxtWarning $e) {
			throw new SecurityTxtCannotParseJsonException($e->getMessage(), $e);
		}
		return $securityTxt;
	}


	/**
	 * @template T of SecurityTxtUriField
	 * @param string $field
	 * @param mixed $value
	 * @param class-string<T> $class
	 * @param callable(T): void $addField
	 * @throws SecurityTxtCannotParseJsonException
	 */
	private function addSecurityTxtUriField(string $field, mixed $value, string $class, callable $addField): void
	{
		if (!is_array($value)) {
			throw new SecurityTxtCannotParseJsonException("fields > {$field} is not an array");
		}
		if (!isset($value['uri']) || !is_string($value['uri'])) {
			throw new SecurityTxtCannotParseJsonException("fields > {$field} > uri is missing or not a string");
		}
		$addField(new $class($value['uri']));
	}


	/**
	 * @param array<array-key, mixed> $values
	 * @throws SecurityTxtCannotParseJsonException
	 */
	public function createCheckHostResultFromJsonValues(array $values): SecurityTxtCheckHostResult
	{
		if (!isset($values['class']) || !is_string($values['class'])) {
			throw new SecurityTxtCannotParseJsonException('class is not set or not a string');
		}
		if ($values['class'] !== SecurityTxtCheckHostResult::class) {
			throw new SecurityTxtCannotParseJsonException('class is not ' . SecurityTxtCheckHostResult::class);
		}
		$this->checkFormatVersion($values);
		if (!isset($values['host']) || !is_string($values['host'])) {
			throw new SecurityTxtCannotParseJsonException('host is not set or not a string');
		}
		try {
			$host = $this->createStoredHost($values['host']);
		} catch (SecurityTxtCannotParseHostnameException $e) {
			throw new SecurityTxtCannotParseJsonException('host is not a hostname', $e);
		}
		if (!isset($values['fetchResult']) || !is_array($values['fetchResult'])) {
			throw new SecurityTxtCannotParseJsonException('fetchResult is not set or not an array');
		}
		if (!isset($values['fetchErrors']) || !is_array($values['fetchErrors'])) {
			throw new SecurityTxtCannotParseJsonException('fetchErrors is not set or not an array');
		}
		if (!isset($values['fetchWarnings']) || !is_array($values['fetchWarnings'])) {
			throw new SecurityTxtCannotParseJsonException('fetchWarnings is not set or not an array');
		}
		if (!isset($values['lineErrors']) || !is_array($values['lineErrors'])) {
			throw new SecurityTxtCannotParseJsonException('lineErrors is not set or not an array');
		}
		$lineErrors = [];
		foreach ($values['lineErrors'] as $errorLine => $errors) {
			if (!is_int($errorLine)) {
				throw new SecurityTxtCannotParseJsonException("lineErrors > {$errorLine} key is not an int");
			}
			if ($errorLine < 1) {
				throw new SecurityTxtCannotParseJsonException("lineErrors > {$errorLine} key is less than 1");
			}
			if (!is_array($errors)) {
				throw new SecurityTxtCannotParseJsonException("lineErrors > {$errorLine} is not an array");
			}
			$lineErrors[$errorLine] = $this->createViolationsFromJsonValues(array_values($errors));
		}
		if (!isset($values['lineWarnings']) || !is_array($values['lineWarnings'])) {
			throw new SecurityTxtCannotParseJsonException('lineWarnings is not set or not an array');
		}
		$lineWarnings = [];
		foreach ($values['lineWarnings'] as $warningLine => $warnings) {
			if (!is_int($warningLine)) {
				throw new SecurityTxtCannotParseJsonException("lineWarnings > {$warningLine} key is not an int");
			}
			if ($warningLine < 1) {
				throw new SecurityTxtCannotParseJsonException("lineWarnings > {$warningLine} key is less than 1");
			}
			if (!is_array($warnings)) {
				throw new SecurityTxtCannotParseJsonException("lineWarnings > {$warningLine} is not an array");
			}
			$lineWarnings[$warningLine] = $this->createViolationsFromJsonValues(array_values($warnings));
		}
		if (!isset($values['fileErrors']) || !is_array($values['fileErrors'])) {
			throw new SecurityTxtCannotParseJsonException('fileErrors is not set or not an array');
		}
		if (!isset($values['fileWarnings']) || !is_array($values['fileWarnings'])) {
			throw new SecurityTxtCannotParseJsonException('fileWarnings is not set or not an array');
		}
		if (!isset($values['securityTxt']) || !is_array($values['securityTxt'])) {
			throw new SecurityTxtCannotParseJsonException('securityTxt is not set or not an array');
		}
		if (isset($values['expired'])) {
			if (!is_bool($values['expired'])) {
				throw new SecurityTxtCannotParseJsonException('expired is not a bool');
			}
			$expired = $values['expired'];
		}
		if (isset($values['expiryDays'])) {
			if (!is_int($values['expiryDays'])) {
				throw new SecurityTxtCannotParseJsonException('expiryDays is not an int');
			}
			$expiryDays = $values['expiryDays'];
		}
		if (!isset($values['valid']) || !is_bool($values['valid'])) {
			throw new SecurityTxtCannotParseJsonException('valid is not set or not a bool');
		}
		if (!isset($values['strictMode']) || !is_bool($values['strictMode'])) {
			throw new SecurityTxtCannotParseJsonException('strictMode is not set or not a bool');
		}
		if (isset($values['expiresWarningThreshold'])) {
			if (!is_int($values['expiresWarningThreshold'])) {
				throw new SecurityTxtCannotParseJsonException('expiresWarningThreshold is not an int');
			}
			$expiresWarningThreshold = $values['expiresWarningThreshold'];
		}
		return new SecurityTxtCheckHostResult(
			$host,
			$this->createFetchResultFromJsonValues($values['fetchResult']),
			$this->createViolationsFromJsonValues(array_values($values['fetchErrors'])),
			$this->createViolationsFromJsonValues(array_values($values['fetchWarnings'])),
			$lineErrors,
			$lineWarnings,
			$this->createViolationsFromJsonValues(array_values($values['fileErrors'])),
			$this->createViolationsFromJsonValues(array_values($values['fileWarnings'])),
			$this->createSecurityTxtFromJsonValues($values['securityTxt']),
			$expired ?? null,
			$expiryDays ?? null,
			$values['valid'],
			$values['strictMode'],
			$expiresWarningThreshold ?? null,
		);
	}


	/**
	 * @param array<array-key, mixed> $values
	 * @throws SecurityTxtCannotParseJsonException
	 */
	public function createFetchResultFromJsonValues(array $values): SecurityTxtFetchResult
	{
		if (!isset($values['class']) || !is_string($values['class'])) {
			throw new SecurityTxtCannotParseJsonException('class is not a string');
		}
		if ($values['class'] !== SecurityTxtFetchResult::class) {
			throw new SecurityTxtCannotParseJsonException('class is not ' . SecurityTxtFetchResult::class);
		}
		$this->checkFormatVersion($values);
		if (!isset($values['constructedUrl']) || !is_string($values['constructedUrl'])) {
			throw new SecurityTxtCannotParseJsonException('constructedUrl is not a string');
		}
		$constructedUrl = $this->createUrlFromJsonValue($values['constructedUrl'], 'constructedUrl');
		if (!isset($values['finalUrl']) || !is_string($values['finalUrl'])) {
			throw new SecurityTxtCannotParseJsonException('finalUrl is not a string');
		}
		$finalUrl = $this->createUrlFromJsonValue($values['finalUrl'], 'finalUrl');
		if (!isset($values['redirects']) || !is_array($values['redirects'])) {
			throw new SecurityTxtCannotParseJsonException('redirects is not an array');
		}
		$redirects = $this->createRedirectsFromJsonValues($values['redirects']);
		if (!isset($values['contents']) || !is_string($values['contents'])) {
			throw new SecurityTxtCannotParseJsonException('contents is not a string');
		}
		if (!isset($values['isTruncated']) || !is_bool($values['isTruncated'])) {
			throw new SecurityTxtCannotParseJsonException('isTruncated is not a bool');
		}
		if (!isset($values['errors']) || !is_array($values['errors'])) {
			throw new SecurityTxtCannotParseJsonException('errors is not an array');
		}
		if (!isset($values['warnings']) || !is_array($values['warnings'])) {
			throw new SecurityTxtCannotParseJsonException('warnings is not an array');
		}
		return new SecurityTxtFetchResult(
			$constructedUrl,
			$finalUrl,
			$redirects,
			$values['contents'],
			$values['isTruncated'],
			$this->splitLines->splitLines($values['contents']),
			$this->createViolationsFromJsonValues(array_values($values['errors'])),
			$this->createViolationsFromJsonValues(array_values($values['warnings'])),
		);
	}


	/**
	 * @param array<array-key, mixed> $values
	 * @throws SecurityTxtCannotParseJsonException
	 */
	private function checkFormatVersion(array $values): void
	{
		if (!isset($values['formatVersion'])) {
			return;
		}
		if (!is_int($values['formatVersion'])) {
			throw new SecurityTxtCannotParseJsonException('formatVersion is not an int');
		}
		if ($values['formatVersion'] > self::FORMAT_VERSION) {
			throw new SecurityTxtCannotParseJsonException(sprintf('formatVersion is %s, this version reads up to %s', $values['formatVersion'], self::FORMAT_VERSION));
		}
	}


	/**
	 * The same rule as the constructor params, said as this caller reports a bad value. One rule, because a URL stored in a field and the same URL stored as a param are the
	 * same question, and two answers to it meant a spelling accepted in one place and refused in the other.
	 */
	private function createUrlFromJsonValue(string $value, string $field): Url
	{
		try {
			return $this->createStoredUrl($value);
		} catch (ValueError) {
			throw new SecurityTxtCannotParseJsonException("{$field} is not a URL");
		}
	}


	/**
	 * @param array<array-key, mixed> $values
	 * @throws SecurityTxtCannotParseJsonException
	 */
	public function createFetcherExceptionFromJsonValues(array $values): SecurityTxtFetcherException
	{
		if (
			!isset($values['error'])
			|| !is_array($values['error'])
			|| !isset($values['error']['class'])
			|| !is_string($values['error']['class'])
			|| !class_exists($values['error']['class'])
		) {
			throw new SecurityTxtCannotParseJsonException('error > class is missing, not a string or not an existing class');
		}
		if (!isset($values['error']['params']) || !is_array($values['error']['params'])) {
			throw new SecurityTxtCannotParseJsonException('error > params is missing or not an array');
		}
		$class = $values['error']['class'];
		if (!is_subclass_of($class, SecurityTxtFetcherException::class)) {
			throw new SecurityTxtCannotParseJsonException(sprintf('The exception class %s is not a subclass of %s', $class, SecurityTxtFetcherException::class));
		}
		return $this->createObjectFromJsonParams($class, $values['error']['params']);
	}


	/**
	 * @template T of object
	 * @param class-string<T> $class
	 * @param array<array-key, mixed> $params
	 * @return T
	 * @throws SecurityTxtCannotParseJsonException
	 */
	private function createObjectFromJsonParams(string $class, array $params): object
	{
		try {
			return new $class(...$this->createConstructorArguments($class, $params));
		} catch (Throwable $e) {
			throw new SecurityTxtCannotParseJsonException("Cannot create an object of class {$class}", previous: $e);
		}
	}


	/**
	 * The wire stays scalar, and the way back is decided by what each constructor parameter is typed as: a `SecurityTxtHost` is rebuilt from the name it reads as, a `Url`
	 * from the spelling the wire carries, a backed enum from a case value. Both run inside the caller's try, so a name that rebuilds a different host or a value naming no case fails as the class it was meant for, the
	 * same way any other bad param does. A host that cannot be rebuilt takes the whole stored error down rather than degrading into one that reads encoded, which was one
	 * host reading as two things: refuse what cannot be rebuilt is the rule `SecurityTxtHost` itself follows, and a refused result is a cache miss to check again. A string
	 * key is left to the spread, which reads it as a named argument, so it selects the parameter here the same way it does there.
	 *
	 * @param class-string $class
	 * @param array<array-key, mixed> $params
	 * @return array<array-key, mixed>
	 */
	private function createConstructorArguments(string $class, array $params): array
	{
		$constructor = (new ReflectionClass($class))->getConstructor();
		if ($constructor === null) {
			return $params;
		}
		$types = [];
		foreach ($constructor->getParameters() as $position => $parameter) {
			$type = $parameter->getType();
			if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
				$types[$position] = $types[$parameter->getName()] = $type->getName();
			}
		}
		// The spread ignores what an integer key says and feeds those values in iteration order, so a key has to mean here what it will mean there: an integer key becomes
		// the position the spread will actually give the value, `['3' => 'c', '0' => 'a']` types and calls as `['c', 'a']`, and a string key keeps naming its parameter
		$arguments = [];
		$position = 0;
		foreach ($params as $key => $value) {
			$key = is_int($key) ? $position++ : $key;
			$type = $types[$key] ?? null;
			if ($type === SecurityTxtHost::class && is_string($value)) {
				$value = $this->createStoredHost($value);
			} elseif ($type === Url::class && is_string($value)) {
				$value = $this->createStoredUrl($value);
			} elseif ($type === SecurityTxtRedirects::class && is_array($value)) {
				foreach ($value as $redirect) {
					if (!is_string($redirect)) {
						throw new ValueError(sprintf('a redirect is of type %s, not a string', get_debug_type($redirect)));
					}
				}
				$value = new SecurityTxtRedirects(...$value);
			} elseif ($type !== null && is_subclass_of($type, BackedEnum::class) && (is_int($value) || is_string($value))) {
				$value = $type::from($value);
			}
			$arguments[$key] = $value;
		}
		return $arguments;
	}


	/**
	 * A host out of a stored result, the inverse of the `getUnicode()` that wrote it, refused rather than rewritten for the same reason as a URL: a value that reads back as
	 * something other than itself, `808` being the IP address `0.0.3.40`, would replay as a host nobody stored. Parsed under HTTPS, like the fetcher fetches, so a host comes
	 * out the same whether it lived through a check or through JSON.
	 *
	 * @throws SecurityTxtCannotParseHostnameException
	 */
	private function createStoredHost(string $host): SecurityTxtHost
	{
		$url = Url::parse("https://{$host}");
		if ($url === null) {
			throw new SecurityTxtCannotParseHostnameException($host);
		}
		try {
			$self = new SecurityTxtHost($url);
		} catch (SecurityTxtCannotParseHostnameException $e) {
			// The constructor names the URL it was handed, which is one this method derived; a caller of this one asked about a host and gets told about that host
			throw new SecurityTxtCannotParseHostnameException($host, $e);
		}
		if ($self->getUnicode() !== $host) {
			throw new SecurityTxtCannotParseHostnameException($host);
		}
		return $self;
	}


	/**
	 * A URL out of the stored params, refused rather than rewritten, like a host: a value that serializes back as something else would replay as a URL nobody stored. Either
	 * canonical spelling counts, since a result stored before the wire carried A-labels holds the readable one.
	 */
	private function createStoredUrl(string $value): Url
	{
		$url = Url::parse($value);
		if ($url === null || ($url->toAsciiString() !== $value && $url->toUnicodeString() !== $value)) {
			throw new ValueError(sprintf('%s is not a URL as this library writes one', $value));
		}
		return $url;
	}

}
