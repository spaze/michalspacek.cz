<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Tags;

use DateTime;
use DateTimeImmutable;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\DateTime\DateTimeMachineFactory;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class TagTranslationsTest extends TestCase
{

	public function __construct(
		private readonly TagTranslations $tagTranslations,
		private readonly Database $database,
		private readonly DateTimeMachineFactory $dateTimeFactory,
		private readonly Tags $tags,
	) {
	}


	#[Override]
	protected function setUp(): void
	{
		$this->dateTimeFactory->setDateTime(new DateTimeImmutable('2025-06-01 00:00:00'));
		$published = new DateTime('2020-01-01 00:00:00');
		$this->database->setFetchAllDefaultResult([
			...$this->translatedPost(1, ['hesla', 'bezpecnost'], $published, ['passwords', 'security'], $published),
			...$this->translatedPost(2, ['hesla', 'https'], $published, ['passwords', 'https'], $published),
			// A tie: one post pairs bezpecnost with security, one with securiteh, so the tags decide
			...$this->translatedPost(3, ['bezpecnost'], $published, ['securiteh'], $published),
			// A tie the other way round: https is paired with https and with sifrovani
			...$this->translatedPost(4, ['sifrovani'], $published, ['https'], $published),
			// Fewer posts than hesla and passwords have, although credentials would come first by name
			...$this->translatedPost(5, ['hesla'], $published, ['credentials'], $published),
			// The translations of these two aren't out
			...$this->translatedPost(6, ['hesla'], $published, ['neverejne'], null),
			...$this->translatedPost(7, ['planovane'], $published, ['scheduled'], new DateTime('2025-07-01 00:00:00')),
			// Fewer tags in the translation
			...$this->translatedPost(8, ['stroj', 'navic'], $published, ['machine'], $published),
			// Not translated at all
			['translationGroupId' => 9, 'locale' => 'cs_CZ', 'published' => $published, 'slugTags' => $this->tags->serialize(['jen-cesky'])],
		]);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->dateTimeFactory->setDateTime(null);
	}


	public function testGetAllLocaleLinkParams(): void
	{
		$expected = [
			'cs_CZ' => [
				'hesla' => ['cs_CZ' => ['tag' => 'hesla'], 'en_US' => ['tag' => 'passwords']],
				'bezpecnost' => ['cs_CZ' => ['tag' => 'bezpecnost'], 'en_US' => ['tag' => 'securiteh']],
				'https' => ['cs_CZ' => ['tag' => 'https'], 'en_US' => ['tag' => 'https']],
				'stroj' => ['cs_CZ' => ['tag' => 'stroj'], 'en_US' => ['tag' => 'machine']],
			],
			'en_US' => [
				'passwords' => ['cs_CZ' => ['tag' => 'hesla'], 'en_US' => ['tag' => 'passwords']],
				'securiteh' => ['cs_CZ' => ['tag' => 'bezpecnost'], 'en_US' => ['tag' => 'securiteh']],
				'https' => ['cs_CZ' => ['tag' => 'https'], 'en_US' => ['tag' => 'https']],
				'machine' => ['cs_CZ' => ['tag' => 'stroj'], 'en_US' => ['tag' => 'machine']],
			],
		];
		Assert::same($expected, $this->tagTranslations->getAllLocaleLinkParams());
	}


	public function testGetLocaleLinkParams(): void
	{
		Assert::same(['cs_CZ' => ['tag' => 'hesla'], 'en_US' => ['tag' => 'passwords']], $this->tagTranslations->getLocaleLinkParams('hesla'));
		Assert::same([], $this->tagTranslations->getLocaleLinkParams('sifrovani'), 'The translation names another tag');
		Assert::same([], $this->tagTranslations->getLocaleLinkParams('planovane'), "The translation isn't out yet");
		Assert::same([], $this->tagTranslations->getLocaleLinkParams('navic'), 'The translation has no tag there');
		Assert::same([], $this->tagTranslations->getLocaleLinkParams('jen-cesky'), 'Not translated');
	}


	/**
	 * @param list<string> $csTags
	 * @param list<string> $enTags
	 * @return list<array<string, int|string|DateTime|null>>
	 */
	private function translatedPost(int $translationGroupId, array $csTags, DateTime $csPublished, array $enTags, ?DateTime $enPublished): array
	{
		return [
			['translationGroupId' => $translationGroupId, 'locale' => 'cs_CZ', 'published' => $csPublished, 'slugTags' => $this->tags->serialize($csTags)],
			['translationGroupId' => $translationGroupId, 'locale' => 'en_US', 'published' => $enPublished, 'slugTags' => $this->tags->serialize($enTags)],
		];
	}

}

TestCaseRunner::run(TagTranslationsTest::class);
