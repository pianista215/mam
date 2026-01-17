<?php

namespace app\models\traits;

use app\helpers\LoggerTrait;
use app\models\Page;

trait PageRelated
{
    use LoggerTrait;

    /**
     * Returns the page code associated with this model.
     * Must be implemented by the model using this trait.
     */
    abstract public function getPageCode(): string;

    protected function afterDeletePageCleanup(): void
    {
        $page = Page::find()->where(['code' => $this->getPageCode()])->one();

        if ($page) {
            $this->logInfo('Deleting page associated', ['model' => $this, 'page' => $page]);
            $page->delete();
        }
    }
}
