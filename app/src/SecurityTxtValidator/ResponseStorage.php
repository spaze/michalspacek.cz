<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use DateTime;
use DateTimeImmutable;
use MichalSpacekCz\Application\DependencyVersion;
use MichalSpacekCz\DateTime\DateTimeFactoryUtc;
use MichalSpacekCz\DateTime\Exceptions\CannotCreateDateTimeObjectException;
use Nette\Database\Explorer;
use Nette\Database\Row;
use Nette\Database\UniqueConstraintViolationException;

/**
 * Every statement on `responses`. A row starts as a claim, taken before the fetch with `default_port_fetch` set, and becomes a
 * response when the fetch fills it in. The readers that want responses skip claims, and having every reader in one
 * class is what keeps that true: one written anywhere else would not know to.
 */
final readonly class ResponseStorage
{

	public function __construct(
		private Explorer $database,
		private DateTimeFactoryUtc $dateTimeFactory,
		private LibraryVersions $libraryVersions,
		private SecurityTxtLibraryVersion $libraryVersion,
		private string $fetchAbandonedAfter,
	) {
	}


	/**
	 * The newest response of the origin fetched after `$since`, so the caller says how old is too old.
	 */
	public function getNewestSince(SecurityTxtValidatorUrl $url, DateTimeImmutable $since): ?Row
	{
		return $this->database->fetch(
			'SELECT
				fetch_time AS fetchTime,
				check_result AS checkResult
			FROM responses
			WHERE scheme = ? AND ascii_host = ? AND port = ? AND default_port_fetch IS NULL AND fetch_time > ?
			ORDER BY fetch_time DESC, id DESC
			LIMIT 1',
			$url->getScheme(),
			$url->getAsciiHost(),
			$url->getPort(),
			$since,
		);
	}


	/**
	 * The newest response of the origin however old, the last thing it said.
	 */
	public function getNewest(SecurityTxtValidatorUrl $url): ?Row
	{
		return $this->database->fetch(
			'SELECT
				fetch_time AS fetchTime,
				check_result AS checkResult
			FROM responses
			WHERE scheme = ? AND ascii_host = ? AND port = ? AND default_port_fetch IS NULL
			ORDER BY fetch_time DESC, id DESC
			LIMIT 1',
			$url->getScheme(),
			$url->getAsciiHost(),
			$url->getPort(),
		);
	}


	/**
	 * When the host was last fetched, if that was after `$since`, and null when it was not. A fetch still running
	 * counts from the moment it started: its claim row is there from then on, so the requests arriving while it runs
	 * wait for it instead of each fetching the same host again.
	 *
	 * Two allowances per host, not one. The origin on the scheme's own port is the one nearly every visitor asks
	 * about, and it counts only its own fetches, so it cannot be taken away from them. Every other origin of that host
	 * shares the second allowance and counts any fetch of the host, so naming a port nobody has asked about buys no
	 * extra fetches: a host is one machine to be polite to however many names point at it.
	 *
	 * One allowance for all of them would let anyone hold a host's only slot open with two requests a minute, and
	 * nobody could check it again once the cached response aged out.
	 *
	 * `$exceptId` leaves one row out: the caller's own claim, which is a fetch about to start, not a recent one.
	 *
	 * @throws CannotCreateDateTimeObjectException
	 */
	public function getRecentFetchTime(SecurityTxtValidatorUrl $url, DateTimeImmutable $since, ?int $exceptId = null): ?DateTimeImmutable
	{
		$exceptCondition = $exceptId === null ? '' : ' AND id <> ?';
		$exceptParams = $exceptId === null ? [] : [$exceptId];
		$result = $url->isDefaultPort()
			? $this->database->fetch(
				'SELECT fetch_time AS fetchTime
				FROM responses
				WHERE ascii_host = ? AND scheme = ? AND port = ? AND fetch_time > ?' . $exceptCondition . '
				ORDER BY fetch_time DESC, id DESC
				LIMIT 1',
				$url->getAsciiHost(),
				$url->getScheme(),
				$url->getPort(),
				$since,
				...$exceptParams,
			)
			: $this->database->fetch(
				'SELECT fetch_time AS fetchTime
				FROM responses
				WHERE ascii_host = ? AND fetch_time > ?' . $exceptCondition . '
				ORDER BY fetch_time DESC, id DESC
				LIMIT 1',
				$url->getAsciiHost(),
				$since,
				...$exceptParams,
			);
		if ($result === null) {
			return null;
		}
		assert($result->fetchTime instanceof DateTime);
		return $this->dateTimeFactory->createFrom($result->fetchTime);
	}


	/**
	 * Takes one of the host's two fetch allowances for a fetch, or returns null when another request holds it. The
	 * insert is the whole check: `default_port_fetch` says which allowance the fetch is under, the default port's own
	 * or the one every other port shares, and it is in a unique key with the host, so two fetches cannot run under the
	 * same allowance and there is nothing to lock or to read first. A claim older than `$fetchAbandonedAfter` belongs
	 * to a fetch that is over whatever happened to it, so those go first, or a request that died mid-fetch would hold
	 * its allowance for good; all of the host's, because a dead claim for another port holds up every other port.
	 */
	public function claim(SecurityTxtValidatorUrl $url, DateTimeImmutable $now): ?int
	{
		$this->database->query(
			'DELETE FROM responses WHERE ascii_host = ? AND default_port_fetch IS NOT NULL AND fetch_time < ?',
			$url->getAsciiHost(),
			$now->modify("-{$this->fetchAbandonedAfter}"),
		);
		$parserVersionId = $this->libraryVersions->getId($this->libraryVersion->getInstalled());
		try {
			$this->database->query('INSERT INTO responses', [
				'scheme' => $url->getScheme(),
				'ascii_host' => $url->getAsciiHost(),
				'port' => $url->getPort(),
				'default_port_fetch' => $url->isDefaultPort(),
				'fetch_time' => $now,
				'key_parser_library_version' => $parserVersionId,
			]);
		} catch (UniqueConstraintViolationException) {
			return null;
		}
		return (int)$this->database->getInsertId();
	}


	public function fill(int $claimId, DateTimeImmutable $fetchedAt, string $checkResult, DependencyVersion $fetcherVersion): void
	{
		$this->database->query('UPDATE responses SET', [
			'default_port_fetch' => null,
			'fetch_time' => $fetchedAt,
			'check_result' => $checkResult,
			'key_fetcher_library_version' => $this->libraryVersions->getId($fetcherVersion),
		], 'WHERE id = ?', $claimId);
	}


	/**
	 * Only while it is still a claim: a write that did go through before its error reached us has turned the row
	 * into a response, and that stays.
	 */
	public function release(int $claimId): void
	{
		$this->database->query('DELETE FROM responses WHERE id = ? AND default_port_fetch IS NOT NULL', $claimId);
	}


	/**
	 * For the clear button. A claim is a fetch under way, not a response to clear, and deleting it would lose the
	 * response its fetch is about to fill it with.
	 */
	public function deleteFetchedUpTo(SecurityTxtValidatorUrl $url, DateTimeImmutable $upTo): void
	{
		$this->database->query(
			'DELETE FROM responses WHERE scheme = ? AND ascii_host = ? AND port = ? AND default_port_fetch IS NULL AND fetch_time <= ?',
			$url->getScheme(),
			$url->getAsciiHost(),
			$url->getPort(),
			$upTo,
		);
	}

}
