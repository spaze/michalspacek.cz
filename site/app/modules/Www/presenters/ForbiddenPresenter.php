<?php
declare(strict_types = 1);

namespace App\WwwModule\Presenters;

use MichalSpacekCz\Templating\Helpers;
use Nette\Application\UI\ITemplate;
use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Http\IResponse;
use Nette\Localization\ITranslator;
use Netxten\Templating\Helpers as NetxtenHelpers;
use Spaze\ContentSecurityPolicy\Config;
use stdClass;

/**
 * A forbidden presenter.
 *
 * Does not extend BasePresenter to avoid loop in startup().
 *
 * @property-read Template|stdClass $template
 */
class ForbiddenPresenter extends Presenter
{

	/** @var ITranslator */
	protected $translator;

	/** @var IResponse */
	protected $httpResponse;

	/** @var Config */
	private $contentSecurityPolicy;

	/** @var Helpers */
	private $templateHelpers;


	/**
	 * @internal
	 * @param Config $contentSecurityPolicy
	 */
	public function injectContentSecurityPolicy(Config $contentSecurityPolicy): void
	{
		$this->contentSecurityPolicy = $contentSecurityPolicy;
	}


	/**
	 * @internal
	 * @param Helpers $templateHelpers
	 */
	public function injectTemplateHelpers(Helpers $templateHelpers): void
	{
		$this->templateHelpers = $templateHelpers;
	}


	public function __construct(ITranslator $translator, IResponse $httpResponse)
	{
		$this->translator = $translator;
		$this->httpResponse = $httpResponse;
		parent::__construct();
	}


	public function beforeRender(): void
	{
		$this->template->setTranslator($this->translator);
	}


	protected function createTemplate(): ITemplate
	{
		/** @var Template $template */
		$template = parent::createTemplate();
		$template->getLatte()->addFilter(null, [new NetxtenHelpers($this->translator->getDefaultLocale()), 'loader']);
		$template->getLatte()->addFilter(null, [$this->templateHelpers, 'loader']);
		return $template;
	}


	public function actionDefault(): void
	{
		$this->httpResponse->setCode(IResponse::S403_FORBIDDEN);
		$this->template->pageTitle = $this->translator->translate("messages.title.forbidden");
	}

}
