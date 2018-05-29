<?php

namespace Fa\Bundle\LexikTranslationBundle\Manager;

use Lexik\Bundle\TranslationBundle\Storage\PropelStorage;
use Lexik\Bundle\TranslationBundle\Manager\TransUnitManager as BaseTransUnitManager;
use Lexik\Bundle\TranslationBundle\Manager\TransUnitInterface;

/**
 * Class to manage TransUnit entities or documents.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class FaTransUnitManager extends BaseTransUnitManager
{
    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var FileManagerInterface
     */
    protected $fileManager;

    /**
     * @var String
     */
    protected $kernelRootDir;

    /**
     * {@inheritdoc}
     */
    public function updateTranslationsContent(TransUnitInterface $transUnit, array $translations, $flush = false)
    {
        foreach ($translations as $locale => $content) {
            //if (!empty($content)) // commented to save empty value for translation FFR-2174
            {
                if ($transUnit->hasTranslation($locale)) {
                    $this->updateTranslation($transUnit, $locale, $content);

                    if ($this->storage instanceof PropelStorage) {
                        $this->storage->persist($transUnit);
                    }
                } else {
                    //We need to get a proper file for this translation
                    $file = $this->getTranslationFile($transUnit, $locale);
                    $this->addTranslation($transUnit, $locale, $content, $file);
                }
            }
        }

        if ($flush) {
            $this->storage->flush();
        }
    }
}
