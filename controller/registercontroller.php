<?php
/**
 * ownCloud - registration
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Pellaeon Lin <pellaeon@hs.ntnu.edu.tw>
 * @author Julius Härtl <jus@bitgrid.net>
 * @copyright Pellaeon Lin 2014
 */

namespace OCA\Registration\Controller;

use OCA\Registration\Db\Registration;
use OCA\Registration\Service\MailService;
use OCA\Registration\Service\RegistrationException;
use OCA\Registration\Service\RegistrationService;
use \OCP\IRequest;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\AppFramework\Http\RedirectResponse;
use \OCP\AppFramework\Controller;
use OCP\IURLGenerator;
use \OCP\IL10N;

class RegisterController extends Controller {

	/** @var IL10N */
	private $l10n;
	/** @var IURLGenerator */
	private $urlgenerator;
	/** @var RegistrationService */
	private $registrationService;
	/** @var MailService */
	private $mailService;


	public function __construct(
		$appName,
		IRequest $request,
		IL10N $l10n,
		IURLGenerator $urlgenerator,
		RegistrationService $registrationService,
		MailService $mailService
	){
		parent::__construct($appName, $request);
		$this->l10n = $l10n;
		$this->urlgenerator = $urlgenerator;
		$this->registrationService = $registrationService;
		$this->mailService = $mailService;
	}

	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * @param $errormsg
	 * @param $entered
	 * @return TemplateResponse
	 */
	public function askEmail($errormsg, $entered) {
		$params = array(
			'errormsg' => $errormsg ? $errormsg : $this->request->getParam('errormsg'),
			'entered' => $entered ? $entered : $this->request->getParam('entered'),
        	'secret_required' => $this->registrationService->validateSecret('') ? false : true,
        	'home' => '<a class="button primary login-back icon-back-white" href="' . \OC::$server->getURLGenerator()->getBaseUrl() . '">' . $this->l10n->t('goto login') . '</a>' //getThemingDefaults()->getBaseUrl()
		);
		return new TemplateResponse('registration', 'register', $params, 'guest');
	}

	/**
	 * @PublicPage
	 *
	 * @return TemplateResponse
	 */
	public function validateEmail() {
		$email = $this->request->getParam('email');
		$reg_secret = $this->request->getParam('reg_secret');
		$secret_required = $this->registrationService->validateSecret('') ? false : true;
		$home_html = '<a class="button primary login-back icon-back-white" href="' . \OC::$server->getURLGenerator()->getBaseUrl() . '">' . $this->l10n->t('goto login') . '</a>';

		if($secret_required && !$this->registrationService->validateSecret($reg_secret)) 
			return new TemplateResponse('registration', 'register', array(
    			'errormsg' => $this->l10n->t('The secret passphrase was wrong.'),
    			'entered' => $email,
    			'secret_required' => true,
    			'home' => $home_html
				), 'guest');

		if (!$this->registrationService->checkAllowedDomains($email)) {
			return new TemplateResponse('registration', 'domains', [
				'domains' => $this->registrationService->getAllowedDomains(),
            	'home' => '<a class="button primary" href="' . \OC::$server->getURLGenerator()->linkToRoute('registration.register.askEmail') . '">' . $this->l10n->t('try again') . '</a>'
			], 'guest');
		}
		try {
			$this->registrationService->validateEmail($email);
			$registration = $this->registrationService->createRegistration($email);
			$this->mailService->sendTokenByMail($registration);
		} catch (RegistrationException $e) {
			return $this->renderError($e->getMessage(), $e->getHint());
		}


		return new TemplateResponse('registration', 'message', array('msg' =>
			$this->l10n->t('Verification email successfully sent.'),
            'home' => '<a class="button primary" href="' . \OC::$server->getURLGenerator()->getBaseUrl() . '">' . $this->l10n->t('goto login') . '</a>'
		), 'guest');
	}

	/**
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * @param $token
	 * @return TemplateResponse
	 */
	public function verifyToken($token) {
		try {
			/** @var Registration $registration */
			$registration = $this->registrationService->verifyToken($token);
			$this->registrationService->confirmEmail($registration);

			// create account without form if username/password are already stored
			if ($registration->getUsername() !== "" && $registration->getPassword() !== "") {
				$this->registrationService->createAccount($registration);
				return new TemplateResponse('registration', 'message',
					['msg' => $this->l10n->t('Your account has been successfully created, you can <a href="%s">log in now</a>.', [$this->urlgenerator->getAbsoluteURL('/')])],
					'guest'
				);
			}

			return new TemplateResponse('registration', 'form', ['email' => $registration->getEmail(), 'token' => $registration->getToken()], 'guest');
		} catch (RegistrationException $exception) {
			return $this->renderError($exception->getMessage(), $exception->getHint());
		}

	}

	/**
	 * @PublicPage
	 * @UseSession
	 *
	 * @param $token
	 * @return RedirectResponse|TemplateResponse
	 */
	public function createAccount($token) {
		$username = $this->request->getParam('username');
		$password = $this->request->getParam('password');
		$registration = $this->registrationService->getRegistrationForToken($token);

		try {
			$user = $this->registrationService->createAccount($registration, $username, $password);
		} catch (RegistrationException $exception) {
			return $this->renderError($exception->getMessage(), $exception->getHint());
		} catch (\InvalidArgumentException $exception) {
			// Render form with previously sent values
			return new TemplateResponse('registration', 'form',
				[
					'email' => $registration->getEmail(),
					'entered_data' => array('user' => $username),
					'errormsgs' => array($exception->getMessage()),
					'token' => $token
				], 'guest');
		}

		return $this->registrationService->loginUser($user->getUID(), $username, $password, false);
	}

	private function renderError($error, $hint="") {
		return new TemplateResponse('', 'error', array(
			'errors' => array(array(
				'error' => $error,
				'hint' => $hint
			))
		), 'error');
	}

}
