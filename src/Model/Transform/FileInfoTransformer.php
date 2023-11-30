<?php

namespace Gems\Model\Transform;

use Symfony\Component\Finder\SplFileInfo;
use Zalt\Late\Late;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Transform\ModelTransformerAbstract;

class FileInfoTransformer extends ModelTransformerAbstract
{
    public function __construct(
        protected readonly string $startPath = '',
    )
    {}

    public function transformLoad(MetaModelInterface $model, array $data, $new = false, $isPostData = false): array
    {
        $newData = [];

        foreach($data as $key => $file) {
            if (!($file instanceof SplFileInfo)) {
                continue;
            }

            $relativePath = str_replace($this->startPath, '', $file->getRealPath());

            $newData[$key] = [
                'fullpath'  => $file->getRealPath(),
                'relpath'   => $relativePath,
                'urlpath'   => $this->fromNameToUrlSave($relativePath),
                'path'      => $file->getPath(),
                'filename'  => $file->getFilename(),
                'extension' => $file->getExtension(),
                'content'   => Late::call('file_get_contents', $file->getRealPath()),
                'size'      => $file->getSize(),
                'changed'   => (new \DateTimeImmutable())->setTimestamp($file->getMTime()),
            ];
        }

        return $newData;
    }

    /**
     * @param string $filename
     * @return string Remove \, / and . from name
     */
    public function fromNameToUrlSave(string $filename): string
    {
        return str_replace(['\\', '/', '.'], ['|', '|', '%2E'], $filename);
    }
}