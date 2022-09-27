<?php

namespace App\Services;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;

class EntitySerializer
{
    public function __construct()
    {}
    
    public function serialize($object): string
    {
        $encoders = [new JsonEncoder()];

        $defaultContext = [
            'circular_reference_handler' => function ($object, $format, $context) {
                return $object->getId();
            },
        ];

        $normalizers = [new DatetimeNormalizer,new ObjectNormalizer(null, null, null, null, null, null, $defaultContext)];
        
        $serializer = new Serializer($normalizers, $encoders);
        
        $jsonContent = $serializer->serialize($object, 'json');

        return $jsonContent;
    }
}