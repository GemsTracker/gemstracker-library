<?php

declare(strict_types=1);

namespace Gems\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SymfonyValidatorFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): ValidatorInterface
    {
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();

        return $validator;
    }
}