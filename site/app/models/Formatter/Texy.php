<?php
namespace MichalSpacekCz\Formatter;

use Nette\Utils\Html;

class Texy extends \Netxten\Formatter\Texy
{

	/** @var string */
	const TRAINING_DATE = 'TRAINING_DATE';

	/** @var \Nette\Localization\ITranslator */
	protected $translator;

	/** @var \Nette\Application\Application */
	protected $application;

	/** @var \MichalSpacekCz\Training\Dates */
	protected $trainingDates;

	/** @var \MichalSpacekCz\Training\Locales */
	protected $trainingLocales;

	/** @var \MichalSpacekCz\Vat */
	protected $vat;

	/** @var \Netxten\Templating\Helpers */
	protected $netxtenHelpers;

	/**
	 * Static files root FQDN, no trailing slash.
	 *
	 * @var string
	 */
	protected $staticRoot;

	/**
	 * Images root, just directory no FQND, no leading slash, no trailing slash.
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
	 * @param \Nette\Caching\IStorage $cacheStorage
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \Nette\Application\Application $application
	 * @param \MichalSpacekCz\Training\Dates $trainingDates
	 * @param \MichalSpacekCz\Vat $vat
	 * @param \MichalSpacekCz\Training\Locales $trainingLocales
	 * @param \Netxten\Templating\Helpers $netxtenHelpers
	 */
	public function __construct(
		\Nette\Caching\IStorage $cacheStorage,
		\Nette\Localization\ITranslator $translator,
		\Nette\Application\Application $application,
		\MichalSpacekCz\Training\Dates $trainingDates,
		\MichalSpacekCz\Vat $vat,
		\MichalSpacekCz\Training\Locales $trainingLocales,
		\Netxten\Templating\Helpers $netxtenHelpers
	)
	{
		$this->translator = $translator;
		$this->application = $application;
		$this->trainingDates = $trainingDates;
		$this->vat = $vat;
		$this->trainingLocales = $trainingLocales;
		$this->netxtenHelpers = $netxtenHelpers;
		parent::__construct($cacheStorage, self::DEFAULT_NAMESPACE . '.' . $this->translator->getLocale());
	}


	/**
	 * Set static content URL root.
	 *
	 * @param string $root
	 */
	public function setStaticRoot($root)
	{
		$this->staticRoot = rtrim($root, '/');
	}


	/**
	 * Get static content URL root.
	 *
	 * @param string $filename
	 * @return string
	 */
	public function getStaticRoot()
	{
		return $this->staticRoot;
	}


	/**
	 * Set images root directory.
	 *
	 * @param string $root
	 */
	public function setImagesRoot($root)
	{
		$this->imagesRoot = trim($root, '/');
	}


	/**
	 * Get absolute URL of the image.
	 *
	 * @param string $filename
	 * @return string
	 */
	public function getImagesRoot($filename)
	{
		return sprintf('%s/%s/%s', $this->staticRoot, $this->imagesRoot, ltrim($filename, '/'));
	}


	/**
	 * Set location root directory.
	 *
	 * @param string $root
	 */
	public function setLocationRoot($root)
	{
		$this->locationRoot = rtrim($root, '/');
	}


	/**
	 * Create Texy object.
	 *
	 * @return \Texy\Texy
	 */
	protected function getTexy()
	{
		$texy = parent::getTexy();
		$texy->imageModule->root = "{$this->staticRoot}/{$this->imagesRoot}";
		$texy->imageModule->fileRoot = "{$this->locationRoot}/{$this->imagesRoot}";
		$texy->figureModule->widthDelta = false;  // prevents adding 'unsafe-inline' style="width: Xpx" attribute to <div class="figure">
		return $texy;
	}


	/**
	 * @param string $format
	 * @param array|null $args
	 * @return Html
	 */
	public function substitute($format, $args)
	{
		return parent::format(vsprintf($format, $args));
	}


	/**
	 * @param string $message
	 * @param array $replacements
	 * @return Html
	 */
	public function translate($message, array $replacements = null)
	{
		return $this->substitute($this->translator->translate($message), $replacements);
	}


	public function addHandlers()
	{
		$this->addHandler('phrase', [$this, 'phraseHandler']);
	}


	/**
	 * @param \Texy\HandlerInvocation  handler invocation
	 * @param string
	 * @param string
	 * @param \Texy\Modifier
	 * @param \Texy\Link
	 * @return \Texy\HtmlElement|string|FALSE
	 */
	function phraseHandler($invocation, $phrase, $content, $modifier, $link)
	{
		if (!$link) {
			return $invocation->proceed();
		}

		$trainingAction = ':Www:Trainings:training';

		if (strncmp($link->URL, 'link:', 5) === 0) {
			$args = preg_split('/[\s,]/', substr($link->URL, 5));
			$action = ':' . array_shift($args);
			if ($action === $trainingAction) {
				$args = $this->trainingLocales->getLocaleActions(reset($args))[$this->translator->getDefaultLocale()];
			}
			$link->URL = $this->application->getPresenter()->link($action, $args);
		}

		if (strncmp($link->URL, 'training:', 9) === 0) {
			$texy = $invocation->getTexy();
			$name = substr($link->URL, 9);
			$name = $this->trainingLocales->getLocaleActions($name)[$this->translator->getDefaultLocale()];
			$link->URL = $this->application->getPresenter()->link($trainingAction, $name);
			$el = \Texy\HtmlElement::el();
			$el->add($texy->phraseModule->solve($invocation, $phrase, $content, $modifier, $link));
			$el->add($texy->protect($this->getTrainingSuffix($name), $texy::CONTENT_TEXTUAL));
			return $el;
		}

		return $invocation->proceed();
	}


	/**
	 * @param string $training Training name
	 * @return Html
	 */
	private function getTrainingSuffix($training)
	{
		$el = Html::el()
			->addHtml(Html::el()->setText(' '))
			->addHtml(Html::el('small')->setText(sprintf('(**%s:%s**)', self::TRAINING_DATE, $training)));
		return $el;
	}


	/**
	 * @param string|null $text
	 * @return Html|false
	 */
	public function format($text)
	{
		return (empty($text) ? false : $this->replace(parent::format($text)));
	}


	/**
	 * @param string|null $text
	 * @return Html|false
	 */
	public function formatBlock($text)
	{
		return (empty($text) ? false : $this->replace(parent::formatBlock($text)));
	}


	/**
	 * @param Html $result
	 * @return Html
	 */
	private function replace(Html $result)
	{
		$replacements = array(
			self::TRAINING_DATE => [$this, 'replaceTrainingDate'],
		);

		$result = preg_replace_callback('~\*\*([^:]+):([^*]+)\*\*~', function ($matches) use ($replacements) {
			if (isset($replacements[$matches[1]])) {
				return $replacements[$matches[1]]($matches[2]);
			}
		}, $result);
		return Html::el()->setHtml($result);
	}


	/**
	 * @param string $name
	 * @return string
	 */
	private function replaceTrainingDate($name)
	{
		$upcoming = $this->trainingDates->getPublicUpcoming();
		$dates = array();
		if (!isset($upcoming[$name]) || empty($upcoming[$name]['dates'])) {
			$dates[] = $this->translator->translate('messages.trainings.nodateyet.short');
		} else {
			foreach ($upcoming[$name]['dates'] as $date) {
				$format = ($date->tentative ? '%B %Y' : 'j. n. Y');
				$start = $this->netxtenHelpers->localDate($date->start, 'cs', $format);
				$el = Html::el()
					->addHtml(Html::el('strong')->setText($start))
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
	 * @param \Nette\Utils\ArrayHash $training
	 * @return \Nette\Utils\ArrayHash
	 */
	public function formatTraining(\Nette\Utils\ArrayHash $training): \Nette\Utils\ArrayHash
	{
		foreach (['name', 'description', 'content', 'upsell', 'prerequisites', 'audience', 'materials', 'duration', 'doubleDuration'] as $key) {
			if (isset($training->$key)) {
				$training->$key = $this->translate($training->$key);
			}
		}
		if (isset($training->doubleDurationPrice)) {
			$training->doubleDurationPrice = $this->translate($training->doubleDurationPrice, [$training->price * 2, $this->vat->addVat($training->price) * 2]);
		}
		return $training;
	}

}
