<?php

namespace TabletModule;

use Nette\Application\UI;
use Nette\Security as NS;
use Nette\Caching\Cache;


class SignPresenter extends BaseTabletPresenter
{

    public function renderIn()
	{
        $this->template->facebookAuth = $this->context->facebook->getLoginUrl([
            'scope' => ['email'],
            'redirect_uri' => $this->link("//fbAuth"),
        ]);

        $this->template->googleAuth = $this->context->google->getLoginUrl([
            'scope' => $this->context->params['google']['scope'],
            'redirect_uri' => $this->link('//googleAuth'),
        ]);
	}



    /**
	 * Sign in form component factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSignInForm()
	{
		$form = new UI\Form;
		$control = $form->addText('username')
			->setRequired('Vyplňte mail.')
			->getControlPrototype();
		$control->attrs['type'] = 'email';
		$control->attrs['autofocus'] = TRUE;

		$form->addPassword('password')
			->setRequired('Vyplňte heslo.');

		$form->addSubmit('send', 'Přihlásit');

		$form->onSuccess[] = $this->signInFormSubmitted;
		return $form;
	}



	public function signInFormSubmitted($form)
	{
		try {
			$values = $form->getValues();

			$this->user->setExpiration('+ 7 days', TRUE);
			$this->user->login($values->username, $values->password);

			$this->redirect(':Tablet:Homepage:');

		} catch (NS\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}



	public function actionOut()
	{
		$withFb = $this->user->isLoggedInWithFacebook();

		$this->user->logout();
		if ($withFb) {
			$url = $this->context->facebook->getLogoutUrl([
				/**
				 * Facebook does not redirect to it without the final slash
				 * @see http://stackoverflow.com/a/8363709/326257
				 */
				'next' => $this->presenter->link('//in') . '/',
			]);
			$this->redirectUrl($url);
		}

		$this->flashMessage('Byli jste odhlášeni.');
		$this->redirect('in');
	}



	public function actionFbAuth()
	{
		$info = $this->context->facebook->api('/me');
		if ($info) {
			$this->user->login($info);
		}

		$this->redirect(':Tablet:Homepage:');
	}



	public function actionGoogleAuth($code, $error = NULL)
	{
		if ($error) {
			$this->flashMessage('Povolte prosím Khanově škole přístup, bez toho se nebudete moct přes váš Google účet přihlásit.');
			$this->redirect('in');
		}

		$g = $this->context->google;
		$token = $g->getToken($code, $this->link('//googleAuth'));

		$this->user->googleLogin($g->getInfo($token));
		$this->redirect(':Tablet:Homepage:');
	}

}