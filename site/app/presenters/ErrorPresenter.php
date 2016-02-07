<?php
namespace App\Presenters;

/**
 * Error presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class ErrorPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Redirections */
	protected $redirections;


	/**
	 * @param \MichalSpacekCz\Redirections $translator
	 */
	public function __construct(\MichalSpacekCz\Redirections $redirections)
	{
		$this->redirections = $redirections;
	}


	public function startup()
	{
		parent::startup();
		if (!$this->getRequest()->isMethod(\Nette\Application\Request::FORWARD)) {
			$this->error();
		}
	}


	/**
	 * @param \Nette\Application\BadRequestException $exception
	 */
	public function actionDefault(\Nette\Application\BadRequestException $exception)
	{
		$destination = $this->redirections->getDestination($this->getHttpRequest()->getUrl());
		if ($destination) {
			$this->redirectUrl($destination, \Nette\Http\IResponse::S301_MOVED_PERMANENTLY);
		}

		$code = $exception->getCode();
		$code = (in_array($code, [403, 404, 405, 410]) ? $code : '4xx');

		$this->template->errorCode = $code;
		$this->template->pageTitle = $this->translator->translate("messages.title.error{$code}");
		$this->template->note =  $this->translator->translate("messages.error.{$code}");
	}


}
