<?php
namespace App\ApiModule\Presenters;

use \Nette\Http\IResponse;

/**
 * API Generic error presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class ErrorPresenter extends \App\Presenters\ErrorPresenter
{

	/** @var array */
	protected $statuses = [
		IResponse::S400_BAD_REQUEST => 'Never gonna request you up',
		IResponse::S403_FORBIDDEN => 'Never gonna accept you up',
		IResponse::S404_NOT_FOUND => 'Never gonna look you up',
		IResponse::S405_METHOD_NOT_ALLOWED => 'Never gonna allow you up',
		IResponse::S410_GONE => 'Never gonna visit you up',
	];


	public function actionDefault(\Nette\Application\BadRequestException $exception)
	{
		$code = (in_array($exception->getCode(), array_keys($this->statuses)) ? $exception->getCode() : IResponse::S400_BAD_REQUEST);
		$this->sendJson([
			'status' => $code,
			'statusMessage' => $this->statuses[$code],
			'token' => base64_encode('https://media.giphy.com/media/Vuw9m5wXviFIQ/giphy.gif'),  // Never gonna decode you up
		]);
	}

}
