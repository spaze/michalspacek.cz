<?php
namespace App\WwwModule\Presenters;

/**
 * A forbidden presenter.
 *
 * Does not extend BasePresenter to avoid loop in startup().
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class ForbiddenPresenter extends \Nette\Application\UI\Presenter
{

	/** @var \Nette\Localization\ITranslator */
	protected $translator;

	/** @var \Nette\Http\IResponse */
	protected $httpResponse;

	/** @var \MichalSpacekCz\WebTracking */
	private $webTracking;

	/** @var \Spaze\ContentSecurityPolicy\Config */
	private $contentSecurityPolicy;

	/** @var \MichalSpacekCz\Templating\Helpers */
	private $templateHelpers;


	/**
	 * @internal
	 * @param \MichalSpacekCz\WebTracking $webTracking
	 */
	public function injectWebTracking(\MichalSpacekCz\WebTracking $webTracking)
	{
		$this->webTracking = $webTracking;
	}


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
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \Nette\Http\IResponse $httpResponse
	 */
	public function __construct(\Nette\Localization\ITranslator $translator, \Nette\Http\IResponse $httpResponse)
	{
		$this->translator = $translator;
		$this->httpResponse = $httpResponse;
		parent::__construct();
	}


	public function beforeRender()
	{
		if ($this->template->trackingCode = $this->webTracking->isEnabled()) {
			$this->contentSecurityPolicy->addSnippet('ga');
		}
		$this->template->setTranslator($this->translator);
	}


	protected function createTemplate()
	{
		$template = parent::createTemplate();
		$template->getLatte()->addFilter(null, [new \Netxten\Templating\Helpers($this->translator), 'loader']);
		$template->getLatte()->addFilter(null, [$this->templateHelpers, 'loader']);
		return $template;
	}


	public function actionDefault()
	{
		$this->httpResponse->setCode(\Nette\Http\Response::S403_FORBIDDEN);
		$this->template->pageTitle = $this->translator->translate("messages.title.forbidden");
	}

}
