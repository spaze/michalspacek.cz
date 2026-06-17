<?php
declare(strict_types = 1);

namespace MichalSpacekCz\User\WebAuthn\Exceptions;

use Throwable;

final class PasskeyResetRevokeFailedException extends PasskeyException
{

	/**
	 * @param non-empty-list<Throwable> $failedSteps
	 */
	public function __construct(private readonly array $failedSteps)
	{
		$failedClasses = implode(', ', array_map(static fn(Throwable $e): string => $e::class, $failedSteps));
		parent::__construct(
			sprintf("Revoking the user's access failed, number of failed steps: %d (%s)", count($failedSteps), $failedClasses),
			previous: $failedSteps[0],
		);
	}


	/**
	 * @return non-empty-list<Throwable>
	 */
	public function getFailedSteps(): array
	{
		return $this->failedSteps;
	}

}
