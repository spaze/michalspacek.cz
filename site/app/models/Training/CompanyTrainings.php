<?php
namespace MichalSpacekCz\Training;

/**
 * Company trainings model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class CompanyTrainings
{

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var \Netxten\Formatter\Texy */
	protected $texyFormatter;


	/**
	 * @param \Nette\Database\Context $context
	 * @param \Netxten\Formatter\Texy $texyFormatter
	 */
	public function __construct(\Nette\Database\Context $context, \Netxten\Formatter\Texy $texyFormatter)
	{
		$this->database = $context;
		$this->texyFormatter = $texyFormatter;
	}


	/**
	 * Get training info.
	 *
	 * @param string $name
	 * @param boolean $includeCustom
	 * @return \Nette\Database\Row
	 */
	public function getInfo($name)
	{
		$result = $this->database->fetch(
			'SELECT
				t.action,
				t.name,
				ct.description,
				t.content,
				ct.upsell,
				t.prerequisites,
				t.audience,
				t.original_href AS originalHref,
				t.capacity,
				ct.price,
				t.student_discount AS studentDiscount,
				t.materials,
				t.custom,
				ct.duration,
				ct.double_duration AS doubleDuration,
				ct.double_duration_price AS doubleDurationPrice
			FROM trainings t
				JOIN company_trainings ct ON t.id_training = ct.key_training
			WHERE
				t.action = ?',
			$name
		);

		if ($result) {
			$result->description   = $this->texyFormatter->format($result->description);
			$result->content       = $this->texyFormatter->format($result->content);
			$result->upsell        = $this->texyFormatter->format($result->upsell);
			$result->prerequisites = $this->texyFormatter->format($result->prerequisites);
			$result->audience      = $this->texyFormatter->format($result->audience);
			$result->materials     = $this->texyFormatter->format($result->materials);
		}

		return $result;
	}


}
