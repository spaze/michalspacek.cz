<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter\TexyPhraseHandler\Shortcuts;

use Contributte\Translation\Translator;
use MichalSpacekCz\Application\WebApplication;
use MichalSpacekCz\Training\Company\CompanyTrainings;
use MichalSpacekCz\Training\TrainingLocales;
use Nette\Application\UI\InvalidLinkException;
use Override;
use Texy\HandlerInvocation;
use Texy\Link;
use Texy\Modifier;

final readonly class TexyShortcutInhouseTraining implements TexyShortcut
{

	private const string PREFIX = 'inhouse-training:';


	public function __construct(
		private WebApplication $webApplication,
		private Translator $translator,
		private TrainingLocales $trainingLocales,
	) {
	}


	#[Override]
	public function canResolve(string $url): bool
	{
		return str_starts_with($url, self::PREFIX);
	}


	/**
	 * @throws InvalidLinkException
	 */
	#[Override]
	public function resolve(string $url, HandlerInvocation $invocation, string $phrase, string $content, Modifier $modifier, Link $link): null
	{
		// "title":[inhouse-training:training]
		$training = substr($url, strlen(self::PREFIX));
		if ($training === '') {
			throw new InvalidLinkException("No company training specified in [{$url}]");
		}
		$actions = $this->trainingLocales->getLocaleActions($training);
		if ($actions === []) {
			throw new InvalidLinkException("Company training linked in [{$url}] doesn't exist");
		}
		$locale = $this->translator->getDefaultLocale();
		if (!isset($actions[$locale])) {
			throw new InvalidLinkException("Company training linked in [{$url}] doesn't exist in locale {$locale}");
		}
		$action = $actions[$locale];
		$link->URL = $this->webApplication->getPresenter()->link('//:' . CompanyTrainings::COMPANY_TRAINING_ACTION, $action);
		return null;
	}

}
