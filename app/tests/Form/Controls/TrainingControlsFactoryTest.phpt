<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Controls;

use MichalSpacekCz\CompanyInfo\CompanyRegister;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\UI\Form;
use Nette\DI\Container;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class TrainingControlsFactoryTest extends TestCase
{

	public function __construct(
		private readonly TrainingControlsFactory $trainingControlsFactory,
		private readonly Container $container,
	) {
	}


	/*
	 * The control's name is the key the value is submitted under, so it is also the key `keysToHide` matches on to
	 * keep the applicant out of the exception log. Nothing else ties the two together: rename a control and the
	 * config in common.neon quietly stops covering it, with every other test still green.
	 */
	public function testAttendeeControlsAreNamedAsTheConfigExpects(): void
	{
		$form = new Form();

		$attendee = $this->trainingControlsFactory->addAttendee($form);

		Assert::same('attendeeName', $attendee->getAttendeeName()->getName());
		Assert::same('email', $attendee->getEmail()->getName());
	}


	/*
	 * The countries an application can come from and the countries with a company register are two separate lists,
	 * and they don't have to match: an application can come from a country whose register has no API. But the form
	 * offers the company data lookup for every country, and for a country without a register the lookup always ends
	 * with the generic error.
	 */
	public function testEveryCountryHasCompanyRegister(): void
	{
		$registerCountries = [];
		foreach ($this->container->findByType(CompanyRegister::class) as $name) {
			$register = $this->container->getService($name);
			assert($register instanceof CompanyRegister);
			$registerCountries[] = $register->getCountry();
		}
		$countries = array_keys($this->trainingControlsFactory->addCountry(new Form())->getItems());

		Assert::same(
			[],
			array_values(array_diff($countries, $registerCountries)),
			'Hide the company data lookup (#loadDataControls in scripts.js) for these countries without a company register, and add a messages.label.taxid.{country} translation if their tax id has its own name',
		);
	}

}

TestCaseRunner::run(TrainingControlsFactoryTest::class);
