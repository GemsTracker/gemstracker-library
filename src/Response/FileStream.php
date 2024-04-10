<?php

namespace Gems\Response;

use Laminas\Diactoros\Stream;

class FileStream extends Stream
{
    protected readonly \SplFileInfo $file;
    public function __construct(\SplFileInfo|string $file, string $mode = 'r', protected bool $deleteFileAfterSend = false)
    {
        if (!$file instanceof \SplFileInfo) {
            $file = new \SplFileInfo($file);
        }
        $this->file = $file;
        $stream = fopen($file, $mode);

        parent::__construct($stream, $mode);
    }

    public function getContents(): string
    {
        $result = parent::getContents();
        if ($this->deleteFileAfterSend && is_file($this->file->getPathname())) {
            unlink ($this->file->getPathname());
        }
        return $result;
    }

    public function deleteFileAfterSend(bool $shouldDelete = true): static
    {
        $this->deleteFileAfterSend = $shouldDelete;
        return $this;
    }


}