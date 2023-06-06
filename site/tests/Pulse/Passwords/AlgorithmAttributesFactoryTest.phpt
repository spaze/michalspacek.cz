<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpFullyQualifiedNameUsageInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use Nette\Utils\Json;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class AlgorithmAttributesFactoryTest extends TestCase
{

	public function __construct(
		private readonly AlgorithmAttributesFactory $algorithmAttributesFactory,
	) {
	}


	/**
	 * @throws \Nette\Utils\JsonException
	 */
	public function testGetBadJson(): void
	{
		$this->algorithmAttributesFactory->get('foo');
	}


	/**
	 * @throws \Nette\Schema\ValidationException Unexpected item 'foo'.
	 */
	public function testGetBadSchemaUnexpectedItem(): void
	{
		$this->algorithmAttributesFactory->get(Json::encode(['foo' => 'bar']));
	}


	/**
	 * @throws \Nette\Schema\ValidationException The item 'inner' expects to be list, 'bar' given.
	 */
	public function testGetBadSchemaExpectsList(): void
	{
		$this->algorithmAttributesFactory->get(Json::encode(['inner' => 'bar']));
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
		$expected = new AlgorithmAttributes(['inner1', 'inner2'], ['outer1', 'outer2'], ['foo' => 'bar', 'baz' => 303]);
		Assert::equal($expected, $this->algorithmAttributesFactory->get($json));
	}


	public function testGetNull(): void
	{
		$expected = new AlgorithmAttributes(null, null, null);
		Assert::equal($expected, $this->algorithmAttributesFactory->get(null));
	}

}

$runner->run(AlgorithmAttributesFactoryTest::class);
