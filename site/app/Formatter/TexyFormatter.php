<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter;

use Contributte\Translation\Exceptions\InvalidArgument;
use Contributte\Translation\Translator;
use MichalSpacekCz\DateTime\DateTimeFormatter;
use MichalSpacekCz\Training\Dates;
use MichalSpacekCz\Training\Prices;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Database\Row;
use Nette\Utils\Html;
use Nette\Utils\Strings;
use Texy\Texy;
use Throwable;

class TexyFormatter
{

	public const TRAINING_DATE_PLACEHOLDER = 'TRAINING_DATE';

	private ?Texy $texy = null;

	private string $cacheNamespace;

	private bool $cacheResult = true;

	/**
	 * Static files root FQDN, no trailing slash.
	 */
	private string $staticRoot;

	/**
	 * Images root, just directory no FQDN, no leading slash, no trailing slash.
	 */
	private string $imagesRoot;

	/**
	 * Physical location root directory, no trailing slash.
	 */
	private string $locationRoot;

	/**
	 * Top heading level, used to avoid starting with H1.
	 */
	private int $topHeading = 1;


	public function __construct(
		private readonly Storage $cacheStorage,
		private readonly Translator $translator,
		private readonly Dates $trainingDates,
		private readonly Prices $prices,
		private readonly DateTimeFormatter $dateTimeFormatter,
		private readonly TexyPhraseHandler $phraseHandler,
		string $staticRoot,
		string $imagesRoot,
		string $locationRoot,
	) {
		$this->staticRoot = rtrim($staticRoot, '/');
		$this->imagesRoot = trim($imagesRoot, '/');
		$this->locationRoot = rtrim($locationRoot, '/');
		$this->cacheNamespace = 'TexyFormatted.' . $this->translator->getLocale();
	}


	/**
	 * Get static content URL root.
	 *
	 * @return string
	 */
	public function getStaticRoot(): string
	{
		return $this->staticRoot;
	}


	/**
	 * Get absolute URL of the image.
	 *
	 * @param string $filename
	 * @return string
	 */
	public function getImagesRoot(string $filename): string
	{
		return sprintf('%s/%s/%s', $this->staticRoot, $this->imagesRoot, ltrim($filename, '/'));
	}


	/**
	 * Set top heading level.
	 *
	 * @param int $level
	 * @return self
	 */
	public function setTopHeading(int $level): self
	{
		$this->topHeading = $level;
		if ($this->texy) {
			$this->texy->headingModule->top = $this->topHeading;
		}
		return $this;
	}


	/**
	 * Create Texy object.
	 *
	 * @return Texy
	 */
	public function getTexy(): Texy
	{
		$this->texy = new Texy();
		$this->texy->allowedTags = $this->texy::NONE;
		$this->texy->imageModule->root = "{$this->staticRoot}/{$this->imagesRoot}";
		$this->texy->imageModule->fileRoot = "{$this->locationRoot}/{$this->imagesRoot}";
		$this->texy->figureModule->widthDelta = false;  // prevents adding 'unsafe-inline' style="width: Xpx" attribute to <div class="figure">
		$this->texy->headingModule->generateID = true;
		$this->texy->headingModule->idPrefix = '';
		$this->texy->typographyModule->locale = substr($this->translator->getDefaultLocale(), 0, 2);  // en_US → en
		$this->texy->allowed['phrase/del'] = true;
		$this->texy->addHandler('phrase', [$this->phraseHandler, 'solve']);
		$this->setTopHeading($this->topHeading);
		return $this->texy;
	}


	/**
	 * @param string $format
	 * @param string[] $args
	 * @return Html<Html|string>
	 * @throws Throwable
	 */
	public function substitute(string $format, array $args): Html
	{
		return $this->format(vsprintf($format, $args));
	}


	/**
	 * @param string $message
	 * @param string[] $replacements
	 * @return Html<Html|string>
	 * @throws InvalidArgument
	 * @throws Throwable
	 */
	public function translate(string $message, array $replacements = []): Html
	{
		return $this->substitute($this->translator->translate($message), $replacements);
	}


	/**
	 * Format string and strip surrounding P element.
	 *
	 * Suitable for "inline" strings like headers.
	 *
	 * @param string $text
	 * @param Texy|null $texy
	 * @return Html<Html|string>
	 * @throws Throwable
	 */
	public function format(string $text, ?Texy $texy = null): Html
	{
		return $this->replace($this->cache("{$text}|" . __FUNCTION__, function () use ($text, $texy): string {
			return Strings::replace(($texy ?? $this->getTexy())->process($text), '~^\s*<p[^>]*>(.*)</p>\s*$~s', '$1');
		}));
	}


	/**
	 * Format string.
	 *
	 * @param string $text
	 * @param Texy|null $texy
	 * @return Html<Html|string>
	 * @throws Throwable
	 */
	public function formatBlock(string $text, ?Texy $texy = null): Html
	{
		return $this->replace($this->cache("{$text}|" . __FUNCTION__, function () use ($text, $texy): string {
			return ($texy ?? $this->getTexy())->process($text);
		}));
	}


	/**
	 * @param Html<Html|string> $result
	 * @return Html<Html|string>
	 */
	private function replace(Html $result): Html
	{
		$replacements = array(
			self::TRAINING_DATE_PLACEHOLDER => [$this, 'replaceTrainingDate'],
		);

		$result = Strings::replace(
			(string)$result,
			'~\*\*([^:]+):([^*]+)\*\*~',
			function ($matches) use ($replacements): string {
				return (isset($replacements[$matches[1]]) ? $replacements[$matches[1]]($matches[2]) : '');
			},
		);
		return Html::el()->setHtml($result);
	}


	/**
	 * @param string $name
	 * @return string
	 * @throws InvalidArgument
	 */
	private function replaceTrainingDate(string $name): string
	{
		$upcoming = $this->trainingDates->getPublicUpcoming();
		$dates = array();
		if (!isset($upcoming[$name]) || empty($upcoming[$name]['dates'])) {
			$dates[] = $this->translator->translate('messages.trainings.nodateyet.short');
		} else {
			foreach ($upcoming[$name]['dates'] as $date) {
				$trainingDate = ($date->tentative ? $this->dateTimeFormatter->localeIntervalMonth($date->start, $date->end) : $this->dateTimeFormatter->localeIntervalDay($date->start, $date->end));
				$el = Html::el()
					->addHtml(Html::el('strong')->setText($trainingDate))
					->addHtml(Html::el()->setText(' '))
					->addHtml(Html::el()->setText($date->remote ? $this->translator->translate('messages.label.remote') : $date->venueCity));
				$dates[] = $el;
			}
		}
		return sprintf(
			'%s: %s',
			count($dates) > 1 ? $this->translator->translate('messages.trainings.nextdates') : $this->translator->translate('messages.trainings.nextdate'),
			implode(', ', $dates),
		);
	}


	/**
	 * Format training items.
	 *
	 * @param Row<mixed> $training
	 * @return Row<mixed>
	 * @throws InvalidArgument
	 * @throws Throwable
	 */
	public function formatTraining(Row $training): Row
	{
		$this->setTopHeading(3);
		foreach (['name', 'description', 'content', 'upsell', 'prerequisites', 'audience', 'materials', 'duration', 'alternativeDuration'] as $key) {
			if (isset($training->$key)) {
				$training->$key = $this->translate($training->$key);
			}
		}

		if (isset($training->alternativeDurationPriceText)) {
			$price = $this->prices->resolvePriceVat($training->alternativeDurationPrice);
			$training->alternativeDurationPriceText = $this->translate($training->alternativeDurationPriceText, [
				$price->getPriceWithCurrency(),
				$price->getPriceVatWithCurrency(),
			]);
		}
		return $training;
	}


	/**
	 * Cache formatted string.
	 *
	 * @param string $text
	 * @param callable(): string $callback
	 * @return Html
	 * @throws Throwable
	 */
	private function cache(string $text, callable $callback): Html
	{
		if ($this->cacheResult) {
			$cache = new Cache($this->cacheStorage, $this->cacheNamespace);
			// Nette Cache itself generates the key by hashing the key passed in load() so we can use whatever we want
			$formatted = $cache->load($text, $callback);
		} else {
			$formatted = $callback();
		}
		return Html::el()->setHtml($formatted);
	}


	public function disableCache(): self
	{
		$this->cacheResult = false;
		return $this;
	}


	public function enableCache(): self
	{
		$this->cacheResult = true;
		return $this;
	}

}
