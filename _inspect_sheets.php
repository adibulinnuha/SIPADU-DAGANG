<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use PhpOffice\PhpSpreadsheet\IOFactory;

$templatePath = __DIR__ . '/storage/app/templates/ERET JULI.xltx';
$spreadsheet = IOFactory::load($templatePath);
$sheets = $spreadsheet->getSheetNames();

echo "Total sheets: " . count($sheets) . "\n";
foreach ($sheets as $name) {
    $sheet = $spreadsheet->getSheetByName($name);
    $val = $sheet->getCell('A1')->getValue();
    $calcVal = $sheet->getCell('A1')->getCalculatedValue();
    echo "'{$name}' => raw='" . ($val ?? 'NULL') . "' calc='" . ($calcVal ?? 'NULL') . "'\n";
}

// Also inspect formula cells B45 and B47
$sheet = $spreadsheet->getSheetByName('01 Juli');
if ($sheet) {
    echo "\n--- Cell B45 ---\n";
    echo "raw: " . var_export($sheet->getCell('B45')->getValue(), true) . "\n";
    echo "isFormula: " . ($sheet->getCell('B45')->isFormula() ? 'YES' : 'NO') . "\n";
    echo "calculated: " . var_export($sheet->getCell('B45')->getCalculatedValue(), true) . "\n";
    
    echo "\n--- Cell B47 ---\n";
    echo "raw: " . var_export($sheet->getCell('B47')->getValue(), true) . "\n";
    echo "isFormula: " . ($sheet->getCell('B47')->isFormula() ? 'YES' : 'NO') . "\n";
    echo "calculated: " . var_export($sheet->getCell('B47')->getCalculatedValue(), true) . "\n";
    
    echo "\n--- Cell B45 in merged range? ---\n";
    $merged = $sheet->getMergeCells();
    foreach ($merged as $range) {
        if (strpos($range, 'B45') !== false || strpos($range, 'B47') !== false) {
            echo "Found merged range: {$range}\n";
        }
    }
    echo "All merged cells:\n";
    print_r($merged);
}

$spreadsheet->disconnectWorksheets();
