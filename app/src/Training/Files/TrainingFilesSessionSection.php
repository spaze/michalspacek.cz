<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Files;

use MichalSpacekCz\Http\SessionSectionDeprecatedGetSet;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Training\Applications\TrainingApplication;
use Nette\Http\SessionSection;

final class TrainingFilesSessionSection extends SessionSection
{

	use SessionSectionDeprecatedGetSet;


	public function setValues(string $token, ?TrainingApplication $application): void
	{
		parent::set('token', $token);
		parent::set('applicationId', $application?->getId());
	}


	public function isComplete(): bool
	{
		return parent::get('applicationId') !== null && parent::get('token') !== null;
	}


	public function getApplicationId(): int
	{
		$applicationId = parent::get('applicationId');
		if (!is_int($applicationId)) {
			throw new ShouldNotHappenException("Session key applicationId type should be int, but it's a " . get_debug_type($applicationId));
		}
		return $applicationId;
	}


	public function getToken(): string
	{
		$token = parent::get('token');
		if (!is_string($token)) {
			throw new ShouldNotHappenException("Session key token type should be string, but it's a " . get_debug_type($token));
		}
		return $token;
	}

}
