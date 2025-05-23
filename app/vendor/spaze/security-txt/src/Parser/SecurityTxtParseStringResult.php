<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser;

use Spaze\SecurityTxt\SecurityTxt;
use Spaze\SecurityTxt\Validator\SecurityTxtValidateResult;
use Spaze\SecurityTxt\Violations\SecurityTxtSpecViolation;

final readonly class SecurityTxtParseStringResult
{

	/**
	 * @param array<int<1, max>, list<SecurityTxtSpecViolation>> $lineErrors
	 * @param array<int<1, max>, list<SecurityTxtSpecViolation>> $lineWarnings
	 */
	public function __construct(
		private SecurityTxt $securityTxt,
		private bool $isValid,
		private bool $strictMode,
		private ?int $expiresWarningThreshold,
		private bool $expiresSoon,
		private array $lineErrors,
		private array $lineWarnings,
		private SecurityTxtValidateResult $validateResult,
	) {
	}


	public function getSecurityTxt(): SecurityTxt
	{
		return $this->securityTxt;
	}


	public function isValid(): bool
	{
		return $this->isValid;
	}


	public function isStrictMode(): bool
	{
		return $this->strictMode;
	}


	public function getExpiresWarningThreshold(): ?int
	{
		return $this->expiresWarningThreshold;
	}


	public function isExpiresSoon(): bool
	{
		return $this->expiresSoon;
	}


	/**
	 * @return array<int<1, max>, list<SecurityTxtSpecViolation>>
	 */
	public function getLineErrors(): array
	{
		return $this->lineErrors;
	}


	/**
	 * @return list<SecurityTxtSpecViolation>
	 */
	public function getFileErrors(): array
	{
		return $this->validateResult->getErrors();
	}


	public function hasErrors(): bool
	{
		return $this->getLineErrors() !== [] || $this->getFileErrors() !== [];
	}


	/**
	 * @return array<int<1, max>, list<SecurityTxtSpecViolation>>
	 */
	public function getLineWarnings(): array
	{
		return $this->lineWarnings;
	}


	/**
	 * @return list<SecurityTxtSpecViolation>
	 */
	public function getFileWarnings(): array
	{
		return $this->validateResult->getWarnings();
	}


	public function hasWarnings(): bool
	{
		return $this->getLineWarnings() !== [] || $this->getFileWarnings() !== [];
	}


	public function getValidateResult(): SecurityTxtValidateResult
	{
		return $this->validateResult;
	}

}
