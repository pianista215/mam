<?php

function checkMatch($pdo, $table, $langTable) {
    $stmt1 = $pdo->query("SELECT COUNT(*) FROM $table");
    $stmt2 = $pdo->query("SELECT COUNT(*) FROM $langTable WHERE language = 'es'");
    $stmt3 = $pdo->query("SELECT COUNT(*) FROM $langTable WHERE language = 'en'");

    $countMain = $stmt1->fetchColumn();
    $countEs   = $stmt2->fetchColumn();
    $countEn   = $stmt3->fetchColumn();

    if ($countMain != $countEs || $countMain != $countEn) {
        echo "❌ Count mismatch in $table / $langTable → ($countMain / $countEs / $countEn)\n";
        return false;
    }

    echo "✅ $table OK ($countMain entries, translations match)\n";
    return true;
}

$pdo = new PDO("mysql:host=127.0.0.1;dbname=test_mam_database", "root", "root");

$ok = true;
$ok &= checkMatch($pdo, "flight_phase_type", "flight_phase_type_lang");
$ok &= checkMatch($pdo, "flight_phase_metric_type", "flight_phase_metric_type_lang");
$ok &= checkMatch($pdo, "issue_type", "issue_type_lang");

exit($ok ? 0 : 1);
