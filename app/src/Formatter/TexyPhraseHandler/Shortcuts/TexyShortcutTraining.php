<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter\TexyPhraseHandler\Shortcuts;

use Contributte\Translation\Translator;
use MichalSpacekCz\Application\WebApplication;
use MichalSpacekCz\Formatter\Exceptions\UnexpectedHandlerInvocationReturnTypeException;
use MichalSpacekCz\Formatter\Placeholders\TrainingDateTexyFormatterPlaceholder;
use MichalSpacekCz\Formatter\TexyPhraseHandler\TexyPhraseHandlerInvocation;
use MichalSpacekCz\Training\TrainingLocales;
use MichalSpacekCz\Training\Trainings\Trainings;
use Nette\Application\UI\InvalidLinkException;
use Nette\Utils\Html;
use Override;
use Texy\HandlerInvocation;
use Texy\HtmlElement;
use Texy\Link;
use Texy\Modifier;
use Texy\Texy;

final readonly class TexyShortcutTraining implements TexyShortcut
{

	private const string URL_PREFIX = 'training:';


	public function __construct(
		private WebApplication $webApplication,
		private Translator $translator,
		private TrainingLocales $trainingLocales,
		private TexyPhraseHandlerInvocation $handlerInvocation,
	) {
	}


	#[Override]
	public function canResolve(string $url): bool
	{
		return str_starts_with($url, self::URL_PREFIX);
	}


	/**
	 * @throws InvalidLinkException
	 * @throws UnexpectedHandlerInvocationReturnTypeException
	 */
	#[Override]
	public function resolve(string $url, HandlerInvocation $invocation, string $phrase, string $content, Modifier $modifier, Link $link): ?HtmlElement
	{
		// "title":[training:training]
		$texy = $invocation->getTexy();
		$name = substr($url, strlen(self::URL_PREFIX));
		if ($name === '') {
			throw new InvalidLinkException(sprintf('No training specified in [%s]', self::URL_PREFIX));
		}
		$actions = $this->trainingLocales->getLocaleActions($name);
		if ($actions === []) {
			throw new InvalidLinkException("Training linked in [{$url}] doesn't exist");
		}
		$locale = $this->translator->getDefaultLocale();
		if (!isset($actions[$locale])) {
			throw new InvalidLinkException("Training linked in [{$url}] doesn't exist in locale {$locale}");
		}
		$name = $actions[$locale];
		$link->URL = $this->webApplication->getPresenter()->link('//:' . Trainings::TRAINING_ACTION, $name);
		$el = HtmlElement::el();
		$trainingLink = $this->handlerInvocation->proceed($invocation, $phrase, $content, $modifier, $link);
		if ($trainingLink !== false) {
			$el->add($trainingLink);
			$el->add($texy->protect($this->getTrainingSuffix($name), Texy::CONTENT_TEXTUAL));
			return $el;
		}
		return null;
	}


	private function getTrainingSuffix(string $training): string
	{
		$el = Html::el()
			->addHtml(Html::el()->setText(' '))
			->addHtml(Html::el('small')->setText(sprintf('(**%s:%s**)', TrainingDateTexyFormatterPlaceholder::getId(), $training)));
		return $el->render();
	}

}
