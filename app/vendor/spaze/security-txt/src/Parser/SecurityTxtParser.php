<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser;

use Spaze\SecurityTxt\Exceptions\SecurityTxtError;
use Spaze\SecurityTxt\Exceptions\SecurityTxtWarning;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetchResult;
use Spaze\SecurityTxt\Fields\SecurityTxtExpiresFactory;
use Spaze\SecurityTxt\Fields\SecurityTxtField;
use Spaze\SecurityTxt\Fields\SecurityTxtRemovedField;
use Spaze\SecurityTxt\Json\SecurityTxtJsonValueFactory;
use Spaze\SecurityTxt\Parser\FieldProcessors\AcknowledgmentsAddFieldValue;
use Spaze\SecurityTxt\Parser\FieldProcessors\BugBountyCheckMultipleFields;
use Spaze\SecurityTxt\Parser\FieldProcessors\BugBountySetFieldValue;
use Spaze\SecurityTxt\Parser\FieldProcessors\CanonicalAddFieldValue;
use Spaze\SecurityTxt\Parser\FieldProcessors\ContactAddFieldValue;
use Spaze\SecurityTxt\Parser\FieldProcessors\CsafAddFieldValue;
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
use Spaze\SecurityTxt\Parser\FieldProcessors\UriCheckFieldValueInWellKnown;
use Spaze\SecurityTxt\Parser\SplitProviders\SecurityTxtSplitProvider;
use Spaze\SecurityTxt\SecurityTxt;
use Spaze\SecurityTxt\SecurityTxtPrintableValue;
use Spaze\SecurityTxt\SecurityTxtValidationLevel;
use Spaze\SecurityTxt\Signature\SecurityTxtSignature;
use Spaze\SecurityTxt\Validator\SecurityTxtValidateResult;
use Spaze\SecurityTxt\Validator\SecurityTxtValidator;
use Spaze\SecurityTxt\Violations\SecurityTxtByteOrderMark;
use Spaze\SecurityTxt\Violations\SecurityTxtContentNotUtf8;
use Spaze\SecurityTxt\Violations\SecurityTxtFieldWhitespaceBeforeColon;
use Spaze\SecurityTxt\Violations\SecurityTxtFieldWrongSpaceAfterColon;
use Spaze\SecurityTxt\Violations\SecurityTxtLineControlCharacter;
use Spaze\SecurityTxt\Violations\SecurityTxtLineLeadingWhitespace;
use Spaze\SecurityTxt\Violations\SecurityTxtLineNeitherFieldNorComment;
use Spaze\SecurityTxt\Violations\SecurityTxtLineNoEol;
use Spaze\SecurityTxt\Violations\SecurityTxtLineNotUtf8;
use Spaze\SecurityTxt\Violations\SecurityTxtLineNulByte;
use Spaze\SecurityTxt\Violations\SecurityTxtLineWrongEol;
use Spaze\SecurityTxt\Violations\SecurityTxtNoExpires;
use Spaze\SecurityTxt\Violations\SecurityTxtPossibelFieldTypo;
use Spaze\SecurityTxt\Violations\SecurityTxtSpecViolation;
use Spaze\SecurityTxt\Violations\SecurityTxtUnknownField;

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
		private readonly SecurityTxtSplitProvider $splitProvider,
		private readonly SecurityTxtJsonValueFactory $jsonValueFactory,
	) {
		$this->initFieldProcessors();
	}


	private function initFieldProcessors(): void
	{
		$this->fieldProcessors[SecurityTxtField::Acknowledgments->value] = [
			new AcknowledgmentsAddFieldValue(),
			new UriCheckFieldValueInWellKnown(SecurityTxtField::Acknowledgments),
		];
		$this->fieldProcessors[SecurityTxtField::BugBounty->value] = [
			new BugBountyCheckMultipleFields(),
			new BugBountySetFieldValue(),
		];
		$this->fieldProcessors[SecurityTxtField::Canonical->value] = [
			new CanonicalAddFieldValue(),
		];
		$this->fieldProcessors[SecurityTxtField::Contact->value] = [
			new ContactAddFieldValue(),
			new UriCheckFieldValueInWellKnown(SecurityTxtField::Contact),
		];
		$this->fieldProcessors[SecurityTxtField::Csaf->value] = [
			new CsafAddFieldValue(),
		];
		$this->fieldProcessors[SecurityTxtField::Encryption->value] = [
			new EncryptionAddFieldValue(),
			new UriCheckFieldValueInWellKnown(SecurityTxtField::Encryption),
		];
		$expiresFieldFormat = new ExpiresCheckFieldFormat();
		$this->fieldProcessors[SecurityTxtField::Expires->value] = [
			new ExpiresCheckMultipleFields(),
			$expiresFieldFormat,
			new ExpiresSetFieldValue($this->expiresFactory, $expiresFieldFormat),
			new ExpiresCheckFieldValueExpiresSoon(fn(): ?int => $this->expiresWarningThreshold),
		];
		$this->fieldProcessors[SecurityTxtField::Hiring->value] = [
			new HiringAddFieldValue(),
			new UriCheckFieldValueInWellKnown(SecurityTxtField::Hiring),
		];
		$this->fieldProcessors[SecurityTxtField::Policy->value] = [
			new PolicyAddFieldValue(),
			new UriCheckFieldValueInWellKnown(SecurityTxtField::Policy),
		];
		$this->fieldProcessors[SecurityTxtField::PreferredLanguages->value] = [
			new PreferredLanguagesCheckMultipleFields(),
			new PreferredLanguagesSetFieldValue($this->splitProvider),
		];
	}


	/**
	 * @param int<1, max> $lineNumber
	 * @param bool $possibleTypo The name may be a typo of the field's, so only the value is checked, nothing counts it as that field, and an error is only a warning
	 */
	private function processField(int $lineNumber, string $value, SecurityTxtField $field, SecurityTxt $securityTxt, bool $possibleTypo = false): void
	{
		foreach ($this->fieldProcessors[$field->value] as $processor) {
			if (
				$possibleTypo
				&& (
					$processor instanceof BugBountyCheckMultipleFields
					|| $processor instanceof ExpiresCheckMultipleFields
					|| $processor instanceof PreferredLanguagesCheckMultipleFields
					|| $processor instanceof ExpiresSetFieldValue
				)
			) {
				continue;
			}
			try {
				$processor->process($value, $securityTxt);
			} catch (SecurityTxtError $e) {
				if ($possibleTypo) {
					$this->lineWarnings[$lineNumber][] = $e->getViolation();
				} else {
					$this->lineErrors[$lineNumber][] = $e->getViolation();
				}
			} catch (SecurityTxtWarning $e) {
				$this->lineWarnings[$lineNumber][] = $e->getViolation();
			}
		}
	}


	/**
	 * @param string|null $fileLocation The URL the file came from, in UTF-8, a stored result carries it as it is
	 */
	public function parseString(string $contents, ?string $fileLocation = null, ?int $expiresWarningThreshold = null, bool $strictMode = false): SecurityTxtParseStringResult
	{
		$this->expiresWarningThreshold = $expiresWarningThreshold;
		$this->lineErrors = $this->lineWarnings = [];
		$securityTxt = new SecurityTxt(SecurityTxtValidationLevel::AllowInvalidValues);
		$fileLocationErrors = [];
		if ($fileLocation !== null) {
			try {
				// `AllowInvalidValues` sets the value and then throws for the caller to collect, which this call site used to skip. Only an error is caught because that is all
				// `setFileLocation()` declares, and a catch for anything it does not is rejected as dead. Nothing checks the other way round: give the setter the warnings callback
				// `setValue()` also takes and the warning escapes here as silently as the error used to, until checked exceptions are turned on
				$securityTxt->setFileLocation($fileLocation);
			} catch (SecurityTxtError $e) {
				$fileLocationErrors[] = $e->getViolation();
			}
		}
		if (!$this->jsonValueFactory->isUtf8($contents)) {
			foreach ($this->splitLines->splitLines($contents) as $index => $rawLine) {
				if (!$this->jsonValueFactory->isUtf8($rawLine)) {
					$this->lineErrors[$index + 1][] = new SecurityTxtLineNotUtf8(rtrim($rawLine, "\r\n"), $this->getNotUtf8($rawLine));
				}
			}
			return new SecurityTxtParseStringResult(
				$securityTxt,
				false,
				$strictMode,
				$this->expiresWarningThreshold,
				$this->lineErrors,
				$this->lineWarnings,
				new SecurityTxtValidateResult([...$fileLocationErrors, new SecurityTxtContentNotUtf8()], []),
			);
		}
		// Reported and then removed, or it would be read as the start of the first field name, and GnuPG wouldn't find the signature, which it's not a part of
		if (str_starts_with($contents, "\u{FEFF}")) {
			$this->lineErrors[1] = [new SecurityTxtByteOrderMark()];
			$contents = substr($contents, strlen("\u{FEFF}"));
		}
		$lines = $this->splitLines->splitLines($contents);
		$securityTxtFields = array_combine(
			array_map(function (SecurityTxtField $securityTxtField): string {
				return strtolower($securityTxtField->value);
			}, SecurityTxtField::cases()),
			SecurityTxtField::cases(),
		);
		$signatureLineNumber = null;
		$skipSignatureArmorHeaders = false;
		$inSignatureArmor = false;
		$afterSignature = false;
		$hasExpires = false;
		for ($lineNumber = 1; $lineNumber <= count($lines); $lineNumber++) {
			$rawLine = $lines[$lineNumber - 1];
			// A NUL is reported and then removed, so nothing a line is handed to has to cope with one, while the signature is still verified over the file as served
			if (str_contains($rawLine, "\0")) {
				$this->lineErrors[$lineNumber][] = new SecurityTxtLineNulByte(rtrim($rawLine, "\r\n"));
				$rawLine = str_replace("\0", '', $rawLine);
			}
			// So are the other control characters, but a vertical tab at an end of the line, which PHP trims as whitespace, is said to be whitespace there
			if (preg_match(SecurityTxtLineControlCharacter::CONTROL_CHARACTERS, trim($rawLine)) === 1) {
				$this->lineErrors[$lineNumber][] = new SecurityTxtLineControlCharacter(rtrim($rawLine, "\r\n"));
				$rawLine = preg_replace(SecurityTxtLineControlCharacter::CONTROL_CHARACTERS, '', $rawLine) ?? $rawLine;
			}
			// The verifier removes the dash escape from a signed line before it checks the signature, so the line is read without it too
			$dashEscaped = $signatureLineNumber !== null && !$skipSignatureArmorHeaders && !$inSignatureArmor && !$afterSignature && str_starts_with($rawLine, '- ');
			if ($dashEscaped) {
				$rawLine = substr($rawLine, 2);
			}
			$lineAsWritten = rtrim($rawLine);
			// A Unicode space is whitespace there too, but not when there's nothing else on the line, which isn't empty then
			$line = preg_replace('/^(?:\s|' . SecurityTxtLineLeadingWhitespace::UNICODE_SPACES . ')++(?=.)/s', '', $lineAsWritten) ?? ltrim($lineAsWritten);
			$lineWithoutEol = rtrim($rawLine, "\r\n");
			if (!str_ends_with($rawLine, "\n")) {
				$this->lineErrors[$lineNumber][] = new SecurityTxtLineNoEol($line);
			}
			if ($skipSignatureArmorHeaders) {
				if ($line === '') {
					$skipSignatureArmorHeaders = false;
				}
				continue;
			}
			if ($inSignatureArmor) {
				// The lines between the signature armor header and tail are the signature, not fields
				if ($this->signature->isSignatureArmorTail($lineAsWritten)) {
					$inSignatureArmor = false;
					$afterSignature = true;
				}
				continue;
			}
			// After the first header, a line with whitespace before what looks like framing is signed text to the verifier
			if (!$dashEscaped && $this->signature->isClearsignHeader($signatureLineNumber === null ? $line : $lineAsWritten)) {
				$signatureLineNumber ??= $lineNumber;
				$skipSignatureArmorHeaders = true;
				continue;
			}
			if ($signatureLineNumber !== null && !$afterSignature && !$dashEscaped && $this->signature->isSignatureArmorHeader($lineAsWritten)) {
				$inSignatureArmor = true;
				continue;
			}
			if ($line !== $lineAsWritten) {
				$this->lineErrors[$lineNumber][] = new SecurityTxtLineLeadingWhitespace($lineAsWritten);
			}
			if (rtrim($lineWithoutEol, " \t") !== $lineAsWritten) {
				$this->lineErrors[$lineNumber][] = new SecurityTxtLineWrongEol($lineWithoutEol);
			}
			if (str_starts_with($line, '#')) {
				continue;
			}
			$field = explode(':', $line, 2);
			if (count($field) !== 2) {
				if ($line !== '') {
					$this->lineErrors[$lineNumber][] = $this->getLineNeitherFieldNorComment($line, $this->getFieldWithoutColon($securityTxtFields, $line));
				}
				continue;
			}
			$fieldNameAsWritten = $field[0];
			// Tried only where a run starts, or each byte of a long one would be a start to try the rest from
			$field[0] = preg_replace('/(?<!\s|' . SecurityTxtLineLeadingWhitespace::UNICODE_SPACES . ')(?:\s|' . SecurityTxtLineLeadingWhitespace::UNICODE_SPACES . ')++$/D', '', $fieldNameAsWritten) ?? rtrim($fieldNameAsWritten);
			$fieldName = strtolower($field[0]);
			$fieldValue = trim(preg_replace('/^(?:\s|' . SecurityTxtLineLeadingWhitespace::UNICODE_SPACES . ')++/', '', $field[1]) ?? $field[1]);
			$securityTxtField = $securityTxtFields[$fieldName] ?? null;
			// A colon before anything but whitespace is more likely a part of the value, `Acknowledgments https://example.com/`, than the one after a field name
			$fieldWithoutColon = $securityTxtField === null && preg_match('/^(?!\s|' . SecurityTxtLineLeadingWhitespace::UNICODE_SPACES . ')./s', $field[1]) === 1 ? $this->getFieldWithoutColon($securityTxtFields, $line) : null;
			$suggestion = $securityTxtField === null && $fieldWithoutColon === null ? $this->getSuggestion($securityTxtFields, $fieldName) : null;
			// A field name is printable ASCII and no space, RFC 5322 section 3.6.8
			if ($securityTxtField === null && $suggestion === null && preg_match('/^[!-~]++$/D', $field[0]) !== 1) {
				$this->lineErrors[$lineNumber][] = $this->getLineNeitherFieldNorComment($line, $fieldWithoutColon);
				continue;
			}
			if ($field[0] !== $fieldNameAsWritten) {
				$this->lineErrors[$lineNumber][] = new SecurityTxtFieldWhitespaceBeforeColon($field[0], $line);
			}
			if ($securityTxtField !== null) {
				$hasExpires = $hasExpires || $securityTxtField === SecurityTxtField::Expires;
				if ($fieldValue !== '' && !$this->isSpaceAfterColon($securityTxtField, $field[1])) {
					$this->lineErrors[$lineNumber][] = new SecurityTxtFieldWrongSpaceAfterColon($field[0], $line);
				}
				$this->processField($lineNumber, $fieldValue, $securityTxtField, $securityTxt);
			} elseif ($suggestion !== null) {
				$this->lineWarnings[$lineNumber][] = new SecurityTxtPossibelFieldTypo($field[0], $suggestion->value, $line);
				// What the value would be told as the suggested field, without the file having that field
				$this->processField($lineNumber, $fieldValue, $suggestion, $this->getPossibleTypoSecurityTxt($securityTxt), true);
			} else {
				// With the removed field only when there is one, so a stored violation carries no null for it
				$removedField = SecurityTxtRemovedField::tryFrom($fieldName);
				$this->lineWarnings[$lineNumber][] = $removedField !== null ? new SecurityTxtUnknownField($field[0], $line, $removedField) : new SecurityTxtUnknownField($field[0], $line);
			}
		}
		if ($signatureLineNumber !== null) {
			$formatViolation = $this->signature->getFormatViolation($lines);
			foreach ($formatViolation as $formatLineNumber => $violation) {
				$this->lineErrors[$formatLineNumber][] = $violation;
			}
			if ($formatViolation === []) {
				$securityTxt = $this->checkSignature($signatureLineNumber, $contents, $securityTxt);
			}
		}
		ksort($this->lineErrors);
		ksort($this->lineWarnings);
		$validated = $this->validator->validate($securityTxt);
		// An Expires field with a value that isn't a date is wrong, which its line says, not missing
		$validatedErrors = array_filter($validated->getErrors(), fn(SecurityTxtSpecViolation $error): bool => !$hasExpires || !$error instanceof SecurityTxtNoExpires);
		$validateResult = new SecurityTxtValidateResult([...$fileLocationErrors, ...$validatedErrors], $validated->getWarnings());
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


	public function parseFetchResult(SecurityTxtFetchResult $fetchResult, ?int $expiresWarningThreshold = null, bool $strictMode = false): SecurityTxtParseHostResult
	{
		// The URL spelled the way this library spells one, not decoded: a file fetched from a host whose punycode does not survive decoding would otherwise be reported at a
		// URL naming a different host, and `SecurityTxtSpecViolation::asUrl()` cannot read that spelling back either, so it prints percent-encoded beside a host that reads
		$parseResult = $this->parseString($fetchResult->getContents(), new SecurityTxtPrintableValue($fetchResult->getFinalUrl())->render(), $expiresWarningThreshold, $strictMode);
		return new SecurityTxtParseHostResult(
			$parseResult->isValid() && $fetchResult->getErrors() === [] && (!$strictMode || $fetchResult->getWarnings() === []),
			$parseResult,
			$fetchResult,
		);
	}


	/**
	 * RFC 9116 gives the fields it defines exactly one space before the value. It does not define the others, `CSAF` for example, so they fall under its
	 * extension field rule, `field-name fs SP unstructured`, where RFC 5322's `unstructured` may start with more spaces and tabs.
	 */
	private function isSpaceAfterColon(SecurityTxtField $field, string $afterColon): bool
	{
		$whitespace = preg_match('/^(?:\s|' . SecurityTxtLineLeadingWhitespace::UNICODE_SPACES . ')*+/', $afterColon, $matches) === 1 ? $matches[0] : substr($afterColon, 0, strlen($afterColon) - strlen(ltrim($afterColon)));
		return match ($field) {
			SecurityTxtField::Acknowledgments,
			SecurityTxtField::Canonical,
			SecurityTxtField::Contact,
			SecurityTxtField::Encryption,
			SecurityTxtField::Expires,
			SecurityTxtField::Hiring,
			SecurityTxtField::Policy,
			SecurityTxtField::PreferredLanguages => $whitespace === ' ',
			SecurityTxtField::BugBounty,
			SecurityTxtField::Csaf => str_starts_with($whitespace, ' ') && ltrim($whitespace, " \t") === '',
		};
	}


	/**
	 * @param positive-int $lineNumber
	 */
	private function checkSignature(int $lineNumber, string $contents, SecurityTxt $securityTxt): SecurityTxt
	{
		try {
			$result = $this->signature->verify($contents);
			return $securityTxt->withSignatureVerifyResult($result);
		} catch (SecurityTxtError $e) {
			$this->lineErrors[$lineNumber][] = $e->getViolation();
		} catch (SecurityTxtWarning $e) {
			$this->lineWarnings[$lineNumber][] = $e->getViolation();
		}
		return $securityTxt;
	}


	/**
	 * Where the value of a field whose name may be a typo is checked, at the file's location, which a path is suggested on, as it is for the field itself
	 */
	private function getPossibleTypoSecurityTxt(SecurityTxt $securityTxt): SecurityTxt
	{
		$possibleTypo = new SecurityTxt(SecurityTxtValidationLevel::AllowInvalidValues);
		$fileLocation = $securityTxt->getFileLocation();
		if ($fileLocation !== null) {
			try {
				$possibleTypo->setFileLocation($fileLocation);
			} catch (SecurityTxtError) {
				// Reported for the file already
			}
		}
		return $possibleTypo;
	}


	/**
	 * Each piece of a line that isn't UTF-8, cut where a character can start, at a byte from 0xC0 with as many continuation bytes after it as it can take, or at a
	 * continuation byte with none before it, and still told apart by the one UTF-8 test
	 *
	 * @return list<string>
	 */
	private function getNotUtf8(string $line): array
	{
		preg_match_all('/[\xC0-\xDF][\x80-\xBF]?|[\xE0-\xEF][\x80-\xBF]{0,2}|[\xF0-\xFF][\x80-\xBF]{0,3}|[\x80-\xBF]/', $line, $matches);
		return array_values(array_unique(array_filter($matches[0], fn(string $piece): bool => !$this->jsonValueFactory->isUtf8($piece))));
	}


	/**
	 * The field whose name the line starts with when whitespace or nothing follows the name, not a colon, `Contact https://example.com/`
	 *
	 * @param array<string, SecurityTxtField> $securityTxtFields
	 */
	private function getFieldWithoutColon(array $securityTxtFields, string $line): ?SecurityTxtField
	{
		return preg_match('/^([!-9;-~]++)(?:\s|' . SecurityTxtLineLeadingWhitespace::UNICODE_SPACES . '|$)/D', $line, $matches) === 1 ? $securityTxtFields[strtolower($matches[1])] ?? null : null;
	}


	/**
	 * With the field only when there is one, so a stored violation carries no null for it
	 */
	private function getLineNeitherFieldNorComment(string $line, ?SecurityTxtField $field): SecurityTxtLineNeitherFieldNorComment
	{
		return $field !== null ? new SecurityTxtLineNeitherFieldNorComment($line, $field) : new SecurityTxtLineNeitherFieldNorComment($line);
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
