<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use DateTime;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\SecurityTxtValidator\SecurityTxtValidatorFetchMock;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetchResult;
use Spaze\SecurityTxt\Parser\SecurityTxtSplitLines;
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
	) {
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


	public function testClearCacheDeletesNothingForAnUnusableHostname(): void
	{
		$this->validator->clearCache('localhost');
		Assert::same([], $this->database->getParamsForQueryContaining('DELETE FROM policy_cache'));
	}

}

TestCaseRunner::run(SecurityTxtValidatorTest::class);
