<?php

namespace Gems\Command;

use Gems\Db\ConfigRepository;
use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\ResultFetcher;
use Gems\Util\Phone\PhoneNumberFactory;
use Laminas\Db\Adapter\Adapter;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'phonenumber:normalize-respondents', description: 'Normalizes phone numbers in respondents')]
class PhoneNumberNormalizer extends Command
{
    public function __construct(
        private readonly ContainerInterface $container,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var ResultFetcher $resultFetcher */
        $resultFetcher = $this->container->get(ResultFetcher::class);
        $writerResultFetcher = $this->getWriterResultFetcher();

        /** @var PhoneNumberFactory $phoneNumberFactory */
        $phoneNumberFactory = $this->container->get(PhoneNumberFactory::class);

        $phoneFields = $this->getPhoneFields();
        $columns = array_merge(['grs_id_user'], $phoneFields);
        $select = $resultFetcher->getSelect('gems__respondents');
        $select->columns($columns);

        $resultSet = $resultFetcher->query($select);
        foreach($resultSet as $row){
            $newValues = [];
            foreach($phoneFields as $fieldName) {
                if (isset($row[$fieldName]) && $row[$fieldName] !== null) {
                    $phoneNumber = $phoneNumberFactory->fromString($row[$fieldName]);
                    if (!$phoneNumber->isValid()) {
                        $newValues[$fieldName] = null;
                    }

                    $newNumber = $phoneNumber->format();
                    if ($newNumber === $row[$fieldName]) {
                        continue;
                    }
                    $newValues[$fieldName] = $newNumber;
                }
            }

            if (!count($newValues)) {
                continue;
            }

            $writerResultFetcher->updateTable('gems__respondents',
                $newValues,
                [
                'grs_id_user' => $row['grs_id_user'],
            ]);
        }

        return Command::SUCCESS;
    }

    private function getPhoneFields(): array
    {
        /** @var DatabaseInfo $databaseInfo */
        $databaseInfo = $this->container->get(DatabaseInfo::class);

        $fields = [];
        $i = 1;
        while($databaseInfo->tableHasColumn('gems__respondents', 'grs_phone_' . $i)) {
            $fields = ['grs_phone_' . $i];
            $i++;
        }

        return $fields;
    }

    private function getWriterResultFetcher(): ResultFetcher
    {
        /**
         * @var ConfigRepository $config
         */
        $config = $this->container->get(ConfigRepository::class);
        $adapter = new Adapter($config->getConfig());

        return new ResultFetcher($adapter);
    }
}