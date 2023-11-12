<?php

declare(strict_types=1);


/**
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets;

use Gems\Audit\AccesslogRepository;

/**
 * @package    Gems
 * @subpackage Snippets
 * @since      Class available since version 1.0
 */
trait AuditLogTrait
{
    protected AccesslogRepository $accesslogRepository;

    protected function logChanges(int $changed, array $currentData = [], array $oldData = [])
    {

        $this->accesslogRepository->logChange($this->request, null, $this->formData);
    }

}