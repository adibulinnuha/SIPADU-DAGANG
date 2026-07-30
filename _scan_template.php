<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use PhpOffice\PhpSpreadsheet\IOFactory;

$tpl = __DIR__ . '/storage/app/templates/ERET JULI.xltx';
$sp = IOFactory::load($tpl);
$sheet = $sp->getSheetByName('01 Juli');

echo "=== Column A scan (01 Juli) ===\n";
for ($r = 1; $r <= 50; $r++) {
    $v = $sheet->getCell('A' . $r)->getCalculatedValue();
    if ($v !== null && trim($v) !== '') {
        echo 'A' . $r . ': ' . trim($v) . "\n";
    }
}

echo "\n=== Check formulas in B-E columns ===\n";
for ($r = 5; $r <= 48; $r++) {
    foreach (['B', 'C', 'D', 'E', 'F'] as $col) {
        $cell = $col . $r;
        if ($sheet->getCell($cell)->isFormula()) {
            echo $cell . ': ' . $sheet->getCell($cell)->getValue() . "\n";
        }
    }
}

$sp->disconnectWorksheets();
