<?php

use yii\db\Migration;

/**
 * Adds max_glideslope_deg column to runway_end table.
 */
class m260321_093000_add_glideslope_to_runway_end extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            'runway_end',
            'max_glideslope_deg',
            $this->decimal(3, 2)->notNull()->defaultValue(3.00)->after('true_heading_deg')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('runway_end', 'max_glideslope_deg');
    }
}
