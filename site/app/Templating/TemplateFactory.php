<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use Contributte\Translation\Translator;
use MichalSpacekCz\Application\Theme;
use Nette\Application\UI\Control;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Bridges\ApplicationLatte\TemplateFactory as NetteTemplateFactory;
use Nette\Caching\Storage;
use Nette\Http\IRequest;
use Nette\Security\User;

class TemplateFactory extends NetteTemplateFactory
{

	public function __construct(
		private LatteFactory $latteFactory,
		private Theme $theme,
		private Filters $filters,
		private Translator $translator,
		private ?IRequest $httpRequest = null,
		private ?User $user = null,
		private ?Storage $cacheStorage = null,
		string $templateClass = null,
	) {
		parent::__construct($this->latteFactory, $this->httpRequest, $this->user, $this->cacheStorage, $templateClass);
	}


	public function createTemplate(Control $control = null, string $class = null): Template
	{
		/** @var Template $template */
		$template = parent::createTemplate($control, $class);
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
