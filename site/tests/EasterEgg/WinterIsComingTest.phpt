<?php
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpMissingParentConstructorInspection */
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Application\UiPresenterMock;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\TextInput;
use Nette\InvalidStateException;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class WinterIsComingTest extends TestCase
{

	private Form $form;

	/** @var callable(TextInput): true */
	private $ruleEmail;

	/** @var callable(TextInput): true */
	private $ruleStreet;

	private UiPresenterMock $presenter;


	public function __construct(
		private readonly WinterIsComing $winterIsComing,
		private readonly ApplicationPresenter $applicationPresenter,
	) {
	}


	protected function setUp(): void
	{
		$this->presenter = new UiPresenterMock();
		$this->form = new Form($this->presenter, 'leForm');
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
		Assert::true($this->applicationPresenter->expectSendResponse(function () use ($email): void {
			($this->ruleEmail)($this->form->addText('foo')->setDefaultValue($email));
		}));
		$this->assertResponse();
	}


	public function testRuleEmailNiceHost(): void
	{
		($this->ruleEmail)($this->form->addText('foo')->setDefaultValue('kuddelmuddel@fussemarketing.net'));
		Assert::false($this->presenter->isResponseSent());
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
		Assert::true(($this->ruleStreet)($this->form->addText('foo')->setDefaultValue($name)));
		Assert::false($this->presenter->isResponseSent());
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
		Assert::true($this->applicationPresenter->expectSendResponse(function () use ($name): void {
			($this->ruleStreet)($this->form->addText('foo')->setDefaultValue($name));
		}));
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
		$response = $this->presenter->getResponse();
		if (!$response instanceof TextResponse) {
			Assert::fail('Response is of a wrong type ' . get_debug_type($response));
		} else {
			$source = $response->getSource();
			if (!is_string($source)) {
				Assert::fail('Source should be a string but is ' . get_debug_type($source));
			} else {
				Assert::contains('Uncaught PDOException: SQLSTATE[42000]: Syntax error or access violation', $source);
			}
		}
	}

}

$runner->run(WinterIsComingTest::class);
