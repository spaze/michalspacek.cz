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
		$this->template->talks     = $this->talks->getAll();
		$this->template->upcomingTalks = $this->talks->getUpcoming();
	}


	public function actionPrednaska($name, $slide = null)
	{
		$talk = $this->talks->get($name);
		if (!$talk) {
			throw new \Nette\Application\BadRequestException("I haven't talked about {$name}, yet", \Nette\Http\Response::S404_NOT_FOUND);
		}

		$this->template->pageTitle = "Přednáška {$talk->title} ({$talk->event})";
		$this->template->pageHeader = "Přednáška {$talk->title}";
		$this->template->description = $talk->description;
		$this->template->href = $talk->href;
		$this->template->date = $talk->date;
		$this->template->eventHref = $talk->eventHref;
		$this->template->event = $talk->event;

		if ($slide !== null) {
			$this->template->canonicalLink = $this->link("//$name");
		}

		$this->template->slidesHref = $talk->slidesHref;
		foreach ($this->embed->getSlidesTemplateVars($talk->slidesHref, $talk->slidesEmbed, $slide) as $key => $value) {
			$this->template->$key = $value;
		}

		$this->template->videoHref = $talk->videoHref;
		foreach ($this->embed->getVideoTemplateVars($talk->videoHref, $talk->videoEmbed) as $key => $value) {
			$this->template->$key = $value;
		}
	}

}
