<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Twitter;

use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Twitter\Exceptions\TwitterCardNotFoundException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class TwitterCardsTest extends TestCase
{

	public function __construct(
		private readonly TwitterCards $twitterCards,
		private readonly Database $database,
	) {
	}


	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testGetAll(): void
	{
		$fetchResult = [
			[
				'cardId' => 1,
				'card' => 'summary',
				'title' => 'Summary',
			],
			[
				'cardId' => 2,
				'card' => 'summary_large_image',
				'title' => 'Summary with Large Image',
			],
		];
		$this->database->addFetchAllResult($fetchResult);
		$cards = $this->twitterCards->getAll();
		Assert::same(1, $cards[0]->getId());
		Assert::same('summary', $cards[0]->getCard());
		Assert::same('Summary', $cards[0]->getTitle());
		Assert::same(2, $cards[1]->getId());
		Assert::same('summary_large_image', $cards[1]->getCard());
		Assert::same('Summary with Large Image', $cards[1]->getTitle());
	}


	public function testGetCard(): void
	{
		$this->database->setFetchResult([
			'cardId' => 3,
			'card' => 'summary',
			'title' => 'Summary',
		]);
		$card = $this->twitterCards->getCard('summary');
		Assert::same(3, $card->getId());
		Assert::same('summary', $card->getCard());
		Assert::same('Summary', $card->getTitle());
	}


	public function testGetCardNotFound(): void
	{
		Assert::exception(function (): void {
			$this->twitterCards->getCard('summary');
		}, TwitterCardNotFoundException::class);
	}


	public function testBuildCard(): void
	{
		$card = $this->twitterCards->buildCard(303, 'summary', 'Le Summary');
		Assert::same(303, $card->getId());
		Assert::same('summary', $card->getCard());
		Assert::same('Le Summary', $card->getTitle());
	}

}

TestCaseRunner::run(TwitterCardsTest::class);
