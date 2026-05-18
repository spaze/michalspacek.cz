<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\AuthTokenGarbageCollector;

use MichalSpacekCz\GarbageCollector\GarbageCollector;
use MichalSpacekCz\GarbageCollector\GarbageCollectorLogger;
use MichalSpacekCz\GarbageCollector\GarbageCollectorReturnCode;
use MichalSpacekCz\GarbageCollector\GarbageCollectorType;
use MichalSpacekCz\User\AuthTokens\UserAuthTokenLifetime;
use Override;
use Throwable;
use Tracy\Debugger;

final readonly class AuthTokenGarbageCollector implements GarbageCollector
{

	public function __construct(
		/** @var iterable<UserAuthTokenLifetime> */
		private iterable $lifetimes,
		private GarbageCollectorLogger $logger,
	) {
	}


	#[Override]
	public function getGarbageCollectorType(): GarbageCollectorType
	{
		return GarbageCollectorType::AuthTokens;
	}


	#[Override]
	public function getIntervalSeconds(): int
	{
		return 24 * 60 * 60;
	}


	#[Override]
	public function clean(): GarbageCollectorReturnCode
	{
		$deleted = 0;
		$errors = [];
		foreach ($this->lifetimes as $lifetime) {
			$type = $lifetime->getTokenType();
			try {
				$deleted += $lifetime->deleteExpired();
			} catch (Throwable $e) {
				Debugger::log($e);
				$errors[] = "{$type->name}: {$e->getMessage()}";
			}
		}
		$code = $errors === [] ? GarbageCollectorReturnCode::Ok : GarbageCollectorReturnCode::Failure;
		$message = $errors === [] ? null : implode('; ', $errors);
		$this->logger->log($this->getGarbageCollectorType(), $code, $deleted, $message);
		return $code;
	}

}
