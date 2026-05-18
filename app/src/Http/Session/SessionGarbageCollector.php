<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Session;

use MichalSpacekCz\GarbageCollector\GarbageCollector;
use MichalSpacekCz\GarbageCollector\GarbageCollectorLogger;
use MichalSpacekCz\GarbageCollector\GarbageCollectorReturnCode;
use MichalSpacekCz\GarbageCollector\GarbageCollectorType;
use Nette\Http\Session;
use Override;
use Spaze\Session\MysqlSessionHandler;
use Throwable;
use Tracy\Debugger;

final readonly class SessionGarbageCollector implements GarbageCollector
{

	public function __construct(
		private MysqlSessionHandler $sessionHandler,
		private GarbageCollectorLogger $logger,
		private Session $session,
	) {
	}


	#[Override]
	public function getGarbageCollectorType(): GarbageCollectorType
	{
		return GarbageCollectorType::Sessions;
	}


	#[Override]
	public function getIntervalSeconds(): int
	{
		return 24 * 60 * 60;
	}


	#[Override]
	public function clean(): GarbageCollectorReturnCode
	{
		try {
			$maxLifetime = $this->session->getOptions()['gc_maxlifetime'];
			assert(is_int($maxLifetime));
			$rows = $this->sessionHandler->gc($maxLifetime);
			if ($rows === false) {
				$message = sprintf('%s::gc() returned false', $this->sessionHandler::class);
				Debugger::log($message, Debugger::ERROR);
				$this->logger->log($this->getGarbageCollectorType(), GarbageCollectorReturnCode::Failure, null, $message);
				return GarbageCollectorReturnCode::Failure;
			}
			$this->logger->log($this->getGarbageCollectorType(), GarbageCollectorReturnCode::Ok, $rows, null);
			return GarbageCollectorReturnCode::Ok;
		} catch (Throwable $e) {
			Debugger::log($e);
			$this->logger->log($this->getGarbageCollectorType(), GarbageCollectorReturnCode::Failure, null, $e->getMessage());
			return GarbageCollectorReturnCode::Failure;
		}
	}

}
