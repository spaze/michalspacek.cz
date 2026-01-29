<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use MichalSpacekCz\Http\SecurityHeaders\PermissionsPolicyOrigin;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class StructuredHeadersTest extends TestCase
{

	public function __construct(
		private readonly StructuredHeaders $structuredHeaders,
	) {
	}


	public function testGet(): void
	{
		$header = $this->structuredHeaders->get([
			'foo' => '',
			'bar' => PermissionsPolicyOrigin::None,
			'baz' => PermissionsPolicyOrigin::Self,
			'fred' => [
				' ',
				PermissionsPolicyOrigin::Src,
				'https://example.com',
				'quotes "\' and slashes \\',
			],
		]);
		Assert::same('foo=(), bar=(), baz=(self), fred=(src "https://example.com" "quotes \"\' and slashes \\\")', $header);
	}

}

TestCaseRunner::run(StructuredHeadersTest::class);
