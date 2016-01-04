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


	/**
	 * @param  Exception
	 * @return void
	 */
	public function actionDefault($exception)
	{
		$destination = $this->redirections->getDestination($this->getHttpRequest()->getUrl());
		if ($destination) {
			$this->redirectUrl($destination, \Nette\Http\IResponse::S301_MOVED_PERMANENTLY);
		}

		if ($exception instanceof \Nette\Application\BadRequestException) {
			$code = $exception->getCode();
			$code = (in_array($code, [403, 404, 405, 410, 500]) ? $code : '4xx');
			// log to access.log
			\Tracy\Debugger::log("HTTP code {$exception->getCode()}: {$exception->getMessage()} in {$exception->getFile()}:{$exception->getLine()}", 'access');
		} else {
			$code = '500';
			\Tracy\Debugger::log($exception, \Tracy\Debugger::EXCEPTION); // and log exception
		}

		$this->template->errorCode = $code;
		$this->template->pageTitle = $this->translator->translate("messages.title.error{$code}");
		$this->template->note =  $this->translator->translate("messages.error.{$code}");
	}


}
