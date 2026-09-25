<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use MichalSpacekCz\DateTime\DateTimeFactoryUtc;
use MichalSpacekCz\GarbageCollector\GarbageCollector;
use MichalSpacekCz\GarbageCollector\GarbageCollectorLogger;
use MichalSpacekCz\GarbageCollector\GarbageCollectorReturnCode;
use MichalSpacekCz\GarbageCollector\GarbageCollectorType;
use Nette\Database\Explorer;
use Override;
use Throwable;
use Tracy\Debugger;

/**
 * A newest row outlives the response's TTL on purpose: past it, the row is still the last response its origin gave,
 * and the page shows that while the host cannot be fetched. What goes here is a row that a newer one of the same
 * origin has replaced, and a newest row nobody has asked about for `$deleteAfter`.
 */
final readonly class ResponsesGarbageCollector implements GarbageCollector
{

	private const string DELETE_REPLACED = 'DELETE replaced FROM responses replaced
		JOIN responses newer ON newer.ascii_host = replaced.ascii_host AND newer.scheme = replaced.scheme AND newer.port = replaced.port
			AND (newer.fetch_time > replaced.fetch_time OR (newer.fetch_time = replaced.fetch_time AND newer.id > replaced.id))';


	public function __construct(
		private Explorer $database,
		private DateTimeFactoryUtc $dateTimeFactory,
		private GarbageCollectorLogger $logger,
		private string $deleteAfter,
	) {
	}


	#[Override]
	public function getGarbageCollectorType(): GarbageCollectorType
	{
		return GarbageCollectorType::SecurityTxtValidatorResponses;
	}


	#[Override]
	public function getIntervalSeconds(): int
	{
		return 24 * 60 * 60;
	}


	#[Override]
	public function clean(): GarbageCollectorReturnCode
	{
		$type = $this->getGarbageCollectorType();
		try {
			$deleted = $this->database->query(self::DELETE_REPLACED)->getRowCount() ?? 0;
			$deleted += $this->database->query(
				'DELETE FROM responses WHERE fetch_time < ?',
				$this->dateTimeFactory->getNow()->modify("-{$this->deleteAfter}"),
			)->getRowCount() ?? 0;
			$this->logger->log($type, GarbageCollectorReturnCode::Ok, $deleted, null);
			return GarbageCollectorReturnCode::Ok;
		} catch (Throwable $e) {
			Debugger::log($e);
			$this->logger->log($type, GarbageCollectorReturnCode::Failure, null, $e->getMessage());
			return GarbageCollectorReturnCode::Failure;
		}
	}

}
