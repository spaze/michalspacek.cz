<?php
namespace App\WwwModule\Presenters;

/**
 * A forbidden presenter.
 *
 * Does not extend BasePresenter to avoid loop in startup().
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 *
 * @property-read \Nette\Bridges\ApplicationLatte\Template|\stdClass $template
 */
class ForbiddenPresenter extends \Nette\Application\UI\Presenter
{

	/** @var \Contributte\Translation\Translator */
	protected $translator;

	/** @var \Nette\Http\IResponse */
	protected $httpResponse;

	/** @var \Spaze\ContentSecurityPolicy\Config */
	private $contentSecurityPolicy;

	/** @var \MichalSpacekCz\Templating\Helpers */
	private $templateHelpers;


	/**
	 * @internal
	 * @param \Spaze\ContentSecurityPolicy\Config $contentSecurityPolicy
	 */
	public function injectContentSecurityPolicy(\Spaze\ContentSecurityPolicy\Config $contentSecurityPolicy)
	{
		$this->contentSecurityPolicy = $contentSecurityPolicy;
	}


	/**
	 * @internal
	 * @param \MichalSpacekCz\Templating\Helpers $templateHelpers
	 */
	public function injectTemplateHelpers(\MichalSpacekCz\Templating\Helpers $templateHelpers)
	{
		$this->templateHelpers = $templateHelpers;
	}


	/**
	 * @param \Contributte\Translation\Translator $translator
	 * @param \Nette\Http\IResponse $httpResponse
	 */
	public function __construct(\Contributte\Translation\Translator $translator, \Nette\Http\IResponse $httpResponse)
	{
		$this->translator = $translator;
		$this->httpResponse = $httpResponse;
		parent::__construct();
	}


	public function beforeRender()
	{
		$this->template->setTranslator($this->translator);
	}


	protected function createTemplate(): \Nette\Application\UI\ITemplate
	{
		$template = parent::createTemplate();
		$template->getLatte()->addFilter(null, [new \Netxten\Templating\Helpers($this->translator->getDefaultLocale()), 'loader']);
		$template->getLatte()->addFilter(null, [$this->templateHelpers, 'loader']);
		return $template;
	}


	public function actionDefault()
	{
		$this->httpResponse->setCode(\Nette\Http\IResponse::S403_FORBIDDEN);
		$this->template->pageTitle = $this->translator->translate("messages.title.forbidden");
	}

}
