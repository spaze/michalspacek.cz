<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter;

use Contributte\Translation\Exceptions\InvalidArgument;
use Contributte\Translation\Translator;
use MichalSpacekCz\Application\LocaleLinkGenerator;
use MichalSpacekCz\DateTime\DateTimeFormatter;
use MichalSpacekCz\Post\LocaleUrls;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Training\Dates;
use MichalSpacekCz\Training\Locales;
use MichalSpacekCz\Training\Prices;
use Nette\Application\Application;
use Nette\Application\UI\InvalidLinkException;
use Nette\Application\UI\Presenter;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Database\Row;
use Nette\Utils\Arrays;
use Nette\Utils\Html;
use Texy\HandlerInvocation;
use Texy\HtmlElement;
use Texy\Link;
use Texy\Modifier;
use Texy\Texy as TexyTexy;
use Throwable;

class Texy
{

	/** @var string */
	private const TRAINING_DATE = 'TRAINING_DATE';

	private ?TexyTexy $texy = null;

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
		private readonly Application $application,
		private readonly Dates $trainingDates,
		private readonly Prices $prices,
		private readonly Locales $trainingLocales,
		private readonly LocaleLinkGenerator $localeLinkGenerator,
		private readonly LocaleUrls $blogPostLocaleUrls,
		private readonly DateTimeFormatter $dateTimeFormatter,
	) {
		$this->cacheNamespace = 'TexyFormatted' . '.' . $this->translator->getLocale();
	}


	/**
	 * Set static content URL root.
	 *
	 * @param string $root
	 */
	public function setStaticRoot(string $root): void
	{
		$this->staticRoot = rtrim($root, '/');
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
	 * Set images root directory.
	 *
	 * @param string $root
	 */
	public function setImagesRoot(string $root): void
	{
		$this->imagesRoot = trim($root, '/');
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
	 * Set location root directory.
	 *
	 * @param string $root
	 */
	public function setLocationRoot(string $root): void
	{
		$this->locationRoot = rtrim($root, '/');
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
	 * @return TexyTexy
	 */
	public function getTexy(): TexyTexy
	{
		$this->texy = new TexyTexy();
		$this->texy->allowedTags = $this->texy::NONE;
		$this->texy->imageModule->root = "{$this->staticRoot}/{$this->imagesRoot}";
		$this->texy->imageModule->fileRoot = "{$this->locationRoot}/{$this->imagesRoot}";
		$this->texy->figureModule->widthDelta = false;  // prevents adding 'unsafe-inline' style="width: Xpx" attribute to <div class="figure">
		$this->texy->headingModule->generateID = true;
		$this->texy->headingModule->idPrefix = '';
		$this->texy->typographyModule->locale = substr($this->translator->getDefaultLocale(), 0, 2);  // en_US → en
		$this->texy->allowed['phrase/del'] = true;
		$this->texy->addHandler('phrase', [$this, 'phraseHandler']);
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
	 * @param HandlerInvocation $invocation handler invocation
	 * @param string $phrase
	 * @param string $content
	 * @param Modifier $modifier
	 * @param Link|null $link
	 * @return HtmlElement<HtmlElement|string>|string|false
	 * @throws InvalidLinkException
	 * @throws ShouldNotHappenException
	 */
	public function phraseHandler(HandlerInvocation $invocation, string $phrase, string $content, Modifier $modifier, ?Link $link): HtmlElement|string|false
	{
		if (!$link) {
			return $invocation->proceed();
		}

		$trainingAction = ':Www:Trainings:training';
		$companyTrainingAction = ':Www:CompanyTrainings:training';
		/** @var Presenter $presenter */
		$presenter = $this->application->getPresenter();

		// "title":[link:Module:Presenter:action params]
		if (strncmp($link->URL, 'link:', 5) === 0) {
			/** @var string[] $args */
			$args = preg_split('/[\s,]+/', substr($link->URL, 5));
			$action = ':' . array_shift($args);
			if (Arrays::contains([$trainingAction, $companyTrainingAction], $action)) {
				$args = $this->trainingLocales->getLocaleActions($args[0])[$this->translator->getDefaultLocale()];
			}
			$link->URL = $presenter->link("//{$action}", $args);
		}

		// "title":[blog:post#fragment]
		if (strncmp($link->URL, 'blog:', 5) === 0) {
			$link->URL = $this->getBlogLinks(substr($link->URL, 5), $this->translator->getDefaultLocale());
		}

		// "title":[blog-en_US:post#fragment]
		if (preg_match('/^blog\-([a-z]{2}_[A-Z]{2}):(.*)\z/', $link->URL, $matches)) {
			$link->URL = $this->getBlogLinks($matches[2], $matches[1]);
		}

		// "title":[inhouse-training:training]
		if (strncmp($link->URL, 'inhouse-training:', 17) === 0) {
			$args = $this->trainingLocales->getLocaleActions(substr($link->URL, 17))[$this->translator->getDefaultLocale()];
			$link->URL = $presenter->link("//{$companyTrainingAction}", $args);
		}

		// "title":[training:training]
		if (strncmp($link->URL, 'training:', 9) === 0) {
			$texy = $invocation->getTexy();
			$name = substr($link->URL, 9);
			$name = $this->trainingLocales->getLocaleActions($name)[$this->translator->getDefaultLocale()];
			$link->URL = $presenter->link("//{$trainingAction}", $name);
			$el = HtmlElement::el();
			$el->add($texy->phraseModule->solve($invocation, $phrase, $content, $modifier, $link));
			$el->add($texy->protect($this->getTrainingSuffix($name), $texy::CONTENT_TEXTUAL));
			return $el;
		}

		return $invocation->proceed();
	}


	/**
	 * @param string $url
	 * @param string $locale
	 * @return string
	 * @throws ShouldNotHappenException
	 */
	private function getBlogLinks(string $url, string $locale): string
	{
		$args = explode('#', $url);
		$fragment = (empty($args[1]) ? '' : "#{$args[1]}");

		$params = [];
		foreach ($this->blogPostLocaleUrls->get($args[0]) as $post) {
			$params[$post->locale] = ['slug' => $post->slug, 'preview' => ($post->needsPreviewKey() ? $post->previewKey : null)];
		}
		$defaultParams = current($params);
		if ($defaultParams === false) {
			throw new ShouldNotHappenException("The blog links array should not be empty, maybe the linked blog post '{$url}' is missing?");
		}
		$this->localeLinkGenerator->setDefaultParams($params, $defaultParams);
		return $this->localeLinkGenerator->allLinks("Www:Post:default{$fragment}", $params)[$locale];
	}


	/**
	 * @param string $training Training name
	 * @return string
	 */
	private function getTrainingSuffix(string $training): string
	{
		$el = Html::el()
			->addHtml(Html::el()->setText(' '))
			->addHtml(Html::el('small')->setText(sprintf('(**%s:%s**)', self::TRAINING_DATE, $training)));
		return $el->render();
	}


	/**
	 * Format string and strip surrounding P element.
	 *
	 * Suitable for "inline" strings like headers.
	 *
	 * @param string|null $text
	 * @param TexyTexy|null $texy
	 * @return Html<Html|string>|null
	 * @throws Throwable
	 */
	public function format(?string $text, ?TexyTexy $texy = null): ?Html
	{
		if (empty($text)) {
			return null;
		}
		return $this->replace($this->cache("{$text}|" . __FUNCTION__, function () use ($text, $texy): string {
			return preg_replace('~^\s*<p[^>]*>(.*)</p>\s*$~s', '$1', ($texy ?? $this->getTexy())->process($text));
		}));
	}


	/**
	 * Format string.
	 *
	 * @param string|null $text
	 * @param TexyTexy|null $texy
	 * @return Html<Html|string>|null
	 * @throws Throwable
	 */
	public function formatBlock(?string $text, ?TexyTexy $texy = null): ?Html
	{
		if (empty($text)) {
			return null;
		}
		return $this->replace($this->cache("{$text}|" . __FUNCTION__, function () use ($text, $texy) {
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
			self::TRAINING_DATE => [$this, 'replaceTrainingDate'],
		);

		$result = preg_replace_callback('~\*\*([^:]+):([^*]+)\*\*~', function ($matches) use ($replacements): string {
			return (isset($replacements[$matches[1]]) ? $replacements[$matches[1]]($matches[2]) : '');
		}, (string)$result);
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
	 * @param callable $callback
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
