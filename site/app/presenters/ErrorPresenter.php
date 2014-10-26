<?php
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
	 * @param \Nette\Localization\ITranslator $translator
	 */
	public function __construct(\Nette\Localization\ITranslator $translator, \MichalSpacekCz\Redirections $redirections)
	{
		parent::__construct($translator);
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
			// load template 403.latte or 404.latte or ... 4xx.latte
			$this->setView(in_array($code, array(403, 404, 405, 410, 500)) ? $code : '4xx');
			// log to access.log
			\Tracy\Debugger::log("HTTP code $code: {$exception->getMessage()} in {$exception->getFile()}:{$exception->getLine()}", 'access');

		} else {
			$this->setView('500'); // load template 500.latte
			\Tracy\Debugger::log($exception, \Tracy\Debugger::EXCEPTION); // and log exception
		}
		$this->template->errorCode = $this->getView();
	}


}
