<?php
/**
 * Články presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class ClankyPresenter extends BasePresenter
{

	public function renderDefault()
	{
		$this->template->pageTitle = 'Články';

		$database = $this->getContext()->nette->database->default;
		$articles = $database->table('articles')->order('date DESC');
		$this->template->articles = $articles;
	}

}
