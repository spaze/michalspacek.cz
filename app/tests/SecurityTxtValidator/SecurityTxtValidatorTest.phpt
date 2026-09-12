<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use DateTime;
use DateTimeImmutable;
use MichalSpacekCz\DateTime\DateTimeFormat;
use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorException;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\DateTime\DateTimeMachineFactoryUtc;
use MichalSpacekCz\Test\SecurityTxtValidator\SecurityTxtValidatorFetchMock;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Json;
use Override;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostNotFoundException;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetchResult;
use Spaze\SecurityTxt\Parser\SecurityTxtSplitLines;
use Spaze\SecurityTxt\SecurityTxtHost;
use Tester\Assert;
use Tester\TestCase;
use Uri\WhatWg\Url;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class SecurityTxtValidatorTest extends TestCase
{

	public function __construct(
		private readonly SecurityTxtValidator $validator,
		private readonly Database $database,
		private readonly SecurityTxtValidatorFetchMock $fetch,
		private readonly SecurityTxtSplitLines $splitLines,
		private readonly DateTimeMachineFactoryUtc $dateTime,
	) {
	}


	private function hostNotFound(): SecurityTxtHostNotFoundException
	{
		$url = new Url('https://example.com/.well-known/security.txt');
		return new SecurityTxtHostNotFoundException($url, new SecurityTxtHost($url));
	}


	private function fetchResult(): SecurityTxtFetchResult
	{
		$url = new Url('https://example.com/.well-known/security.txt');
		$contents = "Contact: mailto:security@example.com\n";
		return new SecurityTxtFetchResult($url, $url, [], $contents, false, $this->splitLines->splitLines($contents), [], []);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->dateTime->setDateTime(null);
	}


	/**
	 * Anyone can press the clear button, so the age condition is the only thing stopping a host being re-fetched over
	 * and over. It belongs in the statement, and the row has to be found by the same key the write used, not by the
	 * URL the visitor typed.
	 */
	public function testClearCacheDeletesByTheWrittenKeyAndOnlyOnceOldEnough(): void
	{
		$this->validator->clearCache('https://foó.example/some/path');
		// The whole condition is the needle: drop the age from the statement and this stops matching, which is the
		// point, because an age checked anywhere but in the DELETE leaves a gap between deciding and deleting.
		$params = $this->database->getParamsForQueryContaining('DELETE FROM policy_cache WHERE scheme = ? AND ascii_host = ? AND port = ? AND last_check_time < ?');
		Assert::count(4, $params); // the three parts of the key, and the age the cached result has to have reached
		Assert::same(['https', 'xn--fo-6ja.example', 443], array_slice($params, 0, 3));
	}


	public function testClearCacheDeletesThePortedRowNotTheBareHostRow(): void
	{
		$this->validator->clearCache('https://foó.example:8443/some/path');
		$params = $this->database->getParamsForQueryContaining('DELETE FROM policy_cache WHERE scheme = ?');
		Assert::same(['https', 'xn--fo-6ja.example', 8443], array_slice($params, 0, 3));
	}


	public function testACacheMissFetchesAndWritesTheRowUnderTheKeyItWillBeReadBy(): void
	{
		$this->fetch->setFetchResult($this->fetchResult());
		$this->validator->validate('https://example.com');
		Assert::same(1, $this->fetch->getFetches());
		$written = $this->database->getParamsArrayForQuery('INSERT INTO policy_cache');
		Assert::same('https', $written[0]['scheme']);
		Assert::same('example.com', $written[0]['ascii_host']);
		Assert::same(443, $written[0]['port']);
	}


	public function testACachedResultIsServedWithoutFetching(): void
	{
		$this->fetch->setFetchResult($this->fetchResult());
		$this->validator->validate('https://example.com');
		$written = $this->database->getParamsArrayForQuery('INSERT INTO policy_cache');
		assert(is_string($written[0]['check_host_result']));
		$this->database->reset();
		$this->database->addFetchResult([
			'lastCheckTime' => new DateTime(),
			'checkHostResult' => $written[0]['check_host_result'],
		]);
		$fetches = $this->fetch->getFetches();
		$this->validator->validate('https://example.com');
		Assert::same($fetches, $this->fetch->getFetches()); // served from the row, nothing went out
		Assert::same([], $this->database->getParamsArrayForQuery('INSERT INTO policy_cache')); // and nothing was written back
	}


	/**
	 * The age belongs in the statement for the same reason it does in the DELETE: an age checked anywhere but in the
	 * SELECT leaves a gap between deciding a row is fresh and using it.
	 */
	public function testTheCacheReadIsBoundedByTheTtl(): void
	{
		$this->fetch->setFetchResult($this->fetchResult());
		$this->validator->validate('https://example.com');
		$params = $this->database->getParamsForQueryContaining('WHERE scheme = ? AND ascii_host = ? AND port = ? AND last_check_time > ?');
		Assert::count(4, $params); // the three parts of the key, and the age a cached result may not have passed
		Assert::same(['https', 'example.com', 443], array_slice($params, 0, 3));
	}


	/**
	 * The floor on how often a host can be fetched again is measured from what this stores, so a fetch that takes 25
	 * seconds would leave a row already 25 seconds into that floor if the stamp came from when the request started.
	 */
	public function testTheRowIsStampedWhenTheFetchFinishedNotWhenTheRequestStarted(): void
	{
		$this->dateTime->setDateTime(new DateTimeImmutable('2025-05-01 12:00:00'));
		$started = $this->dateTime->getNow(); // as the code under test sees it, in UTC
		$this->fetch->setFetchResult($this->fetchResult());
		$this->fetch->whileFetching(function () use ($started): void {
			$this->dateTime->setDateTime($started->modify('+25 seconds'));
		});
		$this->validator->validate('https://example.com');
		$written = $this->database->getParamsArrayForQuery('INSERT INTO policy_cache');
		Assert::same($started->modify('+25 seconds')->format(DateTimeFormat::MYSQL), $written[0]['last_check_time']);
	}


	/**
	 * A host that answered and has no usable file has been checked as much as one that has, and a row is the only
	 * thing that stops the next visitor spending another fetch on the same answer.
	 */
	public function testAFailedCheckIsStoredUnderTheSameKeyAsASuccessfulOne(): void
	{
		$this->fetch->willThrow($this->hostNotFound());
		$this->validator->validate('https://example.com');
		$written = $this->database->getParamsArrayForQuery('INSERT INTO policy_cache');
		Assert::same('https', $written[0]['scheme']);
		Assert::same('example.com', $written[0]['ascii_host']);
		Assert::same(443, $written[0]['port']);
		assert(is_string($written[0]['check_host_result']));
		$decoded = Json::decode($written[0]['check_host_result'], true);
		assert(is_array($decoded) && is_array($decoded['error']));
		Assert::same(SecurityTxtHostNotFoundException::class, $decoded['error']['class']);
	}


	/**
	 * Replayed as the exception it was, so a visitor cannot tell a stored failure from a fresh one, and there is one
	 * place deciding what a failure says rather than a second one for the cached case.
	 */
	public function testAStoredFailureIsReplayedWithoutFetchingAgain(): void
	{
		$this->fetch->willThrow($this->hostNotFound());
		$live = $this->validator->validate('https://example.com');
		$written = $this->database->getParamsArrayForQuery('INSERT INTO policy_cache');
		assert(is_string($written[0]['check_host_result']));
		$this->database->reset();
		$this->database->addFetchResult([
			'lastCheckTime' => new DateTime(),
			'checkHostResult' => $written[0]['check_host_result'],
		]);
		$fetches = $this->fetch->getFetches();
		$cached = $this->validator->validate('https://example.com');
		Assert::same($fetches, $this->fetch->getFetches());
		Assert::same((string)$live->errorMessage, (string)$cached->errorMessage);
		Assert::notNull($cached->downloadedAt); // and it says when it was checked, the same as a stored result does
	}


	/**
	 * Nothing was learned about the host when the way to reach it broke, so storing that would answer a question about
	 * the host with the state of our own plumbing.
	 */
	public function testAFailureToReachTheFetcherIsNotStored(): void
	{
		$this->fetch->willThrow(new SecurityTxtValidatorException('Lambda is having a day'));
		$this->validator->validate('https://example.com');
		Assert::same([], $this->database->getParamsArrayForQuery('INSERT INTO policy_cache'));
	}


	public function testClearCacheDeletesNothingForAnUnusableHostname(): void
	{
		$this->validator->clearCache('localhost');
		Assert::same([], $this->database->getParamsForQueryContaining('DELETE FROM policy_cache'));
	}

}

TestCaseRunner::run(SecurityTxtValidatorTest::class);
