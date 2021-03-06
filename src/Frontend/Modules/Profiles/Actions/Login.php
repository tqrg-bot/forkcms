<?php

namespace Frontend\Modules\Profiles\Actions;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Frontend\Core\Engine\Base\Block as FrontendBaseBlock;
use Frontend\Core\Engine\Form as FrontendForm;
use Frontend\Core\Language\Language as FL;
use Frontend\Core\Engine\Navigation as FrontendNavigation;
use Frontend\Modules\Profiles\Engine\Authentication as FrontendProfilesAuthentication;
use Frontend\Modules\Profiles\Engine\Model as FrontendProfilesModel;

/**
 * This is the login-action.
 */
class Login extends FrontendBaseBlock
{
    /**
     * FrontendForm instance.
     *
     * @var FrontendForm
     */
    private $frm;

    public function execute(): void
    {
        parent::execute();

        // profile not logged in
        if (!FrontendProfilesAuthentication::isLoggedIn()) {
            $this->loadTemplate();
            $this->buildForm();
            $this->validateForm();
            $this->parse();
        } else {
            // profile already logged in
            // query string
            $queryString = urldecode(\SpoonFilter::getGetValue('queryString', null, SITE_URL));

            // redirect
            $this->redirect($queryString);
        }
    }

    private function buildForm(): void
    {
        $this->frm = new FrontendForm('login', null, null, 'loginForm');
        $this->frm->addText('email')->setAttributes(['required' => null, 'type' => 'email']);
        $this->frm->addPassword('password')->setAttributes(['required' => null]);
        $this->frm->addCheckbox('remember', true);
    }

    private function parse(): void
    {
        $this->frm->parse($this->tpl);
    }

    private function validateForm(): void
    {
        // is the form submitted
        if ($this->frm->isSubmitted()) {
            // get fields
            $txtEmail = $this->frm->getField('email');
            $txtPassword = $this->frm->getField('password');
            $chkRemember = $this->frm->getField('remember');

            // required fields
            $txtEmail->isFilled(FL::getError('EmailIsRequired'));
            $txtPassword->isFilled(FL::getError('PasswordIsRequired'));

            // both fields filled in
            if ($txtEmail->isFilled() && $txtPassword->isFilled()) {
                // valid email?
                if ($txtEmail->isEmail(FL::getError('EmailIsInvalid'))) {
                    // get the status for the given login
                    $loginStatus = FrontendProfilesAuthentication::getLoginStatus(
                        $txtEmail->getValue(),
                        $txtPassword->getValue()
                    );

                    // valid login?
                    if ($loginStatus !== FrontendProfilesAuthentication::LOGIN_ACTIVE) {
                        // get the error string to use
                        $errorString = sprintf(
                            FL::getError('Profiles' . \SpoonFilter::toCamelCase($loginStatus) . 'Login'),
                            FrontendNavigation::getURLForBlock('Profiles', 'ResendActivation')
                        );

                        // add the error to stack
                        $this->frm->addError($errorString);

                        // add the error to the template variables
                        $this->tpl->assign('loginError', $errorString);
                    }
                }
            }

            // valid login
            if ($this->frm->isCorrect()) {
                // get profile id
                $profileId = FrontendProfilesModel::getIdByEmail($txtEmail->getValue());

                // login
                FrontendProfilesAuthentication::login($profileId, $chkRemember->getChecked());

                // update salt and password for Dieter's security features
                FrontendProfilesAuthentication::updatePassword($profileId, $txtPassword->getValue());

                // query string
                $queryString = urldecode(\SpoonFilter::getGetValue('queryString', null, SITE_URL));

                // redirect
                $this->redirect($queryString);
            }
        }
    }
}
