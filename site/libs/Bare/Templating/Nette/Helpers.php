<?php
namespace Bare\Templating\Nette;

class Helpers extends \Nette\Object
{

	private $localDateSubstitution = array(
		'%B' => '%m',
	);

	private $localDateFormat = array(
		'cs' => array(
			'%m' => array(
				'01' => 'leden',
				'02' => 'únor',
				'03' => 'březen',
				'04' => 'duben',
				'05' => 'květen',
				'06' => 'červen',
				'07' => 'červenec',
				'08' => 'srpen',
				'09' => 'září',
				'10' => 'říjen',
				'11' => 'listopad',
				'12' => 'prosinec',
			),
		),
	);


	public function loader($helper)
	{
		if (method_exists($this, $helper)) {
			return callback($this, $helper);
		}
	}


	public function localDate($time, $language, $format = null)
	{
		$time = \Nette\DateTime::from($time);

		$replace = array();
		foreach ($this->localDateSubstitution as $key => $value) {
			$substituted   = strftime($value, $time->format('U'));
			$replace[$key] = str_replace('%', '%%', $this->localDateFormat[$language][$value][$substituted]);
		}

		return \Nette\Templating\Helpers::date($time, strtr($format, $replace));
	}


}