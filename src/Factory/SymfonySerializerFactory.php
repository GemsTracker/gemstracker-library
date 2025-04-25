<?php

declare(strict_types=1);

namespace Gems\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class SymfonySerializerFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): SerializerInterface
    {
        $reflectionExtractor = new ReflectionExtractor();

        $listExtractors = [$reflectionExtractor];
        $typeExtractors = [$reflectionExtractor];

        $propertyInfoExtractor = new PropertyInfoExtractor($listExtractors, $typeExtractors);

        $normalizers = [
            new ObjectNormalizer(null, null, null, $propertyInfoExtractor),
            new DateTimeNormalizer(),
        ];

        $encoders = [
            new JsonEncoder(),
        ];

        $serializer = new Serializer($normalizers, $encoders);

        return $serializer;
    }
}