<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\LambdaVersionCheck;

use AsyncAws\Lambda\LambdaClient;
use DateTime;
use DateTimeImmutable;
use MichalSpacekCz\Application\DependencyVersion;
use MichalSpacekCz\DateTime\DateTimeFactoryUtc;
use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorException;
use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorLambdaException;
use MichalSpacekCz\SecurityTxtValidator\LambdaFunctions;
use MichalSpacekCz\SecurityTxtValidator\LambdaResponse;
use MichalSpacekCz\SecurityTxtValidator\LibraryVersions;
use MichalSpacekCz\SecurityTxtValidator\SecurityTxtLibraryVersion;
use Nette\Database\Explorer;
use Nette\Utils\JsonException;
use Tracy\Debugger;

final readonly class SecurityTxtValidatorLambdaVersionCheck
{

	public function __construct(
		private Explorer $database,
		private LambdaClient $lambdaClient,
		private LambdaResponse $lambdaResponse,
		private LambdaFunctions $lambdaFunctions,
		private DateTimeFactoryUtc $dateTimeFactory,
		private SecurityTxtLibraryVersion $libraryVersion,
		private LibraryVersions $libraryVersions,
	) {
	}


	/**
	 * @param array<array-key, mixed> $decoded
	 * @throws SecurityTxtValidatorException
	 */
	public function getVersionFromResponse(array $decoded): DependencyVersion
	{
		if (
			!isset($decoded['libVersion'])
			|| !isset($decoded['libReference'])
			|| !is_string($decoded['libVersion'])
			|| !is_string($decoded['libReference'])
		) {
			throw new SecurityTxtValidatorException('Required fields libVersion and libReference are missing or invalid in the decoded array: ' . implode(', ', array_keys($decoded)));
		}
		return new DependencyVersion($decoded['libVersion'], $decoded['libReference']);
	}


	public function checkResponse(DependencyVersion $lambdaVersion, int $daysThreshold, bool $isManualCheck): bool
	{
		$now = $this->dateTimeFactory->getNow();
		$lastSeenVersion = $this->getLastSeenVersion();
		if ($lastSeenVersion === null) {
			$this->insertCheck($now, $lambdaVersion);
		} else {
			if ($now->diff($lastSeenVersion->getLastCheck())->days >= $daysThreshold) {
				$installedVersion = $this->libraryVersion->getInstalled();
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
						SecurityTxtLibraryVersion::PACKAGE_NAME,
						$isManualCheck ? 'manual' : 'auto',
					);
					Debugger::log($message, Debugger::ERROR);
					$this->insertCheck($now, $lambdaVersion);
				}
			}
		}
		return false;
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
		return $this->checkResponse($this->getVersionFromResponse($decoded), 0, true);
	}


	private function insertCheck(DateTimeImmutable $now, DependencyVersion $version): void
	{
		$this->database->query('INSERT INTO version_check', [
			'last_check' => $now,
			'key_library_version' => $this->libraryVersions->getId($version),
		]);
	}


	private function updateLastCheck(DateTimeImmutable $now, int $checkId, DependencyVersion $version): void
	{
		$data = [
			'last_check' => $now,
			'key_library_version' => $this->libraryVersions->getId($version),
		];
		$this->database->query('UPDATE version_check SET ? WHERE id = ?', $data, $checkId);
	}


	public function getLastSeenVersion(): ?LastSeenLambdaVersion
	{
		$result = $this->database->fetch(
			'SELECT
				vc.id,
				vc.last_check AS lastCheck,
				lv.version AS lambdaVersion,
				lv.reference AS lambdaReference
			FROM version_check vc
				JOIN library_versions lv ON vc.key_library_version = lv.id
			ORDER BY vc.last_check DESC
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
