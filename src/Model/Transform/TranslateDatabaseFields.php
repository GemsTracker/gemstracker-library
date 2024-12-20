<?php

namespace Gems\Model\Transform;

use Gems\Cache\HelperAdapter;
use Gems\Db\ResultFetcher;
use Gems\Html;
use Gems\Locale\Locale;
use Gems\Model\TranslationModel;
use Laminas\Db\Sql\Expression;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModelInterface;

class TranslateDatabaseFields extends \Zalt\Model\Transform\ModelTransformerAbstract
{
    protected readonly string $defaultLanguage;

    protected readonly array $languages;
    protected array $tableKeys = [];

    /**
     * @var array list of tables that have translations
     */
    protected $translateTables = [];

    /**
     * @var array list of tables as keys and the fields that have translated
     */
    protected array $translations = [];

    public function __construct(
        protected readonly HelperAdapter $cacheHelper,
        protected readonly bool $showAllTranslations,
        protected readonly Locale $locale,
        protected readonly ResultFetcher $resultFetcher,
        protected readonly TranslationModel $translationModel,
        protected readonly TranslatorInterface $translator,
        array $config = [],
    )
    {
        $this->defaultLanguage = $config['locale']['default'];

        $this->languages = $config['locale']['availableLocales'];
    }

    public function getFieldInfo(MetaModelInterface $model): array
    {
        $this->tableKeys = $this->getTablesWithTranslations();

        if ($this->showAllTranslations) {
            foreach ($model->getItemsFor(['translate' => true]) as $itemName) {
                $settings = $model->get($itemName);

                // Needed for first translation addition
                if (! isset($this->tableKeys[$settings['table']])) {
                    $this->tableKeys[$settings['table']] = $model->getKeys();
                }

                unset($settings['table'], $settings['validator'], $settings['validators']);
                $settings['original'] = $itemName;
                $settings['required'] = false;
                $settings['column_expression'] = 'NULL'; // Prevent save attempts
                $settings['escapeDescription'] = false;


                $order    = $model->getOrder($itemName);
                foreach ($this->languages as $language) {
                    $newName = $itemName . '___' . $language;

                    $descriptionDiv = Html::create('span', ['class' => 'language-select']);
                    $descriptionDiv->span(['class' => 'language ' . $language], ' ');
                    $descriptionDiv->append(' ');

                    $newSettings = $settings;
                    $newSettings['label']    = ($settings['label'] ?? $itemName) . ' ' . strtoupper($language);
                    $newSettings['language'] = $language;
                    $newSettings['order']    = ++$order;
                    if ($language == $this->defaultLanguage) {
                        $newSettings['elementClass'] = 'Exhibitor';
                        $descriptionDiv->append($this->translator->_('Default language'));
                    } else {
                        $descriptionDiv->append(sprintf($this->translator->_('Translate or leave empty for %s version'), strtoupper($this->defaultLanguage)));
                    }
                    $newSettings['description'] = $descriptionDiv;

                    $model->set($newName, $newSettings);

                    $model->setSaveWhen($newName, false);
                }
            };
        }

        return parent::getFieldInfo($model);
    }

    public function getTableKeyForRow(string $tableName, string $field, array $row): ?string
    {
        if (! isset($this->tableKeys[$tableName])) {
            return null;
        }

        $keys = [];
        foreach ($this->tableKeys[$tableName] as $field) {
            if (isset($row[$field])) {
                $keys[] = $row[$field];
            } else {
                return null;
            }
        }

        return join('_', $keys);
    }

    /**
     * All tables with translations as keys and an array with all table fields with translations
     *
     * @return array List of tables and columns with translations
     */
    public function getTablesWithTranslations()
    {
        $cacheId = 'dataBaseTablesWithTranslations';

        if (!$this->translateTables) {
            $tables = $this->cacheHelper->getCacheItem($cacheId);
            if ($tables) {
                $this->translateTables = $tables;
                return $tables;
            }

            $select = $this->resultFetcher->getSelect('gems__translations');
            $select->columns(['gtrs_table', 'gtrs_field'])
                ->group(['gtrs_table', 'gtrs_field']);

            $rows = $this->resultFetcher->fetchAll($select);

            $this->translateTables = [];
            foreach ($rows as $row) {
                $this->translateTables[$row['gtrs_table']][] = $row['gtrs_field'];
            }

            $this->cacheHelper->setCacheItem($cacheId, $this->translateTables, ['database_translations']);
        }

        return $this->translateTables;
    }

    /**
     * @param MetaModelInterface $model
     * @return array<string, string> translateable => field => name => original field name
     */
    public function getTranslatingFields(MetaModelInterface $model): array
    {
        $output = [];
        foreach ($model->getItemsFor(['translate' => true]) as $field) {
            $output += array_fill_keys($model->getItemsFor(['original' => $field]), $field);
        }

        return $output;
    }

    public function getTranslationKeyForRow(string $tableName, string $field, array $row): ?string
    {
        if (! isset($this->tableKeys[$tableName])) {
            return null;
        }

        $keys = $this->getTableKeyForRow($tableName, $field, $row);
        if ($keys) {
            return $this->getTranslationKey($tableName, $field, $keys);
        }

        return null;
    }

    /**
     * Get a combined translation key to find the current translation
     *
     * @param $tableName string Name of the table
     * @param $field string name of the table column
     * @param $keyValues array|string values of all table keys, separated by _
     * @return string
     */
    public function getTranslationKey($tableName, $field, $keyValues): string
    {
        if (is_array($keyValues)) {
            $keyValues = join('_', $keyValues);
        }
        return $tableName . '_' . $field . '_' .  $keyValues;
    }

    /**
     * Key value database translations
     * @param string $language
     * @return array List of translations
     */
    public function getTranslations(string $language): array
    {
        if (! isset($this->translations[$language])) {
            $cacheId = 'dataBaseTranslations' . '_' . $language;

            $this->translations[$language] = $this->cacheHelper->getCacheItem($cacheId);
            if (!$this->translations[$language]) {
                $select = $this->resultFetcher->getSelect('gems__translations');
                $select->columns([
                        'key' => new Expression(TranslationModel::KEY_COLUMN),
                        'gtrs_translation'
                    ])
                    ->where(['gtrs_iso_lang' => $language]);

                $this->translations[$language] = $this->resultFetcher->fetchPairs($select);

                $this->cacheHelper->setCacheItem($cacheId, $this->translations, ['database_translations']);
            }
        }

        return $this->translations[$language];
    }


    public function transformFilter(MetaModelInterface $model, array $filter)
    {
        if (is_array($this->tableKeys)) {
            foreach($this->tableKeys as $tableName => $itemNames) {
                foreach($itemNames as $itemName) {
                    // Makes the key column available in the query
                    $model->get($itemName, 'label');
                }
            }
        }

        return parent::transformFilter($model, $filter);
    }

    public function transformRowAfterSave(MetaModelInterface $model, array $row)
    {
        if ($this->showAllTranslations) {
//            file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  print_r($row, true) . "\n", FILE_APPEND);
//            file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  print_r($this->tableKeys, true) . "\n", FILE_APPEND);
            foreach ($this->getTranslatingFields($model) as $field => $original) {
                $table = $model->get($original, 'table');
                $key = $this->getTranslationKeyForRow($table, $original, $row);
                if ($key) {
                    $language = $model->get($field, 'language');
                    $check = $this->translationModel->loadFirst(['key' => $key, 'gtrs_iso_lang' => $language], [], ['gtrs_id']);
                    $rowId = $check['gtrs_id'] ?? null;
                    if ($language != $this->defaultLanguage) {
                        if ($row[$field] && ($row[$field] != $row[$original])) {
                            $values = [
                                'gtrs_id' => $rowId,
                                'gtrs_table' => $table,
                                'gtrs_field' => $original,
                                'gtrs_keys' => $this->getTableKeyForRow($table, $original, $row),
                                'gtrs_iso_lang' => $language,
                                'gtrs_translation' => $row[$field]
                            ];
                            $this->translationModel->save($values);
                        } elseif ($rowId && ((! $row[$field]) || ($row[$field] == $row[$original]))) {
                            $this->translationModel->delete(['gtrs_id' => $rowId]);
                        }
                    }
                }
            };
        }

        return $row;
    }

    public function transformLoad(MetaModelInterface $model, array $data, $new = false, $isPostData = false)
    {
        $language = $this->locale->getLanguage();

        if ($this->showAllTranslations) {
            $translateableFields = $this->getTranslatingFields($model);

            foreach ($data as &$row) {
                foreach ($translateableFields as $field => $original) {
                    $key = $this->getTranslationKeyForRow($model->get($original, 'table'), $field, $row);
                    if (!isset($row[$field])) {
                        $row[$field] = $this->translateField($original, $row, $model->get($field, 'language'));
                    }
                };
            }
            // file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  print_r($data, true) . "\n", FILE_APPEND);

            return $data;
        }

        if ($language == $this->defaultLanguage) {
            return $data;
        }

        $translatedData = $this->translateData($data, $language);

        return $translatedData;
    }

    protected function translateData(array $data, string $language): array
    {
        foreach ($data as $key => $row) {
            $data[$key] = $this->translateRow($row, $language);
        }

        return $data;
    }

    public function translateField($name, $row, $language): ?string
    {
        // dump($name, $language);
        if ($language == $this->defaultLanguage) {
            return $row[$name];
        }

        $result = $this->translateRow($row, $language);
        return $row[$name] == $result[$name] ? '' : $result[$name];
    }

    protected function translateRow(array $row, string $language): array
    {
        $translations = $this->getTranslations($language);

        foreach ($this->translateTables as $tableName => $fields) {
            foreach($fields as $field) {
                $key = $this->getTranslationKeyForRow($tableName, $field, $row);
                if ($key && isset($translations[$key])) {
                    $row[$field] = $translations[$key];
                }
            }
        }

        return $row;
    }
}
