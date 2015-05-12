<?php
namespace AdminModule;

use Nette\Application\UI\Form;

/**
 * Service presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class ServicePresenter extends BasePresenter
{

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var \MichalSpacekCz\Encryption\Password */
	protected $passwordEncryption;

	/** @var \MichalSpacekCz\Encryption\Email */
	protected $emailEncryption;


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \MichalSpacekCz\Encryption\Password $passwordEncryption
	 * @param \MichalSpacekCz\Encryption\Email $emailEncryption
	 */
	public function __construct(
		\Nette\Localization\ITranslator $translator,
		\Nette\Database\Context $context,
		\MichalSpacekCz\Encryption\Password $passwordEncryption,
		\MichalSpacekCz\Encryption\Email $emailEncryption
	)
	{
		$this->database = $context;
		$this->passwordEncryption = $passwordEncryption;
		$this->emailEncryption = $emailEncryption;
		parent::__construct($translator);
	}


	public function actionReencryptPasswords()
	{
		$i = 0;
		foreach ($this->database->fetchAll('SELECT * FROM users WHERE password LIKE \'$aes-256-cbc$%\'') as $row) {
			$hash = $this->passwordEncryption->decrypt($row->password);
			$encrypted = $this->passwordEncryption->encrypt($hash);
			$this->database->query('UPDATE users SET', ['password' => $encrypted], 'WHERE id_user = ?', $row->id_user);
			$i++;
		}
		$this->template->i = $i;
	}


	public function actionReencryptEmails()
	{
		$i = 0;
		foreach ($this->database->fetchAll('SELECT * FROM training_applications WHERE email LIKE \'$aes-256-cbc$%\'') as $row) {
			$email = $this->emailEncryption->decrypt($row->email);
			$encrypted = $this->emailEncryption->encrypt($email);
			$this->database->query('UPDATE training_applications SET', ['email' => $encrypted], 'WHERE id_application = ?', $row->id_application);
			$i++;
		}
		$this->template->i = $i;
	}

}
