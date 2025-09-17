<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\Test\TestCaseRunner;
use Nette\DI\Container;
use Tester\Assert;
use Tester\TestCase;
use Tracy\Debugger;
use Tracy\ILogger;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class BootstrapTest extends TestCase
{

	private string $exceptionLog;
	private ?string $tempLog = null;


	public function __construct()
	{
		$logDirectory = Debugger::$logDirectory;
		assert(is_string($logDirectory), 'Call Nette\Bootstrap\Configurator::enableTracy() first, possibly in MichalSpacekCz\Application\Bootstrap::createConfigurator()');
		$this->exceptionLog = $logDirectory . '/' . ILogger::EXCEPTION . '.log';
		if (file_exists($this->exceptionLog)) {
			$this->tempLog = $this->exceptionLog . '.' . uniqid(more_entropy: true);
			rename($this->exceptionLog, $this->tempLog);
		}
		ServerEnv::setString('SERVER_NAME', 'michalspacek.cz');
	}


	public function __destruct()
	{
		if (file_exists($this->exceptionLog)) {
			echo file_get_contents($this->exceptionLog);
			unlink($this->exceptionLog);
		}
		if ($this->tempLog !== null && file_exists($this->tempLog)) {
			rename($this->tempLog, $this->exceptionLog);
		}
	}


	/**
	 * @return array<string, array{environment:string|null}>
	 */
	public function getBootEnvironments(): array
	{
		return [
			'production' => [
				'environment' => null,
			],
			'development' => [
				'environment' => 'development',
			],
		];
	}


	/** @dataProvider getBootEnvironments */
	public function testBoot(?string $environment): void
	{
		if ($environment === null) {
			ServerEnv::unset('ENVIRONMENT');
		} else {
			ServerEnv::setString('ENVIRONMENT', $environment);
		}
		$container = null;
		Assert::noError(function () use (&$container): void {
			$container = Bootstrap::boot();
		});
		Assert::type(Container::class, $container);
	}

}

TestCaseRunner::run(BootstrapTest::class);
