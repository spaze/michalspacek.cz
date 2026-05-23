<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Interviews;

use DateTime;
use MichalSpacekCz\Interviews\Interview;
use MichalSpacekCz\Media\Video;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Html;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class InterviewsInterviewTemplateParametersTest extends TestCase
{

	public function testStoresProperties(): void
	{
		$interview = new Interview(
			1,
			'action',
			'Title',
			null,
			null,
			new DateTime('2024-01-01'),
			'https://example.com/interview',
			null,
			null,
			new Video(null, null, null, null, null, null, 320, 180, null),
			null,
			'Source Name',
			'https://example.com/source',
		);
		$pageTitle = Html::fromText('Page Title');
		$params = new InterviewsInterviewTemplateParameters($pageTitle, $interview);
		Assert::same($pageTitle, $params->pageTitle);
		Assert::same($interview, $params->interview);
	}

}

TestCaseRunner::run(InterviewsInterviewTemplateParametersTest::class);
