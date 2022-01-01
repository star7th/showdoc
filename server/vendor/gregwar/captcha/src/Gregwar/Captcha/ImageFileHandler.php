<?php

namespace Gregwar\Captcha;

use Symfony\Component\Finder\Finder;

/**
 * Handles actions related to captcha image files including saving and garbage collection
 *
 * @author Gregwar <g.passault@gmail.com>
 * @author Jeremy Livingston <jeremy@quizzle.com>
 */
class ImageFileHandler
{
    /**
     * Name of folder for captcha images
     * @var string
     */
    protected $imageFolder;

    /**
     * Absolute path to public web folder
     * @var string
     */
    protected $webPath;

    /**
     * Frequency of garbage collection in fractions of 1
     * @var int
     */
    protected $gcFreq;

    /**
     * Maximum age of images in minutes
     * @var int
     */
    protected $expiration;

    /**
     * @param $imageFolder
     * @param $webPath
     * @param $gcFreq
     * @param $expiration
     */
    public function __construct($imageFolder, $webPath, $gcFreq, $expiration)
    {
        $this->imageFolder      = $imageFolder;
        $this->webPath          = $webPath;
        $this->gcFreq           = $gcFreq;
        $this->expiration       = $expiration;
    }

    /**
     * Saves the provided image content as a file
     *
     * @param string $contents
     *
     * @return string
     */
    public function saveAsFile($contents)
    {
        $this->createFolderIfMissing();

        $filename = md5(uniqid()) . '.jpg';
        $filePath = $this->webPath . '/' . $this->imageFolder . '/' . $filename;
        imagejpeg($contents, $filePath, 15);

        return '/' . $this->imageFolder . '/' . $filename;
    }

    /**
     * Randomly runs garbage collection on the image directory
     *
     * @return bool
     */
    public function collectGarbage()
    {
        if (!mt_rand(1, $this->gcFreq) == 1) {
            return false;
        }

        $this->createFolderIfMissing();

        $finder = new Finder();
        $criteria = sprintf('<= now - %s minutes', $this->expiration);
        $finder->in($this->webPath . '/' . $this->imageFolder)
            ->date($criteria);

        foreach ($finder->files() as $file) {
            unlink($file->getPathname());
        }

        return true;
    }

    /**
     * Creates the folder if it doesn't exist
     */
    protected function createFolderIfMissing()
    {
        if (!file_exists($this->webPath . '/' . $this->imageFolder)) {
            mkdir($this->webPath . '/' . $this->imageFolder, 0755);
        }
    }
}
