<?php
/** @noinspection PhpMissingParentConstructorInspection */
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

use Nette\Application\AbortException;
use Nette\Application\Response;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use stdClass;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class WinterIsComingTest extends TestCase
{

	private Presenter $presenter;

	private Form $form;

	/** @var callable */
	private $ruleEmail;

	/** @var callable */
	private $ruleStreet;

	private stdClass $resultObject;


	protected function setUp()
	{
		$this->resultObject = new stdClass();
		$this->presenter = new class ($this->resultObject) extends Presenter {

			private stdClass $resultObject;


			public function __construct(stdClass $resultObject)
			{
				$this->resultObject = $resultObject;
			}


			public function sendResponse(Response $response): never
			{
				$this->resultObject->response = $response;
				$this->terminate();
			}

		};
		$this->form = new Form($this->presenter, 'leForm');
		$winterIsComing = new WinterIsComing();
		$this->ruleEmail = $winterIsComing->ruleEmail();
		$this->ruleStreet = $winterIsComing->ruleStreet();
	}


	public function testRuleEmail(): void
	{
		Assert::true(($this->ruleEmail)($this->form->addText('foo')->setDefaultValue('foo@bar.com')));
	}


	public function getUnfriendlyEmails(): array
	{
		return [
			'address' => ['winter@example.com'],
			'host' => [random_int(0, PHP_INT_MAX) . '@ssemarketing.net'],
		];
	}


	/** @dataProvider getUnfriendlyEmails */
	public function testRuleEmailFakeError(): void
	{
		Assert::throws(function (): void {
			($this->ruleEmail)($this->form->addText('foo')->setDefaultValue('winter@example.com'));
		}, AbortException::class);
		$this->assertResponse();
	}


	public function testRuleEmailNiceHost(): void
	{
		($this->ruleEmail)($this->form->addText('foo')->setDefaultValue('kuddelmuddel@fussemarketing.net'));
		Assert::hasNotKey('response', (array)$this->resultObject);
	}


	public function getRuleStreetNiceStreets(): array
	{
		return [
			['34 Watts Road'],
			['34 Watts'],
			['35 Watts road'],
		];
	}


	/** @dataProvider getRuleStreetNiceStreets */
	public function testRuleStreetNice(string $name): void
	{
		$result = ($this->ruleStreet)($this->form->addText('foo')->setDefaultValue($name));
		Assert::true($result);
		Assert::hasNotKey('response', (array)$this->resultObject);
	}


	public function getRuleStreetRoughStreets(): array
	{
		return [
			['34 Watts road'],
		];
	}


	/** @dataProvider getRuleStreetRoughStreets */
	public function testRuleStreetRough(string $name): void
	{
		Assert::throws(function () use (&$result, $name): void {
			$result = ($this->ruleStreet)($this->form->addText('foo')->setDefaultValue($name));
		}, AbortException::class);
		$this->assertResponse();
	}


	private function assertResponse(): void
	{
		/** @var TextResponse $response */
		$response = $this->resultObject->response;
		Assert::type(TextResponse::class, $response);
		Assert::contains('Uncaught PDOException: SQLSTATE[42000]: Syntax error or access violation', $response->getSource());
	}

}

(new WinterIsComingTest())->run();
