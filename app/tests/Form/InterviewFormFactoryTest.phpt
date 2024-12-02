<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Arrays;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class InterviewFormFactoryTest extends TestCase
{

	private ?bool $result = null;


	public function __construct(
		private readonly Database $database,
		private readonly InterviewFormFactory $formFactory,
		private readonly ApplicationPresenter $applicationPresenter,
	) {
	}


	public function testCreateOnSuccessAdd(): void
	{
		$form = $this->formFactory->create(
			function (): void {
				$this->result = true;
			},
			null,
		);
		$form->setDefaults([
			'action' => 'foo',
			'date' => '3210-09-08 10:20:30',
		]);
		$this->applicationPresenter->anchorForm($form);
		Arrays::invoke($form->onSuccess, $form);
		Assert::true($this->result);
		Assert::same([
			[
				'action' => 'foo',
				'title' => '',
				'description' => null,
				'date' => '3210-09-08 10:20:30',
				'href' => '',
				'audio_href' => null,
				'audio_embed' => null,
				'video_href' => null,
				'video_thumbnail' => null,
				'video_thumbnail_alternative' => null,
				'video_embed' => null,
				'source_name' => '',
				'source_href' => '',
			],
		], $this->database->getParamsArrayForQuery('INSERT INTO interviews'));
	}

}

TestCaseRunner::run(InterviewFormFactoryTest::class);
