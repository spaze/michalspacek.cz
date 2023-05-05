<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use Contributte\Translation\Translator;
use MichalSpacekCz\Application\Theme;
use MichalSpacekCz\Templating\Exceptions\WrongTemplateClassException;
use Nette\Application\UI\Control;
use Nette\Application\UI\TemplateFactory as UiTemplateFactory;
use Nette\Bridges\ApplicationLatte\DefaultTemplate;
use Nette\Bridges\ApplicationLatte\TemplateFactory as ApplicationTemplateFactory;

class TemplateFactory implements UiTemplateFactory
{

	public function __construct(
		private readonly Theme $theme,
		private readonly Filters $filters,
		private readonly Translator $translator,
		private readonly ApplicationTemplateFactory $templateFactory,
	) {
	}


	public function createTemplate(Control $control = null, string $class = null): DefaultTemplate
	{
		$template = $this->templateFactory->createTemplate($control, $class);
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
