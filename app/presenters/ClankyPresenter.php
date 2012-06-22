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

		$articles = $this->context->createArticles()->order('date DESC');
		$this->template->articles = $articles;
	}

}
