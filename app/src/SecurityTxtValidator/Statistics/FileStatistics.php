<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Statistics;

use Spaze\SecurityTxt\Check\SecurityTxtCheckHostResult;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtFetcherException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtNotFoundException;
use Spaze\SecurityTxt\Parser\SecurityTxtParseStringResult;
use Spaze\SecurityTxt\Violations\SecurityTxtSpecViolation;

/**
 * A violation counts once per file even if it's on multiple lines, so the count says how many files have the
 * problem, not how many lines. Nothing here names the host: the counters say how files are doing, not whose.
 */
final readonly class FileStatistics
{

	/**
	 * @return list<StatisticsBucket>
	 */
	public function forCheckHostResult(SecurityTxtCheckHostResult $result): array
	{
		return $this->forFile(
			$result->isValid(),
			$result->getFetchWarnings() !== [] || $result->getLineWarnings() !== [] || $result->getFileWarnings() !== [],
			[
				$result->getFetchErrors(),
				$result->getFetchWarnings(),
				$result->getFileErrors(),
				$result->getFileWarnings(),
				...$result->getLineErrors(),
				...$result->getLineWarnings(),
			],
			$result->getIsExpired(),
			$result->getExpiryDays(),
		);
	}


	/**
	 * A pasted file is counted like a fetched one, since it is often a file out there: when a host's firewall refuses
	 * the fetcher, the owner loads the file in a browser and pastes it here.
	 *
	 * @return list<StatisticsBucket>
	 */
	public function forParseStringResult(SecurityTxtParseStringResult $result): array
	{
		$expires = $result->getSecurityTxt()->getExpires();
		return $this->forFile(
			$result->isValid(),
			$result->hasWarnings(),
			[
				$result->getFileErrors(),
				$result->getFileWarnings(),
				...$result->getLineErrors(),
				...$result->getLineWarnings(),
			],
			$expires?->isExpired(),
			$expires?->inDays(),
		);
	}


	/**
	 * @return list<StatisticsBucket>
	 */
	public function forFetchFailure(SecurityTxtFetcherException $e): array
	{
		return [$e instanceof SecurityTxtNotFoundException ? StatisticsVerdict::NotFound : StatisticsVerdict::FetchFailed];
	}


	/**
	 * @param list<list<SecurityTxtSpecViolation>> $violationLists
	 * @return list<StatisticsBucket>
	 */
	private function forFile(bool $isValid, bool $hasWarnings, array $violationLists, ?bool $isExpired, ?int $expiryDays): array
	{
		$buckets = [$this->verdict($isValid, $hasWarnings), ...$this->issues($violationLists)];
		$expires = $this->expires($isExpired, $expiryDays);
		if ($expires !== null) {
			$buckets[] = $expires;
		}
		return $buckets;
	}


	private function verdict(bool $isValid, bool $hasWarnings): StatisticsVerdict
	{
		if (!$isValid) {
			return StatisticsVerdict::Invalid;
		}
		return $hasWarnings ? StatisticsVerdict::ValidWithWarnings : StatisticsVerdict::Valid;
	}


	/**
	 * @param list<list<SecurityTxtSpecViolation>> $violationLists
	 * @return list<StatisticsIssue>
	 */
	private function issues(array $violationLists): array
	{
		$violationsByClass = [];
		foreach ($violationLists as $violations) {
			foreach ($violations as $violation) {
				$violationsByClass[$violation::class] = $violation;
			}
		}
		return array_map(fn(SecurityTxtSpecViolation $violation): StatisticsIssue => new StatisticsIssue($violation), array_values($violationsByClass));
	}


	private function expires(?bool $isExpired, ?int $days): ?StatisticsExpires
	{
		if ($isExpired === null || $days === null) {
			return null;
		}
		return match (true) {
			$isExpired => StatisticsExpires::Expired,
			$days <= 31 => StatisticsExpires::WithinMonth,
			$days <= 366 => StatisticsExpires::WithinYear,
			default => StatisticsExpires::OverYear,
		};
	}

}
