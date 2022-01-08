<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Files;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use MichalSpacekCz\Training\Statuses;
use Nette\Database\Explorer;
use Nette\Database\Row;
use Nette\Http\FileUpload;
use Nette\Utils\FileSystem;

class TrainingFiles
{

	public function __construct(
		private Explorer $database,
		private Statuses $trainingStatuses,
		private TrainingFileFactory $trainingFileFactory,
		private TrainingFilesStorage $trainingFilesStorage,
	) {
	}


	/**
	 * @param int $applicationId
	 * @return TrainingFilesCollection<int, TrainingFile>
	 */
	public function getFiles(int $applicationId): TrainingFilesCollection
	{
		$rows = $this->database->fetchAll(
			'SELECT
				f.added,
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
			$this->trainingStatuses->getAllowFilesStatuses(),
		);

		$files = new TrainingFilesCollection();
		foreach ($rows as $row) {
			$files->add($this->trainingFileFactory->fromDatabaseRow($row));
		}
		return $files;
	}


	public function getFile(int $applicationId, string $token, string $filename): ?TrainingFile
	{
		/** @var Row<mixed>|null $row */
		$row = $this->database->fetch(
			'SELECT
				f.added,
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
			$this->trainingStatuses->getAllowFilesStatuses(),
		);
		return $row ? $this->trainingFileFactory->fromDatabaseRow($row) : null;
	}


	/**
	 * @param DateTimeInterface $start
	 * @param FileUpload $file
	 * @param int[] $applicationIds
	 * @return string
	 */
	public function addFile(DateTimeInterface $start, FileUpload $file, array $applicationIds): string
	{
		$name = basename($file->getSanitizedName());
		$file->move($this->trainingFilesStorage->getFilesDir($start) . $name);

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
			),
		);
		$fileId = $this->database->getInsertId();
		foreach ($applicationIds as $applicationId) {
			$this->database->query(
				'INSERT INTO training_materials',
				array(
					'key_file'        => $fileId,
					'key_application' => $applicationId,
				),
			);
		}
		$this->database->commit();
		return $name;
	}


	/**
	 * @param array<int, int> $dateIds
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
			$dateIds,
		);

		foreach ($this->database->fetchPairs('SELECT start FROM training_dates WHERE id_date IN (?)', $dateIds) as $date) {
			FileSystem::delete($this->trainingFilesStorage->getFilesDir($date));
		}
	}

}
