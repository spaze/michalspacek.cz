<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Validator;

use JsonSerializable;
use Override;
use Spaze\SecurityTxt\Violations\SecurityTxtSpecViolation;

final readonly class SecurityTxtValidateResult implements JsonSerializable
{

	/**
	 * @param list<SecurityTxtSpecViolation> $errors
	 * @param list<SecurityTxtSpecViolation> $warnings
	 */
	public function __construct(
		private array $errors,
		private array $warnings,
	) {
	}


	/**
	 * @return list<SecurityTxtSpecViolation>
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}


	/**
	 * @return list<SecurityTxtSpecViolation>
	 */
	public function getWarnings(): array
	{
		return $this->warnings;
	}


	/**
	 * @return array<string, mixed>
	 */
	#[Override]
	public function jsonSerialize(): array
	{
		return [
			'errors' => $this->getErrors(),
			'warnings' => $this->getWarnings(),
		];
	}

}
