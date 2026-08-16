<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Check;

use Closure;
use DateTimeImmutable;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtFetcherException;
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
		if ($colors) {
			$this->consolePrinter->enableColors();
		}
		if ($showUsageHelp) {
			$this->consolePrinter->info($usageHelp);
			$this->exit(CheckExitStatus::Ok);
			return;
		} elseif ($url === null) {
			$this->consolePrinter->info($usageHelp);
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
				$this->consolePrinter->error($this->consolePrinter->colorRed('The file is invalid'));
				$this->exit(CheckExitStatus::Error);
			} else {
				$this->consolePrinter->ok($this->consolePrinter->colorGreen('The file is valid'));
				$this->exit(CheckExitStatus::Ok);
			}
		} catch (SecurityTxtFetcherException $e) {
			$this->consolePrinter->error($e->getMessage());
			$this->exit(CheckExitStatus::FileError);
		}
	}


	private function exit(CheckExitStatus $exitStatus): void
	{
		($this->exit)($exitStatus->value);
	}


	private function initCheckHostCallbacks(): void
	{
		$this->checkHost->addOnUrl(
			function (string $url): void {
				if ($this->verbose) {
					$this->consolePrinter->info('Loading security.txt from ' . $this->consolePrinter->colorBold($url));
				}
			},
		);
		$this->checkHost->addOnRedirect(
			function (string $url, string $destination): void {
				if ($this->verbose) {
					$this->consolePrinter->info('Redirected from ' . $this->consolePrinter->colorBold($url) . ' to ' . $this->consolePrinter->colorBold($destination));
				}
			},
		);
		$this->checkHost->addOnUrlNotFound(
			function (string $url): void {
				if ($this->verbose) {
					$this->consolePrinter->info('Not found ' . $this->consolePrinter->colorBold($url));
				}
			},
		);
		$this->checkHost->addOnFinalUrl(
			function (string $url): void {
				$this->consolePrinter->info('Using ' . $this->consolePrinter->colorBold($url));
			},
		);
		$this->checkHost->addOnIsExpired(
			function (int $daysAgo, DateTimeImmutable $expiryDate): void {
				$this->consolePrinter->error($this->consolePrinter->colorRed("The file has expired {$daysAgo} " . ($daysAgo === 1 ? 'day' : 'days') . ' ago') . " ({$expiryDate->format(DATE_RFC3339)})");
			},
		);
		$this->checkHost->addOnExpires(
			function (int $inDays, DateTimeImmutable $expiryDate): void {
				$this->consolePrinter->ok("The file will expire in {$inDays} " . ($inDays === 1 ? 'day' : 'days') . " ({$expiryDate->format(DATE_RFC3339)})");
			},
		);
		$this->checkHost->addOnHost(
			function (string $host): void {
				$this->consolePrinter->info('Parsing security.txt for ' . $this->consolePrinter->colorBold($host));
			},
		);
		$this->checkHost->addOnValidSignature(
			function (string $keyFingerprint, DateTimeImmutable $signatureDate): void {
				$this->consolePrinter->ok(sprintf(
					'Signature valid, key %s, signed on %s',
					$keyFingerprint,
					$signatureDate->format(DATE_RFC3339),
				));
			},
		);
		$onError = function (?int $line, string $message, string $howToFix, ?string $correctValue): void {
			$this->consolePrinter->error(sprintf(
				'%s%s%s (How to fix: %s%s)',
				$line !== null ? 'on line ' : '',
				$line !== null ? $this->consolePrinter->colorBold((string)$line) . ': ' : '',
				$message,
				$howToFix,
				$correctValue !== null ? ", e.g. {$correctValue}" : '',
			));
		};
		$onWarning = function (?int $line, string $message, string $howToFix, ?string $correctValue): void {
			$this->consolePrinter->warning(sprintf(
				'%s%s%s (How to fix: %s%s)',
				$line !== null ? 'on line ' : '',
				$line !== null ? $this->consolePrinter->colorBold((string)$line) . ': ' : '',
				$message,
				$howToFix,
				$correctValue !== null ? ", e.g. {$correctValue}" : '',
			));
		};
		$this->checkHost->addOnFetchError($onError);
		$this->checkHost->addOnLineError($onError);
		$this->checkHost->addOnFileError($onError);
		$this->checkHost->addOnFetchWarning($onWarning);
		$this->checkHost->addOnLineWarning($onWarning);
		$this->checkHost->addOnFileWarning($onWarning);
	}

}
