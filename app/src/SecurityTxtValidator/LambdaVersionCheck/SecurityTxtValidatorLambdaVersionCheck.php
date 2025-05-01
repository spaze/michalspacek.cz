<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\LambdaVersionCheck;

use AsyncAws\Lambda\LambdaClient;
use Composer\InstalledVersions;
use DateTime;
use DateTimeImmutable;
use MichalSpacekCz\Application\DependencyVersion;
use MichalSpacekCz\DateTime\DateTimeFactoryUtc;
use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorException;
use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorLambdaException;
use MichalSpacekCz\SecurityTxtValidator\LambdaFunctions;
use MichalSpacekCz\SecurityTxtValidator\LambdaResponse;
use MichalSpacekCz\ShouldNotHappenException;
use Nette\Database\Explorer;
use Nette\Utils\JsonException;
use Tracy\Debugger;

final readonly class SecurityTxtValidatorLambdaVersionCheck
{

	public const string PACKAGE_NAME = 'spaze/security-txt';


	public function __construct(
		private Explorer $database,
		private LambdaClient $lambdaClient,
		private LambdaResponse $lambdaResponse,
		private LambdaFunctions $lambdaFunctions,
		private DateTimeFactoryUtc $dateTimeFactory,
	) {
	}


	/**
	 * @param array<array-key, mixed> $decoded
	 * @throws SecurityTxtValidatorException
	 */
	public function checkResponse(array $decoded, int $daysThreshold, bool $isManualCheck): bool
	{
		if (
			!isset($decoded['libVersion'])
			|| !isset($decoded['libReference'])
			|| !is_string($decoded['libVersion'])
			|| !is_string($decoded['libReference'])
		) {
			throw new SecurityTxtValidatorException('Required fields libVersion and libReference are missing or invalid in the decoded array: ' . implode(', ', array_keys($decoded)));
		}

		$lambdaVersion = new DependencyVersion($decoded['libVersion'], $decoded['libReference']);
		$now = $this->dateTimeFactory->getNow();
		$lastSeenVersion = $this->getLastSeenVersion();
		if ($lastSeenVersion === null) {
			$this->insertCheck($now, $lambdaVersion);
		} else {
			if ($now->diff($lastSeenVersion->getLastCheck())->days >= $daysThreshold) {
				$installedVersion = $this->getInstalledVersion();
				if ($lambdaVersion->equals($installedVersion)) {
					$this->updateLastCheck($now, $lastSeenVersion->getId(), $lambdaVersion);
					return true;
				}
				if ($lambdaVersion->equals($lastSeenVersion->getVersion())) {
					$this->updateLastCheck($now, $lastSeenVersion->getId(), $lambdaVersion);
				} else {
					$message = sprintf(
						"The Lambda version (%s) and the app version (%s) of %s don't match, deploy the new version to Lambda or update the app package (%s check)",
						$lambdaVersion->getFullVersion(),
						$installedVersion->getFullVersion(),
						self::PACKAGE_NAME,
						$isManualCheck ? 'manual' : 'auto',
					);
					Debugger::log($message, Debugger::ERROR);
					$this->insertCheck($now, $lambdaVersion);
				}
			}
		}
		return false;
	}


	public function getInstalledVersion(): DependencyVersion
	{
		$version = InstalledVersions::getVersion(self::PACKAGE_NAME);
		$reference = InstalledVersions::getReference(self::PACKAGE_NAME);
		if ($version === null || $reference === null) {
			throw new ShouldNotHappenException(sprintf('Package %s seems to be not installed', self::PACKAGE_NAME));
		}
		return new DependencyVersion($version, $reference);
	}


	/**
	 * @throws JsonException
	 * @throws SecurityTxtValidatorLambdaException
	 * @throws SecurityTxtValidatorException
	 */
	public function check(): bool
	{
		$lambdaResult = $this->lambdaClient->invoke(['FunctionName' => $this->lambdaFunctions->getVersion()]);
		$json = $lambdaResult->getPayload();
		$decoded = $this->lambdaResponse->decode($lambdaResult, $json);
		return $this->checkResponse($decoded, 0, true);
	}


	private function insertCheck(DateTimeImmutable $now, DependencyVersion $version): void
	{
		$this->database->query('INSERT INTO version_check', [
			'last_check' => $now,
			'lambda_version' => $version->getVersion(),
			'lambda_reference' => $version->getReference(),
		]);
	}


	private function updateLastCheck(DateTimeImmutable $now, int $checkId, DependencyVersion $version): void
	{
		$data = [
			'last_check' => $now,
			'lambda_version' => $version->getVersion(),
			'lambda_reference' => $version->getReference(),
		];
		$this->database->query('UPDATE version_check SET ? WHERE id = ?', $data, $checkId);
	}


	public function getLastSeenVersion(): ?LastSeenLambdaVersion
	{
		$result = $this->database->fetch(
			'SELECT
				id,
				last_check AS lastCheck,
				lambda_version AS lambdaVersion,
				lambda_reference AS lambdaReference
			FROM version_check
			ORDER BY last_check DESC
			LIMIT 1',
		);
		if ($result === null) {
			return null;
		}
		assert(is_int($result->id));
		assert($result->lastCheck instanceof DateTime);
		assert(is_string($result->lambdaVersion));
		assert(is_string($result->lambdaReference));
		return new LastSeenLambdaVersion($result->id, $this->dateTimeFactory->createFrom($result->lastCheck), new DependencyVersion($result->lambdaVersion, $result->lambdaReference));
	}

}
