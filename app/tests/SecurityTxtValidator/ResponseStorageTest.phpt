<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use DateTime;
use DateTimeImmutable;
use MichalSpacekCz\Application\DependencyVersion;
use MichalSpacekCz\DateTime\DateTimeFormat;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Database\UniqueConstraintViolationException;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class ResponseStorageTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly ResponseStorage $responseStorage,
		private readonly SecurityTxtValidatorHost $validatorHost,
		private readonly SecurityTxtLibraryVersion $libraryVersion,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	/**
	 * The database double runs no SQL, so what is pinned here is the statements and what is bound to them, not what
	 * the rows would answer. A claim has `default_port_fetch` set and a response has it NULL, and every reader that wants
	 * responses says so in its statement: drop the condition from one and this stops matching.
	 */
	public function testTheReadersThatWantResponsesSkipClaims(): void
	{
		$url = $this->url();
		$since = new DateTimeImmutable('2025-05-01 12:00:00');
		$this->responseStorage->getNewestSince($url, $since);
		$this->responseStorage->getNewest($url);
		$this->responseStorage->deleteFetchedUpTo($url, $since);
		$statements = $this->database->getQueries();
		Assert::count(3, $statements);
		foreach ($statements as $statement) {
			Assert::contains('AND default_port_fetch IS NULL', $statement);
		}
		Assert::same(['https', 'example.com', 443, '2025-05-01 12:00:00'], $this->database->getParamsForQueryContaining('default_port_fetch IS NULL AND fetch_time > ?'));
		Assert::same(['https', 'example.com', 443, '2025-05-01 12:00:00'], $this->database->getParamsForQueryContaining('default_port_fetch IS NULL AND fetch_time <= ?'));
	}


	/**
	 * The origin on the scheme's own port counts only its own fetches, so a visitor naming ports cannot stop everyone
	 * else checking the one address they all actually ask about: the statement for the default origin names the scheme
	 * and the port, and the one for any other names neither. Neither skips claims, because a fetch under way is the
	 * most recent fetch there is.
	 */
	public function testTheDefaultOriginHasAnAllowanceOfItsOwn(): void
	{
		$since = new DateTimeImmutable('2025-05-01 12:00:00');
		Assert::null($this->responseStorage->getRecentFetchTime($this->url(), $since));
		$params = $this->database->getParamsForQueryContaining('WHERE ascii_host = ? AND scheme = ? AND port = ? AND fetch_time > ?');
		Assert::same(['example.com', 'https', 443, '2025-05-01 12:00:00'], $params);
		Assert::same([], $this->database->getParamsForQueryContaining('WHERE ascii_host = ? AND fetch_time > ?'));
		Assert::notContains('default_port_fetch', implode("\n", $this->database->getQueries()));
	}


	public function testEveryOtherOriginOfAHostSharesOne(): void
	{
		$since = new DateTimeImmutable('2025-05-01 12:00:00');
		Assert::null($this->responseStorage->getRecentFetchTime($this->url('https://example.com:8443'), $since));
		$params = $this->database->getParamsForQueryContaining('WHERE ascii_host = ? AND fetch_time > ?');
		Assert::same(['example.com', '2025-05-01 12:00:00'], $params); // any fetch of the host counts, whatever port it was for
		Assert::same([], $this->database->getParamsForQueryContaining('WHERE ascii_host = ? AND scheme = ?'));
		Assert::notContains('default_port_fetch', implode("\n", $this->database->getQueries()));
	}


	/**
	 * Once a request holds a claim and asks again whether it may fetch, its own claim must not be the recent fetch it
	 * finds, so the row is left out by id, in both allowances' statements.
	 */
	public function testTheRecentFetchLookupCanLeaveTheCallersOwnClaimOut(): void
	{
		$since = new DateTimeImmutable('2025-05-01 12:00:00');
		$this->responseStorage->getRecentFetchTime($this->url(), $since, 42);
		$this->responseStorage->getRecentFetchTime($this->url('https://example.com:8443'), $since, 43);
		Assert::same(['example.com', 'https', 443, '2025-05-01 12:00:00', 42], $this->database->getParamsForQueryContaining('WHERE ascii_host = ? AND scheme = ? AND port = ? AND fetch_time > ? AND id <> ?'));
		Assert::same(['example.com', '2025-05-01 12:00:00', 43], $this->database->getParamsForQueryContaining('WHERE ascii_host = ? AND fetch_time > ? AND id <> ?'));
	}


	public function testTheRecentFetchTimeComesBackAsReadFromTheRow(): void
	{
		$this->database->addFetchResult(['fetchTime' => new DateTime('2025-05-01 12:00:00')]);
		$fetchTime = $this->responseStorage->getRecentFetchTime($this->url(), new DateTimeImmutable('2025-05-01 11:59:30'));
		Assert::same('2025-05-01 12:00:00', $fetchTime?->format(DateTimeFormat::MYSQL));
	}


	/**
	 * A claim older than a fetch can possibly run belongs to a request that died, and left there it would hold its
	 * allowance for good. The age is measured from when the fetch started, which is what the claim's stamp says. All
	 * of the host's dead claims go, not only this origin's: one left for another port would hold up every other port.
	 */
	public function testAClaimNobodyIsWaitingOnAnyMoreIsRemovedBeforeClaiming(): void
	{
		$now = new DateTimeImmutable('2025-05-01 12:00:00');
		$this->responseStorage->claim($this->url(), $now);
		$params = $this->database->getParamsForQueryContaining('WHERE ascii_host = ? AND default_port_fetch IS NOT NULL AND fetch_time < ?');
		Assert::same(['example.com', '2025-05-01 11:59:10'], $params);
	}


	/**
	 * The default port has an allowance of its own and every other port of the host shares one: two values that cannot
	 * collide with each other under the unique key, and do collide with themselves.
	 */
	public function testAClaimNamesTheAllowanceItIsUnder(): void
	{
		$now = new DateTimeImmutable('2025-05-01 12:00:00');
		$this->responseStorage->claim($this->url(), $now);
		$this->responseStorage->claim($this->url('https://example.com:8443'), $now);
		$this->responseStorage->claim($this->url('https://example.com:8080'), $now);
		$claims = array_map(fn(array $row): array => [$row['port'], $row['default_port_fetch']], $this->database->getParamsArrayForQuery('INSERT INTO responses'));
		Assert::same([[443, true], [8443, false], [8080, false]], $claims);
	}


	public function testAClaimIsARowUnderTheHostsAllowanceWithTheBuildThatWillParse(): void
	{
		$this->database->addInsertId('7'); // the parser's build
		$this->database->addInsertId('42'); // the claim
		$now = new DateTimeImmutable('2025-05-01 12:00:00');
		Assert::same(42, $this->responseStorage->claim($this->url(), $now));
		$parser = $this->libraryVersion->getInstalled();
		$builds = $this->database->getParamsArrayForQuery('INSERT INTO library_versions');
		Assert::same([$parser->getVersion(), $parser->getReference()], [$builds[0]['version'], $builds[0]['reference']]);
		Assert::same([[
			'scheme' => 'https',
			'ascii_host' => 'example.com',
			'port' => 443,
			'default_port_fetch' => true,
			'fetch_time' => '2025-05-01 12:00:00',
			'key_parser_library_version' => 7,
		]], $this->database->getParamsArrayForQuery('INSERT INTO responses'));
	}


	/**
	 * The insert is the whole check: nothing is read first that could be out of date by the time the insert runs, and
	 * the key refusing a second claim under the same allowance of the host is what says it is taken.
	 */
	public function testAClaimAlreadyHeldIsRefusedByTheKeyNotByAReadFirst(): void
	{
		$this->database->willThrowOnQuery('INSERT INTO responses', new UniqueConstraintViolationException("Duplicate entry 'example.com-\x01' for key 'responses.default_port_fetch'"));
		Assert::null($this->responseStorage->claim($this->url(), new DateTimeImmutable('2025-05-01 12:00:00')));
		Assert::same([], array_filter($this->database->getQueries(), fn(string $sql): bool => str_starts_with($sql, 'SELECT')));
	}


	public function testFillingAClaimTurnsItIntoTheResponseInPlace(): void
	{
		$this->database->addInsertId('8'); // the fetcher's build
		$this->responseStorage->fill(42, new DateTimeImmutable('2025-05-01 12:00:25'), '{"valid":true}', new DependencyVersion('1.2.3', 'cafe1234'));
		Assert::same([[
			'default_port_fetch' => null,
			'fetch_time' => '2025-05-01 12:00:25',
			'check_result' => '{"valid":true}',
			'key_fetcher_library_version' => 8,
		]], $this->database->getParamsArrayForQuery('UPDATE responses SET'));
		Assert::same(['WHERE id = ?', 42], $this->database->getParamsForQuery('UPDATE responses SET'));
	}


	public function testReleasingAClaimDeletesThatRowAndNothingElse(): void
	{
		$this->responseStorage->release(42);
		Assert::same(['DELETE FROM responses WHERE id = ? AND default_port_fetch IS NOT NULL'], $this->database->getQueries());
		Assert::same([42], $this->database->getParamsForQuery('DELETE FROM responses WHERE id = ? AND default_port_fetch IS NOT NULL'));
	}


	private function url(string $url = 'https://example.com'): SecurityTxtValidatorUrl
	{
		return $this->validatorHost->getHost($url);
	}

}

TestCaseRunner::run(ResponseStorageTest::class);
