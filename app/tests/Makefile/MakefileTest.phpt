<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Makefile;

use MichalSpacekCz\Makefile\Exceptions\MakefileContainsRealTargetsException;
use MichalSpacekCz\Makefile\Exceptions\MakefileNotFoundException;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\FileMock;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class MakefileTest extends TestCase
{

	public function __construct(
		private readonly Makefile $makefile,
	) {
	}


	public function testCheckAllTargetsArePhonyNoFile(): void
	{
		Assert::exception(function (): void {
			$this->makefile->checkAllTargetsArePhony('Fake it till you makefile it.jpg');
		}, MakefileNotFoundException::class, "Makefile 'Fake it till you makefile it.jpg' not found");
	}


	public function testCheckAllTargetsArePhonyEmptyFile(): void
	{
		Assert::noError(function (): void {
			$this->makefile->checkAllTargetsArePhony(FileMock::create());
		});
	}


	public function testCheckAllTargetsArePhony(): void
	{
		Assert::noError(function (): void {
			$makefile = FileMock::create(" foo bar   baz:\n\twaldo\n # comment: ignored\n.PHONY: foo\n.PHONY: bar baz");
			$this->makefile->checkAllTargetsArePhony($makefile);
		});
	}


	public function testCheckAllTargetsArePhonyOneIsNot(): void
	{
		Assert::exception(function (): void {
			$makefile = FileMock::create("foo:\nbar:\n.PHONY:foo");
			$this->makefile->checkAllTargetsArePhony($makefile);
		}, MakefileContainsRealTargetsException::class, "Makefile contains a real target:\n- `bar` defined on line 2\nAdd it to a .PHONY target!");
	}


	public function testCheckAllTargetsArePhonyMultipleAreNot(): void
	{
		Assert::exception(function (): void {
			$makefile = FileMock::create(" foo bar   baz:\n\twaldo\n # comment: ignored\n.PHONY: foo\nbaz:\n.PHONY: bar\nquux:");
			$this->makefile->checkAllTargetsArePhony($makefile);
		}, MakefileContainsRealTargetsException::class, "Makefile contains real targets:\n- `baz` defined on lines 1, 5\n- `quux` defined on line 7\nAdd them to a .PHONY target!");
	}

}

TestCaseRunner::run(MakefileTest::class);
