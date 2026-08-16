<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser;

use Spaze\SecurityTxt\Fetcher\SecurityTxtFetchResult;
use Spaze\SecurityTxt\SecurityTxt;
use Spaze\SecurityTxt\Violations\SecurityTxtSpecViolation;

final readonly class SecurityTxtParseHostResult
{

	public function __construct(
		private bool $isValid,
		private SecurityTxtParseStringResult $parseStringResult,
		private SecurityTxtFetchResult $fetchResult,
	) {
	}


	public function getSecurityTxt(): SecurityTxt
	{
		return $this->parseStringResult->getSecurityTxt();
	}


	public function isValid(): bool
	{
		return $this->isValid;
	}


	public function isStrictMode(): bool
	{
		return $this->parseStringResult->isStrictMode();
	}


	public function getExpiresWarningThreshold(): ?int
	{
		return $this->parseStringResult->getExpiresWarningThreshold();
	}


	/**
	 * @return list<SecurityTxtSpecViolation>
	 */
	public function getFetchErrors(): array
	{
		return $this->fetchResult->getErrors();
	}


	/**
	 * @return array<int<1, max>, list<SecurityTxtSpecViolation>>
	 */
	public function getLineErrors(): array
	{
		return $this->parseStringResult->getLineErrors();
	}


	/**
	 * @return list<SecurityTxtSpecViolation>
	 */
	public function getFileErrors(): array
	{
		return $this->parseStringResult->getValidateResult()->getErrors();
	}


	public function hasErrors(): bool
	{
		return $this->getFetchErrors() !== [] || $this->getLineErrors() !== [] || $this->getFileErrors() !== [];
	}


	/**
	 * @return list<SecurityTxtSpecViolation>
	 */
	public function getFetchWarnings(): array
	{
		return $this->fetchResult->getWarnings();
	}


	/**
	 * @return array<int<1, max>, list<SecurityTxtSpecViolation>>
	 */
	public function getLineWarnings(): array
	{
		return $this->parseStringResult->getLineWarnings();
	}


	/**
	 * @return list<SecurityTxtSpecViolation>
	 */
	public function getFileWarnings(): array
	{
		return $this->parseStringResult->getValidateResult()->getWarnings();
	}


	public function hasWarnings(): bool
	{
		return $this->getFetchWarnings() !== [] || $this->getLineWarnings() !== [] || $this->getFileWarnings() !== [];
	}


	public function getFetchResult(): SecurityTxtFetchResult
	{
		return $this->fetchResult;
	}

}
