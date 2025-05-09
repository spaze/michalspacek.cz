<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Json;

use DateTimeImmutable;
use Exception;
use Spaze\SecurityTxt\Check\Exceptions\SecurityTxtCannotParseJsonException;
use Spaze\SecurityTxt\Exceptions\SecurityTxtError;
use Spaze\SecurityTxt\Exceptions\SecurityTxtWarning;
use Spaze\SecurityTxt\Fields\SecurityTxtAcknowledgments;
use Spaze\SecurityTxt\Fields\SecurityTxtCanonical;
use Spaze\SecurityTxt\Fields\SecurityTxtContact;
use Spaze\SecurityTxt\Fields\SecurityTxtEncryption;
use Spaze\SecurityTxt\Fields\SecurityTxtExpires;
use Spaze\SecurityTxt\Fields\SecurityTxtHiring;
use Spaze\SecurityTxt\Fields\SecurityTxtPolicy;
use Spaze\SecurityTxt\Fields\SecurityTxtPreferredLanguages;
use Spaze\SecurityTxt\Fields\SecurityTxtUriField;
use Spaze\SecurityTxt\SecurityTxt;
use Spaze\SecurityTxt\SecurityTxtValidationLevel;
use Spaze\SecurityTxt\Signature\SecurityTxtSignatureVerifyResult;
use Spaze\SecurityTxt\Violations\SecurityTxtSpecViolation;

final class SecurityTxtJson
{

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
	public function createFromJsonValues(array $values): SecurityTxt
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
				} catch (Exception $e) {
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
				} catch (Exception $e) {
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

}
