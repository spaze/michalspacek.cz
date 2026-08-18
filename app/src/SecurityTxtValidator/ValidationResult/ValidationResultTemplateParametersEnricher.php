<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\ValidationResult;

use DateInterval;
use DateTimeImmutable;
use LogicException;
use MichalSpacekCz\DateTime\DateIntervalFormatter;
use MichalSpacekCz\SecurityTxtValidator\Issue\SecurityTxtIssue;
use MichalSpacekCz\SecurityTxtValidator\Issue\SecurityTxtIssueLevel;
use MichalSpacekCz\SecurityTxtValidator\Issue\SecurityTxtIssueMessageFormatter;
use MichalSpacekCz\SecurityTxtValidator\Issue\SecurityTxtLineIssue;
use MichalSpacekCz\Utils\Strings;
use Spaze\SecurityTxt\Check\SecurityTxtCheckHostResult;
use Spaze\SecurityTxt\Parser\SecurityTxtParseStringResult;
use Spaze\SecurityTxt\Parser\SecurityTxtSplitLines;
use Spaze\SecurityTxt\Violations\SecurityTxtSpecViolation;

final readonly class ValidationResultTemplateParametersEnricher
{

	public function __construct(
		private DateIntervalFormatter $dateIntervalFormatter,
		private Strings $strings,
		private SecurityTxtIssueMessageFormatter $issueMessageFormatter,
		private SecurityTxtSplitLines $splitLines,
	) {
	}


	public function addFromCheckHostResult(
		ValidationResultTemplateParameters $template,
		SecurityTxtCheckHostResult $checkHostResult,
		DateTimeImmutable $downloadedAt,
		?DateInterval $downloadedAgo,
		?DateInterval $clearableIn,
	): void {
		$template->downloadedAt = $downloadedAt;
		if ($downloadedAgo !== null) {
			$template->downloadedAgo = $this->dateIntervalFormatter->toMinutesSecondsAgo($downloadedAgo);
		}
		if ($clearableIn !== null) {
			$template->clearableIn = $this->dateIntervalFormatter->toMinutesSecondsIn($clearableIn);
		}
		$template->fileExists = true;
		$hasWarnings = $checkHostResult->getFetchWarnings() !== [] || $checkHostResult->getLineWarnings() !== [] || $checkHostResult->getFileWarnings() !== [];
		$template->isValid = $checkHostResult->isValid() && !$hasWarnings;
		$template->isValidWithWarnings = $checkHostResult->isValid() && $hasWarnings;
		$template->isInvalid = !$checkHostResult->isValid();
		$template->expiresInDays = $checkHostResult->getExpiryDays();
		$contents = $checkHostResult->getContents();
		$template->contents = $this->strings->addLineNumbersAndEolChars($contents, 'line', 'number', 'eol');
		$template->signed = $checkHostResult->getSecurityTxt()->getSignatureVerifyResult();
		$template->isTruncated = $checkHostResult->getFetchResult()->isTruncated();
		$finalUrl = $checkHostResult->getFinalUrl();
		$template->url = $finalUrl;
		$template->displayUrl = $this->strings->addWordBreaks($finalUrl);
		$template->fetchErrors = array_map($this->createIssue(...), $checkHostResult->getFetchErrors());
		$template->fetchWarnings = array_map($this->createIssue(...), $checkHostResult->getFetchWarnings());
		$template->lineIssues = $this->getLineIssues($checkHostResult, fn(int $lineNr): ?string => $checkHostResult->getFetchResult()->getLine($lineNr));
		$template->fileErrors = array_map($this->createIssue(...), $checkHostResult->getFileErrors());
		$template->fileWarnings = array_map($this->createIssue(...), $checkHostResult->getFileWarnings());
		$template->allRedirects = $checkHostResult->getRedirects();
		$this->setLogoParameters($template);
	}


	public function addFromParseStringResult(
		ValidationResultTemplateParameters $template,
		SecurityTxtParseStringResult $parseStringResult,
		string $contents,
	): void {
		$template->fileExists = true;
		$hasWarnings = $parseStringResult->getLineWarnings() !== [] || $parseStringResult->getFileWarnings() !== [];
		$template->isValid = $parseStringResult->isValid() && !$hasWarnings;
		$template->isValidWithWarnings = $parseStringResult->isValid() && $hasWarnings;
		$template->isInvalid = !$parseStringResult->isValid();
		$template->expiresInDays = $parseStringResult->getSecurityTxt()->getExpires()?->inDays();
		$template->contents = $this->strings->addLineNumbersAndEolChars($contents, 'line', 'number', 'eol');
		$template->signed = $parseStringResult->getSecurityTxt()->getSignatureVerifyResult();
		$template->isTruncated = false;
		$lines = $this->splitLines->splitLines($contents);
		$template->lineIssues = $this->getLineIssues($parseStringResult, fn(int $lineNr): ?string => $lines[$lineNr - 1] ?? null);
		$template->fileErrors = array_map($this->createIssue(...), $parseStringResult->getFileErrors());
		$template->fileWarnings = array_map($this->createIssue(...), $parseStringResult->getFileWarnings());
		$this->setLogoParameters($template);
	}


	/**
	 * Returns errors and warnings together sorted by lines.
	 *
	 * @param callable(int<1, max>): ?string $lineProvider
	 * @return array<int, list<SecurityTxtLineIssue>> line => issues
	 */
	private function getLineIssues(SecurityTxtCheckHostResult|SecurityTxtParseStringResult $result, callable $lineProvider): array
	{
		$issues = [];
		foreach ($result->getLineErrors() as $lineNr => $errors) {
			$line = $lineProvider($lineNr);
			if ($line === null) {
				throw new LogicException("This line shouldn't not exist");
			}
			foreach ($errors as $error) {
				$issues[$lineNr][] = new SecurityTxtLineIssue(SecurityTxtIssueLevel::Error, $this->createIssue($error), trim($line));
			}
		}
		foreach ($result->getLineWarnings() as $lineNr => $warnings) {
			$line = $lineProvider($lineNr);
			if ($line === null) {
				throw new LogicException("This line shouldn't not exist");
			}
			foreach ($warnings as $warning) {
				$issues[$lineNr][] = new SecurityTxtLineIssue(SecurityTxtIssueLevel::Warning, $this->createIssue($warning), trim($line));
			}
		}
		ksort($issues);
		return $issues;
	}


	private function createIssue(SecurityTxtSpecViolation $violation): SecurityTxtIssue
	{
		return new SecurityTxtIssue(
			$this->issueMessageFormatter->format($violation->getMessageFormat(), $violation->getMessageValues()),
			$this->issueMessageFormatter->format($violation->getHowToFixFormat(), $violation->getHowToFixValues()),
			$violation->getCorrectValue(),
			$violation->getSpecSection(),
		);
	}


	private function setLogoParameters(ValidationResultTemplateParameters $template): void
	{
		if ($template->errorMessage !== null) {
			$template->logoExtraCssClass = LogoExtraCssClass::Error;
			$template->logoExtraIcon = LogoExtraIcon::ExclamationTriangle;
		} elseif ($template->isValid === true) {
			$template->logoExtraCssClass = LogoExtraCssClass::Valid;
			$template->logoExtraIcon = LogoExtraIcon::CertificateCheck;
		} elseif ($template->isValidWithWarnings === true) {
			$template->logoExtraCssClass = LogoExtraCssClass::Warning;
		} elseif ($template->isInvalid === true) {
			$template->logoExtraCssClass = LogoExtraCssClass::Invalid;
			$template->logoExtraIcon = LogoExtraIcon::CertificateOff;
		}
	}

}
