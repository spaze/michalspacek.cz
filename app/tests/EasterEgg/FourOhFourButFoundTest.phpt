<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpMissingParentConstructorInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Application\UiPresenterMock;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\Responses\TextResponse;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class FourOhFourButFoundTest extends TestCase
{

	public function __construct(
		private readonly FourOhFourButFound $fourOhFourButFound,
		private readonly ApplicationPresenter $applicationPresenter,
	) {
	}


	/**
	 * @return non-empty-list<array{0:string, 1:string|null}>
	 */
	public function getUrlContains(): array
	{
		return [
			['/', null],
			['/etc/foo', null],
			['/etc/passwd', 'rick:x:1337:1337:Astley'],
			['/etc/passwd/foo/bar', 'rick:x:1337:1337:Astley'],
			['/etc/foo?file=../../../etc/passwd', 'rick:x:1337:1337:Astley'],
			['/etc/foo?file=..%2F..%2F..%2Fetc%2Fpasswd', 'rick:x:1337:1337:Astley'],
			['/etc/foo?file=../../../etc/passwd&foo/bar', 'rick:x:1337:1337:Astley'],
			['/etc/foo?file=..%2F..%2F..%2Fetc%2Fpasswd&foo/bar', 'rick:x:1337:1337:Astley'],
		];
	}


	/** @dataProvider getUrlContains */
	public function testSendItMaybe(string $url, ?string $contains): void
	{
		$presenter = new UiPresenterMock();
		$_SERVER['REQUEST_URI'] = $url;
		if ($contains === null) {
			Assert::false($this->applicationPresenter->expectSendResponse(function () use ($presenter): void {
				$this->fourOhFourButFound->sendItMaybe($presenter);
			}));
		} else {
			Assert::true($this->applicationPresenter->expectSendResponse(function () use ($presenter): void {
				$this->fourOhFourButFound->sendItMaybe($presenter);
			}));
			$response = $presenter->getResponse();
			if (!$response instanceof TextResponse) {
				Assert::fail('Response is of a wrong type ' . get_debug_type($response));
			} elseif (!is_string($response->getSource())) {
				Assert::fail('Source should be a string but is ' . get_debug_type($response->getSource()));
			} else {
				Assert::contains($contains, $response->getSource());
			}
		}
	}

}

TestCaseRunner::run(FourOhFourButFoundTest::class);
