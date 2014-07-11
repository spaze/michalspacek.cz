<?php
use Nette\Diagnostics\Debugger,
	Nette\Application as NA;

/**
 * Error presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class ErrorPresenter extends BasePresenter
{

	/**
	 * @var \MichalSpacekCz\Redirections
	 */
	protected $redirections;


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 */
	public function __construct(\Nette\Localization\ITranslator $translator)
	{
		parent::__construct($translator);
	}


	/**
	 * @param \MichalSpacekCz\Redirections
	 */
	public function injectRedirections(\MichalSpacekCz\Redirections $redirections)
	{
		if ($this->redirections) {
			throw new Nette\InvalidStateException('Redirections has already been set');
		}
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

		if ($exception instanceof NA\BadRequestException) {
			$code = $exception->getCode();
			// load template 403.latte or 404.latte or ... 4xx.latte
			$this->setView(in_array($code, array(403, 404, 405, 410, 500)) ? $code : '4xx');
			// log to access.log
			Debugger::log("HTTP code $code: {$exception->getMessage()} in {$exception->getFile()}:{$exception->getLine()}", 'access');

		} else {
			$this->setView('500'); // load template 500.latte
			Debugger::log($exception, Debugger::ERROR); // and log exception
		}
		$this->template->errorCode = $this->getView();
	}


}
