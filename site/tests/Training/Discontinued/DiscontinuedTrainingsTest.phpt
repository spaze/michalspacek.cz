<?php
/** @noinspection PhpUndefinedFieldInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Discontinued;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Http\Response;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Nette\Http\IResponse;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../../bootstrap.php';

/** @testCase */
class DiscontinuedTrainingsTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly DiscontinuedTrainings $discontinuedTrainings,
		private readonly Response $httpResponse,
		private readonly LatteFactory $latteFactory,
	) {
	}


	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testGetAllDiscontinued(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'id' => 1,
				'description' => 'foo',
				'training' => 'intro',
				'href' => 'https://foo.example',
			],
			[
				'id' => 1,
				'description' => 'foo',
				'training' => 'classes',
				'href' => 'https://foo.example',
			],
			[
				'id' => 2,
				'description' => 'bar',
				'training' => 'web',
				'href' => 'https://bar.example',
			],
		]);
		$discontinued = $this->discontinuedTrainings->getAllDiscontinued();
		Assert::count(2, $discontinued);
		Assert::same('foo', $discontinued[0]->getDescription());
		Assert::same(['intro', 'classes'], $discontinued[0]->getTrainings());
		Assert::same('https://foo.example', $discontinued[0]->getNewHref());
		Assert::same('bar', $discontinued[1]->getDescription());
		Assert::same(['web'], $discontinued[1]->getTrainings());
		Assert::same('https://bar.example', $discontinued[1]->getNewHref());
	}


	public function testMaybeMarkAsDiscontinued(): void
	{
		$this->httpResponse->setCode(IResponse::S200_OK);
		$template = new DefaultTemplate($this->latteFactory->create());

		$this->discontinuedTrainings->maybeMarkAsDiscontinued($template, null);
		Assert::same([], $template->discontinued);
		Assert::same(IResponse::S200_OK, $this->httpResponse->getCode());

		$this->discontinuedTrainings->maybeMarkAsDiscontinued($template, 404);
		Assert::same([], $template->discontinued);
		Assert::same(IResponse::S200_OK, $this->httpResponse->getCode());

		$this->database->setFetchAllDefaultResult([
			[
				'description' => 'foo',
				'training' => 'intro',
				'href' => 'https://foo.example',
			],
			[
				'description' => 'foo',
				'training' => 'classes',
				'href' => 'https://foo.example',
			],
		]);
		$this->discontinuedTrainings->maybeMarkAsDiscontinued($template, 302);
		Assert::type(DiscontinuedTraining::class, $template->discontinued[0]);
		Assert::same('foo', $template->discontinued[0]->getDescription());
		Assert::same(['intro', 'classes'], $template->discontinued[0]->getTrainings());
		Assert::same('https://foo.example', $template->discontinued[0]->getNewHref());
		Assert::same(IResponse::S410_Gone, $this->httpResponse->getCode());
	}

}

$runner->run(DiscontinuedTrainingsTest::class);
