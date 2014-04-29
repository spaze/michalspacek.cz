<?php
namespace WebleedModule;

/**
 * Homepage presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class HomepagePresenter extends \BasePresenter
{

	const HEARTBLEED_DISCLOSURE = '2014-04-07';

	/** @var \Nette\Database\Connection */
	protected $database;


	public function __construct(\Nette\DI\Container $context, \Nette\Database\Connection $connection)
	{
		parent::__construct($context);
		$this->database = $connection;
	}


	public function actionDefault()
	{
		$points = array();
		$result = $this->database->fetchAll('SELECT date, vulnerable, total FROM webleed ORDER BY date');
		foreach ($result as $row) {
			$time = strtotime($row->date);
			$points[] = \Nette\Utils\Json::encode(array(
				'y' => date('Y', $time),
				'm' => date('n', $time) - 1,  // Dear JS...
				'd' => date('j', $time),
				'v' => $row->vulnerable,
				'p' => round($row->vulnerable / $row->total * 100, 2),
			));
			$vulnerable = $row->vulnerable;
		}
		$disclosure = new \DateTime(self::HEARTBLEED_DISCLOSURE);
		$daysSince = $disclosure->diff(new \DateTime('now'));
		$this->template->points = $points;
		$this->template->vulnerable = $vulnerable;
		$this->template->daysSince = $daysSince->format('%a');
	}


}