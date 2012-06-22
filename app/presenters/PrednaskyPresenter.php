<?php
/**
 * Přednášky presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class PrednaskyPresenter extends BasePresenter
{

	public function renderDefault()
	{
		$this->template->pageTitle = 'Přednášky';

		$talks = $this->context->createTalks()->order('date DESC');
		$this->template->talks = $talks;
	}

}
