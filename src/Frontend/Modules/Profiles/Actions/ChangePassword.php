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
use Frontend\Modules\Profiles\Engine\Profile;

/**
 * Change the password of the current logged in profile.
 */
class ChangePassword extends FrontendBaseBlock
{
    /**
     * FrontendForm instance.
     *
     * @var FrontendForm
     */
    private $frm;

    /**
     * The current profile.
     *
     * @var Profile
     */
    private $profile;

    public function execute(): void
    {
        // profile logged in
        if (FrontendProfilesAuthentication::isLoggedIn()) {
            parent::execute();
            $this->getData();
            $this->loadTemplate();
            $this->buildForm();
            $this->validateForm();
            $this->parse();
        } else {
            $this->redirect(
                FrontendNavigation::getURLForBlock(
                    'Profiles',
                    'Login'
                ) . '?queryString=' . FrontendNavigation::getURLForBlock('Profiles', 'ChangePassword'),
                307
            );
        }
    }

    private function getData(): void
    {
        // get profile
        $this->profile = FrontendProfilesAuthentication::getProfile();
    }

    private function buildForm(): void
    {
        $this->frm = new FrontendForm('updatePassword', null, null, 'updatePasswordForm');
        $this->frm->addPassword('old_password')->setAttributes(['required' => null]);
        $this->frm->addPassword('new_password')->setAttributes(
            [
                'required' => null,
                'data-role' => 'fork-new-password',
            ]
        );
        $this->frm->addPassword('verify_new_password')->setAttributes(
            [
                'required' => null,
                'data-role' => 'fork-new-password',
            ]
        );
        $this->frm->addCheckbox('show_password')->setAttributes(
            ['data-role' => 'fork-toggle-visible-password']
        );
    }

    private function parse(): void
    {
        // have the settings been saved?
        if ($this->URL->getParameter('sent') == 'true') {
            // show success message
            $this->tpl->assign('updatePasswordSuccess', true);
        }

        // parse the form
        $this->frm->parse($this->tpl);
    }

    private function validateForm(): void
    {
        // is the form submitted
        if ($this->frm->isSubmitted()) {
            // get fields
            $txtOldPassword = $this->frm->getField('old_password');
            $txtNewPassword = $this->frm->getField('new_password');

            // old password filled in?
            if ($txtOldPassword->isFilled(FL::getError('PasswordIsRequired'))) {
                // old password correct?
                if (FrontendProfilesAuthentication::getLoginStatus($this->profile->getEmail(), $txtOldPassword->getValue()) !== FrontendProfilesAuthentication::LOGIN_ACTIVE) {
                    // set error
                    $txtOldPassword->addError(FL::getError('InvalidPassword'));
                }

                // new password filled in?
                $txtNewPassword->isFilled(FL::getError('PasswordIsRequired'));

                // passwords match?
                if ($this->frm->getField('new_password')->getValue() !== $this->frm->getField('verify_new_password')->getValue()) {
                    $this->frm->getField('verify_new_password')->addError(FL::err('PasswordsDontMatch'));
                }
            }

            // no errors
            if ($this->frm->isCorrect()) {
                // update password
                FrontendProfilesAuthentication::updatePassword($this->profile->getId(), $txtNewPassword->getValue());

                // redirect
                $this->redirect(
                    SITE_URL . FrontendNavigation::getURLForBlock('Profiles', 'ChangePassword') . '?sent=true'
                );
            } else {
                $this->tpl->assign('updatePasswordHasFormError', true);
            }
        }
    }
}
