<?php
/** @noinspection PhpMissingParentConstructorInspection */
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

use Nette\Application\IResponse;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use stdClass;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/**
* @testCase MichalSpacekCz\EasterEgg\WinterIsComingTest
*/
class WinterIsComingTest extends TestCase
{
	/** @var Presenter */
	private $presenter;

	/** @var Form */
	private $form;

	/** @var callable */
	private $rule;

	/** @var stdClass */
	private $resultObject;


	protected function setUp()
	{
		$this->resultObject = new stdClass();
		$this->presenter = new class($this->resultObject) extends Presenter {

			/** @var stdClass */
			private $resultObject;


			public function __construct(stdClass $resultObject)
			{
				$this->resultObject = $resultObject;
			}


			public function sendResponse(IResponse $response): void
			{
				$this->resultObject->response = $response;
			}

		};
		$this->form = new Form($this->presenter, 'leForm');
		$this->rule = (new WinterIsComing())->rule();
	}


	public function testRule(): void
	{
		Assert::true(($this->rule)($this->form->addText('foo')->setDefaultValue('foo@bar.com')));
	}


	public function testRuleFakeError(): void
	{
		($this->rule)($this->form->addText('foo')->setDefaultValue('winter@example.com'));
		/** @var TextResponse $response */
		$response = $this->resultObject->response;
		Assert::type(TextResponse::class, $response);
		Assert::contains('Uncaught PDOException: SQLSTATE[42000]: Syntax error or access violation', $response->getSource());
	}

}

(new WinterIsComingTest())->run();
