<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Check;

use DateTimeImmutable;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtFetcherException;

final readonly class SecurityTxtCheckHostCli
{

	public function __construct(
		private ConsolePrinter $consolePrinter,
		private SecurityTxtCheckHost $checkHost,
	) {
	}


	public function check(
		?string $url,
		?int $expiresWarningThreshold,
		bool $colors,
		bool $strictMode,
		bool $requireTopLevelLocation,
		bool $noIpv6,
		string $usageHelp,
	): never {
		$this->checkHost->addOnUrl(
			function (string $url): void {
				$this->consolePrinter->info('Loading security.txt from ' . $this->consolePrinter->colorBold($url));
			},
		);
		$this->checkHost->addOnFinalUrl(
			function (string $url): void {
				$this->consolePrinter->info('Selecting security.txt located at ' . $this->consolePrinter->colorBold($url) . ' for further tests');
			},
		);
		$this->checkHost->addOnRedirect(
			function (string $url, string $destination): void {
				$this->consolePrinter->info('Redirected from ' . $this->consolePrinter->colorBold($url) . ' to ' . $this->consolePrinter->colorBold($destination));
			},
		);
		$this->checkHost->addOnUrlNotFound(
			function (string $url): void {
				$this->consolePrinter->info('Not found ' . $this->consolePrinter->colorBold($url));
			},
		);
		$this->checkHost->addOnIsExpired(
			function (int $daysAgo, DateTimeImmutable $expiryDate): void {
				$this->consolePrinter->error($this->consolePrinter->colorRed("The file has expired {$daysAgo} " . ($daysAgo === 1 ? 'day' : 'days') . ' ago') . " ({$expiryDate->format(DATE_RFC3339)})");
			},
		);
		$this->checkHost->addOnExpires(
			function (int $inDays, DateTimeImmutable $expiryDate): void {
				$this->consolePrinter->info($this->consolePrinter->colorGreen("The file will expire in {$inDays} " . ($inDays === 1 ? 'day' : 'days')) . " ({$expiryDate->format(DATE_RFC3339)})");
			},
		);
		$this->checkHost->addOnHost(
			function (string $host): void {
				$this->consolePrinter->info('Parsing security.txt for ' . $this->consolePrinter->colorBold($host));
			},
		);
		$this->checkHost->addOnValidSignature(
			function (string $keyFingerprint, DateTimeImmutable $signatureDate): void {
				$this->consolePrinter->info(sprintf(
					'%s, key %s, signed on %s',
					$this->consolePrinter->colorGreen('Signature valid'),
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

		if ($colors) {
			$this->consolePrinter->enableColors();
		}
		if ($url === null) {
			$this->consolePrinter->info($usageHelp);
			$this->exit(CheckExitStatus::NoFile);
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
				$this->consolePrinter->error($this->consolePrinter->colorRed('Please update the file!'));
				$this->exit(CheckExitStatus::Error);
			} else {
				$this->exit(CheckExitStatus::Ok);
			}
		} catch (SecurityTxtFetcherException $e) {
			$this->consolePrinter->error($e->getMessage());
			$this->exit(CheckExitStatus::FileError);
		}
	}


	private function exit(CheckExitStatus $exitStatus): never
	{
		exit($exitStatus->value);
	}

}
