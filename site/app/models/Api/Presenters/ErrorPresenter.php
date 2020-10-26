<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Api\Presenters;

use MichalSpacekCz\Www\Presenters\BaseErrorPresenter;
use Nette\Application\BadRequestException;
use Nette\Http\IResponse;

class ErrorPresenter extends BaseErrorPresenter
{

	/** @var array<integer, string> */
	protected $statuses = [
		IResponse::S400_BAD_REQUEST => 'Never gonna request you up',
		IResponse::S403_FORBIDDEN => 'Never gonna accept you up',
		IResponse::S404_NOT_FOUND => 'Never gonna look you up',
		IResponse::S405_METHOD_NOT_ALLOWED => 'Never gonna allow you up',
		IResponse::S410_GONE => 'Never gonna visit you up',
	];


	public function actionDefault(BadRequestException $exception): void
	{
		$code = (in_array($exception->getCode(), array_keys($this->statuses)) ? $exception->getCode() : IResponse::S400_BAD_REQUEST);
		$this->sendJson([
			'status' => $code,
			'statusMessage' => $this->statuses[$code],
			'token' => base64_encode('https://media.giphy.com/media/Vuw9m5wXviFIQ/giphy.gif'),  // Never gonna decode you up
		]);
	}

}
