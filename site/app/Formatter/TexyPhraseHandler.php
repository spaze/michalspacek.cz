<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter;

use Contributte\Translation\Translator;
use MichalSpacekCz\Application\LocaleLinkGenerator;
use MichalSpacekCz\Post\LocaleUrls;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Training\Locales;
use Nette\Application\Application;
use Nette\Application\UI\InvalidLinkException;
use Nette\Application\UI\Presenter;
use Nette\Utils\Arrays;
use Nette\Utils\Html;
use Texy\HandlerInvocation;
use Texy\HtmlElement;
use Texy\Link;
use Texy\Modifier;

class TexyPhraseHandler
{

	public function __construct(
		private readonly Application $application,
		private readonly Locales $trainingLocales,
		private readonly LocaleLinkGenerator $localeLinkGenerator,
		private readonly LocaleUrls $blogPostLocaleUrls,
		private readonly Translator $translator,
	) {
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
	public function solve(HandlerInvocation $invocation, string $phrase, string $content, Modifier $modifier, ?Link $link): HtmlElement|string|false
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
			->addHtml(Html::el('small')->setText(sprintf('(**%s:%s**)', TexyFormatter::TRAINING_DATE_PLACEHOLDER, $training)));
		return $el->render();
	}

}
