<?php
namespace App\Presenters;

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


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \Nette\Http\IResponse $httpResponse
	 */
	public function __construct(\Nette\Localization\ITranslator $translator, \Nette\Http\IResponse $httpResponse)
	{
		$this->translator = $translator;
		$this->httpResponse = $httpResponse;
	}


	public function beforeRender()
	{
		$webTracking = $this->getContext()->getByType(\MichalSpacekCz\WebTracking::class);
		$this->template->trackingCode = $webTracking->isEnabled();
		$this->template->setTranslator($this->translator);
	}


	protected function createTemplate($class = null)
	{
		$helpers = $this->getContext()->getByType(\MichalSpacekCz\Templating\Helpers::class);

		$template = parent::createTemplate($class);
		$template->getLatte()->addFilter(null, [new \Netxten\Templating\Helpers(), 'loader']);
		$template->getLatte()->addFilter(null, [$helpers, 'loader']);
		return $template;
	}


	public function actionDefault()
	{
		$this->httpResponse->setCode(\Nette\Http\Response::S403_FORBIDDEN);
		$this->template->pageTitle = $this->translator->translate("messages.title.forbidden");
	}

}
