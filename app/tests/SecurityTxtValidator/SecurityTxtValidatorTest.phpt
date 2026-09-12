<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use DateTime;
use DateTimeImmutable;
use Exception;
use MichalSpacekCz\DateTime\DateTimeFormat;
use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorException;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\DateTime\DateTimeMachineFactoryUtc;
use MichalSpacekCz\Test\SecurityTxtValidator\SecurityTxtValidatorFetchMock;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Json;
use Override;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotOpenUrlExtensionNotLoadedException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostNotFoundException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtNotFoundException;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetchResult;
use Spaze\SecurityTxt\Fetcher\SecurityTxtIpAddressType;
use Spaze\SecurityTxt\Parser\SecurityTxtSplitLines;
use Spaze\SecurityTxt\SecurityTxtHost;
use Tester\Assert;
use Tester\TestCase;
use TypeError;
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
		private readonly SecurityTxtLibraryVersion $libraryVersion,
		private readonly DateTimeMachineFactoryUtc $dateTime,
	) {
	}


	private function hostNotFound(): SecurityTxtHostNotFoundException
	{
		$url = new Url('https://example.com/.well-known/security.txt');
		return new SecurityTxtHostNotFoundException($url, new SecurityTxtHost($url));
	}


	private function notFound(): SecurityTxtNotFoundException
	{
		$wellKnownUrl = 'https://example.com/.well-known/security.txt';
		$components = [
			'ip' => '1.2.3.4',
			'type' => SecurityTxtIpAddressType::V4->value,
			'code' => 403,
			'redirects' => ['https://example.com/security.txt'],
			'html' => false,
			'truncated' => false,
		];
		return new SecurityTxtNotFoundException(
			[$wellKnownUrl => $components, 'https://example.com/security.txt' => $components],
			new Url($wellKnownUrl),
		);
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
		$params = $this->database->getParamsForQueryContaining('DELETE FROM responses WHERE scheme = ? AND ascii_host = ? AND port = ? AND fetch_time < ?');
		Assert::count(4, $params); // the three parts of the key, and the age the cached result has to have reached
		Assert::same(['https', 'xn--fo-6ja.example', 443], array_slice($params, 0, 3));
	}


	public function testClearCacheDeletesThePortedRowNotTheBareHostRow(): void
	{
		$this->validator->clearCache('https://foó.example:8443/some/path');
		$params = $this->database->getParamsForQueryContaining('DELETE FROM responses WHERE scheme = ?');
		Assert::same(['https', 'xn--fo-6ja.example', 8443], array_slice($params, 0, 3));
	}


	public function testACacheMissFetchesAndWritesTheRowUnderTheKeyItWillBeReadBy(): void
	{
		$this->fetch->setFetchResult($this->fetchResult());
		$this->validator->validate('https://example.com');
		Assert::same(1, $this->fetch->getFetches());
		$written = $this->database->getParamsArrayForQuery('INSERT INTO responses');
		Assert::same('https', $written[0]['scheme']);
		Assert::same('example.com', $written[0]['ascii_host']);
		Assert::same(443, $written[0]['port']);
	}


	public function testAFetchedResponseRecordsWhichLibraryFetchedItAndWhichParsedIt(): void
	{
		$this->database->addInsertId('7'); // the parser's build
		$this->database->addInsertId('8'); // the fetcher's build
		$this->fetch->setFetchResult($this->fetchResult());
		$this->validator->validate('https://example.com');
		$parser = $this->libraryVersion->getInstalled();
		$fetcher = $this->fetch->getFetcherVersion();
		Assert::notSame($fetcher->getFullVersion(), $parser->getFullVersion()); // or the test could not tell the two apart
		$builds = $this->database->getParamsArrayForQuery('INSERT INTO library_versions');
		Assert::count(2, $builds);
		Assert::same([$parser->getVersion(), $parser->getReference()], [$builds[0]['version'], $builds[0]['reference']]);
		Assert::same([$fetcher->getVersion(), $fetcher->getReference()], [$builds[1]['version'], $builds[1]['reference']]);
		$written = $this->database->getParamsArrayForQuery('INSERT INTO responses');
		Assert::same(7, $written[0]['key_parser_library_version']);
		Assert::same(8, $written[0]['key_fetcher_library_version']);
	}


	public function testACachedResultIsServedWithoutFetching(): void
	{
		$this->fetch->setFetchResult($this->fetchResult());
		$this->validator->validate('https://example.com');
		$written = $this->database->getParamsArrayForQuery('INSERT INTO responses');
		assert(is_string($written[0]['check_result']));
		$this->database->reset();
		$this->database->addFetchResult([
			'fetchTime' => new DateTime(),
			'checkResult' => $written[0]['check_result'],
		]);
		$fetches = $this->fetch->getFetches();
		$this->validator->validate('https://example.com');
		Assert::same($fetches, $this->fetch->getFetches()); // served from the row, nothing went out
		Assert::same([], $this->database->getParamsArrayForQuery('INSERT INTO responses')); // and nothing was written back
	}


	/**
	 * The age belongs in the statement for the same reason it does in the DELETE: an age checked anywhere but in the
	 * SELECT leaves a gap between deciding a row is fresh and using it.
	 */
	public function testTheCacheReadIsBoundedByTheTtl(): void
	{
		$this->fetch->setFetchResult($this->fetchResult());
		$this->validator->validate('https://example.com');
		$params = $this->database->getParamsForQueryContaining('WHERE scheme = ? AND ascii_host = ? AND port = ? AND fetch_time > ?');
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
		$written = $this->database->getParamsArrayForQuery('INSERT INTO responses');
		Assert::same($started->modify('+25 seconds')->format(DateTimeFormat::MYSQL), $written[0]['fetch_time']);
	}


	/**
	 * A host that answered and has no usable file has been checked as much as one that has, and a row is the only
	 * thing that stops the next visitor spending another fetch on the same answer.
	 */
	public function testAFailedCheckIsStoredUnderTheSameKeyAsASuccessfulOne(): void
	{
		$this->fetch->willThrow($this->hostNotFound());
		$this->validator->validate('https://example.com');
		$written = $this->database->getParamsArrayForQuery('INSERT INTO responses');
		Assert::same('https', $written[0]['scheme']);
		Assert::same('example.com', $written[0]['ascii_host']);
		Assert::same(443, $written[0]['port']);
		assert(is_string($written[0]['check_result']));
		$decoded = Json::decode($written[0]['check_result'], true);
		assert(is_array($decoded) && is_array($decoded['error']));
		Assert::same(SecurityTxtHostNotFoundException::class, $decoded['error']['class']);
	}


	/**
	 * An origin that already has a row is the normal case once anything has been checked twice. The new response is
	 * a row of its own and the earlier one stays as it was: a single set of values goes to the database, with nothing
	 * to put in the place of an existing row.
	 */
	public function testAFetchedResponseIsANewRowNotAChangeToTheOneBefore(): void
	{
		$this->fetch->setFetchResult($this->fetchResult());
		$this->validator->validate('https://example.com');
		$written = $this->database->getParamsArrayForQuery('INSERT INTO responses');
		Assert::count(1, $written); // the values of the new row, and no second set for an existing one
	}


	/**
	 * A failure takes as long to arrive as an answer does, and the wait before the host is asked again is measured
	 * from what this stores, so it is stamped when the fetch gave up rather than when the request started.
	 */
	public function testAFailedCheckIsStampedWhenTheFetchGaveUp(): void
	{
		$this->dateTime->setDateTime(new DateTimeImmutable('2025-05-01 12:00:00'));
		$started = $this->dateTime->getNow();
		$this->fetch->whileFetching(function () use ($started): void {
			$this->dateTime->setDateTime($started->modify('+25 seconds'));
		});
		$this->fetch->willThrow($this->hostNotFound());
		$this->validator->validate('https://example.com');
		$written = $this->database->getParamsArrayForQuery('INSERT INTO responses');
		Assert::same($started->modify('+25 seconds')->format(DateTimeFormat::MYSQL), $written[0]['fetch_time']);
	}


	/**
	 * Replayed as the exception it was, so a visitor cannot tell a stored failure from a fresh one, and there is one
	 * place deciding what a failure says rather than a second one for the cached case.
	 */
	public function testAStoredFailureIsReplayedWithoutFetchingAgain(): void
	{
		$this->fetch->willThrow($this->hostNotFound());
		$live = $this->validator->validate('https://example.com');
		$written = $this->database->getParamsArrayForQuery('INSERT INTO responses');
		assert(is_string($written[0]['check_result']));
		$this->database->reset();
		$this->database->addFetchResult([
			'fetchTime' => new DateTime(),
			'checkResult' => $written[0]['check_result'],
		]);
		$fetches = $this->fetch->getFetches();
		$cached = $this->validator->validate('https://example.com');
		Assert::same($fetches, $this->fetch->getFetches());
		Assert::same((string)$live->errorMessage, (string)$cached->errorMessage);
		Assert::notNull($cached->downloadedAt); // and it says when it was checked, the same as a stored result does
	}


	/**
	 * The failure a host is most likely to produce, and the one with the most to lose on the way through the cache:
	 * its own arm in validate() reads the redirects and the IP addresses back out of the exception, so a stored one has
	 * to come back still carrying both. The other failures reach an arm that only reads the message.
	 */
	public function testAStoredNotFoundIsReplayedStillCarryingItsRedirectsAndAddresses(): void
	{
		$this->fetch->willThrow($this->notFound());
		$live = $this->validator->validate('https://example.com');
		$written = $this->database->getParamsArrayForQuery('INSERT INTO responses');
		assert(is_string($written[0]['check_result']));
		$this->database->reset();
		$this->database->addFetchResult([
			'fetchTime' => new DateTime(),
			'checkResult' => $written[0]['check_result'],
		]);
		$fetches = $this->fetch->getFetches();
		$cached = $this->validator->validate('https://example.com');

		Assert::same($fetches, $this->fetch->getFetches());
		Assert::same((string)$live->errorMessage, (string)$cached->errorMessage);
		Assert::notSame([], $cached->allRedirects); // getAllRedirects() survived
		Assert::same($live->allRedirects, $cached->allRedirects);
		// And getIpAddresses() did too: the range lookup only runs for an address the exception still knows about
		Assert::notSame([], $this->database->getParamsForQueryContaining('FROM ip_ranges r'));
	}


	/**
	 * The answer is the visitor's; writing it down is ours. A cache write that fails has to leave them with what their
	 * host said rather than replacing it with a generic apology about our database.
	 */
	public function testAFailedCacheWriteDoesNotReplaceTheResponseItWasWriting(): void
	{
		$this->fetch->willThrow($this->hostNotFound());
		$expected = (string)$this->validator->validate('https://example.com')->errorMessage;

		$this->database->reset();
		$this->fetch->willThrow($this->hostNotFound());
		$this->database->willThrow(new Exception('The cache write failed'));
		$actual = (string)$this->validator->validate('https://example.com')->errorMessage;

		Assert::same($expected, $actual);
		Assert::notSame('', $expected); // and it is a real message, not both paths being empty
	}


	/**
	 * The fetcher throws these for our own runtime and our own settings, so they say nothing about the host, and
	 * filing one under the host's name would tell everyone asking about them that they are broken when we are.
	 */
	public function testOurOwnMisconfigurationIsNotStoredAsTheHostsResponse(): void
	{
		$this->fetch->willThrow(new SecurityTxtCannotOpenUrlExtensionNotLoadedException(new Url('https://example.com/.well-known/security.txt')));
		$this->validator->validate('https://example.com');
		Assert::same([], $this->database->getParamsArrayForQuery('INSERT INTO responses'));
	}


	/**
	 * A programming mistake is not an `Exception`, and a visitor should get a page rather than a stack trace whichever
	 * of the two went wrong.
	 */
	public function testAnErrorIsHandledLikeAnException(): void
	{
		$this->fetch->setFetchResult($this->fetchResult());
		$this->fetch->whileFetching(function (): void {
			throw new TypeError('Whatever a caller got wrong');
		});
		$template = $this->validator->validate('https://example.com');
		Assert::contains('Something went wrong while checking', (string)$template->errorMessage);
	}


	/**
	 * Nothing was learned about the host when the way to reach it broke, so storing that would answer a question about
	 * the host with the state of our own plumbing.
	 */
	public function testAFailureToReachTheFetcherIsNotStored(): void
	{
		$this->fetch->willThrow(new SecurityTxtValidatorException('Lambda is having a day'));
		$this->validator->validate('https://example.com');
		Assert::same([], $this->database->getParamsArrayForQuery('INSERT INTO responses'));
	}


	public function testClearCacheDeletesNothingForAnUnusableHostname(): void
	{
		$this->validator->clearCache('localhost');
		Assert::same([], $this->database->getParamsForQueryContaining('DELETE FROM responses'));
	}

}

TestCaseRunner::run(SecurityTxtValidatorTest::class);
