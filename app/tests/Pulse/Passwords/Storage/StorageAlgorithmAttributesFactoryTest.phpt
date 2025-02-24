<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Storage;

use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Schema\ValidationException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class StorageAlgorithmAttributesFactoryTest extends TestCase
{

	public function __construct(
		private readonly StorageAlgorithmAttributesFactory $algorithmAttributesFactory,
	) {
	}


	public function testGetBadJson(): void
	{
		Assert::exception(function (): void {
			$this->algorithmAttributesFactory->get('foo');
		}, JsonException::class);
	}


	public function testGetBadSchemaUnexpectedItem(): void
	{
		Assert::exception(function (): void {
			$this->algorithmAttributesFactory->get(Json::encode(['foo' => 'bar']));
		}, ValidationException::class, "Unexpected item 'foo'.");
	}


	public function testGetBadSchemaExpectsList(): void
	{
		Assert::exception(function (): void {
			$this->algorithmAttributesFactory->get(Json::encode(['inner' => 'bar']));
		}, ValidationException::class, "The item 'inner' expects to be list, 'bar' given.");
	}


	public function testGetInnerOnly(): void
	{
		Assert::same(['foo'], $this->algorithmAttributesFactory->get(Json::encode(['inner' => ['foo']]))->getInner());
	}


	public function testGetOuterOnly(): void
	{
		Assert::same(['foo'], $this->algorithmAttributesFactory->get(Json::encode(['outer' => ['foo']]))->getOuter());
	}


	public function testGetParamsOnly(): void
	{
		Assert::same(['foo' => 'bar'], $this->algorithmAttributesFactory->get(Json::encode(['params' => ['foo' => 'bar']]))->getParams());
	}


	public function testGetAll(): void
	{
		$json = Json::encode([
			'inner' => ['inner1', 'inner2'],
			'outer' => ['outer1', 'outer2'],
			'params' => ['foo' => 'bar', 'baz' => 303],
		]);
		$expected = new StorageAlgorithmAttributes(['inner1', 'inner2'], ['outer1', 'outer2'], ['foo' => 'bar', 'baz' => 303]);
		Assert::equal($expected, $this->algorithmAttributesFactory->get($json));
	}


	public function testGetNull(): void
	{
		$expected = new StorageAlgorithmAttributes(null, null, null);
		Assert::equal($expected, $this->algorithmAttributesFactory->get(null));
	}

}

TestCaseRunner::run(StorageAlgorithmAttributesFactoryTest::class);
