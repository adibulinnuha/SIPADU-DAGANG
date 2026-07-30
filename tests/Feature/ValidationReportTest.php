<?php

namespace Tests\Feature;

use App\Services\ValidationReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ValidationReportTest — Unit tests for the ValidationReport service (Task 9)
 */
class ValidationReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_starts_empty_and_valid()
    {
        $report = new ValidationReport();

        $this->assertEquals(0, $report->getTotalCount());
        $this->assertEquals(0, $report->getWarningCount());
        $this->assertEquals(0, $report->getErrorCount());
        $this->assertEquals(0, $report->getSuccessCount());
        $this->assertTrue($report->isValid());
        $this->assertEquals('Valid', $report->getWorkbookStatus());
    }

    public function test_adds_success_entry()
    {
        $report = new ValidationReport();
        $report->addSuccess('Rejomulyo', 'Manual', 5);

        $this->assertEquals(1, $report->getTotalCount());
        $this->assertEquals(1, $report->getSuccessCount());
        $this->assertEquals(0, $report->getWarningCount());
        $this->assertEquals(0, $report->getErrorCount());
        $this->assertTrue($report->isValid());

        $entries = $report->getEntries();
        $this->assertEquals('Rejomulyo', $entries[0]['market']);
        $this->assertEquals('Manual', $entries[0]['group']);
        $this->assertEquals(5, $entries[0]['row']);
        $this->assertEquals('OK', $entries[0]['status']);
    }

    public function test_adds_warning_entry()
    {
        $report = new ValidationReport();
        $report->addWarning('Pasar Tidak Ada', 'tidak ditemukan di template');

        $this->assertEquals(1, $report->getTotalCount());
        $this->assertEquals(0, $report->getSuccessCount());
        $this->assertEquals(1, $report->getWarningCount());
        $this->assertEquals(0, $report->getErrorCount());
        $this->assertTrue($report->isValid()); // warnings don't make it invalid
        $this->assertEquals('Valid (with warnings)', $report->getWorkbookStatus());

        $warnings = $report->getWarnings();
        $this->assertCount(1, $warnings);
        $this->assertEquals('tidak ditemukan di template', $warnings[0]['message']);
    }

    public function test_adds_error_entry()
    {
        $report = new ValidationReport();
        $report->addError('System', 'Terjadi kesalahan');

        $this->assertEquals(1, $report->getTotalCount());
        $this->assertEquals(0, $report->getSuccessCount());
        $this->assertEquals(0, $report->getWarningCount());
        $this->assertEquals(1, $report->getErrorCount());
        $this->assertFalse($report->isValid());
        $this->assertEquals('Invalid', $report->getWorkbookStatus());

        $errors = $report->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals('Terjadi kesalahan', $errors[0]['message']);
    }

    public function test_filters_entries_by_status()
    {
        $report = new ValidationReport();
        $report->addSuccess('Market A', 'Manual', 5);
        $report->addWarning('Market B', 'tidak ditemukan');
        $report->addError('System', 'error');
        $report->addSuccess('Market C', 'E-Retribusi', 24);

        $okEntries = $report->getEntriesByStatus('OK');
        $this->assertCount(2, $okEntries);

        $warningEntries = $report->getEntriesByStatus('WARNING');
        $this->assertCount(1, $warningEntries);

        $errorEntries = $report->getEntriesByStatus('ERROR');
        $this->assertCount(1, $errorEntries);
    }

    public function test_sets_date_and_sheet()
    {
        $report = new ValidationReport();
        $report->setDate('2026-07-21');
        $report->setSheet('21 Jul');

        $array = $report->toArray();
        $this->assertEquals('2026-07-21', $array['date']);
        $this->assertEquals('21 Jul', $array['sheet']);
    }

    public function test_merges_multiple_reports()
    {
        $report1 = new ValidationReport();
        $report1->addSuccess('Karimata 1', 'E-Retribusi', 24);

        $report2 = new ValidationReport();
        $report2->addWarning('Market X', 'tidak ditemukan');
        $report2->addSuccess('Dargo', 'Manual', 13);

        $report1->merge($report2);

        $this->assertEquals(3, $report1->getTotalCount());
        $this->assertEquals(2, $report1->getSuccessCount());
        $this->assertEquals(1, $report1->getWarningCount());
        $this->assertEquals(0, $report1->getErrorCount());
    }

    public function test_generates_text_report()
    {
        $report = new ValidationReport();
        $report->setDate('2026-07-21');
        $report->setSheet('21 Jul');
        $report->addSuccess('Rejomulyo', 'Manual', 5);
        $report->addWarning('Market X', 'tidak ditemukan di template');

        $text = $report->toText();

        $this->assertStringContainsString('VALIDATION REPORT', $text);
        $this->assertStringContainsString('2026-07-21', $text);
        $this->assertStringContainsString('21 Jul', $text);
        $this->assertStringContainsString('Rejomulyo', $text);
        $this->assertStringContainsString('Market X', $text);
        $this->assertStringContainsString('SUMMARY', $text);
        $this->assertStringContainsString('Total Markets: 2', $text);
        $this->assertStringContainsString('Warnings: 1', $text);
    }

    public function test_generates_array_report()
    {
        $report = new ValidationReport();
        $report->setDate('2026-07-21');
        $report->addSuccess('Karimata 1', 'E-Retribusi', 24);

        $array = $report->toArray();

        $this->assertArrayHasKey('date', $array);
        $this->assertArrayHasKey('summary', $array);
        $this->assertArrayHasKey('entries', $array);
        $this->assertArrayHasKey('warnings_list', $array);
        $this->assertArrayHasKey('errors_list', $array);
        $this->assertArrayHasKey('workbook_status', $array['summary']);
        $this->assertEquals('Valid', $array['summary']['workbook_status']);
        $this->assertEquals(1, $array['summary']['success']);
    }

    public function test_mixed_status_workbook_is_valid_with_warnings()
    {
        $report = new ValidationReport();
        $report->addSuccess('Rejomulyo', 'Manual', 5);
        $report->addWarning('Karimata 1', 'nilai kosong');

        $this->assertTrue($report->isValid()); // Still valid, just warnings
        $this->assertEquals('Valid (with warnings)', $report->getWorkbookStatus());
    }

    public function test_error_report_is_not_valid()
    {
        $report = new ValidationReport();
        $report->addError('Critical', 'Sheet tidak ditemukan');

        $this->assertFalse($report->isValid());
        $this->assertEquals('Invalid', $report->getWorkbookStatus());
    }
}

