<?php

declare(strict_types=1);

namespace Gems\Model\Setup\Transform;

use Exception;
use Gems\Messenger\ErrorDetailsStamp;
use ReflectionClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Transform\ModelTransformerAbstract;

class MessageBodyInfoTransformer extends ModelTransformerAbstract
{
    public function transformLoad(MetaModelInterface $model, array $data, $new = false, $isPostData = false)
    {
        $serializer = new PhpSerializer();

        foreach($data as $key => $row) {
            if (!isset($row['body'])) {
                continue;
            }

            try {
                $envelope = $serializer->decode([
                    'body' => $row['body'],
                    'headers' => $row['headers'],
                ]);
            } catch (Exception) {
                continue;
            }

            $message = $envelope->getMessage();
            $reflection = new ReflectionClass($message);
            $data[$key]['messageShortClass'] = $reflection->getShortName();

            $data[$key]['envelope'] = $envelope;

            $data[$key]['stamps'] = $envelope->all();

            $data[$key]['message'] = $message;
            $data[$key]['messageDump'] = print_r($message, true);

            $data[$key]['messageInfo'] = $this->getInfoFromStamps($envelope);
        }

        return $data;
    }

    private function getInfoFromStamps(Envelope $envelope): string|null
    {
        if ($errorDetailsStamp = $envelope->last(ErrorDetailsStamp::class)) {
            return $errorDetailsStamp->getExceptionMessage();
        }
        if ($errorDetailsStamp = $envelope->last(\Symfony\Component\Messenger\Stamp\ErrorDetailsStamp::class)) {
            return $errorDetailsStamp->getExceptionMessage();
        }

        return null;
    }
}