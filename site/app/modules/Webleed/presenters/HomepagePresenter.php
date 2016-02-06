<?php
namespace App\WebleedModule\Presenters;

/**
 * Homepage presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class HomepagePresenter extends \App\Presenters\BasePresenter
{

	const HEARTBLEED_DISCLOSURE = '2014-04-07';

	/** @var \Nette\Database\Context */
	protected $database;


	public function __construct(\Nette\Database\Context $context)
	{
		$this->database = $context;
	}


	public function actionDefault()
	{
		$points = array();
		$vulnerable = null;
		$result = $this->database->fetchAll('SELECT date, port, vulnerable, total FROM webleed ORDER BY date, port');
		foreach ($result as $row) {
			$points[$row->port ?: 'any'][] = \Nette\Utils\Json::encode(array(
				(int)$row->date->format('Y'),
				(int)$row->date->format('n') - 1,  // Dear JS...
				(int)$row->date->format('j'),
				$row->vulnerable,
				round($row->vulnerable / $row->total * 100, 2),
			));
			if ($row->port === null) {
				$vulnerable = $row->vulnerable;
			}
		}
		$disclosure = new \DateTime(self::HEARTBLEED_DISCLOSURE);
		$daysSince = $disclosure->diff($row->date);
		$this->template->points = $points;
		$this->template->vulnerable = $vulnerable;
		$this->template->daysSince = $daysSince->format('%a');
		$this->template->smallPrint = $this->getSmallPrint();
	}


	private function getSmallPrint()
	{
		$smallPrint = array(
			'Knocking on yer servar\'s ports since 2014.',
			'Do you even scan?',
			'Wow. So heart. Much bleed.',
			'<script>alert(\'XSS\');</script>',
			\Nette\Utils\Html::el()->setHtml('<a href="https://www.youtube.com/watch?v=BROWqjuTM0g">admin</a>'),
		);
		return $smallPrint[array_rand($smallPrint)];
	}


}
