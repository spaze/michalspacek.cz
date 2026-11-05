<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Controls;

use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\UI\Form;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/*
 * The control's name is the key the value is submitted under, so it is also the key `keysToHide` matches on to
 * keep the applicant out of the exception log. Nothing else ties the two together: rename a control and the
 * config in common.neon quietly stops covering it, with every other test still green.
 */

/** @testCase */
final class TrainingControlsFactoryTest extends TestCase
{

	public function __construct(
		private readonly TrainingControlsFactory $trainingControlsFactory,
	) {
	}


	public function testAttendeeControlsAreNamedAsTheConfigExpects(): void
	{
		$form = new Form();

		$attendee = $this->trainingControlsFactory->addAttendee($form);

		Assert::same('attendeeName', $attendee->getAttendeeName()->getName());
		Assert::same('email', $attendee->getEmail()->getName());
	}

}

TestCaseRunner::run(TrainingControlsFactoryTest::class);
