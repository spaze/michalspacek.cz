<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser;

use LogicException;
use Spaze\SecurityTxt\Exceptions\SecurityTxtError;
use Spaze\SecurityTxt\Exceptions\SecurityTxtWarning;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotOpenUrlException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotReadUrlException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostIpAddressInvalidTypeException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostIpAddressNotFoundException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostNotFoundException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtNoHttpCodeException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtNoLocationHeaderException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtNotFoundException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtOnlyIpv6HostButIpv6DisabledException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtTooManyRedirectsException;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetcher;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetchResult;
use Spaze\SecurityTxt\Fields\SecurityTxtExpiresFactory;
use Spaze\SecurityTxt\Fields\SecurityTxtField;
use Spaze\SecurityTxt\Parser\FieldProcessors\AcknowledgmentsAddFieldValue;
use Spaze\SecurityTxt\Parser\FieldProcessors\CanonicalAddFieldValue;
use Spaze\SecurityTxt\Parser\FieldProcessors\ContactAddFieldValue;
use Spaze\SecurityTxt\Parser\FieldProcessors\EncryptionAddFieldValue;
use Spaze\SecurityTxt\Parser\FieldProcessors\ExpiresCheckMultipleFields;
use Spaze\SecurityTxt\Parser\FieldProcessors\ExpiresSetFieldValue;
use Spaze\SecurityTxt\Parser\FieldProcessors\FieldProcessor;
use Spaze\SecurityTxt\Parser\FieldProcessors\HiringAddFieldValue;
use Spaze\SecurityTxt\Parser\FieldProcessors\PolicyAddFieldValue;
use Spaze\SecurityTxt\Parser\FieldProcessors\PreferredLanguagesCheckMultipleFields;
use Spaze\SecurityTxt\Parser\FieldProcessors\PreferredLanguagesSetFieldValue;
use Spaze\SecurityTxt\SecurityTxt;
use Spaze\SecurityTxt\SecurityTxtValidationLevel;
use Spaze\SecurityTxt\Signature\Exceptions\SecurityTxtCannotVerifySignatureException;
use Spaze\SecurityTxt\Signature\SecurityTxtSignature;
use Spaze\SecurityTxt\Validator\SecurityTxtValidator;
use Spaze\SecurityTxt\Violations\SecurityTxtLineNoEol;
use Spaze\SecurityTxt\Violations\SecurityTxtPossibelFieldTypo;
use Spaze\SecurityTxt\Violations\SecurityTxtSpecViolation;

final class SecurityTxtParser
{

	/** @var list<string> */
	private array $lines = [];

	/**
	 * @var array<string, list<FieldProcessor>>
	 */
	private array $fieldProcessors = [];

	/** @var array<int, list<SecurityTxtSpecViolation>> */
	private array $lineErrors = [];

	/** @var array<int, list<SecurityTxtSpecViolation>> */
	private array $lineWarnings = [];


	public function __construct(
		private readonly SecurityTxtValidator $validator,
		private readonly SecurityTxtSignature $signature,
		private readonly SecurityTxtFetcher $fetcher,
		private readonly SecurityTxtExpiresFactory $expiresFactory,
	) {
	}


	private function initFieldProcessors(): void
	{
		if ($this->fieldProcessors !== []) {
			return;
		}
		$this->fieldProcessors[SecurityTxtField::Acknowledgments->value] = [
			new AcknowledgmentsAddFieldValue(),
		];
		$this->fieldProcessors[SecurityTxtField::Canonical->value] = [
			new CanonicalAddFieldValue(),
		];
		$this->fieldProcessors[SecurityTxtField::Contact->value] = [
			new ContactAddFieldValue(),
		];
		$this->fieldProcessors[SecurityTxtField::Encryption->value] = [
			new EncryptionAddFieldValue(),
		];
		$this->fieldProcessors[SecurityTxtField::Expires->value] = [
			new ExpiresCheckMultipleFields(),
			new ExpiresSetFieldValue($this->expiresFactory),
		];
		$this->fieldProcessors[SecurityTxtField::Hiring->value] = [
			new HiringAddFieldValue(),
		];
		$this->fieldProcessors[SecurityTxtField::Policy->value] = [
			new PolicyAddFieldValue(),
		];
		$this->fieldProcessors[SecurityTxtField::PreferredLanguages->value] = [
			new PreferredLanguagesCheckMultipleFields(),
			new PreferredLanguagesSetFieldValue(),
		];
	}


	private function processField(int $lineNumber, string $value, SecurityTxtField $field, SecurityTxt $securityTxt): void
	{
		$this->initFieldProcessors();
		foreach ($this->fieldProcessors[$field->value] as $processor) {
			try {
				$processor->process($value, $securityTxt);
			} catch (SecurityTxtError $e) {
				$this->lineErrors[$lineNumber][] = $e->getViolation();
			} catch (SecurityTxtWarning $e) {
				$this->lineWarnings[$lineNumber][] = $e->getViolation();
			}
		}
	}


	/**
	 * @throws SecurityTxtCannotVerifySignatureException
	 */
	public function parseString(string $contents, ?int $expiresWarningThreshold = null, bool $strictMode = false): SecurityTxtParseResult
	{
		$this->lineErrors = $this->lineWarnings = [];
		$lines = preg_split("/(?<=\n)/", $contents, flags: PREG_SPLIT_NO_EMPTY);
		if ($lines === false) {
			throw new LogicException('This should not happen');
		}
		$this->lines = $lines;
		$securityTxtFields = array_combine(
			array_map(function (SecurityTxtField $securityTxtField): string {
				return strtolower($securityTxtField->value);
			}, SecurityTxtField::cases()),
			SecurityTxtField::cases(),
		);
		$securityTxt = new SecurityTxt(SecurityTxtValidationLevel::AllowInvalidValues);
		for ($lineNumber = 1; $lineNumber <= count($this->lines); $lineNumber++) {
			$line = trim($this->lines[$lineNumber - 1]);
			if (!str_ends_with($this->lines[$lineNumber - 1], "\n")) {
				$this->lineErrors[$lineNumber][] = new SecurityTxtLineNoEol($line);
			}
			if (str_starts_with($line, '#')) {
				continue;
			}
			$securityTxt = $this->checkSignature($lineNumber, $line, $contents, $securityTxt);
			$field = explode(':', $line, 2);
			if (count($field) !== 2) {
				continue;
			}
			$fieldName = strtolower($field[0]);
			$fieldValue = trim($field[1]);
			if (isset($securityTxtFields[$fieldName])) {
				$this->processField($lineNumber, $fieldValue, $securityTxtFields[$fieldName], $securityTxt);
			} else {
				$suggestion = $this->getSuggestion($securityTxtFields, $fieldName);
				if ($suggestion !== null) {
					$this->lineWarnings[$lineNumber][] = new SecurityTxtPossibelFieldTypo($field[0], $suggestion->value, $line);
				}
			}
		}
		$validateResult = $this->validator->validate($securityTxt);
		$expires = $securityTxt->getExpires();
		$expiresSoon = $expiresWarningThreshold !== null && $expires?->inDays() < $expiresWarningThreshold;
		$hasErrors = $this->lineErrors !== [] || $validateResult->getErrors() !== [];
		$hasWarnings = $this->lineWarnings !== [] || $validateResult->getWarnings() !== [];
		return new SecurityTxtParseResult(
			$securityTxt,
			($expires === null || !$expires->isExpired()) && (!$strictMode || !$expiresSoon) && !$hasErrors && (!$strictMode || !$hasWarnings),
			$strictMode,
			$expiresWarningThreshold,
			$expiresSoon,
			$this->lineErrors,
			$this->lineWarnings,
			$validateResult,
		);
	}


	/**
	 * @throws SecurityTxtCannotOpenUrlException
	 * @throws SecurityTxtCannotReadUrlException
	 * @throws SecurityTxtNotFoundException
	 * @throws SecurityTxtTooManyRedirectsException
	 * @throws SecurityTxtHostNotFoundException
	 * @throws SecurityTxtNoHttpCodeException
	 * @throws SecurityTxtNoLocationHeaderException
	 * @throws SecurityTxtOnlyIpv6HostButIpv6DisabledException
	 * @throws SecurityTxtHostIpAddressInvalidTypeException
	 * @throws SecurityTxtHostIpAddressNotFoundException
	 * @throws SecurityTxtCannotVerifySignatureException
	 */
	public function parseHost(string $host, ?int $expiresWarningThreshold = null, bool $strictMode = false, bool $noIpv6 = false): SecurityTxtParseResult
	{
		$fetchResult = $this->fetcher->fetchHost($host, $noIpv6);
		$parseResult = $this->parseString($fetchResult->getContents(), $expiresWarningThreshold, $strictMode);
		return $this->createParseResult($parseResult, $fetchResult, $strictMode);
	}


	/**
	 * @throws SecurityTxtCannotVerifySignatureException
	 */
	public function parseFetchResult(SecurityTxtFetchResult $fetchResult, ?int $expiresWarningThreshold = null, bool $strictMode = false): SecurityTxtParseResult
	{
		$parseResult = $this->parseString($fetchResult->getContents(), $expiresWarningThreshold, $strictMode);
		return $this->createParseResult($parseResult, $fetchResult, $strictMode);
	}


	/**
	 * @throws SecurityTxtCannotVerifySignatureException
	 */
	private function checkSignature(int $lineNumber, string $line, string $contents, SecurityTxt $securityTxt): SecurityTxt
	{
		if ($this->signature->isCleartextHeader($line)) {
			try {
				$result = $this->signature->verify($contents);
				return $securityTxt->withSignatureVerifyResult($result);
			} catch (SecurityTxtError $e) {
				$this->lineErrors[$lineNumber][] = $e->getViolation();
			} catch (SecurityTxtWarning $e) {
				$this->lineWarnings[$lineNumber][] = $e->getViolation();
			}
		}
		return $securityTxt;
	}


	public function getLine(int $lineNumber): ?string
	{
		return $this->lines[$lineNumber] ?? null;
	}


	/**
	 * @see https://github.com/nette/utils/blob/c7ec4476eff478e6eec4263171ae0e3b0e2b4e55/src/Utils/Helpers.php#L72 Algorithm taken from nette/utils under the terms of the New BSD License
	 * @param array<string, SecurityTxtField> $securityTxtFields
	 */
	public function getSuggestion(array $securityTxtFields, string $lowercaseName): ?SecurityTxtField
	{
		$best = null;
		$min = (strlen($lowercaseName) / 4 + 1) * 10 + .1;
		foreach ($securityTxtFields as $lowercaseSecurityTxtFieldName => $securityTxtField) {
			$len = levenshtein($lowercaseSecurityTxtFieldName, $lowercaseName, 10, 11, 10);
			if ($lowercaseSecurityTxtFieldName !== $lowercaseName && $len < $min) {
				$min = $len;
				$best = $securityTxtField;
			}
		}
		return $best;
	}


	private function createParseResult(SecurityTxtParseResult $parseResult, SecurityTxtFetchResult $fetchResult, bool $strictMode): SecurityTxtParseResult
	{
		return new SecurityTxtParseResult(
			$parseResult->getSecurityTxt(),
			$parseResult->isValid() && $fetchResult->getErrors() === [] && (!$strictMode || $fetchResult->getWarnings() === []),
			$parseResult->isStrictMode(),
			$parseResult->getExpiresWarningThreshold(),
			$parseResult->isExpiresSoon(),
			$parseResult->getLineErrors(),
			$parseResult->getLineWarnings(),
			$parseResult->getValidateResult(),
			$fetchResult,
		);
	}

}
