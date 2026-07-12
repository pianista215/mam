<?php

namespace tests\unit\models;

use app\models\FlightPhaseIssue;
use app\models\IssueType;
use app\models\IssueTypeLang;
use tests\unit\BaseUnitTest;

class FlightPhaseIssueTest extends BaseUnitTest
{
    private function makeIssueType(string $code, ?int $penalty, string $description): IssueType
    {
        $issueType = IssueType::findOne(['code' => $code]);
        if ($issueType === null) {
            $issueType = new IssueType(['code' => $code, 'penalty' => $penalty]);
            $this->assertTrue($issueType->save());
        }

        $lang = IssueTypeLang::findOne(['issue_type_id' => $issueType->id, 'language' => 'en']);
        if ($lang === null) {
            $lang = new IssueTypeLang([
                'issue_type_id' => $issueType->id,
                'language'      => 'en',
                'description'   => $description,
            ]);
            $this->assertTrue($lang->save());
        }

        return $issueType;
    }

    public function testGetDescriptionForTakeoffWithBadPayloadAppendsKgValue(): void
    {
        $issueType = $this->makeIssueType(
            'TakeoffWithBadPayload',
            20,
            "The aircraft's estimated payload doesn't match the load sheet"
        );

        $issue = new FlightPhaseIssue([
            'issue_type_id' => $issueType->id,
            'value'         => '531',
        ]);

        $this->assertEquals(
            "The aircraft's estimated payload doesn't match the load sheet: (531 Kg)",
            $issue->getDescription()
        );
    }
}
