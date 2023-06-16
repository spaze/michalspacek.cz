<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Dates;

use MichalSpacekCz\Test\NoOpTranslator;
use Nette\Utils\Json;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class TrainingDateLabelTest extends TestCase
{

	public function __construct(
		private readonly TrainingDateLabel $label,
		private readonly NoOpTranslator $translator,
	) {
	}


	public function testDecodeLabel(): void
	{
		Assert::null($this->label->decodeLabel(null));
		$json = Json::encode([$this->translator->getDefaultLocale() => 'fó', 'en_US' => 'foo']);
		Assert::same('fó', $this->label->decodeLabel($json));
	}

}

$runner->run(TrainingDateLabelTest::class);
