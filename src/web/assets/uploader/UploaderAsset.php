<?php

namespace panlatent\craft\aliyun\web\assets\uploader;

use Craft;
use craft\helpers\ConfigHelper;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class UploaderAsset extends AssetBundle
{
    /** @inheritdoc */
    public $sourcePath = __DIR__ . '/dist';

    /** @inheritdoc */
    public $js = [
        'Uploader.js',
    ];

    /**
     * @inheritdoc
     */
    public $depends = [
        CpAsset::class,
    ];

    public function registerAssetFiles($view): void
    {
//        if (!Module::getInstance()->getConfig()->useAssetCdn) {
//            return;
//        }

        parent::registerAssetFiles($view);

        $maxFileSize = ConfigHelper::sizeInBytes(Craft::$app->getConfig()->getGeneral()->maxUploadFileSize);
        $js = <<<JS
window.Craft.CloudUploader.defaults.maxFileSize = $maxFileSize;
JS;
        $view->registerJs($js, \yii\web\View::POS_END);
    }
}
