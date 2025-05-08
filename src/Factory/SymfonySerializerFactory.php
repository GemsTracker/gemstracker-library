<?php

declare(strict_types=1);

namespace Gems\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class SymfonySerializerFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): SerializerInterface
    {
        $reflectionExtractor = new ReflectionExtractor();

        $metadataFactory = new ClassMetadataFactory(new AttributeLoader());

        $normalizers = [
            new ObjectNormalizer(
                classMetadataFactory: $metadataFactory,
                nameConverter: null,
                propertyAccessor: null,
                propertyTypeExtractor: $reflectionExtractor,classDiscriminatorResolver: null, defaultContext: [
                    'allow_extra_attributes' => false,
                    ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => false,
                    ObjectNormalizer::SKIP_NULL_VALUES => true,
                ]),
            new DateTimeNormalizer(),
        ];

        $encoders = [
            new JsonEncoder(),
        ];

        $serializer = new Serializer($normalizers, $encoders);

        return $serializer;
    }
}