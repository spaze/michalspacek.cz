<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\ValidationResult;

use DateInterval;
use DateTimeImmutable;
use LogicException;
use MichalSpacekCz\DateTime\DateIntervalFormatter;
use MichalSpacekCz\Pgp\Keyserver;
use MichalSpacekCz\SecurityTxtValidator\Issue\SecurityTxtIssue;
use MichalSpacekCz\SecurityTxtValidator\Issue\SecurityTxtIssueLevel;
use MichalSpacekCz\SecurityTxtValidator\Issue\SecurityTxtIssueMessageFormatter;
use MichalSpacekCz\SecurityTxtValidator\Issue\SecurityTxtLineIssue;
use MichalSpacekCz\Utils\Strings;
use Nette\Utils\Html;
use Spaze\SecurityTxt\Check\SecurityTxtCheckHostResult;
use Spaze\SecurityTxt\Parser\SecurityTxtParseStringResult;
use Spaze\SecurityTxt\Parser\SecurityTxtSplitLines;
use Spaze\SecurityTxt\SecurityTxtPrintableValue;
use Spaze\SecurityTxt\Violations\SecurityTxtSpecViolation;

final readonly class ValidationResultTemplateParametersEnricher
{

	public function __construct(
		private DateIntervalFormatter $dateIntervalFormatter,
		private Strings $strings,
		private SecurityTxtIssueMessageFormatter $issueMessageFormatter,
		private SecurityTxtSplitLines $splitLines,
		private Keyserver $keyserver,
	) {
	}


	public function addErrorMessageAndLogo(ValidationResultTemplateParameters $template, Html $errorMessage): void
	{
		$template->errorMessage = $errorMessage;
		$this->setLogoParameters($template);
	}


	/**
	 * Said when a fetch was refused because this host was fetched a moment ago, whatever port or scheme that was for.
	 */
	public function addFetchedTooRecently(ValidationResultTemplateParameters $template, string $host, DateInterval $tryAgainIn): void
	{
		$this->addErrorMessageAndLogo($template, Html::el()
			->addHtml(Html::el('code')->setText($host))
			->addText(' was checked a moment ago, try again ')
			->addText($this->dateIntervalFormatter->toMinutesSecondsIn($tryAgainIn)));
	}


	/**
	 * When the answer was fetched and how long it has left, which a cached failure has to say as much as a cached
	 * result does: both are answers of the same age, arrived at the same way.
	 */
	public function addCacheTiming(
		ValidationResultTemplateParameters $template,
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
	}


	public function addFromCheckHostResult(
		ValidationResultTemplateParameters $template,
		SecurityTxtCheckHostResult $checkHostResult,
		DateTimeImmutable $downloadedAt,
		?DateInterval $downloadedAgo,
		?DateInterval $clearableIn,
	): void {
		$this->addCacheTiming($template, $downloadedAt, $downloadedAgo, $clearableIn);
		$template->fileExists = true;
		$hasWarnings = $checkHostResult->getFetchWarnings() !== [] || $checkHostResult->getLineWarnings() !== [] || $checkHostResult->getFileWarnings() !== [];
		$template->isValid = $checkHostResult->isValid() && !$hasWarnings;
		$template->isValidWithWarnings = $checkHostResult->isValid() && $hasWarnings;
		$template->isInvalid = !$checkHostResult->isValid();
		$template->expiresInDays = $checkHostResult->getExpiryDays();
		$contents = $checkHostResult->getContents();
		$template->contents = $this->strings->addLineNumbersAndEolChars($contents, 'line', 'number', 'eol');
		$template->signed = $checkHostResult->getSecurityTxt()->getSignatureVerifyResult();
		$template->signingKeyUrl = $template->signed === null ? null : $this->keyserver->getLookupUrl($template->signed->getKeyFingerprint());
		$template->isTruncated = $checkHostResult->getFetchResult()->isTruncated();
		$finalUrl = new SecurityTxtPrintableValue($checkHostResult->getFinalUrl())->render();
		$template->url = $finalUrl;
		$template->displayUrl = $this->strings->addWordBreaks($finalUrl);
		$template->fetchErrors = array_map($this->createIssue(...), $checkHostResult->getFetchErrors());
		$template->fetchWarnings = array_map($this->createIssue(...), $checkHostResult->getFetchWarnings());
		$template->lineIssues = $this->getLineIssues($checkHostResult, fn(int $lineNr): ?string => $checkHostResult->getFetchResult()->getLine($lineNr));
		$template->fileErrors = array_map($this->createIssue(...), $checkHostResult->getFileErrors());
		$template->fileWarnings = array_map($this->createIssue(...), $checkHostResult->getFileWarnings());
		$template->allRedirects = array_map(fn($redirects) => $redirects->toStrings(), $checkHostResult->getRedirects());
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
		$template->signingKeyUrl = $template->signed === null ? null : $this->keyserver->getLookupUrl($template->signed->getKeyFingerprint());
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
		$correctValue = $violation->getCorrectValue();
		return new SecurityTxtIssue(
			$this->issueMessageFormatter->format($violation->getMessageFormat(), $violation->getMessageValues()),
			$this->issueMessageFormatter->format($violation->getHowToFixFormat(), $violation->getHowToFixValues()),
			$correctValue !== null ? new SecurityTxtPrintableValue($correctValue)->render() : null,
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
