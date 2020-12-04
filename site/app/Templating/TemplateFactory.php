<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use MichalSpacekCz\Application\Theme;
use Nette\Application\UI\Control;
use Nette\Application\UI\ITemplate;
use Nette\Bridges\ApplicationLatte\ILatteFactory;
use Nette\Bridges\ApplicationLatte\Template as NetteTemplate;
use Nette\Bridges\ApplicationLatte\TemplateFactory as NetteTemplateFactory;
use Nette\Caching\IStorage;
use Nette\Http\IRequest;
use Nette\Localization\Translator;
use Nette\Security\User;
use Netxten\Templating\Helpers as NetxtenHelpers;

class TemplateFactory extends NetteTemplateFactory
{

	private Theme $theme;

	private NetxtenHelpers $netxtenHelpers;

	private Helpers $templateHelpers;

	private Translator $translator;


	public function __construct(
		ILatteFactory $latteFactory,
		IRequest $httpRequest = null,
		User $user = null,
		IStorage $cacheStorage = null,
		Theme $theme,
		NetxtenHelpers $netxtenHelpers,
		Helpers $templateHelpers,
		Translator $translator,
		string $templateClass = null
	) {
		parent::__construct($latteFactory, $httpRequest, $user, $cacheStorage, $templateClass);
		$this->theme = $theme;
		$this->netxtenHelpers = $netxtenHelpers;
		$this->templateHelpers = $templateHelpers;
		$this->translator = $translator;
	}


	public function createTemplate(Control $control = null): ITemplate
	{
		/** @var NetteTemplate $template */
		$template = parent::createTemplate($control);
		$template->darkMode = $this->theme->isDarkMode();
		$template->addFilter(null, [$this->netxtenHelpers, 'loader']);
		$template->addFilter(null, [$this->templateHelpers, 'loader']);
		$template->setTranslator($this->translator);
		return $template;
	}

}
