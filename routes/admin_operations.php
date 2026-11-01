<?php

use App\Http\Controllers\Admin\AcademicTermController;
use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\ApprovalQueueController;
use App\Http\Controllers\Admin\DocumentRequirementController;
use App\Http\Controllers\Admin\DocumentReviewController;
use App\Http\Controllers\Admin\PartnerCompanyController;
use App\Http\Controllers\Admin\ReportTemplateController;
use App\Http\Controllers\Admin\RiskDashboardController;
use App\Http\Controllers\Admin\StudentExportController;
use App\Http\Controllers\Admin\StudentImportController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Middleware\EnsureUserCapability;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'active', 'role:admin'])
    ->group(function (): void {
        Route::middleware(EnsureUserCapability::class.':academic.manage')->group(function (): void {
            Route::get('/academic-terms', [AcademicTermController::class, 'index'])->name('academic_terms.index');
            Route::post('/academic-terms', [AcademicTermController::class, 'store'])->name('academic_terms.store');
            Route::patch('/academic-terms/{academic_term}', [AcademicTermController::class, 'update'])->name('academic_terms.update');
            Route::delete('/academic-terms/{academic_term}', [AcademicTermController::class, 'destroy'])->name('academic_terms.destroy');
        });

        Route::middleware(EnsureUserCapability::class.':companies.manage')->group(function (): void {
            Route::get('/partner-companies', [PartnerCompanyController::class, 'index'])->name('partner_companies.index');
            Route::post('/partner-companies', [PartnerCompanyController::class, 'store'])->name('partner_companies.store');
            Route::patch('/partner-companies/{partner_company}', [PartnerCompanyController::class, 'update'])->name('partner_companies.update');
            Route::delete('/partner-companies/{partner_company}', [PartnerCompanyController::class, 'destroy'])->name('partner_companies.destroy');
        });

        Route::middleware(EnsureUserCapability::class.':documents.manage')->group(function (): void {
            Route::get('/document-requirements', [DocumentRequirementController::class, 'index'])->name('document_requirements.index');
            Route::post('/document-requirements', [DocumentRequirementController::class, 'store'])->name('document_requirements.store');
            Route::patch('/document-requirements/{document_requirement}', [DocumentRequirementController::class, 'update'])->name('document_requirements.update');
            Route::delete('/document-requirements/{document_requirement}', [DocumentRequirementController::class, 'destroy'])->name('document_requirements.destroy');
            Route::get('/document-reviews', [DocumentReviewController::class, 'index'])->name('document_reviews.index');
            Route::get('/document-reviews/{document}/download', [DocumentReviewController::class, 'download'])->name('document_reviews.download');
            Route::patch('/document-reviews/{document}', [DocumentReviewController::class, 'update'])->name('document_reviews.update');
        });

        Route::middleware(EnsureUserCapability::class.':reports.manage')->group(function (): void {
            Route::get('/report-templates', [ReportTemplateController::class, 'index'])->name('report_templates.index');
            Route::post('/report-templates', [ReportTemplateController::class, 'store'])->name('report_templates.store');
            Route::patch('/report-templates/{report_template}', [ReportTemplateController::class, 'update'])->name('report_templates.update');
            Route::delete('/report-templates/{report_template}', [ReportTemplateController::class, 'destroy'])->name('report_templates.destroy');
        });

        Route::middleware(EnsureUserCapability::class.':announcements.manage')->group(function (): void {
            Route::post('/announcements/{announcement}/publish', [AdminAnnouncementController::class, 'publish'])->name('announcements.publish');
            Route::post('/announcements/{announcement}/retry', [AdminAnnouncementController::class, 'retry'])->name('announcements.retry');
            Route::get('/announcements', [AdminAnnouncementController::class, 'index'])->name('announcements.index');
            Route::post('/announcements', [AdminAnnouncementController::class, 'store'])->name('announcements.store');
            Route::patch('/announcements/{announcement}', [AdminAnnouncementController::class, 'update'])->name('announcements.update');
            Route::delete('/announcements/{announcement}', [AdminAnnouncementController::class, 'destroy'])->name('announcements.destroy');
        });

        Route::middleware(EnsureUserCapability::class.':students.manage')->group(function (): void {
            Route::get('/student-imports', [StudentImportController::class, 'create'])->name('students.import');
            Route::post('/student-imports/preview', [StudentImportController::class, 'preview'])->name('students.import.preview');
            Route::post('/student-imports', [StudentImportController::class, 'store'])->name('students.import.store');
        });

        Route::get('/student-exports.csv', StudentExportController::class)
            ->middleware(EnsureUserCapability::class.':reports.view')
            ->name('students.export');
        Route::get('/risk-dashboard', RiskDashboardController::class)
            ->middleware(EnsureUserCapability::class.':risk.view')
            ->name('risk_dashboard');

        Route::middleware(EnsureUserCapability::class.':approvals.manage')->group(function (): void {
            Route::get('/approval-queue', [ApprovalQueueController::class, 'index'])->name('approval_queue');
            Route::patch('/approval-queue/submissions/{submission}', [ApprovalQueueController::class, 'reviewSubmission'])->name('approval_queue.submissions.review');
            Route::patch('/approval-queue/corrections/{correctionRequest}', [ApprovalQueueController::class, 'reviewCorrection'])->name('approval_queue.corrections.review');
        });
    });

Route::middleware(['auth', 'active', 'role:student'])->group(function (): void {
    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
});
