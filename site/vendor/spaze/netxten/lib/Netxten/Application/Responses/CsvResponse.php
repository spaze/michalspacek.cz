<?php
declare(strict_types = 1);

namespace Netxten\Application\Responses;

/**
 * CSV download response.
 *
 * @author Petr 'PePa' Pavel
 * @author Michal Špaček
 * Based on NCsvResponse by Petr 'PePa' Pavel http://addons.nette.org/cs/csvresponse
 *
 * @property-read array  $data
 * @property-read string $name
 * @property-read bool   $addHeading
 * @property-read string $glue
 * @property-read string $contentType
 */
class CsvResponse implements \Nette\Application\IResponse
{

	/** Never add quotes */
	const QUOTES_NEVER = 0;

	/** Always add quotes */
	const QUOTES_ALWAYS = 1;

	/** Add quotes only when needed */
	const QUOTES_SMART = 2;

	/** @var string[][] */
	private $data;

	/** @var string */
	private $name;

	/** @var bool */
	public $addHeading;

	/** @var string */
	public $glue;

	/** @var string */
	public $newLine;

	/** @var integer */
	public $addQuotes;

	/** @var string */
	private $initialData;

	/** @var string */
	private $charset;

	/** @var string */
	private $contentType;


	/**
	 * @param string[][] $data array of arrays - rows/columns
	 * @param string|null $name imposed file name
	 * @param boolean $addHeading return array keys as the first row (column headings)
	 * @param string $glue between columns (comma or a semi-colon)
	 * @param string $newLine string to use as new line
	 * @param integer $addQuotes whether to add quotes
	 * @param string $initialData data to start output with, think UTF-8 BOM
	 * @param string|null $charset output character set, if any
	 * @param string $contentType MIME content type
	 */
	public function __construct(
		array $data,
		?string $name = null,
		bool $addHeading = true,
		string $glue = ',',
		string $newLine = "\r\n",
		int $addQuotes = self::QUOTES_SMART,
		string $initialData = '',
		?string $charset = null,
		string $contentType = 'text/csv'
	) {
		$this->data = $data;
		$this->name = $name;
		$this->addHeading = $addHeading;
		$this->glue = $glue;
		$this->newLine = $newLine;
		$this->addQuotes = $addQuotes;
		$this->initialData = $initialData;
		$this->charset = $charset;
		$this->contentType = $contentType;
	}


	/**
	 * Returns the file name.
	 *
	 * @return string
	 */
	final public function getName(): string
	{
		return $this->name;
	}


	/**
	 * Returns the MIME content type of a downloaded content.
	 *
	 * @return string
	 */
	final public function getContentType(): string
	{
		return $this->contentType;
	}


	/**
	 * Sends response to output.
	 *
	 * @return void
	 */
	public function send(\Nette\Http\IRequest $httpRequest, \Nette\Http\IResponse $httpResponse): void
	{
		$httpResponse->setContentType($this->contentType, $this->charset);

		if (empty($this->name)) {
			$httpResponse->setHeader('Content-Disposition', 'attachment');
		} else {
			$httpResponse->setHeader('Content-Disposition', 'attachment; filename="' . $this->name . '"');
		}

		$data = $this->formatCsv();

		$httpResponse->setHeader('Content-Length', strlen($data));
		echo $data;
	}


	protected function formatCsv(): string
	{
		if (empty($this->data)) {
			return '';
		}

		$csv = $this->initialData;

		if (!is_array($this->data)) {
			$this->data = iterator_to_array($this->data);
		}

		if ($this->addHeading) {
			$firstRow = reset($this->data);
			if (!is_array($firstRow)) {
				$firstRow = iterator_to_array($firstRow);
			}

			$labels = array();
			foreach (array_keys($firstRow) as $key) {
				$labels[] = ucwords(str_replace('_', ' ', $key));
			}
			$csv .= $this->formatRow($labels);
		}

		foreach ($this->data as $row) {
			if (!is_array($row)) {
				$row = iterator_to_array($row);
			}
			$csv .= $this->formatRow($row);
		}

		return $csv;
	}


	/**
	 * @param string[] $row
	 * @return string
	 */
	protected function formatRow(array $row): string
	{
		foreach ($row as $key => &$value) {
			$value = preg_replace('/[\r\n]+/', ' ', $value);
			if ($this->addQuotes == self::QUOTES_ALWAYS) {
				$value = '"' . $value . '"';
			} elseif ($this->addQuotes == self::QUOTES_SMART) {
				if (strpos($value,	'"') !== false) {
					$value = str_replace('"', '""', $value);
					$value = '"' . $value . '"';
				}
			}
		}
		return implode($this->glue, $row) . $this->newLine;
	}

}
