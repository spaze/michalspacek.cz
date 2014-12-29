<?php
namespace MichalSpacekCz\Training;

/**
 * Training files/materials model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Files
{

	/** @var \Nette\Database\Context */
	protected $database;

	/**
	 * Files directory, ends with a slash.
	 *
	 * @var string
	 */
	protected $filesDir;


	public function __construct(\Nette\Database\Context $context)
	{
		$this->database = $context;
	}


	public function setFilesDir($dir)
	{
		if ($dir[strlen($dir) - 1] != '/') {
			$dir .= '/';
		}
		$this->filesDir = $dir;
	}


	public function getFiles($applicationId)
	{
		$files = $this->database->fetchAll(
			'SELECT
				f.id_file AS fileId,
				f.filename AS fileName,
				CAST(DATE(d.start) AS char) AS date
			FROM
				files f
				JOIN training_materials m ON f.id_file = m.key_file
				JOIN training_applications a ON m.key_application = a.id_application
				JOIN training_application_status s ON a.key_status = s.id_status
				JOIN training_dates d ON a.key_date = d.id_date
			WHERE
				a.id_application = ?
				AND s.status IN (?, ?, ?)',
			$applicationId,
			Statuses::STATUS_ATTENDED,
			Statuses::STATUS_MATERIALS_SENT,
			Statuses::STATUS_ACCESS_TOKEN_USED
		);

		foreach ($files as $file) {
			$file->dirName = $this->filesDir . $file->date;
		}

		return $files;
	}


	public function getFile($applicationId, $token, $filename)
	{
		$file = $this->database->fetch(
			'SELECT
				f.id_file AS fileId,
				f.filename AS fileName,
				CAST(DATE(d.start) AS char) AS date
			FROM
				files f
				JOIN training_materials m ON f.id_file = m.key_file
				JOIN training_applications a ON m.key_application = a.id_application
				JOIN training_application_status s ON a.key_status = s.id_status
				JOIN training_dates d ON a.key_date = d.id_date
			WHERE
				a.id_application = ?
				AND a.access_token = ?
				AND f.filename = ?
				AND s.status IN (?, ?, ?)',
			$applicationId,
			$token,
			$filename,
			Statuses::STATUS_ATTENDED,
			Statuses::STATUS_MATERIALS_SENT,
			Statuses::STATUS_ACCESS_TOKEN_USED
		);

		if ($file) {
			$file->dirName = $this->filesDir . $file->date;
		}

		return $file;
	}


	public function addFile(\Nette\Database\Row $training, \Nette\Http\FileUpload $file, array $applicationIds)
	{
		$name = basename($file->getSanitizedName());
		$file->move($this->filesDir . $training->start->format('Y-m-d') . '/' . $name);

		$datetime = new \DateTime();
		$this->database->beginTransaction();

		$this->database->query(
			'INSERT INTO files',
			array(
				'filename'       => $name,
				'added'          => $datetime,
				'added_timezone' => $datetime->getTimezone()->getName(),
			)
		);
		$fileId = $this->database->getInsertId();
		foreach ($applicationIds as $applicationId) {
			$this->database->query(
				'INSERT INTO training_materials',
				array(
					'key_file'        => $fileId,
					'key_application' => $applicationId,
				)
			);
		}
		$this->database->commit();
		return $name;
	}


	public function logDownload($applicationId, $downloadId)
	{
		$this->database->query('INSERT INTO training_material_downloads', array(
			'key_application'   => $applicationId,
			'key_file_download' => $downloadId,
		));
	}

}
