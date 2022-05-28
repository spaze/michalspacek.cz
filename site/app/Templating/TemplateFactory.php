<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use Contributte\Translation\Translator;
use MichalSpacekCz\Application\Theme;
use Nette\Application\UI\Control;
use Nette\Application\UI\TemplateFactory as UiTemplateFactory;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Bridges\ApplicationLatte\TemplateFactory as ApplicationTemplateFactory;
use Spaze\NonceGenerator\Generator;

class TemplateFactory implements UiTemplateFactory
{

	public function __construct(
		private readonly Theme $theme,
		private readonly Filters $filters,
		private readonly Translator $translator,
		private readonly ApplicationTemplateFactory $templateFactory,
		private readonly Generator $nonceGenerator,
	) {
	}


	public function createTemplate(Control $control = null, string $class = null): Template
	{
		/** @var Template $template */
		$template = $this->templateFactory->createTemplate($control, $class);
		$template->darkMode = $this->theme->isDarkMode();
		foreach ($this->filters->getAll() as $name => $callback) {
			$template->addFilter($name, $callback);
		}
		$template->setTranslator($this->translator);
		$template->getLatte()->addProvider('uiNonce', $this->nonceGenerator->getNonce());
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
