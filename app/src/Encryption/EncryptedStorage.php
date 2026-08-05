<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Encryption;

/**
 * Implement this when the class stores encrypted data in one or more table columns, so the data can be re-encrypted
 * after the encryption key has been rotated or the stored format has changed.
 */
interface EncryptedStorage
{

	/**
	 * What the storage keeps encrypted, e.g. 'training application emails', for the re-encryption report.
	 *
	 * @return non-empty-string
	 */
	public function getEncryptedDataLabel(): string;


	/**
	 * The columns this storage keeps encrypted. It only declares them, the re-encryption itself is driven from
	 * one place, so nothing here can decide on its own whether a run writes or is a dry run.
	 *
	 * @return non-empty-list<EncryptedColumn> A storage with nothing encrypted has no business implementing this,
	 *     and an empty list would silently leave it out of the report entirely
	 */
	public function getEncryptedColumns(): array;

}
