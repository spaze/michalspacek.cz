<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use DateTime;
use Nette\Database\Context;
use \Nette\Database\Row;
use Nette\Http\FileUpload;
use SplFileInfo;

class Files
{

	/** @var Context */
	protected $database;

	/** @var Statuses */
	protected $trainingStatuses;

	/**
	 * Files directory, does not end with a slash.
	 *
	 * @var string
	 */
	protected $filesDir;


	public function __construct(Context $context, Statuses $trainingStatuses)
	{
		$this->database = $context;
		$this->trainingStatuses = $trainingStatuses;
	}


	public function setFilesDir(string $dir): void
	{
		$this->filesDir = rtrim($dir, '/');
	}


	/**
	 * @param integer $applicationId
	 * @return Row[]
	 */
	public function getFiles(int $applicationId): array
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
				AND s.status IN (?)',
			$applicationId,
			$this->trainingStatuses->getAttendedStatuses()
		);

		foreach ($files as $file) {
			$file->info = new SplFileInfo("{$this->filesDir}/{$file->date}/{$file->fileName}");
		}

		return $files;
	}


	/**
	 * @param integer $applicationId
	 * @param string $token
	 * @param string $filename
	 * @return Row<mixed>|null
	 */
	public function getFile(int $applicationId, string $token, string $filename): ?Row
	{
		/** @var Row<mixed>|null $file */
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
			$file->info = new SplFileInfo("{$this->filesDir}/{$file->date}/{$file->fileName}");
		}

		return $file;
	}


	/**
	 * @param Row<mixed> $training
	 * @param FileUpload $file
	 * @param integer[] $applicationIds
	 * @return string
	 */
	public function addFile(Row $training, FileUpload $file, array $applicationIds): string
	{
		$name = basename($file->getSanitizedName());
		$file->move($this->filesDir . '/' . $training->start->format('Y-m-d') . '/' . $name);

		$datetime = new DateTime();
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

}
