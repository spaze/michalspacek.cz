<?php
/**
 * Articles model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Articles extends BaseModel
{
	const TABLE_NAME = 'articles';

	protected $formattedProperties = array(
		'excerpt',
	);
}