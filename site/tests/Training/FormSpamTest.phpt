<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use MichalSpacekCz\Test\Http\NullSession;
use MichalSpacekCz\Test\Http\Request;
use MichalSpacekCz\Test\Http\Response;
use MichalSpacekCz\Test\NullLogger;
use MichalSpacekCz\Test\ServicesTrait;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Utils\ArrayHash;
use Tester\Assert;
use Tester\TestCase;
use UnexpectedValueException;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class FormSpamTest extends TestCase
{

	use ServicesTrait;


	private const FORM_NAME = 'formName';

	private Request $request;
	private Response $response;
	private Session $sessionHandler;
	private SessionSection $session;
	private NullLogger $nullLogger;
	private FormDataLogger $formDataLogger;
	private FormSpam $formSpam;


	protected function setUp(): void
	{
		$this->request = $this->getHttpRequest();
		$this->response = $this->getHttpResponse();
		$this->sessionHandler = new NullSession($this->request, $this->response);
		$this->session = $this->sessionHandler->getSection('foo');
		$this->nullLogger = $this->getLogger();
		$this->formDataLogger = new FormDataLogger();
		$this->formSpam = new FormSpam($this->formDataLogger);
	}


	protected function tearDown()
	{
		$this->nullLogger->reset();
	}


	public function getValues(): array
	{
		return [
			[
				[
					'note' => 'foo href="https:// example" bar baz',
				],
				false,
				'Application session data for ' . self::FORM_NAME . ': empty, form values: note => "foo href="https:// example" bar baz"',
			],
			[
				[
					'name' => 'zggnbijhah',
					'companyId' => 'vwetyeofcx',
					'companyTaxId' => 'tyqvukaims',
					'company' => 'qzpormrfcq',
				],
				false,
				'Application session data for ' . self::FORM_NAME . ': empty, form values: name => "zggnbijhah", companyId => "vwetyeofcx", companyTaxId => "tyqvukaims", company => "qzpormrfcq"',
			],
			[
				[
					'name' => 'foo bar',
				],
				true,
				null,
			]
		];
	}


	/**
	 * @dataProvider getValues
	 */
	public function testIsSpam(array $values, bool $isNice, ?string $logged): void
	{
		$check = function () use ($values): void {
			$this->formSpam->check(ArrayHash::from($values), self::FORM_NAME, $this->session);
		};
		if ($isNice) {
			Assert::noError($check);
			Assert::null($this->nullLogger->getLogged());
		} else {
			Assert::throws($check, UnexpectedValueException::class);
			Assert::same($logged, $this->nullLogger->getLogged());
		}
	}

}

(new FormSpamTest())->run();
