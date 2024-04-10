<?php

namespace Gems\Response;

use Laminas\Diactoros\Response;
use Symfony\Component\Mime\MimeTypes;

class DownloadResponse extends Response
{
    protected bool $deleteFileAfterSend = false;
    public function __construct(\SplFileInfo|string $file, string|null $targetName, int $status = 200, array $headers = [], string $disposition = 'attachment')
    {
        if (!$file instanceof \SplFileInfo) {
            $file = new \SplFileInfo($file);
        }


        $headers = $this->addContentType($file, $headers);
        $headers = $this->addContentLength($file, $headers);
        if ($targetName) {
            $headers = $this->addDisposition($disposition, $targetName, $headers);
        }

        $fileStream = new FileStream($file, 'r', $this->deleteFileAfterSend);

        parent::__construct($fileStream, $status, $headers);
    }

    protected function addContentLength(\SplFileInfo $file, array $headers): array
    {
        $headers['Content-Length'] = $file->getSize();
        return $headers;
    }

    protected function addContentType(\SplFileInfo $file, array $headers): array
    {
        $headers['Content-Type'] = MimeTypes::getDefault()->guessMimeType($file->getPathname()) ?? 'application/octet-stream';
        return $headers;
    }

    protected function addDisposition(string $disposition, string $targetName, array $headers): array
    {
        if (in_array($disposition, ['attachment', 'inline'])) {
            $headers['Content-Disposition'] = $disposition . '; filename="' . $targetName . '"';
        }
        return $headers;
    }

    public function deleteFileAfterSend(bool $shouldDelete = true): static
    {
        if ($this->deleteFileAfterSend === $shouldDelete) {
            return $this;
        }
        $this->deleteFileAfterSend = $shouldDelete;
        /**
         * @var FileStream $stream
         */
        $stream = $this->getBody();
        $stream->deleteFileAfterSend($shouldDelete);

        return $this->withBody($stream);
    }
}