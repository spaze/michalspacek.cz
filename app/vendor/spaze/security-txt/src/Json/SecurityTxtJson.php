<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Json;

use DateMalformedStringException;
use DateTimeImmutable;
use Spaze\SecurityTxt\Check\Exceptions\SecurityTxtCannotParseJsonException;
use Spaze\SecurityTxt\Check\SecurityTxtCheckHostResult;
use Spaze\SecurityTxt\Exceptions\SecurityTxtError;
use Spaze\SecurityTxt\Exceptions\SecurityTxtWarning;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtFetcherException;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetchResult;
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
use Spaze\SecurityTxt\SecurityTxtValidationLevel;
use Spaze\SecurityTxt\Signature\SecurityTxtSignatureVerifyResult;
use Spaze\SecurityTxt\Violations\SecurityTxtSpecViolation;
use Throwable;

final readonly class SecurityTxtJson
{

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
			try {
				$object = new $class(...$violation['params']);
			} catch (Throwable $e) {
				throw new SecurityTxtCannotParseJsonException("Cannot create an object of class {$class}", previous: $e);
			}
			$objects[] = $object;
		}
		return $objects;
	}


	/**
	 * @param array<array-key, mixed> $values
	 * @return array<string, list<string>>
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
			foreach ($urlRedirects as $urlRedirect) {
				if (!is_string($urlRedirect)) {
					throw new SecurityTxtCannotParseJsonException('redirects contains an item which is not a string');
				}
				$redirects[$url][] = $urlRedirect;
			}
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
		if (!isset($values['host']) || !is_string($values['host'])) {
			throw new SecurityTxtCannotParseJsonException('host is not set or not a string');
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
		foreach ($values['lineErrors'] as $line => $violations) {
			if (!is_int($line)) {
				throw new SecurityTxtCannotParseJsonException("lineErrors > {$line} key is not an int");
			}
			if ($line < 1) {
				throw new SecurityTxtCannotParseJsonException("lineErrors > {$line} key is less than 1");
			}
			if (!is_array($violations)) {
				throw new SecurityTxtCannotParseJsonException("lineErrors > {$line} is not an array");
			}
			$lineErrors[$line] = $this->createViolationsFromJsonValues(array_values($violations));
		}
		if (!isset($values['lineWarnings']) || !is_array($values['lineWarnings'])) {
			throw new SecurityTxtCannotParseJsonException('lineWarnings is not set or not an array');
		}
		$lineWarnings = [];
		foreach ($values['lineWarnings'] as $line => $violations) {
			if (!is_int($line)) {
				throw new SecurityTxtCannotParseJsonException("lineWarnings > {$line} key is not an int");
			}
			if ($line < 1) {
				throw new SecurityTxtCannotParseJsonException("lineWarnings > {$line} key is less than 1");
			}
			if (!is_array($violations)) {
				throw new SecurityTxtCannotParseJsonException("lineWarnings > {$line} is not an array");
			}
			$lineWarnings[$line] = $this->createViolationsFromJsonValues(array_values($violations));
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
			$values['host'],
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
		if (!isset($values['constructedUrl']) || !is_string($values['constructedUrl'])) {
			throw new SecurityTxtCannotParseJsonException('constructedUrl is not a string');
		}
		if (!isset($values['finalUrl']) || !is_string($values['finalUrl'])) {
			throw new SecurityTxtCannotParseJsonException('finalUrl is not a string');
		}
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
			$values['constructedUrl'],
			$values['finalUrl'],
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
		try {
			$exception = new $class(...$values['error']['params']);
		} catch (Throwable $e) {
			throw new SecurityTxtCannotParseJsonException("Cannot create an object of class {$class}", previous: $e);
		}
		return $exception;
	}

}
