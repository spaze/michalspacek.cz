<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz;

use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';

/**
 * @testCase MichalSpacekCz\TagsTest
 */
class TagsTest extends TestCase
{

	private Tags $tags;


	public function __construct()
	{
		$this->tags = new Tags();
	}


	public function testToArray(): void
	{
		Assert::same(['foo', 'bar'], $this->tags->toArray(",foo \t, bar,\t"));
		Assert::same([], $this->tags->toArray(''));
		Assert::same([], $this->tags->toArray(' ,, '));
	}


	public function testToString(): void
	{
		Assert::same('foo, bar', $this->tags->toString(['foo', 'bar']));
	}


	public function testToSlugArray(): void
	{
		Assert::same(['foo', 'bar-waldo'], $this->tags->toSlugArray(",fóo \t, Bař walďo,\t"));
	}


	public function testSerialize(): void
	{
		Assert::same('["foo","bar"]', $this->tags->serialize(['foo', 'bar']));
	}


	public function testUnserialize(): void
	{
		Assert::same(['foo', 'bar'], $this->tags->unserialize('["foo","bar"]'));
	}

}

(new TagsTest())->run();
