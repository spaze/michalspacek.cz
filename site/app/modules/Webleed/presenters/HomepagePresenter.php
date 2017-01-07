<?php
declare(strict_types = 1);

namespace App\WebleedModule\Presenters;

/**
 * Homepage presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class HomepagePresenter extends \App\WwwModule\Presenters\BasePresenter
{

	private const HEARTBLEED_DISCLOSURE = '2014-04-07';

	/** @var \Nette\Database\Context */
	protected $database;


	/**
	 * @param \Nette\Database\Context $context
	 */
	public function __construct(\Nette\Database\Context $context)
	{
		$this->database = $context;
		parent::__construct();
	}


	public function actionDefault(): void
	{
		$points = array();
		$vulnerable = null;
		$disclosure = $latest = new \DateTime(self::HEARTBLEED_DISCLOSURE);
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
			$latest = $row->date;
		}
		$daysSince = $disclosure->diff($latest);
		$this->template->points = $points;
		$this->template->vulnerable = $vulnerable;
		$this->template->daysSince = $daysSince->format('%a');
		$this->template->smallPrint = $this->getSmallPrint();
	}


	/**
	 * @return \Nette\Utils\Html
	 */
	private function getSmallPrint(): \Nette\Utils\Html
	{
		$smallPrint = array(
			// 'Knocking on yer servar\'s ports since 2014.',
			// 'Do you even scan?',
			// 'Wow. So heart. Much bleed.',
			htmlspecialchars('<script>alert(\'XSS\');</script>'),
			'<a href="https://www.youtube.com/watch?v=DLzxrzFCyOs">admin</a>',
		);
		return \Nette\Utils\Html::el()->setHtml($smallPrint[array_rand($smallPrint)]);
	}

}
