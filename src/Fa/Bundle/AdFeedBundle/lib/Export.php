<?php
namespace Fa\Bundle\AdFeedBundle\lib;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;

class Export
{
    private $dir;


    public function __construct($container)
    {
        $this->container = $container;
        $this->em = $this->container->get('doctrine')->getManager();
        $this->dir  = $this->container->getParameter('fa.feed.data.dir');
        $this->file = new Filesystem();
    }

    protected function rootDir()
    {
        return $this->dir;
    }

    protected function writeData($string, $fileName, $fmode = 0, $dir = null)
    {
        if ($dir) {
            $this->dumpFile($this->rootDir().'/export/'.$dir.'/'.$fileName, $string, 0666, $fmode);
        } else {
            $this->dumpFile($this->rootDir().'/export/'.$fileName, $string, 0666, $fmode);
        }

    }

     /**
     * Atomically dumps content into a file.
     *
     * @param  string       $filename The file to be written to.
     * @param  string       $content  The data to write into the file.
     * @param  null|int     $mode     The file mode (octal). If null, file permissions are not modified
     * @param  null|int     $fmode     The file mode (octal). If null, file permissions are not modified
     *                                Deprecated since version 2.3.12, to be removed in 3.0.
     * @throws IOException            If the file cannot be written to.
     */
    public function dumpFile($filename, $content, $mode = 0666, $fmode = 0)
    {
        $dir = dirname($filename);

        if (!is_dir($dir)) {
             $this->file->mkdir($dir);
        } elseif (!is_writable($dir)) {
            throw new IOException(sprintf('Unable to write to the "%s" directory.', $dir), 0, null, $dir);
        }

        if (false === @file_put_contents($filename, $content, $fmode)) {
            throw new IOException(sprintf('Failed to write file "%s".', $filename), 0, null, $filename);
        }

        if (null !== $mode) {
             $this->file->chmod($filename, $mode);
        }
    }

    protected function removeFile($fileName, $dir = null)
    {
        if ($dir) {
            $this->file->remove($this->rootDir().'/export/'.$dir.'/'.$fileName);
        } else {
            $this->file->remove($this->rootDir().'/export/'.$fileName);
        }
    }
}
