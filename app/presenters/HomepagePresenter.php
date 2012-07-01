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
		$this->template->trainings = $this->trainings;

		$articles = $this->context->createArticles()->order('date DESC')->limit(3);
		$this->template->articles = $articles;

		$talks = $this->context->createTalks()->order('date DESC')->limit(5);
		$this->template->talks = $talks;

		$upcomingTrainings = $this->context->createTrainingDates()->where('end > NOW()')->order('key_training ASC');
		$this->template->upcomingTrainings = $upcomingTrainings;
	}

}
