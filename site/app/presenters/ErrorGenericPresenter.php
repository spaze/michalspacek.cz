<?php
namespace App\Presenters;

/**
 * Generic error presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class ErrorGenericPresenter implements \Nette\Application\IPresenter
{

	/** @var \Tracy\ILogger */
	private $logger;


	/**
	 * @param \Tracy\ILogger $logger
	 */
	public function __construct(\Tracy\ILogger $logger)
	{
		$this->logger = $logger;
	}


	/**
	 * @return Nette\Application\IResponse
	 */
	public function run(\Nette\Application\Request $request)
	{
		$e = $request->getParameter('exception');

		if ($e instanceof \Nette\Application\BadRequestException) {
			$this->logger->log("HTTP code {$e->getCode()}: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}", 'access');
			return new \Nette\Application\Responses\ForwardResponse($request->setPresenterName('Error'));
		}

		$this->logger->log($e, \Tracy\ILogger::EXCEPTION);
		return new \Nette\Application\Responses\CallbackResponse(function () {
			require __DIR__ . '/templates/Error/exception.phtml';
		});
	}

}
