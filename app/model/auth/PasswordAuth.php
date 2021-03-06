<?php

namespace Authenticator;

use Nette\Security as NS;


/**
 * Users authenticator.
 */
class Password extends \Nette\Object implements NS\IAuthenticator
{

	/** @var Users */
	private $users;



	public function __construct(\Selector\Users $users)
	{
		$this->users = $users;
	}



	/**
	 * Performs an authentication
	 * @param  array
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($mail, $password) = $credentials;
		$user = $this->users->findOneBy(['mail' => $mail]);

		if (!$user) {
			throw new NS\AuthenticationException("Uživatel „{$mail}“ neexistuje.", self::IDENTITY_NOT_FOUND);
		}

		if (!$user->password) {
			$services = [];
			if ($user->facebook_id) {
				$services[] = 'Facebook';
			}
			if ($user->google_id) {
				$services[] = 'Google';
			}

			throw new NS\AuthenticationException("Nemáte nastavené heslo. Můžete se přihlásit přes " . implode(' nebo ', $services) . ".", self::INVALID_CREDENTIAL);
		}

		$hash = (new \Model\Password())->calculateHash($password, $user->salt);
		if ($user->password !== $hash) {
			throw new NS\AuthenticationException("Nesprávné heslo.", self::INVALID_CREDENTIAL);
		}

		return new NS\Identity($user->id, explode(';', $user->role), []);
	}

}
