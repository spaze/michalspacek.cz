<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\Application\Exceptions\ServerEnvNotArrayException;
use MichalSpacekCz\Application\Exceptions\ServerEnvNotFoundException;
use MichalSpacekCz\Application\Exceptions\ServerEnvNotStringException;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class ServerEnvTest extends TestCase
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


	public function testGetList(): void
	{
		$_SERVER['foo'] = ['foo' => 'bar', 'waldo' => 'quux', 303];
		Assert::same(['bar', 'quux', 303], ServerEnv::getList('foo'));

		$_SERVER['foo'] = ['foo', 'bar'];
		Assert::same(['foo', 'bar'], ServerEnv::getList('foo'));

		$_SERVER['foo'] = 123;
		Assert::exception(function (): void {
			ServerEnv::getList('foo');
		}, ServerEnvNotArrayException::class);

		unset($_SERVER['foo']);
		Assert::exception(function (): void {
			ServerEnv::getList('foo');
		}, ServerEnvNotFoundException::class);
	}


	public function testTryGetList(): void
	{
		$_SERVER['foo'] = ['foo' => 'bar', 'waldo' => 'quux', 303];
		Assert::same(['bar', 'quux', 303], ServerEnv::tryGetList('foo'));

		$_SERVER['foo'] = ['foo', 'bar'];
		Assert::same(['foo', 'bar'], ServerEnv::tryGetList('foo'));

		$_SERVER['foo'] = 123;
		Assert::null(ServerEnv::tryGetList('foo'));

		unset($_SERVER['foo']);
		Assert::null(ServerEnv::tryGetList('foo'));
	}

}

$runner->run(ServerEnvTest::class);
