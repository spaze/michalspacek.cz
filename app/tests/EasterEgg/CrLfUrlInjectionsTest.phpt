<?php
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

use MichalSpacekCz\Test\Http\Request;
use MichalSpacekCz\Test\Http\Response;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Http\IResponse;
use Nette\Http\UrlScript;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class CrLfUrlInjectionsTest extends TestCase
{

	public function __construct(
		private readonly CrLfUrlInjections $crLfUrlInjections,
		private readonly Request $request,
		private readonly Response $response,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->response->reset();
	}


	/**
	 * @return non-empty-list<array{0:string, 1:bool, 2:int}>
	 */
	public function getUrls(): array
	{
		return [
			['/foo', false, 0],
			['/foo/Set-Cookie:crlfinjection=1337', false, 0],
			['/foo%0A', true, 0],
			['/foo%0ASetCookie:crlfinjection=1337', true, 0],
			['/foo%0ASet-Cookie:crlfinjection=1337', true, 1],
			['/foo%0D', true, 0],
			['/foo%0DSetCookie:crlfinjection=1337', true, 0],
			['/foo%0DSet-Cookie:crlfinjection=1337', true, 1],
			['/foo%0D%0A', true, 0],
			['/foo%0D%0ASetCookie:crlfinjection=1337', true, 0],
			['/foo%0D%0ASet-Cookie:crlfinjection=1337', true, 1],
			['/foo%0D%0ASet-Cookie:PHPSESSID=1338', true, 0],
		];
	}


	/** @dataProvider getUrls */
	public function testDetectAttempt(string $path, bool $attempt, int $cookies): void
	{
		$this->request->setUrl((new UrlScript())->withPath(urldecode($path)));
		if ($attempt) {
			Assert::same($attempt, $this->crLfUrlInjections->detectAttempt());
			Assert::same(IResponse::S204_NoContent, $this->response->getCode());
			Assert::same('U WOT M8', $this->response->getReason());
			Assert::count($cookies, $this->response->getCookie('crlfinjection'));
			if ($cookies > 0) {
				Assert::same('1337', $this->response->getCookie('crlfinjection')[0]->getValue());
			}
		} else {
			Assert::false($this->crLfUrlInjections->detectAttempt());
			Assert::same(IResponse::S200_OK, $this->response->getCode());
			Assert::null($this->response->getReason());
			Assert::same([], $this->response->getCookie('crlfinjection'));
		}
	}

}

TestCaseRunner::run(CrLfUrlInjectionsTest::class);
