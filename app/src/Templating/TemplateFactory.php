<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use Contributte\Translation\Translator;
use MichalSpacekCz\Application\Theme\Theme;
use MichalSpacekCz\Templating\Exceptions\WrongTemplateClassException;
use Nette\Application\UI\Control;
use Nette\Application\UI\TemplateFactory as UiTemplateFactory;
use Nette\Bridges\ApplicationLatte\TemplateFactory as ApplicationTemplateFactory;
use Override;

final readonly class TemplateFactory implements UiTemplateFactory
{

	public function __construct(
		private Theme $theme,
		private Filters $filters,
		private Translator $translator,
		private ApplicationTemplateFactory $templateFactory,
	) {
	}


	#[Override]
	public function createTemplate(?Control $control = null, ?string $class = null): DefaultTemplate
	{
		$template = $this->templateFactory->createTemplate($control, $class ?? DefaultTemplate::class);
		if (!$template instanceof DefaultTemplate) {
			throw new WrongTemplateClassException($template::class, DefaultTemplate::class);
		}
		$template->darkMode = $this->theme->isDarkMode();
		foreach ($this->filters->getAll() as $name => $callback) {
			$template->addFilter($name, $callback);
		}
		$template->setTranslator($this->translator);
		return $template;
	}


	/**
	 * @return array<int, string>
	 */
	public function getCustomFilters(): array
	{
		$filters = array_keys($this->filters->getAll());
		sort($filters);
		return $filters;
	}

}
