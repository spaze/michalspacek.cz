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

		$database = $this->getContext()->nette->database->default;
		$talks = $database->table('talks')->order('date DESC');
		$this->template->talks = $talks;
	}

}
