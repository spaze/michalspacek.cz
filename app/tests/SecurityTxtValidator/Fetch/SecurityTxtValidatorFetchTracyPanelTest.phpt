<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Fetch;

use AsyncAws\Lambda\LambdaClient;
use MichalSpacekCz\SecurityTxtValidator\Fetch\SecurityTxtValidatorDirectFetch;
use MichalSpacekCz\SecurityTxtValidator\Fetch\SecurityTxtValidatorFetchTracyPanel;
use MichalSpacekCz\SecurityTxtValidator\Fetch\SecurityTxtValidatorLambdaFetch;
use MichalSpacekCz\SecurityTxtValidator\LambdaFunctions;
use MichalSpacekCz\SecurityTxtValidator\LambdaResponse;
use MichalSpacekCz\SecurityTxtValidator\LambdaVersionCheck\SecurityTxtValidatorLambdaVersionCheck;
use MichalSpacekCz\SecurityTxtValidator\SecurityTxtValidatorLogger;
use MichalSpacekCz\Test\TestCaseRunner;
use Spaze\SecurityTxt\Fetcher\SecurityTxtFetcher;
use Spaze\SecurityTxt\Json\SecurityTxtJson;
use Spaze\SvgIcons\SvgIcons;
use Tester\Assert;
use Tester\TestCase;
use Tracy\IBarPanel;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class SecurityTxtValidatorFetchTracyPanelTest extends TestCase
{

	public function __construct(
		private readonly SecurityTxtFetcher $securityTxtFetcher,
		private readonly SecurityTxtJson $securityTxtJson,
		private readonly LambdaClient $lambdaClient,
		private readonly LambdaResponse $lambdaResponse,
		private readonly LambdaFunctions $lambdaFunctions,
		private readonly SecurityTxtValidatorLambdaVersionCheck $lambdaVersionCheck,
		private readonly SecurityTxtValidatorLogger $validatorLogger,
	) {
	}


	public function testGetTabPanel(): void
	{
		$directFetch = new SecurityTxtValidatorDirectFetch($this->securityTxtFetcher, true);
		$panel = new SecurityTxtValidatorFetchTracyPanel($directFetch, new SvgIcons(__DIR__ . '/../../../node_modules/humbleicons/icons'));
		Assert::contains('<path stroke="currentColor"', $panel->getTab());
		Assert::type(IBarPanel::class, $panel);
		Assert::contains('Direct fetch', $panel->getTab());
		$names = explode('\\', $directFetch::class);
		Assert::contains($names[array_key_last($names)], $panel->getPanel());

		$lambdaFetch = new SecurityTxtValidatorLambdaFetch($this->lambdaClient, $this->lambdaResponse, $this->lambdaFunctions, $this->securityTxtJson, $this->lambdaVersionCheck, $this->validatorLogger, true, 'wget/1.2.3');
		$panel = new SecurityTxtValidatorFetchTracyPanel($lambdaFetch, new SvgIcons(__DIR__ . '/../../../node_modules/humbleicons/icons'));
		Assert::contains('AWS λ fetch', $panel->getTab());
		$names = explode('\\', $lambdaFetch::class);
		Assert::contains($names[array_key_last($names)], $panel->getPanel());
	}

}

TestCaseRunner::run(SecurityTxtValidatorFetchTracyPanelTest::class);
