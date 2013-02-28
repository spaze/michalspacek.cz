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
		$this->template->articles          = $this->articles->getAll(3);
		$this->template->talks             = $this->talks->getAll(5);
		$this->template->upcomingTalks     = $this->talks->getUpcoming();
		$this->template->upcomingTrainings = $this->trainings->getUpcoming();
		$this->template->interviews        = $this->interviews->getAll(5);
	}


}
