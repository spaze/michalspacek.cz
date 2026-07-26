<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Utils;

use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Utils\Exceptions\EmptyFilenameException;
use Tester\Assert;
use Tester\FileMock;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class MimeTypeTest extends TestCase
{

	/**
	 * @return list<array{0:string, 1:string}>
	 */
	public function getContents(): array
	{
		return [
			['image/gif', "\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x00\x00\x00\x2C\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x01\x01\x00\x3B"],
			['application/x-7z-compressed', "\x37\x7A\xBC\xAF\x27\x1C\x00\x04\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"],
			['application/pdf', "\x25\x50\x44\x46\x2D\x31\x2E\x0A\x31\x20\x30\x20\x6F\x62\x6A\x3C\x3C\x2F\x50\x61\x67\x65\x73\x20\x32\x20\x30\x20\x52\x3E\x3E\x65\x6E\x64\x6F\x62\x6A\x0A\x32\x20\x30\x20\x6F\x62\x6A\x3C\x3C\x2F\x4B\x69\x64\x73\x5B\x33\x20\x30\x20\x52\x5D\x2F\x43\x6F\x75\x6E\x74\x20\x31\x3E\x3E\x65\x6E\x64\x6F\x62\x6A\x0A\x33\x20\x30\x20\x6F\x62\x6A\x3C\x3C\x2F\x50\x61\x72\x65\x6E\x74\x20\x32\x20\x30\x20\x52\x3E\x3E\x65\x6E\x64\x6F\x62\x6A\x0A\x74\x72\x61\x69\x6C\x65\x72\x20\x3C\x3C\x2F\x52\x6F\x6F\x74\x20\x31\x20\x30\x20\x52\x3E\x3E"],
			['text/plain', 'pain text'],
			['application/x-empty', ''],
		];
	}


	/**
	 * @dataProvider getContents
	 */
	public function testDetectMimeType(string $expectedType, string $contents): void
	{
		$file = FileMock::create($contents);
		Assert::same($expectedType, MimeType::detectMimeType($file));
	}


	public function testDetectMimeTypeEmptyFilename(): void
	{
		Assert::throws(fn() => MimeType::detectMimeType(''), EmptyFilenameException::class);
	}


	/**
	 * @return list<array{0:lowercase-string, 1:string}>
	 */
	public function getMimeTypes(): array
	{
		return [
			['gif', 'image/gif'],
			['jpeg', 'image/jpeg'],
			['png', 'image/png'],
			['webp', 'image/webp'],
		];
	}


	/**
	 * @param lowercase-string $extension
	 * @dataProvider getMimeTypes
	 */
	public function testGetMimeTypeByExtension(string $extension, string $type): void
	{
		Assert::same($type, MimeType::getMimeTypeByExtension($extension));
	}

}

TestCaseRunner::run(MimeTypeTest::class);
