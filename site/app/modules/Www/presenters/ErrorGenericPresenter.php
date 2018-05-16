<?php
namespace App\WwwModule\Presenters;

use \Nette\Application\Responses;

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
	 * @return \Nette\Application\IResponse
	 */
	public function run(\Nette\Application\Request $request)
	{
		$e = $request->getParameter('exception');

		if ($e instanceof \Nette\Application\BadRequestException) {
			list($module, , $sep) = \Nette\Application\Helpers::splitName($request->getPresenterName());
			return new Responses\ForwardResponse($request->setPresenterName($module . $sep . 'Error'));
		}

		$this->logger->log($e, \Tracy\ILogger::EXCEPTION);
		return new Responses\CallbackResponse(function () {
			require __DIR__ . '/templates/Error/exception.phtml';
		});
	}

}
