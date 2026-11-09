<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Tags;

use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class TagsTest extends TestCase
{

	public function __construct(
		private readonly Tags $tags,
	) {
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

TestCaseRunner::run(TagsTest::class);
