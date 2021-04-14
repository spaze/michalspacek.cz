<?php
/** @noinspection PhpMissingParentConstructorInspection */
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

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
	/** @var Presenter */
	private $presenter;

	/** @var Form */
	private $form;

	/** @var callable */
	private $ruleEmail;

	/** @var stdClass */
	private $resultObject;


	protected function setUp()
	{
		$this->resultObject = new stdClass();
		$this->presenter = new class ($this->resultObject) extends Presenter {

			private stdClass $resultObject;


			public function __construct(stdClass $resultObject)
			{
				$this->resultObject = $resultObject;
			}


			public function sendResponse(Response $response): void
			{
				$this->resultObject->response = $response;
			}

		};
		$this->form = new Form($this->presenter, 'leForm');
		$this->ruleEmail = (new WinterIsComing())->ruleEmail();
	}


	public function testRuleEmail(): void
	{
		Assert::true(($this->ruleEmail)($this->form->addText('foo')->setDefaultValue('foo@bar.com')));
	}


	public function testRuleEmailFakeError(): void
	{
		($this->ruleEmail)($this->form->addText('foo')->setDefaultValue('winter@example.com'));
		$this->assertResponse();
	}


	public function testRuleEmailFakeErrorEmailHost(): void
	{
		($this->ruleEmail)($this->form->addText('foo')->setDefaultValue(random_int(0, PHP_INT_MAX) . '@ssemarketing.net'));
		$this->assertResponse();
	}


	public function testRuleEmailNiceHost(): void
	{
		($this->ruleEmail)($this->form->addText('foo')->setDefaultValue('kuddelmuddel@fussemarketing.net'));
		Assert::hasNotKey('response', (array)$this->resultObject);
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
