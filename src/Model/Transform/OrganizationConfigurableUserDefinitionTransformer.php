<?php

declare(strict_types=1);

namespace Gems\Model\Transform;

use Gems\User\UserDefinitionConfigurableInterface;
use Gems\User\UserLoader;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Transform\ModelTransformerAbstract;

class OrganizationConfigurableUserDefinitionTransformer extends ModelTransformerAbstract
{
    public function __construct(
        private readonly UserLoader $userLoader,
    )
    {
    }

    public function transformLoad(MetaModelInterface $model, array $data, $new = false, $isPostData = false): array
    {
        foreach ($data as &$row) {
            if (isset($row['gor_user_class']) && !empty($row['gor_user_class'])) {
                $definition = $this->userLoader->getUserDefinition($row['gor_user_class']);

                if ($definition instanceof UserDefinitionConfigurableInterface && $definition->hasConfig()) {
                    $definition->addConfigFields($model);
                    $row = $row + $definition->loadConfig($row);
                }
            }

        }
        return $data;
    }

    public function transformRowAfterSave(MetaModelInterface $model, array $row): array
    {
        $savedValues = parent::transformRowAfterSave($model, $row);

        if (isset($row['gor_user_class'])) {
            $definition = $this->userLoader->getUserDefinition($row['gor_user_class']);

            if ($definition instanceof UserDefinitionConfigurableInterface && $definition->hasConfig()) {
                $savedValues = $definition->saveConfig($savedValues, $row);
            }
        }

        return $savedValues;
    }
}