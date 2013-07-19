<?php
namespace AdminModule;

/**
 * Homepage presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class HomepagePresenter extends BasePresenter
{


	public function actionDefault()
	{
		throw new \Nette\Application\BadRequestException('No admin for you my dear', \Nette\Http\Response::S404_NOT_FOUND);
	}


}
