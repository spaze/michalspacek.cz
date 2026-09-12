<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Check;

use Closure;
use DateTimeImmutable;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtFetcherException;
use Spaze\SecurityTxt\SecurityTxtHost;
use Spaze\SecurityTxt\Violations\SecurityTxtSpecViolation;
use Uri\WhatWg\Url;

final class SecurityTxtCheckHostCli
{

	private bool $verbose = false;


	/**
	 * @param Closure(int): void $exit
	 */
	public function __construct(
		private readonly ConsolePrinter $consolePrinter,
		private readonly SecurityTxtCheckHost $checkHost,
		private readonly Closure $exit,
	) {
		$this->initCheckHostCallbacks();
	}


	public function check(
		?Url $url,
		?int $expiresWarningThreshold,
		bool $colors,
		bool $verbose,
		bool $strictMode,
		bool $requireTopLevelLocation,
		bool $noIpv6,
		bool $showUsageHelp,
		string $usageHelp,
	): void {
		$this->verbose = $verbose;
		$this->consolePrinter->setColors($colors);
		if ($showUsageHelp) {
			$this->printUsageHelp($usageHelp);
			$this->exit(CheckExitStatus::Ok);
			return;
		} elseif ($url === null) {
			$this->printUsageHelp($usageHelp);
			$this->exit(CheckExitStatus::NoFile);
			return;
		}
		try {
			$checkResult = $this->checkHost->check(
				$url,
				$expiresWarningThreshold,
				$strictMode,
				$requireTopLevelLocation,
				$noIpv6,
			);
			if (!$checkResult->isValid()) {
				$this->consolePrinter->error('<red>The file is invalid</red>');
				$this->exit(CheckExitStatus::Error);
			} else {
				$this->consolePrinter->ok('<green>The file is valid</green>');
				$this->exit(CheckExitStatus::Ok);
			}
		} catch (SecurityTxtFetcherException $e) {
			$this->consolePrinter->error($e->getMessageFormat(), ...$e->getMessageValues());
			$this->exit(CheckExitStatus::FileError);
		}
	}


	private function exit(CheckExitStatus $exitStatus): void
	{
		($this->exit)($exitStatus->value);
	}


	/**
	 * The text is written by whoever calls `check()`, not by a checked host, so it is printed the way they wrote it.
	 */
	private function printUsageHelp(string $usageHelp): void
	{
		$this->consolePrinter->infoText($usageHelp);
	}


	/**
	 * The violation's own formats and values are composed, not its rendered strings, so what the console prints and what `getMessage()` returns are the same values put through
	 * the same rule. Which values are URLs is the violation's to say, it was handed them, so nothing is guessed here.
	 *
	 * Two things the types cannot say. A violation format must not use positional specifiers, `%1$s`, because the line number is put in front of it, which would renumber the
	 * rest, and must not contain the printer's color markup, because a format is where markup is read. Both hold for every violation and neither is checked, `literal-string`
	 * says where a string was written, not what is in it.
	 *
	 * `vsprintf()` only refuses too few values, so composing the two formats relies on each violation bringing exactly as many values as its own format takes. All of them do,
	 * and a surplus would shift the values of the second half.
	 *
	 * @return array{0:literal-string, 1:list<string|Url|SecurityTxtHost>}
	 */
	private function getViolationMessage(?int $line, SecurityTxtSpecViolation $violation): array
	{
		$format = $violation->getMessageFormat() . ' (How to fix: ' . $violation->getHowToFixFormat();
		$values = [...$violation->getMessageValues(), ...$violation->getHowToFixValues()];
		if ($line !== null) {
			$format = 'on line <b>%s</b>: ' . $format;
			array_unshift($values, (string)$line);
		}
		$correctValue = $violation->getCorrectValue();
		if ($correctValue !== null) {
			$format .= ', e.g. %s';
			$values[] = $correctValue;
		}
		return [$format . ')', $values];
	}


	private function initCheckHostCallbacks(): void
	{
		$this->checkHost->addOnUrl(
			function (Url $url): void {
				if ($this->verbose) {
					$this->consolePrinter->info('Loading security.txt from <b>%s</b>', $url);
				}
			},
		);
		$this->checkHost->addOnRedirect(
			function (Url $url, Url $destination): void {
				if ($this->verbose) {
					$this->consolePrinter->info('Redirected from <b>%s</b> to <b>%s</b>', $url, $destination);
				}
			},
		);
		$this->checkHost->addOnUrlNotFound(
			function (Url $url): void {
				if ($this->verbose) {
					$this->consolePrinter->info('Not found <b>%s</b>', $url);
				}
			},
		);
		$this->checkHost->addOnFinalUrl(
			function (Url $url): void {
				$this->consolePrinter->info('Using <b>%s</b>', $url);
			},
		);
		$this->checkHost->addOnIsExpired(
			function (int $daysAgo, DateTimeImmutable $expiryDate): void {
				$this->consolePrinter->error(
					'<red>The file has expired %s ' . ($daysAgo === 1 ? 'day' : 'days') . ' ago</red> (%s)',
					(string)$daysAgo,
					$expiryDate->format(DATE_RFC3339),
				);
			},
		);
		$this->checkHost->addOnExpires(
			function (int $inDays, DateTimeImmutable $expiryDate): void {
				$this->consolePrinter->ok(
					'The file will expire in %s ' . ($inDays === 1 ? 'day' : 'days') . ' (%s)',
					(string)$inDays,
					$expiryDate->format(DATE_RFC3339),
				);
			},
		);
		$this->checkHost->addOnHost(
			function (SecurityTxtHost $host): void {
				$this->consolePrinter->info('Parsing security.txt for <b>%s</b>', $host);
			},
		);
		$this->checkHost->addOnValidSignature(
			function (string $keyFingerprint, DateTimeImmutable $signatureDate): void {
				$this->consolePrinter->ok(
					'Signature valid, key %s, signed on %s',
					$keyFingerprint,
					$signatureDate->format(DATE_RFC3339),
				);
			},
		);
		$onError = function (?int $line, SecurityTxtSpecViolation $violation): void {
			[$format, $values] = $this->getViolationMessage($line, $violation);
			$this->consolePrinter->error($format, ...$values);
		};
		$onWarning = function (?int $line, SecurityTxtSpecViolation $violation): void {
			[$format, $values] = $this->getViolationMessage($line, $violation);
			$this->consolePrinter->warning($format, ...$values);
		};
		$this->checkHost->addOnFetchError($onError);
		$this->checkHost->addOnLineError($onError);
		$this->checkHost->addOnFileError($onError);
		$this->checkHost->addOnFetchWarning($onWarning);
		$this->checkHost->addOnLineWarning($onWarning);
		$this->checkHost->addOnFileWarning($onWarning);
	}

}
