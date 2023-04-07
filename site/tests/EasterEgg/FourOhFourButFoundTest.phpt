<?php
/** @noinspection PhpMissingParentConstructorInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

use MichalSpacekCz\Test\Http\Request;
use Nette\Application\AbortException;
use Nette\Application\Response;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\UrlScript;
use stdClass;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class FourOhFourButFoundTest extends TestCase
{

	private stdClass $resultObject;

	private Presenter $presenter;


	public function __construct(
		private readonly FourOhFourButFound $fourOhFourButFound,
		private readonly Request $request,
	) {
	}


	protected function setUp(): void
	{
		$this->resultObject = new stdClass();
		$this->resultObject->response = null;
		$this->presenter = new class ($this->resultObject) extends Presenter {

			public function __construct(
				private readonly stdClass $resultObject,
			) {
			}


			public function sendResponse(Response $response): never
			{
				$this->resultObject->response = $response;
				$this->terminate();
			}

		};
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
		$this->request->setUrl(new UrlScript($url));
		if ($contains === null) {
			$this->fourOhFourButFound->sendItMaybe($this->presenter);
			Assert::null($this->resultObject->response);
		} else {
			Assert::throws(function (): void {
				$this->fourOhFourButFound->sendItMaybe($this->presenter);
			}, AbortException::class);
			Assert::type(TextResponse::class, $this->resultObject->response);
			Assert::contains($contains, $this->resultObject->response->getSource());
		}
	}

}

$runner->run(FourOhFourButFoundTest::class);
