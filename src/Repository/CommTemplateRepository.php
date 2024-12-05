<?php

declare(strict_types=1);

namespace Gems\Repository;

use Gems\Db\CachedResultFetcher;

class CommTemplateRepository
{
    protected array $cacheTags = ['comm-templates'];
    public function __construct(
        protected readonly CachedResultFetcher $cachedResultFetcher,
    )
    {}


    public function getAllCommTemplateData(): array
    {
        $select = $this->cachedResultFetcher->getSelect('gems__comm_templates');
        $select->columns([
            'gct_id_template',
            'gct_name',
            'gct_target',
            'gct_code',
        ]);
        return $this->cachedResultFetcher->fetchAll('commTemplates', $select, null, $this->cacheTags);
    }


    /**
     *
     * @param string $target Optional communications template target
     * @param string $code Optional communications template code
     * @return array id of communications template or false if none exists
     */
    public function getCommTemplateForCode(string $code, string|null $target = null): array
    {
        $allTemplates = $this->getAllCommTemplateData();

        $result = array_filter($allTemplates, function ($template) use ($code, $target) {
            if ($target !== null) {
                return ($template['gct_code'] === $code) && $template['gct_target'] === $target;
            }
            return $template['gct_code'] === $code;
        });

        return array_column($result, 'gct_id_template');
    }

    /**
     *
     * @param string|null $target Optional communications template target
     * @param string|null $code Optional communications template code
     * @return array Of id => name
     */
    public function getCommTemplatesForTarget(string|null $target = null, string|null $code = null): array
    {
        $allTemplates = $this->getAllCommTemplateData();

        return array_filter($allTemplates, function ($template) use ($code, $target) {
            $forTarget = true;
            if ($target !== null ) {
                $forTarget = $template['gct_target'] === $target;
            }

            $forCode = true;
            if ($code !== null ) {
                $forCode = $template['gct_code'] === $code;
            }
            return $forTarget && $forCode;
        });
    }
}