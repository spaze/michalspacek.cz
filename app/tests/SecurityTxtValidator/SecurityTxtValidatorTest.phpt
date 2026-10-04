<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use DateTime;
use DateTimeImmutable;
use Exception;
use MichalSpacekCz\DateTime\DateTimeFormat;
use MichalSpacekCz\SecurityTxtValidator\Exceptions\SecurityTxtValidatorException;
use MichalSpacekCz\SecurityTxtValidator\ValidationResult\LogoExtraCssClass;
use MichalSpacekCz\SecurityTxtValidator\ValidationResult\LogoExtraIcon;
use MichalSpacekCz\SecurityTxtValidator\ValidationResult\ValidationResultTemplateParameters;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\DateTime\DateTimeMachineFactoryUtc;
use MichalSpacekCz\Test\NoOpTranslator;
use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\SecurityTxtValidator\SecurityTxtValidatorFetchMock;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Utils\Json;
use Override;
use RuntimeException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtCannotOpenUrlExtensionNotLoadedException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtHostNotFoundException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtNotFoundException;
use Spaze\SecurityTxt\Fetcher\Exceptions\SecurityTxtTooManyRedirectsException;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetchResult;
use Spaze\SecurityTxt\Fetcher\SecurityTxtIpAddressType;
use Spaze\SecurityTxt\Fetcher\SecurityTxtRedirects;
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
		private readonly NoOpTranslator $translator,
		private readonly NullLogger $logger,
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
		$this->fetch->reset();
		$this->translator->reset();
		$this->logger->reset();
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
		$params = $this->database->getParamsForQueryContaining('DELETE FROM responses WHERE scheme = ? AND ascii_host = ? AND port = ? AND default_port_fetch IS NULL AND fetch_time <= ?');
		Assert::count(4, $params); // the three parts of the key, and the age the cached result has to have reached
		Assert::same(['https', 'xn--fo-6ja.example', 443], array_slice($params, 0, 3));
	}


	public function testClearCacheDeletesThePortedRowNotTheBareHostRow(): void
	{
		$this->validator->clearCache('https://foó.example:8443/some/path');
		$params = $this->database->getParamsForQueryContaining('DELETE FROM responses WHERE scheme = ?');
		Assert::same(['https', 'xn--fo-6ja.example', 8443], array_slice($params, 0, 3));
	}


	/**
	 * The row is written twice over: claimed under the key it will be read by before the fetch, with nothing to say
	 * yet, and filled in afterwards in place. The claim is what the requests arriving during the fetch find.
	 */
	public function testACacheMissClaimsTheOriginThenFillsTheClaimWithTheResponse(): void
	{
		$this->database->addInsertId('1'); // the parser's build
		$this->database->addInsertId('42'); // the claim
		$this->fetch->setFetchResult($this->fetchResult());
		$this->validator->validate('https://example.com');
		Assert::same(1, $this->fetch->getFetches());
		$claimed = $this->database->getParamsArrayForQuery('INSERT INTO responses');
		Assert::same('https', $claimed[0]['scheme']);
		Assert::same('example.com', $claimed[0]['ascii_host']);
		Assert::same(443, $claimed[0]['port']);
		Assert::true($claimed[0]['default_port_fetch']);
		Assert::false(array_key_exists('check_result', $claimed[0]));
		$filled = $this->database->getParamsArrayForQuery('UPDATE responses SET');
		Assert::null($filled[0]['default_port_fetch']);
		Assert::type('string', $filled[0]['check_result']);
		Assert::same(['WHERE id = ?', 42], $this->database->getParamsForQuery('UPDATE responses SET'));
	}


	public function testAFetchedResponseRecordsWhichLibraryFetchedItAndWhichParsedIt(): void
	{
		$this->database->addInsertId('7'); // the parser's build
		$this->database->addInsertId('42'); // the claim
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
		$claimed = $this->database->getParamsArrayForQuery('INSERT INTO responses');
		Assert::same(7, $claimed[0]['key_parser_library_version']); // known when the claim is taken
		$filled = $this->database->getParamsArrayForQuery('UPDATE responses SET');
		Assert::same(8, $filled[0]['key_fetcher_library_version']); // known only once the fetcher has answered
	}


	public function testACachedResultIsServedWithoutFetching(): void
	{
		$this->fetch->setFetchResult($this->fetchResult());
		$live = $this->validator->validate('https://example.com');
		$written = $this->database->getParamsArrayForQuery('UPDATE responses SET');
		assert(is_string($written[0]['check_result']));
		$this->database->reset();
		$this->database->addFetchResult([
			'fetchTime' => new DateTime(),
			'checkResult' => $written[0]['check_result'],
		]);
		$fetches = $this->fetch->getFetches();
		$cached = $this->validator->validate('https://example.com');
		Assert::same($fetches, $this->fetch->getFetches()); // served from the row, nothing went out
		Assert::same([], $this->database->getParamsArrayForQuery('INSERT INTO responses')); // and nothing was written back
		// And the row was rendered rather than read and dropped: the visitor gets the page the fetch produced
		Assert::true($cached->fileExists);
		Assert::same($live->isValid, $cached->isValid);
		Assert::same((string)$live->contents, (string)$cached->contents);
		Assert::notNull($cached->downloadedAt);
		Assert::notNull($cached->downloadedAgo); // which a freshly fetched result has not got, so this is the stored one
		// No signature, so nothing to look up: the page hides the whole signing key section on the same condition
		Assert::null($cached->signed);
		Assert::null($cached->signingKeyUrl);
		Assert::false($cached->isStale); // and it is within the age it claims, unlike the one served when nothing may be fetched
	}


	/**
	 * The age belongs in the statement for the same reason it does in the DELETE: an age checked anywhere but in the
	 * SELECT leaves a gap between deciding a row is fresh and using it.
	 */
	public function testTheCacheReadIsBoundedByTheTtl(): void
	{
		$this->dateTime->setDateTime(new DateTimeImmutable('2025-05-01 12:00:00'));
		$now = $this->dateTime->getNow();
		$this->fetch->setFetchResult($this->fetchResult());
		$this->validator->validate('https://example.com');
		$params = $this->database->getParamsForQueryContaining('WHERE scheme = ? AND ascii_host = ? AND port = ? AND default_port_fetch IS NULL AND fetch_time > ?');
		Assert::count(8, $params); // the three parts of the key and the age a cached result may not have passed, read before the claim and again once it is held
		Assert::same(['https', 'example.com', 443], array_slice($params, 0, 3));
		Assert::same(['https', 'example.com', 443], array_slice($params, 4, 3));
		// The value too, not only that something was bound there: the same statement with the sign the other way round
		// would serve results from the future, and nothing else about it would look wrong
		Assert::same($now->modify('-5 minutes')->format(DateTimeFormat::MYSQL), $params[3]);
		Assert::same($params[3], $params[7]);
	}


	/**
	 * How long until a host can be fetched again is measured from what this stores, so a fetch that takes 25 seconds
	 * would leave a row already 25 seconds through that wait if the stamp came from when the request started.
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
		$claimed = $this->database->getParamsArrayForQuery('INSERT INTO responses');
		Assert::same($started->format(DateTimeFormat::MYSQL), $claimed[0]['fetch_time']); // the claim says when the fetch started
		$filled = $this->database->getParamsArrayForQuery('UPDATE responses SET');
		Assert::same($started->modify('+25 seconds')->format(DateTimeFormat::MYSQL), $filled[0]['fetch_time']); // the response says when it finished
	}


	/**
	 * A host that answered and has no usable file has been checked as much as one that has, and a row is the only
	 * thing that stops the next visitor spending another fetch on the same answer.
	 */
	public function testAFailedCheckIsStoredUnderTheSameKeyAsASuccessfulOne(): void
	{
		$this->fetch->willThrow($this->hostNotFound());
		$this->validator->validate('https://example.com');
		$claimed = $this->database->getParamsArrayForQuery('INSERT INTO responses');
		Assert::same('https', $claimed[0]['scheme']);
		Assert::same('example.com', $claimed[0]['ascii_host']);
		Assert::same(443, $claimed[0]['port']);
		$filled = $this->database->getParamsArrayForQuery('UPDATE responses SET');
		assert(is_string($filled[0]['check_result']));
		$decoded = Json::decode($filled[0]['check_result'], true);
		assert(is_array($decoded) && is_array($decoded['error']));
		Assert::same(SecurityTxtHostNotFoundException::class, $decoded['error']['class']);
	}


	/**
	 * An origin that already has a row is the normal case once anything has been checked twice. The new response is
	 * a row of its own, claimed and then filled, and the earlier one stays as it was: a single set of values goes to
	 * the database as a new row, and what gets filled in afterwards is that row, not an older one.
	 */
	public function testAFetchedResponseIsANewRowNotAChangeToTheOneBefore(): void
	{
		$this->database->addInsertId('1'); // the parser's build
		$this->database->addInsertId('42'); // the claim
		$this->fetch->setFetchResult($this->fetchResult());
		$this->validator->validate('https://example.com');
		Assert::count(1, $this->database->getParamsArrayForQuery('INSERT INTO responses')); // the values of the new row, and no second set for an existing one
		Assert::same(['WHERE id = ?', 42], $this->database->getParamsForQuery('UPDATE responses SET'));
	}


	/**
	 * The counters say how the files out there are doing and not whose, so a response is counted under the day and
	 * what it said, and the host goes nowhere near the table.
	 */
	public function testAFetchedResponseIsCountedUnderWhatItSaidAndNotWhoSaidIt(): void
	{
		$this->dateTime->setDateTime(new DateTimeImmutable('2025-05-01 12:00:00'));
		$this->fetch->setFetchResult($this->fetchResult());
		$this->validator->validate('https://example.com');
		$counted = $this->database->getParamsArrayForQuery('INSERT INTO statistics');
		Assert::same([[
			['day' => '2025-05-01', 'metric' => 'volume', 'bucket' => 'fetched', 'count' => 1],
			['day' => '2025-05-01', 'metric' => 'verdict', 'bucket' => 'invalid', 'count' => 1],
			['day' => '2025-05-01', 'metric' => 'issue', 'bucket' => 'SecurityTxtNoExpires', 'count' => 1],
		]], $counted);
		Assert::notContains('example.com', Json::encode($counted));
	}


	public function testAStoredFailureIsCountedAsFetchedAndByWhatWentWrong(): void
	{
		$this->dateTime->setDateTime(new DateTimeImmutable('2025-05-01 12:00:00'));
		$this->fetch->willThrow($this->notFound());
		$this->validator->validate('https://example.com');
		Assert::same([[
			['day' => '2025-05-01', 'metric' => 'volume', 'bucket' => 'fetched', 'count' => 1],
			['day' => '2025-05-01', 'metric' => 'verdict', 'bucket' => 'not_found', 'count' => 1],
		]], $this->database->getParamsArrayForQuery('INSERT INTO statistics'));
	}


	/**
	 * What a stored response says was counted when it was fetched, so serving it again counts only the serving, and
	 * the same way whether what was stored is a result or a failure.
	 */
	public function testAResponseServedFromTheStoreIsCountedAsCachedWhateverItSays(): void
	{
		$this->dateTime->setDateTime(new DateTimeImmutable('2025-05-01 12:00:00'));
		$counted = [];
		foreach (['a result' => null, 'a failure' => $this->hostNotFound()] as $name => $failure) {
			$this->database->reset();
			$this->fetch->reset();
			if ($failure === null) {
				$this->fetch->setFetchResult($this->fetchResult());
			} else {
				$this->fetch->willThrow($failure);
			}
			$this->validator->validate('https://example.com');
			$written = $this->database->getParamsArrayForQuery('UPDATE responses SET');
			assert(is_string($written[0]['check_result']));
			$this->database->reset();
			$this->database->addFetchResult([
				'fetchTime' => new DateTime($this->dateTime->getNow()->format(DateTimeFormat::MYSQL)),
				'checkResult' => $written[0]['check_result'],
			]);
			$this->validator->validate('https://example.com');
			$counted[$name] = $this->database->getParamsArrayForQuery('INSERT INTO statistics');
		}
		$cached = [[['day' => '2025-05-01', 'metric' => 'volume', 'bucket' => 'cached', 'count' => 1]]];
		Assert::same(['a result' => $cached, 'a failure' => $cached], $counted);
	}


	/**
	 * The counters describe the hosts, so a failure that is ours, which is not stored as the host's response either,
	 * is not counted as one.
	 */
	public function testAFailureThatIsOursIsNotCountedAsTheHostsResponse(): void
	{
		$failures = [
			'our runtime' => new SecurityTxtCannotOpenUrlExtensionNotLoadedException(new Url('https://example.com/.well-known/security.txt')),
			'our fetcher' => new SecurityTxtValidatorException('Lambda is having a day'),
		];
		$counted = [];
		foreach ($failures as $name => $failure) {
			$this->database->reset();
			$this->fetch->willThrow($failure);
			$this->validator->validate('https://example.com');
			$counted[$name] = $this->database->getParamsArrayForQuery('INSERT INTO statistics');
		}
		Assert::same(['our runtime' => [], 'our fetcher' => []], $counted);
	}


	public function testAPastedFileIsCountedAsPastedAndByWhatItSays(): void
	{
		$this->dateTime->setDateTime(new DateTimeImmutable('2025-05-01 12:00:00'));
		$this->validator->validateDirectInput("Contact: mailto:security@example.com\n", new ValidationResultTemplateParameters());
		Assert::same([[
			['day' => '2025-05-01', 'metric' => 'volume', 'bucket' => 'pasted', 'count' => 1],
			['day' => '2025-05-01', 'metric' => 'verdict', 'bucket' => 'invalid', 'count' => 1],
			['day' => '2025-05-01', 'metric' => 'issue', 'bucket' => 'SecurityTxtNoExpires', 'count' => 1],
		]], $this->database->getParamsArrayForQuery('INSERT INTO statistics'));
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
		$filled = $this->database->getParamsArrayForQuery('UPDATE responses SET');
		Assert::same($started->modify('+25 seconds')->format(DateTimeFormat::MYSQL), $filled[0]['fetch_time']);
	}


	/**
	 * Replayed as the exception it was, so a visitor cannot tell a stored failure from a fresh one, and there is one
	 * place deciding what a failure says rather than a second one for the cached case.
	 */
	public function testAStoredFailureIsReplayedWithoutFetchingAgain(): void
	{
		$this->fetch->willThrow($this->hostNotFound());
		$live = $this->validator->validate('https://example.com');
		$written = $this->database->getParamsArrayForQuery('UPDATE responses SET');
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
		$written = $this->database->getParamsArrayForQuery('UPDATE responses SET');
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
	 * The provider's name is an extra on a not-found message that is complete without it, so the lookup failing must
	 * not turn the host's answer into an apology about our database.
	 */
	public function testADatabaseFailureWhileNamingTheProviderKeepsTheNotFoundAnswer(): void
	{
		$this->fetch->willThrow($this->notFound());
		$this->fetch->whileFetching(function (): void {
			$this->database->willThrowOnRead(new RuntimeException('Database gone'));
		});
		$template = $this->validator->validate('https://example.com');
		$message = (string)$template->errorMessage;
		Assert::contains("Can't read <code>security.txt</code>", $message); // the host's answer, as it reads with the lookup working
		Assert::notContains('known provider', $message);
		Assert::same(['example.com: Database gone'], $this->logger->getLogged());
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
		$this->fetch->whileFetching(function (): void {
			$this->database->willThrow(new Exception('The cache write failed')); // once the claim is taken, so it is the response that cannot be written
		});
		$actual = (string)$this->validator->validate('https://example.com')->errorMessage;

		Assert::same($expected, $actual);
		Assert::notSame('', $expected); // and it is a real message, not both paths being empty
	}


	/**
	 * The fetch is over whether or not its response could be written, so the claim is given back rather than holding
	 * the origin until it counts as abandoned. Only a claim is deleted: a write that went through before its error
	 * reached us has made the row a response, and the condition on the delete keeps it.
	 */
	public function testAFailedCacheWriteGivesTheClaimBack(): void
	{
		$this->database->addInsertId('1'); // the parser's build
		$this->database->addInsertId('42'); // the claim
		$this->fetch->willThrow($this->hostNotFound());
		$this->fetch->whileFetching(function (): void {
			$this->database->willThrowOnQuery('UPDATE responses SET', new Exception('The cache write failed'));
		});
		$this->validator->validate('https://example.com');
		Assert::same([42], $this->database->getParamsForQuery('DELETE FROM responses WHERE id = ? AND default_port_fetch IS NOT NULL'));
	}


	/**
	 * The fetcher throws these for our own runtime and our own settings, so they say nothing about the host, and
	 * filing one under the host's name would tell everyone asking about them that they are broken when we are.
	 */
	public function testOurOwnMisconfigurationIsNotStoredAsTheHostsResponse(): void
	{
		$this->database->addInsertId('1'); // the parser's build
		$this->database->addInsertId('42'); // the claim
		$this->fetch->willThrow(new SecurityTxtCannotOpenUrlExtensionNotLoadedException(new Url('https://example.com/.well-known/security.txt')));
		$this->validator->validate('https://example.com');
		Assert::same([], $this->database->getParamsArrayForQuery('UPDATE responses SET')); // nothing filled in
		Assert::same([42], $this->database->getParamsForQuery('DELETE FROM responses WHERE id = ? AND default_port_fetch IS NOT NULL')); // and the claim taken for it given back
	}


	/**
	 * @return list<array{0:string, 1:LogoExtraCssClass, 2:LogoExtraIcon|null}>
	 */
	public function getLogoCases(): array
	{
		return [
			['localhost', LogoExtraCssClass::Error, LogoExtraIcon::ExclamationTriangle], // refused before anything is fetched
			['//', LogoExtraCssClass::Error, LogoExtraIcon::ExclamationTriangle], // refused as unparseable
		];
	}


	/**
	 * The logo says at a glance what the page says at length, so a page reporting a failure gets the logo for one.
	 *
	 * @dataProvider getLogoCases
	 */
	public function testAPageReportingAFailureGetsTheLogoForOne(string $host, LogoExtraCssClass $cssClass, ?LogoExtraIcon $icon): void
	{
		$template = $this->validator->validate($host);
		Assert::notNull($template->errorMessage);
		Assert::same($cssClass, $template->logoExtraCssClass);
		Assert::same($icon, $template->logoExtraIcon);
	}


	/**
	 * A programming mistake is not an `Exception`, and a visitor should get a page rather than a stack trace whichever
	 * of the two went wrong.
	 */
	public function testAnErrorIsHandledLikeAnException(): void
	{
		$this->database->addInsertId('1'); // the parser's build
		$this->database->addInsertId('42'); // the claim
		$this->fetch->setFetchResult($this->fetchResult());
		$this->fetch->whileFetching(function (): void {
			throw new TypeError('Whatever a caller got wrong');
		});
		$template = $this->validator->validate('https://example.com');
		Assert::contains('Something went wrong while checking', (string)$template->errorMessage);
		Assert::same([42], $this->database->getParamsForQuery('DELETE FROM responses WHERE id = ? AND default_port_fetch IS NOT NULL')); // and the mistake does not keep the host claimed
	}


	/**
	 * The claim is what stops a room of people checking the same site from fetching it once each: a request arriving
	 * while another one holds the origin cannot take it, and the insert itself is the answer, the unique key refusing
	 * a second claim under the same allowance of the host. There is no read before it that could be out of date by
	 * the time it runs.
	 */
	public function testASecondVisitorDuringAFetchWaitsForItInsteadOfFetchingToo(): void
	{
		$this->database->willThrowOnQuery('INSERT INTO responses', new UniqueConstraintViolationException("Duplicate entry 'example.com-\x01' for key 'responses.default_port_fetch'"));
		$this->fetch->setFetchResult($this->fetchResult());
		$template = $this->validator->validate('https://example.com');
		Assert::same(0, $this->fetch->getFetches());
		Assert::same('<code>example.com</code> is being checked right now, try again in a few seconds', (string)$template->errorMessage);
		Assert::same([], $this->database->getParamsArrayForQuery('UPDATE responses SET'));
	}


	/**
	 * A claim older than a fetch can possibly run belongs to a request that died, and left there it would hold its
	 * allowance for good. The age is measured from when the fetch started, which is what the claim's stamp says. All
	 * of the host's dead claims go, not only this origin's: one left for another port would hold up every other port.
	 */
	public function testAClaimNobodyIsWaitingOnAnyMoreIsRemovedBeforeClaiming(): void
	{
		$this->dateTime->setDateTime(new DateTimeImmutable('2025-05-01 12:00:00'));
		$now = $this->dateTime->getNow();
		$this->fetch->setFetchResult($this->fetchResult());
		$this->validator->validate('https://example.com');
		$params = $this->database->getParamsForQueryContaining('WHERE ascii_host = ? AND default_port_fetch IS NOT NULL AND fetch_time < ?');
		Assert::same(['example.com', $now->modify('-50 seconds')->format(DateTimeFormat::MYSQL)], $params);
	}


	/**
	 * A fetch of the host under the other allowance can finish between the first read and the claim, and the claim
	 * cannot collide with a fetch that is over, so the wait is checked once more once the claim is held, leaving the
	 * request's own claim out of it: a fetch found there means the claim goes back and the visitor waits after all.
	 */
	public function testAFetchOfTheHostThatFinishedWhileClaimingIsWaitedFor(): void
	{
		$this->dateTime->setDateTime(new DateTimeImmutable('2025-05-01 12:00:00'));
		$now = $this->dateTime->getNow();
		$this->database->addInsertId('1'); // the parser's build
		$this->database->addInsertId('42'); // the claim
		$this->database->addFetchResult([]); // nothing fresh before the claim
		$this->database->addFetchResult([]); // and no recent fetch before it either
		$this->database->addFetchResult([]); // nothing fresh once the claim is held
		$this->database->addFetchResult(['fetchTime' => new DateTime($now->modify('-5 seconds')->format(DateTimeFormat::MYSQL))]); // but a fetch of the host finished meanwhile
		$this->fetch->setFetchResult($this->fetchResult());
		$template = $this->validator->validate('https://example.com');
		Assert::same(0, $this->fetch->getFetches());
		Assert::contains('was checked a moment ago, try again', (string)$template->errorMessage);
		Assert::same([42], $this->database->getParamsForQuery('DELETE FROM responses WHERE id = ? AND default_port_fetch IS NOT NULL'));
		$params = $this->database->getParamsForQueryContaining('AND fetch_time > ? AND id <> ?');
		Assert::same(['example.com', 'https', 443, $now->modify('-30 seconds')->format(DateTimeFormat::MYSQL), 42], $params); // the request's own claim left out
	}


	/**
	 * Holding the claim, nothing else can be writing this origin, so a response that landed between the first read and
	 * the claim is final: it is served, the claim is given back, and the host is not fetched a second time for it.
	 */
	public function testAResponseThatLandedWhileClaimingIsServedAndTheClaimGivenBack(): void
	{
		$this->fetch->setFetchResult($this->fetchResult());
		$this->validator->validate('https://example.com');
		$filled = $this->database->getParamsArrayForQuery('UPDATE responses SET');
		assert(is_string($filled[0]['check_result']));

		$this->database->reset();
		$this->fetch->reset();
		$this->database->addInsertId('1'); // the parser's build
		$this->database->addInsertId('42'); // the claim
		$this->database->addFetchResult([]); // nothing fresh before the claim
		$this->database->addFetchResult([]); // and no recent fetch
		$this->database->addFetchResult(['fetchTime' => new DateTime(), 'checkResult' => $filled[0]['check_result']]); // but there is a response once the claim is held
		$this->fetch->setFetchResult($this->fetchResult());
		$served = $this->validator->validate('https://example.com');
		Assert::same(0, $this->fetch->getFetches());
		Assert::true($served->fileExists);
		Assert::same([42], $this->database->getParamsForQuery('DELETE FROM responses WHERE id = ? AND default_port_fetch IS NOT NULL'));
		Assert::same([], $this->database->getParamsArrayForQuery('UPDATE responses SET'));
	}


	/**
	 * Nothing was learned about the host when the way to reach it broke, so storing that would answer a question about
	 * the host with the state of our own plumbing.
	 */
	public function testAFailureToReachTheFetcherIsNotStored(): void
	{
		$this->database->addInsertId('1'); // the parser's build
		$this->database->addInsertId('42'); // the claim
		$this->fetch->willThrow(new SecurityTxtValidatorException('Lambda is having a day'));
		$this->validator->validate('https://example.com');
		Assert::same([], $this->database->getParamsArrayForQuery('UPDATE responses SET'));
		Assert::same([42], $this->database->getParamsForQuery('DELETE FROM responses WHERE id = ? AND default_port_fetch IS NOT NULL'));
	}


	/**
	 * Naming another port, or another scheme, is a key nothing is cached under, so with nothing limiting how often the
	 * host itself is fetched, one visitor could spend a fetch on each of them.
	 */
	public function testAnotherPortIsNotFetchedWhileTheHostWasJustFetched(): void
	{
		// `Bootstrap::setTimeZone()` puts the app in Europe/Prague while the validator stamps rows in UTC, so a time
		// built from a literal here would land two hours from where the code under test reads it. Derived instead.
		$this->dateTime->setDateTime(new DateTimeImmutable('2025-05-01 12:00:00'));
		$rowTime = $this->dateTime->getNow();
		$this->dateTime->setDateTime($rowTime->modify('+10 seconds +400000 microseconds'));
		$this->database->addFetchResult([]); // nothing cached for this exact origin
		// The row's timestamp carries no microseconds, because the column holds none, so the wait is 19.6 seconds and
		// the visitor has to be told 20: being told 19 and refused at 19 is worse than being told one second too many
		$this->database->addFetchResult(['fetchTime' => new DateTime($rowTime->format(DateTimeFormat::MYSQL))]);
		$this->fetch->setFetchResult($this->fetchResult());
		$template = $this->validator->validate('https://example.com:8443');
		Assert::same(0, $this->fetch->getFetches());
		Assert::contains('<code>example.com</code> was checked a moment ago, try again ', (string)$template->errorMessage);
		// Tests do not translate, so the message is the key, and how long to wait is the number handed to it
		Assert::contains('messages.timeIntervalIn.seconds', (string)$template->errorMessage);
		Assert::same([20], $this->translator->getParameters('messages.timeIntervalIn.seconds')[0]);
		// The age belongs in the statement for the same reason the TTL does, an age checked anywhere else leaves a gap
		$params = $this->database->getParamsForQueryContaining('WHERE ascii_host = ? AND fetch_time > ?');
		Assert::same('example.com', $params[0]);
		Assert::count(2, $params);
	}


	/**
	 * The number beside a cached result is what decides whether the page offers a Clear button or says to come back
	 * later, so it is the visible half of the wait and has to be computed, not left null.
	 */
	public function testACachedResultSaysWhenTheHostCanBeCheckedAgain(): void
	{
		$this->dateTime->setDateTime(new DateTimeImmutable('2025-05-01 12:00:00'));
		$rowTime = $this->dateTime->getNow();
		$this->fetch->setFetchResult($this->fetchResult());
		$this->validator->validate('https://example.com');
		$written = $this->database->getParamsArrayForQuery('UPDATE responses SET');
		assert(is_string($written[0]['check_result']));

		$this->database->reset();
		$this->translator->reset();
		$this->dateTime->setDateTime($rowTime->modify('+10 seconds'));
		$row = ['fetchTime' => new DateTime($rowTime->format(DateTimeFormat::MYSQL))];
		$this->database->addFetchResult($row + ['checkResult' => $written[0]['check_result']]);
		$this->database->addFetchResult($row); // and the same fetch is what the wait is measured from
		$cached = $this->validator->validate('https://example.com');

		Assert::notNull($cached->clearableIn);
		Assert::same([20], $this->translator->getParameters('messages.timeIntervalIn.seconds')[0]); // 30 less the 10 since
		Assert::same([10], $this->translator->getParameters('messages.timeIntervalAgo.seconds')[0]);
	}


	/**
	 * Being told to come back later is the worst answer available when this origin has said something before. Someone
	 * else holding the host's allowance open should cost a visitor freshness, not the answer.
	 */
	public function testAStaleResponseBeatsNoResponseWhileTheHostIsWaiting(): void
	{
		$this->dateTime->setDateTime(new DateTimeImmutable('2025-05-01 12:00:00'));
		$rowTime = $this->dateTime->getNow();
		$this->fetch->setFetchResult($this->fetchResult());
		$live = $this->validator->validate('https://example.com');
		$written = $this->database->getParamsArrayForQuery('UPDATE responses SET');
		assert(is_string($written[0]['check_result']));

		$this->database->reset();
		$this->fetch->reset();
		$this->dateTime->setDateTime($rowTime->modify('+12 minutes')); // well past the five the answer is fresh for
		$this->database->addFetchResult([]); // so the read bounded by the age finds nothing
		$this->database->addFetchResult(['fetchTime' => new DateTime($rowTime->modify('+11 minutes 40 seconds')->format(DateTimeFormat::MYSQL))]); // but the host was fetched 20 seconds ago
		$this->database->addFetchResult([ // and this origin did say something once
			'fetchTime' => new DateTime($rowTime->format(DateTimeFormat::MYSQL)),
			'checkResult' => $written[0]['check_result'],
		]);
		$stale = $this->validator->validate('https://example.com:8443');

		Assert::same(0, $this->fetch->getFetches()); // still no fetch, the wait is the wait
		Assert::same($live->isValid, $stale->isValid); // but the last answer is on the page
		Assert::same((string)$live->contents, (string)$stale->contents);
		Assert::notNull($stale->downloadedAgo); // saying how old it is
		Assert::true($stale->isStale); // and saying that is what it is, rather than claiming to be cached and current
		Assert::null($stale->errorMessage);
		Assert::same([[['day' => '2025-05-01', 'metric' => 'volume', 'bucket' => 'stale', 'count' => 1]]], $this->database->getParamsArrayForQuery('INSERT INTO statistics'));
	}


	/**
	 * The origin on the scheme's own port counts only its own fetches, so a visitor naming ports cannot stop everyone
	 * else checking the one address they all actually ask about.
	 *
	 * The database double runs no SQL, so what is pinned here is which question gets asked, not what the rows answer:
	 * the statement for the default origin names the scheme and the port, and the one for any other names neither.
	 */
	public function testTheDefaultOriginHasAnAllowanceOfItsOwn(): void
	{
		$this->fetch->setFetchResult($this->fetchResult());
		$this->validator->validate('https://example.com');
		// The statement is asked twice in a fetch, before the claim and again once it is held with the claim left out, so
		// the line break pins the first one; the second is the same statement with one more condition
		$params = $this->database->getParamsForQueryContaining("WHERE ascii_host = ? AND scheme = ? AND port = ? AND fetch_time > ?\n");
		Assert::same(['example.com', 'https', 443], array_slice($params, 0, 3));
		Assert::count(4, $params); // the origin, and how far back a fetch still counts
		Assert::same([], $this->database->getParamsForQueryContaining("WHERE ascii_host = ? AND fetch_time > ?\n"));
	}


	public function testEveryOtherOriginOfAHostSharesOne(): void
	{
		$this->fetch->setFetchResult($this->fetchResult());
		$this->validator->validate('https://example.com:8443');
		$params = $this->database->getParamsForQueryContaining("WHERE ascii_host = ? AND fetch_time > ?\n");
		Assert::same('example.com', $params[0]); // any fetch of the host counts, whatever port it was for
		Assert::count(2, $params);
		Assert::same([], $this->database->getParamsForQueryContaining('WHERE ascii_host = ? AND scheme = ?'));
	}


	/**
	 * Clearing deletes an answer so a new one can be fetched, so refusing the fetch afterwards would leave the visitor
	 * with neither.
	 */
	public function testClearCacheDoesNothingWhileTheHostWasJustFetched(): void
	{
		$this->dateTime->setDateTime(new DateTimeImmutable('2025-05-01 12:00:00'));
		$now = $this->dateTime->getNow();
		$this->database->addFetchResult(['fetchTime' => new DateTime($now->modify('-10 seconds')->format(DateTimeFormat::MYSQL))]);
		$this->validator->clearCache('https://example.com');
		Assert::same([], $this->database->getParamsForQueryContaining('DELETE FROM responses'));
	}


	public function testClearCacheDeletesNothingForAnUnusableHostname(): void
	{
		$this->validator->clearCache('localhost');
		Assert::same([], $this->database->getParamsForQueryContaining('DELETE FROM responses'));
	}


	/**
	 * The wire carries a file the host did not write in UTF-8, so the page is the first thing to be handed such bytes.
	 * It has to show the file and say what is wrong with it, rather than a run of line numbers with nothing between
	 * them, which is what escaping answers for a line holding one.
	 */
	public function testAFileThatIsNotUtf8ReachesThePageWithItsContents(): void
	{
		$contents = "Contact: mailto:security@example.com\n# Kontakt: Michal \xA9pa\xE8ek\n";
		Assert::false(mb_check_encoding($contents, 'UTF-8')); // or this test proves nothing
		$url = new Url('https://example.com/.well-known/security.txt');
		$this->fetch->setFetchResult(new SecurityTxtFetchResult($url, $url, [], $contents, false, $this->splitLines->splitLines($contents), [], []));
		$template = $this->validator->validate('https://example.com');
		$replacement = "\u{FFFD}";
		Assert::contains('Contact: mailto:security@example.com', (string)$template->contents);
		Assert::contains('# Kontakt: Michal ' . $replacement . 'pa' . $replacement . 'ek', (string)$template->contents);
		Assert::count(1, $template->fileErrors);
		Assert::same('The file content is not encoded in <code>UTF-8</code>', (string)$template->fileErrors[0]->getMessage());
	}


	/**
	 * The whole chain goes into the log, not only that the max redirects setting was hit, because where a host was
	 * heading is what says whether one more redirect would have got there.
	 */
	public function testRunningOutOfRedirectsIsLoggedWithTheChain(): void
	{
		$this->fetch->willThrow(new SecurityTxtTooManyRedirectsException(
			new Url('https://www.example.com/en/'),
			new SecurityTxtRedirects('https://example.com/.well-known/security.txt', 'https://www.example.com/.well-known/security.txt', 'https://www.example.com/'),
			3,
		));
		$template = $this->validator->validate('https://example.com');
		Assert::contains('too many redirects', (string)$template->errorMessage);
		Assert::same(
			["example.com: Can't read https://www.example.com/en/, too many redirects, max allowed is 3 (redirects: https://example.com/.well-known/security.txt \u{2192} https://www.example.com/.well-known/security.txt \u{2192} https://www.example.com/, the last one not loaded)"],
			$this->logger->getLogged(),
		);
	}


	/**
	 * Not finding the file is the common verdict, and logging every one of those would bury whatever else is in the log.
	 */
	public function testNotFindingTheFileIsNotLogged(): void
	{
		$this->fetch->willThrow($this->notFound());
		$this->validator->validate('https://example.com');
		Assert::same([], $this->logger->getLogged());
	}

}

TestCaseRunner::run(SecurityTxtValidatorTest::class);
