<?php
declare(strict_types = 1);

namespace App\ApiModule\Presenters;

use MichalSpacekCz\Application\Error;
use Nette\Application\IPresenter;
use Nette\Application\IResponse;
use Nette\Application\Request;

/**
 * API Generic error presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class ErrorGenericPresenter implements IPresenter
{

	/** @var Error */
	private $error;


	public function __construct(Error $error)
	{
		$this->error = $error;
	}


	public function run(Request $request): IResponse
	{
		return $this->error->response($request);
	}

}
