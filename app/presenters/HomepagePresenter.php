<?php
/**
 * Homepage presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class HomepagePresenter extends BasePresenter
{


	public function renderDefault()
	{
		$this->template->articles          = $this->context->createArticles()->getAll(3);
		$this->template->talks             = $this->context->createTalks()->getAll(5);
		$this->template->upcomingTrainings = $this->context->createTrainings()->getUpcoming();
	}


}
