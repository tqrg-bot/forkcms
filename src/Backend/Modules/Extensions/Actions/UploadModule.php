<?php

namespace Backend\Modules\Extensions\Actions;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Backend\Core\Engine\Base\ActionAdd as BackendBaseActionAdd;
use Backend\Core\Engine\Model as BackendModel;
use Backend\Core\Engine\Form as BackendForm;
use Backend\Core\Language\Language as BL;
use Backend\Modules\Extensions\Engine\Model as BackendExtensionsModel;
use Symfony\Component\Filesystem\Filesystem;

/**
 * This is the module upload-action.
 * It will install a module via a compressed zip file.
 */
class UploadModule extends BackendBaseActionAdd
{
    public function execute(): void
    {
        // call parent, this will probably add some general CSS/JS or other required files
        parent::execute();

        // zip extension is required for module upload
        if (!extension_loaded('zlib')) {
            $this->tpl->assign('zlibIsMissing', true);
        }

        if (!$this->isWritable()) {
            // we need write rights to upload files
            $this->tpl->assign('notWritable', true);
        } else {
            // everything allright, we can upload
            $this->buildForm();
            $this->validateForm();
            $this->parse();
        }

        // display the page
        $this->display();
    }

    /**
     * Process the zip-file & install the module
     *
     * @return string
     */
    private function uploadModuleFromZip(): string
    {
        // list of validated files (these files will actually be unpacked)
        $files = [];

        // shorten field variables
        /** @var $fileFile \SpoonFormFile */
        $fileFile = $this->frm->getField('file');

        // create \ziparchive instance
        $zip = new \ZipArchive();

        // try and open it
        if ($zip->open($fileFile->getTempFileName()) !== true) {
            $fileFile->addError(BL::getError('CorruptedFile'));
        }

        // zip file needs to contain some files
        if ($zip->numFiles == 0) {
            $fileFile->addError(BL::getError('FileIsEmpty'));

            return;
        }

        // directories we are allowed to upload to
        $allowedDirectories = [
            'src/Backend/Modules/',
            'src/Frontend/Modules/',
        ];

        // name of the module we are trying to upload
        $moduleName = null;

        // has the module zip one level of folders too much?
        $prefix = '';

        // check every file in the zip
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            // get the file name
            $file = $zip->statIndex($i);
            $fileName = $file['name'];

            if ($i === 0) {
                $prefix = $this->extractPrefix($file);
            }

            // check if the file is in one of the valid directories
            foreach ($allowedDirectories as $directory) {
                // yay, in a valid directory
                if (mb_stripos($fileName, $prefix . $directory) === 0) {
                    // extract the module name from the url
                    $tmpName = trim(str_ireplace($prefix . $directory, '', $fileName), '/');
                    if ($tmpName == '') {
                        break;
                    }
                    $chunks = explode('/', $tmpName);
                    $tmpName = $chunks[0];

                    // ignore hidden files
                    if (mb_substr(basename($fileName), 0, 1) == '.') {
                        break;
                    } elseif ($moduleName === null) {
                        // first module we find, store the name
                        $moduleName = $tmpName;
                    } elseif ($moduleName !== $tmpName) {
                        // the name does not match the previous module we found, skip the file
                        break;
                    }

                    // passed all our tests, store it for extraction
                    $files[] = $fileName;

                    // go to next file
                    break;
                }
            }
        }

        // after filtering no files left (nothing useful found)
        if (count($files) == 0) {
            $fileFile->addError(BL::getError('FileContentsIsUseless'));

            return;
        }

        // module already exists on the filesystem
        if (BackendExtensionsModel::existsModule($moduleName)) {
            $fileFile->addError(sprintf(BL::getError('ModuleAlreadyExists'), $moduleName));

            return;
        }

        // installer in array?
        if (!in_array($prefix . 'src/Backend/Modules/' . $moduleName . '/Installer/Installer.php', $files)) {
            $fileFile->addError(sprintf(BL::getError('NoInstallerFile'), $moduleName));

            return;
        }

        // unpack module files
        $zip->extractTo($this->getContainer()->getParameter('site.path_www'), $files);

        // place all the items in the prefixed folders in the right folders
        if (!empty($prefix)) {
            $filesystem = new Filesystem();
            foreach ($files as &$file) {
                $fullPath = $this->getContainer()->getParameter('site.path_www') . '/' . $file;
                $newPath = str_replace(
                    $this->getContainer()->getParameter('site.path_www') . '/' . $prefix,
                    $this->getContainer()->getParameter('site.path_www') . '/',
                    $fullPath
                );

                if ($filesystem->exists($fullPath) && is_dir($fullPath)) {
                    $filesystem->mkdir($newPath);
                } elseif ($filesystem->exists($fullPath) && is_file($fullPath)) {
                    $filesystem->copy(
                        $fullPath,
                        $newPath
                    );
                }
            }

            $filesystem->remove($this->getContainer()->getParameter('site.path_www') . '/' . $prefix);
        }

        // return the files
        return $moduleName;
    }

    /**
     * Try to extract a prefix if a module has been zipped with unexpected
     * paths.
     *
     * @param string $file
     *
     * @return string
     */
    private function extractPrefix(string $file): string
    {
        $name = explode(PATH_SEPARATOR, $file['name']);
        $prefix = [];

        foreach ($name as $element) {
            if ($element == 'src') {
                return implode(PATH_SEPARATOR, $prefix);
            }

            $prefix[] = $element;
        }

        // If the zip has a top-level single directory, eg
        // /myModuleName/, then we should just assume that is the prefix.
        return $file['name'];
    }

    /**
     * Do we have write rights to the modules folders?
     *
     * @return bool
     */
    private function isWritable(): bool
    {
        if (!BackendExtensionsModel::isWritable(FRONTEND_MODULES_PATH)) {
            return false;
        }

        return BackendExtensionsModel::isWritable(BACKEND_MODULES_PATH);
    }

    private function buildForm(): void
    {
        // create form
        $this->frm = new BackendForm('upload');

        // create and add elements
        $this->frm->addFile('file');
    }

    private function validateForm(): void
    {
        // the form is submitted
        if ($this->frm->isSubmitted()) {
            // shorten field variables
            $fileFile = $this->frm->getField('file');

            // validate the file
            if ($fileFile->isFilled(BL::err('FieldIsRequired')) && $fileFile->isAllowedExtension(['zip'], sprintf(BL::getError('ExtensionNotAllowed'), 'zip'))) {
                $moduleName = $this->uploadModuleFromZip();
            }

            // passed all validation
            if ($this->frm->isCorrect()) {
                // redirect to the install url, this is needed for doctrine modules because the container needs to
                // load this module as an allowed module to get the entities working
                $this->redirect(BackendModel::createURLForAction('InstallModule') . '&module=' . $moduleName);
            }
        }
    }
}
