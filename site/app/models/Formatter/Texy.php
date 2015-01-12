<?php
namespace MichalSpacekCz\Formatter;

class Texy extends \Bare\Next\Formatter\Texy
{

	/** @var \Nette\Localization\ITranslator */
	protected $translator;

	/** @var \Nette\Application\Application */
	protected $application;


	public function __construct(
		\Nette\Caching\IStorage $cacheStorage,
		\Nette\Localization\ITranslator $translator,
		\Nette\Application\Application $application
	)
	{
		$this->translator = $translator;
		$this->application = $application;
		parent::__construct($cacheStorage);
	}


	/**
	 * @param string $format
	 * @param string $args
	 * @return \Nette\Utils\Html
	 */
	public function substitute($format, $args)
	{
		return parent::format(vsprintf($format, $args));
	}


	/**
	 * @param string $message
	 * @param array $replacements
	 * @return string
	 */
	public function translate($message, array $replacements = null)
	{
		return $this->substitute($this->translator->translate($message), $replacements);
	}


	/**
	 * @param \Texy $texy
	 */
	public function addHandlers()
	{
		$this->addHandler('phrase', [$this, 'phraseHandler']);
	}


	/**
	 * @param TexyHandlerInvocation  handler invocation
	 * @param string
	 * @param string
	 * @param TexyModifier
	 * @param TexyLink
	 * @return TexyHtml|string|FALSE
	 */
	function phraseHandler($invocation, $phrase, $content, $modifier, $link)
	{
		if (!$link) {
			return $invocation->proceed();
		}

		if (strncmp($link->URL, 'link:', 5) === 0) {
			$args = preg_split('/[\s,]/', substr($link->URL, 5));
			$link->URL = $this->application->getPresenter()->link(array_shift($args), $args);
		}

		return $invocation->proceed();
	}

}
