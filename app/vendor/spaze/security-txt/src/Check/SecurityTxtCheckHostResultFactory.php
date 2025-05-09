<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Check;

use Spaze\SecurityTxt\Check\Exceptions\SecurityTxtCannotParseJsonException;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetchResultFactory;
use Spaze\SecurityTxt\Json\SecurityTxtJson;
use Spaze\SecurityTxt\Parser\SecurityTxtParseResult;

final readonly class SecurityTxtCheckHostResultFactory
{

	public function __construct(
		private SecurityTxtJson $securityTxtJson,
		private SecurityTxtFetchResultFactory $securityTxtFetchResultFactory,
	) {
	}


	public function create(string $host, SecurityTxtParseResult $parseResult): SecurityTxtCheckHostResult
	{
		return new SecurityTxtCheckHostResult(
			$host,
			$parseResult->getFetchResult(),
			$parseResult->getFetchErrors(),
			$parseResult->getFetchWarnings(),
			$parseResult->getLineErrors(),
			$parseResult->getLineWarnings(),
			$parseResult->getFileErrors(),
			$parseResult->getFileWarnings(),
			$parseResult->getSecurityTxt(),
			$parseResult->isExpiresSoon(),
			$parseResult->getSecurityTxt()->getExpires()?->isExpired(),
			$parseResult->getSecurityTxt()->getExpires()?->inDays(),
			$parseResult->isValid(),
			$parseResult->isStrictMode(),
			$parseResult->getExpiresWarningThreshold(),
		);
	}


	/**
	 * @param array<array-key, mixed> $values
	 * @throws SecurityTxtCannotParseJsonException
	 */
	public function createFromJsonValues(array $values): SecurityTxtCheckHostResult
	{
		if (!is_string($values['class'])) {
			throw new SecurityTxtCannotParseJsonException('class is not a string');
		}
		if ($values['class'] !== SecurityTxtCheckHostResult::class) {
			throw new SecurityTxtCannotParseJsonException('class is not ' . SecurityTxtCheckHostResult::class);
		}
		if (!is_string($values['host'])) {
			throw new SecurityTxtCannotParseJsonException('host is not a string');
		}
		if (!is_array($values['fetchResult'])) {
			throw new SecurityTxtCannotParseJsonException('fetchResult is not an array');
		}
		if (!is_array($values['fetchErrors'])) {
			throw new SecurityTxtCannotParseJsonException('fetchErrors is not an array');
		}
		if (!is_array($values['fetchWarnings'])) {
			throw new SecurityTxtCannotParseJsonException('fetchWarnings is not an array');
		}
		if (!is_array($values['lineErrors'])) {
			throw new SecurityTxtCannotParseJsonException('lineErrors is not an array');
		}
		$lineErrors = [];
		foreach ($values['lineErrors'] as $line => $violations) {
			if (!is_int($line)) {
				throw new SecurityTxtCannotParseJsonException("lineErrors > {$line} key is not an int");
			}
			if (!is_array($violations)) {
				throw new SecurityTxtCannotParseJsonException("lineErrors > {$line} is not an array");
			}
			$lineErrors[$line] = $this->securityTxtJson->createViolationsFromJsonValues(array_values($violations));
		}
		if (!is_array($values['lineWarnings'])) {
			throw new SecurityTxtCannotParseJsonException('lineWarnings is not an array');
		}
		$lineWarnings = [];
		foreach ($values['lineWarnings'] as $line => $violations) {
			if (!is_int($line)) {
				throw new SecurityTxtCannotParseJsonException("lineWarnings > {$line} key is not an int");
			}
			if (!is_array($violations)) {
				throw new SecurityTxtCannotParseJsonException("lineWarnings > {$line} is not an array");
			}
			$lineWarnings[$line] = $this->securityTxtJson->createViolationsFromJsonValues(array_values($violations));
		}
		if (!is_array($values['fileErrors'])) {
			throw new SecurityTxtCannotParseJsonException('fileErrors is not an array');
		}
		if (!is_array($values['fileWarnings'])) {
			throw new SecurityTxtCannotParseJsonException('fileWarnings is not an array');
		}
		if (!is_array($values['securityTxt'])) {
			throw new SecurityTxtCannotParseJsonException('securityTxt is not an array');
		}
		$securityTxtFields = [];
		foreach ($values['securityTxt'] as $field => $fieldValues) {
			if (!is_string($field)) {
				throw new SecurityTxtCannotParseJsonException("securityTxt > {$field} key is not a string");
			}
			if ($fieldValues !== null && !is_array($fieldValues)) {
				throw new SecurityTxtCannotParseJsonException("securityTxt > {$field} is not an array");
			}
			$securityTxtFields[$field] = $fieldValues;
		}
		if (!is_bool($values['expiresSoon'])) {
			throw new SecurityTxtCannotParseJsonException('expiresSoon is not a bool');
		}
		if ($values['expired'] !== null && !is_bool($values['expired'])) {
			throw new SecurityTxtCannotParseJsonException('expired is not an int');
		}
		if ($values['expiryDays'] !== null && !is_int($values['expiryDays'])) {
			throw new SecurityTxtCannotParseJsonException('expiryDays is not an int');
		}
		if (!is_bool($values['valid'])) {
			throw new SecurityTxtCannotParseJsonException('valid is not a bool');
		}
		if (!is_bool($values['strictMode'])) {
			throw new SecurityTxtCannotParseJsonException('strictMode is not a bool');
		}
		if ($values['expiresWarningThreshold'] !== null && !is_int($values['expiresWarningThreshold'])) {
			throw new SecurityTxtCannotParseJsonException('expiresWarningThreshold is not an int');
		}
		return new SecurityTxtCheckHostResult(
			$values['host'],
			$this->securityTxtFetchResultFactory->createFromJsonValues($values['fetchResult']),
			$this->securityTxtJson->createViolationsFromJsonValues(array_values($values['fetchErrors'])),
			$this->securityTxtJson->createViolationsFromJsonValues(array_values($values['fetchWarnings'])),
			$lineErrors,
			$lineWarnings,
			$this->securityTxtJson->createViolationsFromJsonValues(array_values($values['fileErrors'])),
			$this->securityTxtJson->createViolationsFromJsonValues(array_values($values['fileWarnings'])),
			$this->securityTxtJson->createFromJsonValues($securityTxtFields),
			$values['expiresSoon'],
			$values['expired'],
			$values['expiryDays'],
			$values['valid'],
			$values['strictMode'],
			$values['expiresWarningThreshold'],
		);
	}

}
