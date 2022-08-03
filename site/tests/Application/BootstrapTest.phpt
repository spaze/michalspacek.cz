<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use Nette\DI\Container;
use Tester\Assert;
use Tester\TestCase;
use Tracy\ILogger;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class BootstrapTest extends TestCase
{

	private const SITE_DIR = __DIR__ . '/../..';
	private const EXCEPTION_LOG = self::SITE_DIR . '/log/' . ILogger::EXCEPTION . '.log';
	private ?string $tempLog = null;


	public function __construct()
	{
		if (file_exists(self::EXCEPTION_LOG)) {
			$this->tempLog = self::EXCEPTION_LOG . '.' . uniqid(more_entropy: true);
			rename(self::EXCEPTION_LOG, $this->tempLog);
		}
		$_SERVER['SERVER_NAME'] = 'michalspacek.cz';
	}


	public function __destruct()
	{
		if (file_exists(self::EXCEPTION_LOG)) {
			echo file_get_contents(self::EXCEPTION_LOG);
			unlink(self::EXCEPTION_LOG);
		}
		if ($this->tempLog && file_exists($this->tempLog)) {
			rename($this->tempLog, self::EXCEPTION_LOG);
		}
	}


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
			unset($_SERVER['ENVIRONMENT']);
		} else {
			$_SERVER['ENVIRONMENT'] = $environment;
		}
		Assert::noError(function () use (&$container): void {
			$container = (new Bootstrap(self::SITE_DIR))->boot();
		});
		Assert::type(Container::class, $container);
	}

}

(new BootstrapTest())->run();
