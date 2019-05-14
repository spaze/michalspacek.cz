<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter;

use MichalSpacekCz\Application\LocaleLinkGenerator;
use MichalSpacekCz\Post\LocaleUrls;
use MichalSpacekCz\Training\Dates;
use MichalSpacekCz\Training\Locales;
use MichalSpacekCz\Vat;
use Nette\Application\Application;
use Nette\Application\UI\Presenter;
use Nette\Caching\IStorage;
use Nette\Database\Row;
use Nette\Localization\ITranslator;
use Nette\Utils\Html;
use Netxten\Formatter\Texy as NetxtenTexy;
use Netxten\Templating\Helpers;
use Texy\HandlerInvocation;
use Texy\HtmlElement;
use Texy\Link;
use Texy\Modifier;
use Texy\Texy as TexyTexy;

class Texy extends NetxtenTexy
{

	/** @var string */
	private const TRAINING_DATE = 'TRAINING_DATE';

	/** @var ITranslator */
	protected $translator;

	/** @var Application */
	protected $application;

	/** @var Dates */
	protected $trainingDates;

	/** @var Locales */
	protected $trainingLocales;

	/** @var Vat */
	protected $vat;

	/** @var LocaleLinkGenerator */
	private $localeLinkGenerator;

	/** @var LocaleUrls */
	private $blogPostLocaleUrls;

	/** @var Helpers */
	protected $netxtenHelpers;

	/**
	 * Static files root FQDN, no trailing slash.
	 *
	 * @var string
	 */
	protected $staticRoot;

	/**
	 * Images root, just directory no FQDN, no leading slash, no trailing slash.
	 *
	 * @var string
	 */
	protected $imagesRoot;

	/**
	 * Physical location root directory, no trailing slash.
	 *
	 * @var string
	 */
	protected $locationRoot;

	/**
	 * Top heading level, used to avoid starting with H1.
	 *
	 * @var integer
	 */
	protected $topHeading = 1;


	public function __construct(
		IStorage $cacheStorage,
		ITranslator $translator,
		Application $application,
		Dates $trainingDates,
		Vat $vat,
		Locales $trainingLocales,
		LocaleLinkGenerator $localeLinkGenerator,
		LocaleUrls $localeUrls,
		Helpers $netxtenHelpers
	) {
		$this->translator = $translator;
		$this->application = $application;
		$this->trainingDates = $trainingDates;
		$this->vat = $vat;
		$this->trainingLocales = $trainingLocales;
		$this->localeLinkGenerator = $localeLinkGenerator;
		$this->blogPostLocaleUrls = $localeUrls;
		$this->netxtenHelpers = $netxtenHelpers;
		parent::__construct($cacheStorage, self::DEFAULT_NAMESPACE . '.' . $this->translator->getLocale());
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
	 * @param integer $level
	 * @return self
	 */
	public function setTopHeading(int $level): self
	{
		$this->topHeading = $level;
		return $this;
	}


	/**
	 * Create Texy object.
	 *
	 * @return TexyTexy
	 */
	protected function getTexy(): TexyTexy
	{
		$texy = parent::getTexy();
		$texy->imageModule->root = "{$this->staticRoot}/{$this->imagesRoot}";
		$texy->imageModule->fileRoot = "{$this->locationRoot}/{$this->imagesRoot}";
		$texy->figureModule->widthDelta = false;  // prevents adding 'unsafe-inline' style="width: Xpx" attribute to <div class="figure">
		$texy->headingModule->top = $this->topHeading;
		$texy->headingModule->generateID = true;
		$texy->headingModule->idPrefix = '';
		$texy->typographyModule->locale = substr($this->translator->getDefaultLocale(), 0, 2);  // en_US → en
		$texy->allowed['phrase/del'] = true;
		return $texy;
	}


	/**
	 * @param string $format
	 * @param string[]|null $args
	 * @return Html
	 */
	public function substitute(string $format, ?array $args): Html
	{
		return $this->format(vsprintf($format, $args));
	}


	/**
	 * @param string $message
	 * @param string[]|null $replacements
	 * @return Html
	 */
	public function translate($message, ?array $replacements = null): Html
	{
		return $this->substitute($this->translator->translate($message), $replacements);
	}


	public function addHandlers(): void
	{
		$this->addHandler('phrase', [$this, 'phraseHandler']);
	}


	/**
	 * @param HandlerInvocation $invocation handler invocation
	 * @param string $phrase
	 * @param string $content
	 * @param Modifier $modifier
	 * @param Link|null $link
	 * @return HtmlElement|string|FALSE
	 */
	function phraseHandler(HandlerInvocation $invocation, string $phrase, string $content, Modifier $modifier, ?Link $link)
	{
		if (!$link) {
			return $invocation->proceed();
		}

		$trainingAction = ':Www:Trainings:training';
		/** @var Presenter $presenter */
		$presenter = $this->application->getPresenter();

		if (strncmp($link->URL, 'link:', 5) === 0) {
			$args = preg_split('/[\s,]+/', substr($link->URL, 5));
			$action = ':' . array_shift($args);
			if ($action === $trainingAction) {
				$args = $this->trainingLocales->getLocaleActions(reset($args))[$this->translator->getDefaultLocale()];
			}
			$link->URL = $presenter->link("//{$action}", $args);
		}

		// "title":[blog:post#fragment]
		if (strncmp($link->URL, 'blog:', 5) === 0) {
			$args = explode('#', substr($link->URL, 5));
			$fragment = (empty($args[1]) ? '' : "#{$args[1]}");

			$params = [];
			foreach ($this->blogPostLocaleUrls->get($args[0]) as $post) {
				$params[$post->locale] = ['slug' => $post->slug, 'preview' => ($post->needsPreviewKey() ? $post->previewKey : null)];
			}
			$this->localeLinkGenerator->setDefaultParams($params, current($params));
			$link->URL = $this->localeLinkGenerator->allLinks("Www:Post:default{$fragment}", $params)[$this->translator->getDefaultLocale()];
		}

		// "title":[inhouse-training:training]
		if (strncmp($link->URL, 'inhouse-training:', 17) === 0) {
			$args = preg_split('/[\s,]+/', substr($link->URL, 17));
			$args = $this->trainingLocales->getLocaleActions(reset($args))[$this->translator->getDefaultLocale()];
			$link->URL = $presenter->link('//:Www:CompanyTrainings:training', $args);
		}

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
	 * @param string|null $text
	 * @return Html|null
	 */
	public function format(?string $text): ?Html
	{
		return (empty($text) ? null : $this->replace(parent::format($text)));
	}


	/**
	 * @param string|null $text
	 * @return Html|null
	 */
	public function formatBlock(?string $text): ?Html
	{
		return (empty($text) ? null : $this->replace(parent::formatBlock($text)));
	}


	/**
	 * @param Html $result
	 * @return Html
	 */
	private function replace(Html $result): Html
	{
		$replacements = array(
			self::TRAINING_DATE => [$this, 'replaceTrainingDate'],
		);

		$result = preg_replace_callback('~\*\*([^:]+):([^*]+)\*\*~', function ($matches) use ($replacements) {
			return (isset($replacements[$matches[1]]) ? $replacements[$matches[1]]($matches[2]) : null);
		}, (string)$result);
		return Html::el()->setHtml($result);
	}


	/**
	 * @param string $name
	 * @return string
	 */
	private function replaceTrainingDate($name): string
	{
		$upcoming = $this->trainingDates->getPublicUpcoming();
		$dates = array();
		if (!isset($upcoming[$name]) || empty($upcoming[$name]['dates'])) {
			$dates[] = $this->translator->translate('messages.trainings.nodateyet.short');
		} else {
			foreach ($upcoming[$name]['dates'] as $date) {
				$trainingDate = ($date->tentative ? $this->netxtenHelpers->localeIntervalMonth($date->start, $date->end) : $this->netxtenHelpers->localeIntervalDay($date->start, $date->end));
				$el = Html::el()
					->addHtml(Html::el('strong')->setText($trainingDate))
					->addHtml(Html::el()->setText(' '))
					->addHtml(Html::el()->setText($date->venueCity));
				$dates[] = $el;
			}
		}
		return implode(', ', $dates);
	}


	/**
	 * Format training items.
	 *
	 * @param Row $training
	 * @return Row
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
			$training->alternativeDurationPriceText = $this->translate($training->alternativeDurationPriceText, [
				(string)$training->alternativeDurationPrice,
				(string)$this->vat->addVat($training->alternativeDurationPrice),
			]);
		}
		return $training;
	}

}
