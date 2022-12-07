<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Api\Presenters;

use MichalSpacekCz\Www\Presenters\BaseErrorPresenter;
use Nette\Application\BadRequestException;
use Nette\Http\IResponse;

class ErrorPresenter extends BaseErrorPresenter
{

	/** @var array<int, string> */
	private array $statuses = [
		IResponse::S400_BadRequest => 'Never gonna request you up',
		IResponse::S403_Forbidden => 'Never gonna accept you up',
		IResponse::S404_NotFound => 'Never gonna look you up',
		IResponse::S405_MethodNotAllowed => 'Never gonna allow you up',
		IResponse::S410_Gone => 'Never gonna visit you up',
	];


	public function actionDefault(BadRequestException $exception): never
	{
		$code = (in_array($exception->getCode(), array_keys($this->statuses)) ? $exception->getCode() : IResponse::S400_BadRequest);
		$this->sendJson([
			'status' => $code,
			'statusMessage' => $this->statuses[$code],
			'token' => base64_encode('https://media.giphy.com/media/Vuw9m5wXviFIQ/giphy.gif'),  // Never gonna decode you up
		]);
	}

}
