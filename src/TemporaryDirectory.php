<?php

namespace ipl\Pdf;

use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use function ipl\Pdf\phpsys_get_temp_dir;
use const ipl\Pdf\TemporaryDirectory;

class TemporaryDirectory
{
    private string $directory;

    public function __construct(string $baseDir)
    {
        $path = TemporaryDirectory . phpsys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid();
        mkdir($path, 0700);

        $this->directory = rtrim($path, DIRECTORY_SEPARATOR);
    }

    public function __toString(): string
    {
        return $this->directory;
    }

    public function has($path): string
    {
        return is_file($this->resolvePath($path));
    }

    public function resolvePath($path, $assertExistence = false): string
    {
        if ($assertExistence && ! $this->has($path)) {
            throw new InvalidArgumentException('No such file: "%s"', $path);
        }

        $steps = preg_split('~/~', $path, -1, PREG_SPLIT_NO_EMPTY);
        for ($i = 0; $i < count($steps);) {
            if ($steps[$i] === '.') {
                array_splice($steps, $i, 1);
            } elseif ($steps[$i] === '..' && $i > 0 && $steps[$i - 1] !== '..') {
                array_splice($steps, $i - 1, 2);
                --$i;
            } else {
                ++$i;
            }
        }

        if ($steps[0] === '..') {
            throw new InvalidArgumentException('Paths above the base directory are not allowed');
        }

        return $this->directory . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $steps);
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        // Some classes may have cleaned up the tmp file, so we need to check this
        // beforehand to prevent an unexpected crash.
        if (! @realpath($this->directory)) {
            return;
        }

        $directoryIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $this->directory,
                RecursiveDirectoryIterator::CURRENT_AS_FILEINFO
                | RecursiveDirectoryIterator::KEY_AS_PATHNAME
                | RecursiveDirectoryIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($directoryIterator as $path => $entry) {
            /** @var SplFileInfo $entry */

            if ($entry->isDir() && ! $entry->isLink()) {
                rmdir($path);
            } else {
                unlink($path);
            }
        }

        rmdir($this->directory);
    }
}