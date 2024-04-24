<?php

namespace Gems\Command;

use Gems\Translate\JavascriptTranslations;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zalt\Base\TranslatorInterface;

#[AsCommand(name: 'js:translations', description: 'Generate Javascript translation files')]
class GenerateJsTranslations extends Command
{
    public function __construct(
        protected readonly array $config,
        protected readonly TranslatorInterface $translator,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $translationPaths = $this->config['javascript']['translations'] ?? [];
        $availableLanguages = $this->config['locale']['availableLocales'] ?? ['en'];

        foreach($translationPaths as $translationClassName => $translationPath) {
            /**
             * @var JavascriptTranslations $translationClass
             */
            $translationClass = new $translationClassName($this->translator);
            foreach($availableLanguages as $language) {
                $translations = $translationClass($language);
                $translationCount = $this->countTranslations($translations);
                $filename = rtrim($translationPath, '/') . '/' . $language . '.test.json';
                file_put_contents($filename, json_encode($translations, JSON_PRETTY_PRINT));
                $output->writeln(sprintf('Created %s translation file \'%s\' with %d base translations', $language, $filename, $translationCount));
            }
        }

        return static::SUCCESS;
    }

    protected function countTranslations(array $translations): int
    {
        $i = 0;
        foreach($translations as $translation) {
            if (is_array($translation)) {
                $i += $this->countTranslations($translation);
                continue;
            }
            $i++;
        }

        return $i;
    }
}