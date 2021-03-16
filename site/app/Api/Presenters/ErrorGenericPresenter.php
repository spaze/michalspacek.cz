<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Api\Presenters;

use MichalSpacekCz\Application\Error;
use Nette\Application\IPresenter;
use Nette\Application\Request;
use Nette\Application\Response;

class ErrorGenericPresenter implements IPresenter
{

	private Error $error;


	public function __construct(Error $error)
	{
		$this->error = $error;
	}


	public function run(Request $request): Response
	{
		return $this->error->response($request);
	}

}
