<?php

namespace Common\Doctrine\Type;

use Common\Doctrine\ValueObject\AbstractFile;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

abstract class AbstractFileType extends Type
{
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return 'VARCHAR(255)';
    }

    /**
     * @param string $fileName
     * @param AbstractPlatform $platform
     *
     * @return AbstractFile
     */
    public function convertToPHPValue($fileName, AbstractPlatform $platform): AbstractFile
    {
        return $this->createFromString($fileName);
    }

    /**
     * @param AbstractFile $file
     * @param AbstractPlatform $platform
     *
     * @return string
     */
    public function convertToDatabaseValue($file, AbstractPlatform $platform): string
    {
        return (string) $file;
    }

    abstract protected function createFromString(string $fileName): AbstractFile;
}
