<?php

use yii\db\Migration;

/**
 * Updates AppHighVsBelow1000AGL, AppHighVsAvgBelow1000AGL and AppHighVsBelow2000AGL
 * descriptions to use {limit} placeholder.
 */
class m260324_093000_add_limit_placeholder_to_2000agl_issue extends Migration
{
    private array $updates = [
        'AppHighVsBelow1000AGL' => [
            'en' => ['new' => 'High descent rate ({limit} fpm) below 1000 AGL',         'old' => 'High descent rate (<-1500 fpm) below 1000 AGL'],
            'es' => ['new' => 'Alta tasa de descenso ({limit} fpm) por debajo de 1000 AGL', 'old' => 'Alta tasa de descenso (<-1500 fpm) por debajo de 1000 AGL'],
        ],
        'AppHighVsAvgBelow1000AGL' => [
            'en' => ['new' => 'High sampled descent rate ({limit} fpm) below 1000 AGL',         'old' => 'High sampled descent rate (<-1150 fpm) below 1000 AGL'],
            'es' => ['new' => 'Alta tasa de descenso muestreada ({limit} fpm) por debajo de 1000 AGL', 'old' => 'Alta tasa de descenso muestreada (<-1150 fpm) por debajo de 1000 AGL'],
        ],
        'AppHighVsBelow2000AGL' => [
            'en' => ['new' => 'High descent rate ({limit} fpm) below 2000 AGL',         'old' => 'High descent rate (<-2000 fpm) below 2000 AGL'],
            'es' => ['new' => 'Alta tasa de descenso ({limit} fpm) por debajo de 2000 AGL', 'old' => 'Alta tasa de descenso (<-2000 fpm) por debajo de 2000 AGL'],
        ],
    ];

    public function safeUp()
    {
        $this->applyDescriptions('new');
    }

    public function safeDown()
    {
        $this->applyDescriptions('old');
    }

    private function applyDescriptions(string $key): void
    {
        foreach ($this->updates as $code => $langs) {
            $issueTypeId = (new \yii\db\Query())
                ->select('id')
                ->from('issue_type')
                ->where(['code' => $code])
                ->scalar();

            if ($issueTypeId) {
                foreach ($langs as $language => $descriptions) {
                    $this->update('issue_type_lang',
                        ['description' => $descriptions[$key]],
                        ['issue_type_id' => $issueTypeId, 'language' => $language]
                    );
                }
            }
        }
    }
}
