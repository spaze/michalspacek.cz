<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\SessionGarbageCollector;

use MichalSpacekCz\DateTime\DateTimeFactory;
use Nette\Database\Explorer;
use Spaze\Session\MysqlSessionHandler;
use Throwable;
use Tracy\Debugger;

final readonly class SessionGarbageCollector
{

	public function __construct(
		private MysqlSessionHandler $sessionHandler,
		private Explorer $database,
		private DateTimeFactory $dateTimeFactory,
	) {
	}


	public function cleanSessions(): SessionGarbageCollectorReturnCode
	{
		try {
			$rows = $this->sessionHandler->gc(24 * 60 * 60);
			if ($rows === false) {
				Debugger::log(sprintf('Something went wrong, %s::gc() returned false', $this->sessionHandler::class), Debugger::ERROR);
				return $this->log(SessionGarbageCollectorReturnCode::GcFailure);
			}
			return $this->log(SessionGarbageCollectorReturnCode::Ok, $rows);
		} catch (Throwable $e) {
			return $this->log(SessionGarbageCollectorReturnCode::Exception, e: $e);
		}
	}


	private function log(SessionGarbageCollectorReturnCode $code, ?int $rows = null, ?Throwable $e = null): SessionGarbageCollectorReturnCode
	{
		if ($e !== null) {
			Debugger::log($e);
		}
		$datetime = $this->dateTimeFactory->create();
		$data = [
			'id_sessions_gc_log' => 1, // There should be just one row in the table, so hardcoding the id is fine
			'gc_time' => $datetime,
			'gc_time_timezone' => $datetime->getTimezone()->getName(),
			'deleted' => $rows,
			'return_code' => $code,
			'message' => $e?->getMessage(),
		];
		$this->database->query('INSERT INTO sessions_gc_log', $data, 'ON DUPLICATE KEY UPDATE', $data);
		return $code;
	}

}
