<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training;

use DateTime;
use DateTimeZone;
use Nette\Database\Explorer;
use Nette\Database\Row;
use Nette\Http\FileUpload;
use Nette\Utils\FileSystem;
use RuntimeException;
use SplFileInfo;

class Files
{

	protected Explorer $database;

	protected Statuses $trainingStatuses;

	/**
	 * Files directory, does not end with a slash.
	 */
	protected string $filesDir;


	public function __construct(Explorer $context, Statuses $trainingStatuses)
	{
		$this->database = $context;
		$this->trainingStatuses = $trainingStatuses;
	}


	public function setFilesDir(string $dir): void
	{
		$path = realpath($dir);
		if (!$path) {
			throw new RuntimeException("Can't get absolute path, maybe {$dir} doesn't exist?");
		}
		$this->filesDir = $path;
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
				d.start
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
			$this->trainingStatuses->getAllowFilesStatuses()
		);

		foreach ($files as $file) {
			$file->info = new SplFileInfo($this->getDir($file->start) . $file->fileName);
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
				d.start
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
				AND s.status IN (?)',
			$applicationId,
			$token,
			$filename,
			$this->trainingStatuses->getAllowFilesStatuses()
		);

		if ($file) {
			$file->info = new SplFileInfo($this->getDir($file->start) . $file->fileName);
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
		$file->move($this->getDir($training->start) . $name);

		$datetime = new DateTime();
		$this->database->beginTransaction();

		/** @var DateTimeZone|false $timeZone */
		$timeZone = $datetime->getTimezone();
		$this->database->query(
			'INSERT INTO files',
			array(
				'filename'       => $name,
				'added'          => $datetime,
				'added_timezone' => ($timeZone ? $timeZone->getName() : date_default_timezone_get()),
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


	private function getDir(DateTime $date): string
	{
		return $this->filesDir . '/' . $date->format('Y-m-d') . '/';
	}


	/**
	 * @param array<integer, integer> $dateIds
	 */
	public function deleteFiles(array $dateIds): void
	{
		$this->database->query(
			'DELETE FROM files WHERE id_file IN (
				SELECT
					m.key_file
				FROM
					training_materials m
					JOIN training_applications a ON a.id_application = m.key_application
				WHERE a.key_date IN (?)
			)',
			$dateIds
		);

		foreach ($this->database->fetchPairs('SELECT start FROM training_dates WHERE id_date IN (?)', $dateIds) as $date) {
			FileSystem::delete($this->getDir($date));
		}
	}

}
