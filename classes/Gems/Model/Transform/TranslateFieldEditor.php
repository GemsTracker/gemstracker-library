<?php


namespace Gems\Model\Transform;


use MUtil\Bootstrap\Form\Element\Text;
use MUtil\Registry\TargetTrait;

class TranslateFieldEditor extends \MUtil_Model_Transform_NestedTransformer implements \MUtil_Registry_TargetInterface
{
    use TargetTrait;

    protected $copyParentFieldSettings = [
        'elementClass',
        'maxlength',
        'minlength',
        'size',
        'cols',
        'rows',
        'wrap',
        'formatFunction',
        'itemDisplay',
        'nohidden'
    ];

    protected $flagDir = 'gems-responsive/images/locale/png/';

    protected $flagSize = 30;

    protected $flagExtension = '.png';

    /**
     * @var \Zend_Cache_Core
     */
    protected $cache;

    /**
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    public function getFieldInfo(\MUtil_Model_ModelAbstract $model)
    {
        $items = $model->getColNames('translate');

        foreach($items as $itemName) {
            $itemSettings = $model->get($itemName);
            $tableKeys = $model->getKeys();
            $keys = new \Zend_Db_Expr(reset($tableKeys));
            if (count($tableKeys) > 1) {
                $keysString = join(', \'_\', ', $tableKeys);

                $keys = new \Zend_Db_Expr('CONCAT(' . $keysString . ')');
            }
            $translationModel = new \MUtil_Model_TableModel('gems__translations');

            \Gems_Model::setChangeFieldsByPrefix($translationModel, 'gtrs');

            $emptyValue = function($value) { echo ''; };

            $translationModel->set('gtrs_iso_lang',
                'label', 'language',
                'elementClass', 'exhibitor',
                'decorators', ['CountryInputGroupAddon'],
                'formatFunction', $emptyValue
            );
            $translationModel->set('gtrs_translation', 'label', 'translation');

            // Inherit parent settings
            $parentSettings = $model->get($itemName, ...$this->copyParentFieldSettings);
            $translationModel->set('gtrs_translation', $parentSettings);

            $subFilter = [
                'gtrs_table' => $itemSettings['table'],
                'gtrs_field' => $itemName,
                'gtrs_keys' => $keys,
            ];
            $translationModel->addFilter($subFilter);

            $translationName = 'translations_' . $itemName;
            if ($model instanceof \MUtil_Model_DatabaseModelAbstract) {
                $model->addColumn(new \Zend_Db_Expr($keys), 'table_keys');
            }


            $this->addModel($translationModel,
                [
                    'table_keys' => 'gtrs_keys',
                ],
                $translationName
            );

            if ((!isset($itemSettings['elementClass']) || $itemSettings['elementClass'] == 'text') && \MUtil_Bootstrap::enabled()) {
                $model->set($itemName, 'decorators',
                    [
                        'ViewHelper',
                        'AddDefaultLanguage' => [
                            'decorator' => 'AddLanguage',
                            'options' => [
                                'language' => $this->project->getLocaleDefault()
                            ],
                        ],
                        'Errors',
                        'Description' => ['decorator' => 'Description', 'options' => ['tag' => 'p', 'class' => 'help-block']],
                        'HtmlTag' => [
                            'decorator' => 'HtmlTag',
                            'options' => [
                                'tag' => 'div',
                                'id' => ['callback', Text::class, 'resolveElementId'],
                                'class' => 'element-container',
                            ]
                        ],
                        'Label',
                        'BootstrapRow'
                    ]
                );
            }

            $model->set($translationName,
                'model', $translationModel,
                'elementClass', 'FormTable',
                'decorators', ['InputGroupForm', 'Label', 'BootstrapRow'],
                'type', \MUtil_Model::TYPE_CHILD_MODEL,
                'order', $model->getOrder($itemName)+1
            );
        }

        return parent::getFieldInfo($model);
    }

    /**
     * Function to allow overruling of transform for certain models
     *
     * @param \MUtil_Model_ModelAbstract $model Parent model
     * @param \MUtil_Model_ModelAbstract $sub Sub model
     * @param array $data The nested data rows
     * @param array $join The join array
     * @param string $name Name of sub model
     * @param boolean $new True when loading a new item
     * @param boolean $isPostData With post data, unselected multiOptions values are not set so should be added
     */
    protected function transformLoadSubModel(
        \MUtil_Model_ModelAbstract $model, \MUtil_Model_ModelAbstract $sub, array &$data, array $join,
        $name, $new, $isPostData)
    {
        $allLanguages = $this->getLocales();
        $defaultLanguage = $this->project->getLocaleDefault();

        foreach ($data as $key => $row) {

            $tableKeys = $model->getKeys();
            $keyValues;
            foreach($tableKeys as $keyName => $column) {
                if (isset($row[$column])) {
                    $keyValues[] = $row[$column];
                }
            }

            // E.g. if loaded from a post
            if (isset($row[$name])) {
                $rows = $sub->processAfterLoad($row[$name], $new, $isPostData);
            } elseif ($new) {
                $rows = $sub->loadAllNew();
            } else {
                $filter = $sub->getFilter();
                $filter['gtrs_keys'] = join('_', $keyValues);
                // If $filter is empty, treat as new
                if (empty($filter)) {
                    $rows = $sub->loadAllNew();
                } else {
                    $rows = $sub->load($filter);
                }
            }

            $newRow = $sub->getFilter();

            $newRow['gtrs_keys'] = join('_', $keyValues);

            $loadedLanguages = array_column($rows, 'gtrs_iso_lang');
            $loadedLanguages = [];
            foreach($rows as $subrow) {
                $loadedLanguages[$subrow['gtrs_iso_lang']] = $subrow;
            }

            $fullRows = [];
            foreach($allLanguages as $isoLang=>$languageName) {
                if ($isoLang == $defaultLanguage) {
                    continue;
                }

                if (isset($loadedLanguages[$isoLang])) {
                    $fullRows[] = $loadedLanguages[$isoLang];
                    continue;
                }

                $langRow = $newRow;
                $langRow['gtrs_iso_lang'] = $isoLang;
                $fullRows[] = $langRow;
            }

            $data[$key][$name] = $fullRows;
        }

    }

    /**
     * This transform function performs the actual save (if any) of the transformer data and is called after
     * the saving of the data in the source model.
     *
     * @param \MUtil_Model_ModelAbstract $model The parent model
     * @param array $row Array containing row
     * @return array Row array containing (optionally) transformed data
     */
    public function transformRowAfterSave(\MUtil_Model_ModelAbstract $model, array $row)
    {
        $result = parent::transformRowAfterSave($model, $row);
        if ($this->_changed) {
            $this->cache->clean('matchingTag', ['database_translations']);
        }

        return $result;
    }

    /**
     * Function to allow overruling of transform for certain models
     *
     * @param \MUtil_Model_ModelAbstract $model
     * @param \MUtil_Model_ModelAbstract $sub
     * @param array $data
     * @param array $join
     * @param string $name
     */
    protected function transformSaveSubModel(
        \MUtil_Model_ModelAbstract $model, \MUtil_Model_ModelAbstract $sub, array &$row, array $join, $name)
    {
        if (!isset($row['table_keys'])) {
            $tableKeys = $model->getKeys();
            $keyValues;
            foreach($tableKeys as $keyName => $column) {
                if (isset($row[$column])) {
                    $keyValues[] = $row[$column];
                }
            }
            $row['table_keys'] = join('_', $keyValues);
        }

        parent::transformSaveSubModel($model, $sub, $row, $join, $name);
    }

    protected function getLocales()
    {
        if (isset($this->project->locales)) {
            $locales = $this->project->locales;
        } elseif (isset($this->project->locale['default'])) {
            $locales = [$this->project->locale['default']];
        } else {
            $locales = ['en'];
        }

        return $locales;
    }

}
