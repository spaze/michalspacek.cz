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
		$this->template->articles          = $this->context->createArticles()->order('date DESC')->limit(3);
		$this->template->talks             = $this->context->createTalks()->order('date DESC')->limit(5);
		$this->template->upcomingTrainings = $this->context->createTrainingDates()->where('end > NOW()')->order('key_training ASC');
	}


}
