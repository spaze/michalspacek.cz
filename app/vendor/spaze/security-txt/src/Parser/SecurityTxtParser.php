<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser;

use Spaze\SecurityTxt\Exceptions\SecurityTxtError;
use Spaze\SecurityTxt\Exceptions\SecurityTxtWarning;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetchResult;
use Spaze\SecurityTxt\Fields\SecurityTxtExpiresFactory;
use Spaze\SecurityTxt\Fields\SecurityTxtField;
use Spaze\SecurityTxt\Parser\FieldProcessors\AcknowledgmentsAddFieldValue;
use Spaze\SecurityTxt\Parser\FieldProcessors\CanonicalAddFieldValue;
use Spaze\SecurityTxt\Parser\FieldProcessors\ContactAddFieldValue;
use Spaze\SecurityTxt\Parser\FieldProcessors\EncryptionAddFieldValue;
use Spaze\SecurityTxt\Parser\FieldProcessors\ExpiresCheckFieldFormat;
use Spaze\SecurityTxt\Parser\FieldProcessors\ExpiresCheckFieldValueExpiresSoon;
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

	/**
	 * @var array<string, list<FieldProcessor>>
	 */
	private array $fieldProcessors = [];

	/** @var array<int<1, max>, list<SecurityTxtSpecViolation>> */
	private array $lineErrors = [];

	/** @var array<int<1, max>, list<SecurityTxtSpecViolation>> */
	private array $lineWarnings = [];

	private ?int $expiresWarningThreshold = null;


	public function __construct(
		private readonly SecurityTxtValidator $validator,
		private readonly SecurityTxtSignature $signature,
		private readonly SecurityTxtExpiresFactory $expiresFactory,
		private readonly SecurityTxtSplitLines $splitLines,
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
			new ExpiresCheckFieldFormat(),
			new ExpiresSetFieldValue($this->expiresFactory),
			new ExpiresCheckFieldValueExpiresSoon(fn(): ?int => $this->expiresWarningThreshold),
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


	/**
	 * @param int<1, max> $lineNumber
	 */
	private function processField(int $lineNumber, string $value, SecurityTxtField $field, SecurityTxt $securityTxt): void
	{
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
	public function parseString(string $contents, ?int $expiresWarningThreshold = null, bool $strictMode = false): SecurityTxtParseStringResult
	{
		$this->expiresWarningThreshold = $expiresWarningThreshold;
		$this->initFieldProcessors();
		$this->lineErrors = $this->lineWarnings = [];
		$lines = $this->splitLines->splitLines($contents);
		$securityTxtFields = array_combine(
			array_map(function (SecurityTxtField $securityTxtField): string {
				return strtolower($securityTxtField->value);
			}, SecurityTxtField::cases()),
			SecurityTxtField::cases(),
		);
		$securityTxt = new SecurityTxt(SecurityTxtValidationLevel::AllowInvalidValues);
		for ($lineNumber = 1; $lineNumber <= count($lines); $lineNumber++) {
			$line = trim($lines[$lineNumber - 1]);
			if (!str_ends_with($lines[$lineNumber - 1], "\n")) {
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
		$hasErrors = $this->lineErrors !== [] || $validateResult->getErrors() !== [];
		$hasWarnings = $this->lineWarnings !== [] || $validateResult->getWarnings() !== [];
		return new SecurityTxtParseStringResult(
			$securityTxt,
			($expires === null || !$expires->isExpired()) && !$hasErrors && (!$strictMode || !$hasWarnings),
			$strictMode,
			$this->expiresWarningThreshold,
			$this->lineErrors,
			$this->lineWarnings,
			$validateResult,
		);
	}


	/**
	 * @throws SecurityTxtCannotVerifySignatureException
	 */
	public function parseFetchResult(SecurityTxtFetchResult $fetchResult, ?int $expiresWarningThreshold = null, bool $strictMode = false): SecurityTxtParseHostResult
	{
		$parseResult = $this->parseString($fetchResult->getContents(), $expiresWarningThreshold, $strictMode);
		return new SecurityTxtParseHostResult(
			$parseResult->isValid() && $fetchResult->getErrors() === [] && (!$strictMode || $fetchResult->getWarnings() === []),
			$parseResult,
			$fetchResult,
		);
	}


	/**
	 * @param int<1, max> $lineNumber
	 * @throws SecurityTxtCannotVerifySignatureException
	 */
	private function checkSignature(int $lineNumber, string $line, string $contents, SecurityTxt $securityTxt): SecurityTxt
	{
		if ($this->signature->isClearsignHeader($line)) {
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

}
