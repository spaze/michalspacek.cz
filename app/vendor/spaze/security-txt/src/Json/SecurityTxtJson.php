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
use Spaze\SecurityTxt\Fields\SecurityTxtCanonical;
use Spaze\SecurityTxt\Fields\SecurityTxtContact;
use Spaze\SecurityTxt\Fields\SecurityTxtEncryption;
use Spaze\SecurityTxt\Fields\SecurityTxtExpires;
use Spaze\SecurityTxt\Fields\SecurityTxtHiring;
use Spaze\SecurityTxt\Fields\SecurityTxtPolicy;
use Spaze\SecurityTxt\Fields\SecurityTxtPreferredLanguages;
use Spaze\SecurityTxt\Fields\SecurityTxtUriField;
use Spaze\SecurityTxt\Parser\SecurityTxtSplitLines;
use Spaze\SecurityTxt\SecurityTxt;
use Spaze\SecurityTxt\SecurityTxtValidationLevel;
use Spaze\SecurityTxt\Signature\SecurityTxtSignatureVerifyResult;
use Spaze\SecurityTxt\Violations\SecurityTxtSpecViolation;

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
			$object = new $violation['class'](...$violation['params']);
			if (!$object instanceof SecurityTxtSpecViolation) {
				throw new SecurityTxtCannotParseJsonException(sprintf("class %s doesn't extend %s", $violation['class'], SecurityTxtSpecViolation::class));
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
				throw new SecurityTxtCannotParseJsonException(sprintf('redirects key is a %s not a string', get_debug_type($url)));
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
	 * @param array<string, mixed> $values
	 * @return SecurityTxt
	 * @throws SecurityTxtCannotParseJsonException
	 */
	public function createSecurityTxtFromJsonValues(array $values): SecurityTxt
	{
		$securityTxt = new SecurityTxt(SecurityTxtValidationLevel::AllowInvalidValuesSilently);
		try {
			if (isset($values['expires'])) {
				if (!is_array($values['expires'])) {
					throw new SecurityTxtCannotParseJsonException('expires is not an array');
				} elseif (!isset($values['expires']['dateTime']) || !is_string($values['expires']['dateTime'])) {
					throw new SecurityTxtCannotParseJsonException('expires > dateTime is missing or not a string');
				}
				try {
					$dateTime = new DateTimeImmutable($values['expires']['dateTime']);
				} catch (DateMalformedStringException $e) {
					throw new SecurityTxtCannotParseJsonException('expires > dateTime is wrong format', $e);
				}
				if (!is_bool($values['expires']['isExpired'])) {
					throw new SecurityTxtCannotParseJsonException('expires > isExpired is not a bool');
				}
				if (!is_int($values['expires']['inDays'])) {
					throw new SecurityTxtCannotParseJsonException('expires > inDays is not an int');
				}
				$securityTxt->setExpires(new SecurityTxtExpires($dateTime, $values['expires']['isExpired'], $values['expires']['inDays']));
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
			if (isset($values['preferredLanguages'])) {
				if (!is_array($values['preferredLanguages'])) {
					throw new SecurityTxtCannotParseJsonException('preferredLanguages is not an array');
				} elseif (
					!isset($values['preferredLanguages']['languages'])
					|| !is_array($values['preferredLanguages']['languages'])
				) {
					throw new SecurityTxtCannotParseJsonException('preferredLanguages > languages is missing or not an array');
				}
				$languages = [];
				foreach ($values['preferredLanguages']['languages'] as $language) {
					if (!is_string($language)) {
						throw new SecurityTxtCannotParseJsonException('preferredLanguages > languages contains an item which is not a string');
					}
					$languages[] = $language;
				}
				$securityTxt->setPreferredLanguages(new SecurityTxtPreferredLanguages($languages));
			}
			$this->addSecurityTxtUriField($values, 'canonical', SecurityTxtCanonical::class, $securityTxt->addCanonical(...));
			$this->addSecurityTxtUriField($values, 'contact', SecurityTxtContact::class, $securityTxt->addContact(...));
			$this->addSecurityTxtUriField($values, 'acknowledgments', SecurityTxtAcknowledgments::class, $securityTxt->addAcknowledgments(...));
			$this->addSecurityTxtUriField($values, 'hiring', SecurityTxtHiring::class, $securityTxt->addHiring(...));
			$this->addSecurityTxtUriField($values, 'policy', SecurityTxtPolicy::class, $securityTxt->addPolicy(...));
			$this->addSecurityTxtUriField($values, 'encryption', SecurityTxtEncryption::class, $securityTxt->addEncryption(...));
		} catch (SecurityTxtError | SecurityTxtWarning $e) {
			throw new SecurityTxtCannotParseJsonException($e->getMessage(), $e);
		}
		return $securityTxt;
	}


	/**
	 * @template T of SecurityTxtUriField
	 * @param array<array-key, mixed> $values
	 * @param callable(T): void $addField
	 * @param class-string<T> $class
	 * @throws SecurityTxtCannotParseJsonException
	 */
	private function addSecurityTxtUriField(array $values, string $field, string $class, callable $addField): void
	{
		if (!is_array($values[$field])) {
			throw new SecurityTxtCannotParseJsonException("Field {$field} is not an array");
		}
		foreach ($values[$field] as $value) {
			if (!is_array($value)) {
				throw new SecurityTxtCannotParseJsonException("{$field} is not an array");
			} elseif (!isset($value['uri']) || !is_string($value['uri'])) {
				throw new SecurityTxtCannotParseJsonException("{$field} > uri is missing or not a string");
			}
			$addField(new $class($value['uri']));
		}
	}


	/**
	 * @param array<array-key, mixed> $values
	 * @throws SecurityTxtCannotParseJsonException
	 */
	public function createCheckHostResultFromJsonValues(array $values): SecurityTxtCheckHostResult
	{
		if (!is_string($values['class'])) {
			throw new SecurityTxtCannotParseJsonException('class is not a string');
		}
		if ($values['class'] !== SecurityTxtCheckHostResult::class) {
			throw new SecurityTxtCannotParseJsonException('class is not ' . SecurityTxtCheckHostResult::class);
		}
		if (!is_string($values['host'])) {
			throw new SecurityTxtCannotParseJsonException('host is not a string');
		}
		if (!is_array($values['fetchResult'])) {
			throw new SecurityTxtCannotParseJsonException('fetchResult is not an array');
		}
		if (!is_array($values['fetchErrors'])) {
			throw new SecurityTxtCannotParseJsonException('fetchErrors is not an array');
		}
		if (!is_array($values['fetchWarnings'])) {
			throw new SecurityTxtCannotParseJsonException('fetchWarnings is not an array');
		}
		if (!is_array($values['lineErrors'])) {
			throw new SecurityTxtCannotParseJsonException('lineErrors is not an array');
		}
		$lineErrors = [];
		foreach ($values['lineErrors'] as $line => $violations) {
			if (!is_int($line)) {
				throw new SecurityTxtCannotParseJsonException("lineErrors > {$line} key is not an int");
			}
			if ($line < 1) {
				throw new SecurityTxtCannotParseJsonException("lineErrors > {$line} is less than 1");
			}
			if (!is_array($violations)) {
				throw new SecurityTxtCannotParseJsonException("lineErrors > {$line} is not an array");
			}
			$lineErrors[$line] = $this->createViolationsFromJsonValues(array_values($violations));
		}
		if (!is_array($values['lineWarnings'])) {
			throw new SecurityTxtCannotParseJsonException('lineWarnings is not an array');
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
		if (!is_array($values['fileErrors'])) {
			throw new SecurityTxtCannotParseJsonException('fileErrors is not an array');
		}
		if (!is_array($values['fileWarnings'])) {
			throw new SecurityTxtCannotParseJsonException('fileWarnings is not an array');
		}
		if (!is_array($values['securityTxt'])) {
			throw new SecurityTxtCannotParseJsonException('securityTxt is not an array');
		}
		$securityTxtFields = [];
		foreach ($values['securityTxt'] as $field => $fieldValues) {
			if (!is_string($field)) {
				throw new SecurityTxtCannotParseJsonException("securityTxt > {$field} key is not a string");
			}
			if ($fieldValues !== null && !is_array($fieldValues)) {
				throw new SecurityTxtCannotParseJsonException("securityTxt > {$field} is not an array");
			}
			$securityTxtFields[$field] = $fieldValues;
		}
		if ($values['expired'] !== null && !is_bool($values['expired'])) {
			throw new SecurityTxtCannotParseJsonException('expired is not an int');
		}
		if ($values['expiryDays'] !== null && !is_int($values['expiryDays'])) {
			throw new SecurityTxtCannotParseJsonException('expiryDays is not an int');
		}
		if (!is_bool($values['valid'])) {
			throw new SecurityTxtCannotParseJsonException('valid is not a bool');
		}
		if (!is_bool($values['strictMode'])) {
			throw new SecurityTxtCannotParseJsonException('strictMode is not a bool');
		}
		if ($values['expiresWarningThreshold'] !== null && !is_int($values['expiresWarningThreshold'])) {
			throw new SecurityTxtCannotParseJsonException('expiresWarningThreshold is not an int');
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
			$this->createSecurityTxtFromJsonValues($securityTxtFields),
			$values['expired'],
			$values['expiryDays'],
			$values['valid'],
			$values['strictMode'],
			$values['expiresWarningThreshold'],
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
		$exception = new $values['error']['class'](...$values['error']['params']);
		if (!$exception instanceof SecurityTxtFetcherException) {
			throw new SecurityTxtCannotParseJsonException(sprintf('The exception is %s, not %s', $exception::class, SecurityTxtFetcherException::class));
		}
		return $exception;
	}

}
