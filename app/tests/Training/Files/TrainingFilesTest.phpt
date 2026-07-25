<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Files;

use DateTimeImmutable;
use MichalSpacekCz\DateTime\DateTimeFormat;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\DateTime\DateTimeMachineFactory;
use MichalSpacekCz\Test\TestCaseRunner;
use MichalSpacekCz\Test\Training\TrainingFilesNullStorage;
use MichalSpacekCz\Training\Exceptions\TrainingFileUnsupportedExtensionException;
use Nette\Http\FileUpload;
use Nette\Utils\FileSystem;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class TrainingFilesTest extends TestCase
{

	public function __construct(
		private readonly TrainingFiles $trainingFiles,
		private readonly TrainingFilesNullStorage $storage,
		private readonly Database $database,
		private readonly DateTimeMachineFactory $dateTimeFactory,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
		$this->dateTimeFactory->setDateTime(null);
	}


	public function testIsAllowedExtension(): void
	{
		Assert::true($this->trainingFiles->isAllowedExtension('pdf'));
		Assert::true($this->trainingFiles->isAllowedExtension('PDF'));
		Assert::true($this->trainingFiles->isAllowedExtension('png'));
		Assert::true($this->trainingFiles->isAllowedExtension('jpeg'));
		Assert::false($this->trainingFiles->isAllowedExtension('jpg')); // nette/forms saves an uploaded JPEG as .jpeg, not .jpg
		Assert::false($this->trainingFiles->isAllowedExtension('php'));
		Assert::false($this->trainingFiles->isAllowedExtension('html'));
	}


	public function testAddFileRejectsDisallowedExtensionReportingDetectedType(): void
	{
		$content = "This is plain text, not an executable.\n";
		$tmpName = $this->uniqueTempPath();
		FileSystem::write($tmpName, $content);
		$file = new FileUpload([
			'name' => 'malware.exe',
			'tmp_name' => $tmpName,
			'size' => strlen($content),
			'error' => UPLOAD_ERR_OK,
		]);
		try {
			$e = Assert::exception(function () use ($file): void {
				$this->trainingFiles->addFile(new DateTimeImmutable(), $file, [1]);
			}, TrainingFileUnsupportedExtensionException::class);
			assert($e instanceof TrainingFileUnsupportedExtensionException);
			// The type is detected from the file content (text/plain), not derived from the .exe name
			Assert::same(
				"Unsupported training file extension of 'malware.exe' (text/plain), allowed extensions: 7z, docx, gif, gz, jpeg, odp, ods, odt, pdf, png, pptx, tar, tgz, txt, webp, xlsx, zip",
				$e->getMessage(),
			);
		} finally {
			FileSystem::delete($tmpName);
		}
	}


	public function testAddFileStoresAllowedFileAndRecordsIt(): void
	{
		$now = new DateTimeImmutable('2020-10-20 12:34:56');
		$this->dateTimeFactory->setDateTime($now);
		$filesDir = $this->uniqueTempPath() . '/';
		$this->storage->setFilesDir($filesDir);
		$this->database->setDefaultInsertId('1337');

		$content = "Course notes, plain text.\n";
		$tmpName = $this->uniqueTempPath();
		FileSystem::write($tmpName, $content);
		$file = new FileUpload([
			'name' => 'notes.txt',
			'tmp_name' => $tmpName,
			'size' => strlen($content),
			'error' => UPLOAD_ERR_OK,
		]);
		try {
			$name = $this->trainingFiles->addFile(new DateTimeImmutable(), $file, [42]);
			Assert::same('notes.txt', $name);
			Assert::same($content, FileSystem::read($filesDir . 'notes.txt'));

			Assert::same(
				[[
					'filename' => 'notes.txt',
					'added' => $now->format(DateTimeFormat::MYSQL),
					'added_timezone' => $now->getTimezone()->getName(),
				]],
				$this->database->getParamsArrayForQuery('INSERT INTO files'),
			);
			Assert::same(
				[['key_file' => '1337', 'key_application' => 42]],
				$this->database->getParamsArrayForQuery('INSERT INTO training_materials'),
			);
		} finally {
			FileSystem::delete($filesDir);
			FileSystem::delete($tmpName);
		}
	}


	private function uniqueTempPath(): string
	{
		return sys_get_temp_dir() . '/training-file-upload-test-' . bin2hex(random_bytes(8));
	}

}

TestCaseRunner::run(TrainingFilesTest::class);
