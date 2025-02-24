<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\Application\Exceptions\ServerEnvNotFoundException;
use MichalSpacekCz\Application\Exceptions\ServerEnvNotStringException;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class ServerEnvTest extends TestCase
{

	public function testGetString(): void
	{
		$_SERVER['foo'] = 'bar';
		Assert::same('bar', ServerEnv::getString('foo'));

		$_SERVER['foo'] = 123;
		Assert::exception(function (): void {
			ServerEnv::getString('foo');
		}, ServerEnvNotStringException::class);

		$_SERVER['foo'] = ['foo', 'bar'];
		Assert::exception(function (): void {
			ServerEnv::getString('foo');
		}, ServerEnvNotStringException::class);

		unset($_SERVER['foo']);
		Assert::exception(function (): void {
			ServerEnv::getString('foo');
		}, ServerEnvNotFoundException::class);
	}


	public function testTryGetString(): void
	{
		$_SERVER['foo'] = 'bar';
		Assert::same('bar', ServerEnv::tryGetString('foo'));

		$_SERVER['foo'] = 123;
		Assert::null(ServerEnv::tryGetString('foo'));

		$_SERVER['foo'] = ['foo', 'bar'];
		Assert::null(ServerEnv::tryGetString('foo'));

		unset($_SERVER['foo']);
		Assert::null(ServerEnv::tryGetString('foo'));
	}


	public function testSetString(): void
	{
		ServerEnv::setString('what', 'ever');
		Assert::same('ever', $_SERVER['what']);
	}


	public function testUnset(): void
	{
		$_SERVER['foo'] = 123;
		ServerEnv::unset('foo');
		Assert::hasNotKey('foo', $_SERVER);
	}

}

TestCaseRunner::run(ServerEnvTest::class);
