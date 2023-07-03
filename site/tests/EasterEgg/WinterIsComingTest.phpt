<?php
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpMissingParentConstructorInspection */
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

use Nette\Application\AbortException;
use Nette\Application\Response;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Forms\Controls\TextInput;
use Nette\InvalidStateException;
use stdClass;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class WinterIsComingTest extends TestCase
{

	private Form $form;

	/** @var callable */
	private $ruleEmail;

	/** @var callable */
	private $ruleStreet;

	private stdClass $resultObject;


	public function __construct(
		private readonly WinterIsComing $winterIsComing,
	) {
	}


	protected function setUp(): void
	{
		$this->resultObject = new stdClass();
		$presenter = new class ($this->resultObject) extends Presenter {

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
		$this->form = new Form($presenter, 'leForm');
		$this->ruleEmail = $this->winterIsComing->ruleEmail();
		$this->ruleStreet = $this->winterIsComing->ruleStreet();
	}


	public function testRuleEmail(): void
	{
		Assert::true(($this->ruleEmail)($this->form->addText('foo')->setDefaultValue('foo@bar.com')));
	}


	/**
	 * @return array<string, array{email:string}>
	 */
	public function getUnfriendlyEmails(): array
	{
		return [
			'address' => ['email' => 'winter@example.com'],
			'host' => ['email' => random_int(0, PHP_INT_MAX) . '@ssemarketing.net'],
		];
	}


	/** @dataProvider getUnfriendlyEmails */
	public function testRuleEmailFakeError(string $email): void
	{
		Assert::exception(function () use ($email): void {
			($this->ruleEmail)($this->form->addText('foo')->setDefaultValue($email));
		}, AbortException::class);
		$this->assertResponse();
	}


	public function testRuleEmailNiceHost(): void
	{
		($this->ruleEmail)($this->form->addText('foo')->setDefaultValue('kuddelmuddel@fussemarketing.net'));
		Assert::hasNotKey('response', (array)$this->resultObject);
	}


	/**
	 * @return list<array{name:string}>
	 */
	public function getRuleStreetNiceStreets(): array
	{
		return [
			['name' => '34 Watts Road'],
			['name' => '34 Watts'],
			['name' => '35 Watts road'],
		];
	}


	/** @dataProvider getRuleStreetNiceStreets */
	public function testRuleStreetNice(string $name): void
	{
		$result = ($this->ruleStreet)($this->form->addText('foo')->setDefaultValue($name));
		Assert::true($result);
		Assert::hasNotKey('response', (array)$this->resultObject);
	}


	/**
	 * @return list<array{name:string}>
	 */
	public function getRuleStreetRoughStreets(): array
	{
		return [
			['name' => '34 Watts road'],
		];
	}


	/** @dataProvider getRuleStreetRoughStreets */
	public function testRuleStreetRough(string $name): void
	{
		Assert::exception(function () use (&$result, $name): void {
			$result = ($this->ruleStreet)($this->form->addText('foo')->setDefaultValue($name));
		}, AbortException::class);
		$this->assertResponse();
	}


	public function testNoForm(): void
	{
		Assert::exception(function (): void {
			($this->ruleEmail)((new TextInput())->setDefaultValue('winter@example.com'));
		}, InvalidStateException::class, "Component of type 'Nette\Forms\Controls\TextInput' is not attached to 'Nette\Forms\Form'.");
	}


	private function assertResponse(): void
	{
		/** @var TextResponse $response */
		$response = $this->resultObject->response;
		Assert::type(TextResponse::class, $response);
		/** @var string $source */
		$source = $response->getSource();
		Assert::type('string', $source);
		Assert::contains('Uncaught PDOException: SQLSTATE[42000]: Syntax error or access violation', $source);
	}

}

$runner->run(WinterIsComingTest::class);
