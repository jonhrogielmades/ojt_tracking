<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaAssetsTest extends TestCase
{
    public function test_manifest_declares_installable_app_and_exact_icons(): void
    {
        $manifestPath = public_path('manifest.webmanifest');
        $this->assertFileExists($manifestPath);

        $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('OJT Tracker for Students', $manifest['name']);
        $this->assertSame('/dashboard', $manifest['start_url']);
        $this->assertSame('/', $manifest['scope']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('#0f766e', $manifest['theme_color']);
        $this->assertSame('#f1f5f9', $manifest['background_color']);

        foreach ([192, 512] as $size) {
            $path = public_path("icons/icon-{$size}.png");
            $this->assertFileExists($path);
            $dimensions = getimagesize($path);
            $this->assertIsArray($dimensions);
            $this->assertSame($size, $dimensions[0]);
            $this->assertSame($size, $dimensions[1]);
        }
    }

    public function test_service_worker_uses_network_only_navigation_and_static_allowlist(): void
    {
        $serviceWorkerPath = public_path('sw.js');
        $offlinePagePath = public_path('offline.html');
        $this->assertFileExists($serviceWorkerPath);
        $this->assertFileExists($offlinePagePath);

        $source = (string) file_get_contents($serviceWorkerPath);

        $this->assertStringContainsString("fetch(request, { cache: 'no-store' })", $source);
        $this->assertStringContainsString("url.pathname.startsWith('/build/assets/')", $source);
        $this->assertStringContainsString("'/offline-drafts.js'", $source);

        foreach (['/dashboard', '/login', '/profile', '/dtr', '/activities', '/documents', '/reports', '/sync'] as $privateRoute) {
            $this->assertStringNotContainsString("'{$privateRoute}'", $source);
        }
    }

    public function test_offline_page_provides_dtr_and_activity_forms_using_shared_queue_script(): void
    {
        $offlinePagePath = public_path('offline.html');
        $offlineScriptPath = public_path('offline-drafts.js');
        $this->assertFileExists($offlinePagePath);
        $this->assertFileExists($offlineScriptPath);

        $page = (string) file_get_contents($offlinePagePath);
        $script = (string) file_get_contents($offlineScriptPath);

        $this->assertStringContainsString('<script defer src="/offline-drafts.js"></script>', $page);
        $this->assertStringContainsString('data-kind="dtr" data-sync-url="/sync/dtr"', $page);
        $this->assertStringContainsString('name="work_date"', $page);
        $this->assertStringContainsString('name="time_in"', $page);
        $this->assertStringContainsString('name="time_out"', $page);
        $this->assertStringContainsString('data-kind="activity" data-sync-url="/sync/activities"', $page);
        $this->assertStringContainsString('name="activity_date"', $page);
        $this->assertStringContainsString('name="tasks_completed"', $page);

        $this->assertStringContainsString("const DB_NAME = 'ojt-tracker-offline';", $script);
        $this->assertStringContainsString("const STORE_NAME = 'drafts';", $script);
        $this->assertStringContainsString("const ACTIVE_STUDENT_STORAGE_KEY = 'ojt-tracker-active-student-key';", $script);
        $this->assertStringContainsString("keyPath: 'idempotencyKey'", $script);
        $this->assertStringContainsString("store.createIndex('studentKey', 'studentKey'", $script);
    }

    public function test_authenticated_app_clears_active_student_key_before_allowed_logout_submission(): void
    {
        $appScriptPath = resource_path('js/app.js');
        $this->assertFileExists($appScriptPath);

        $source = (string) file_get_contents($appScriptPath);

        $this->assertStringContainsString("const ACTIVE_STUDENT_STORAGE_KEY = 'ojt-tracker-active-student-key';", $source);
        $this->assertStringContainsString('localStorage.removeItem(ACTIVE_STUDENT_STORAGE_KEY);', $source);
        $this->assertMatchesRegularExpression(
            '/function submitLogout\(\).*?forgetActiveStudentKey\(\);.*?logoutForm\.requestSubmit\(\);/s',
            $source,
        );
        $this->assertMatchesRegularExpression(
            '/if \(drafts\.length === 0\).*?forgetActiveStudentKey\(\);.*?form\.requestSubmit\(\);/s',
            $source,
        );
    }

    public function test_create_form_script_queues_only_text_payloads_when_network_delivery_fails(): void
    {
        $appScriptPath = resource_path('js/app.js');
        $activityViewPath = resource_path('views/activities/create.blade.php');
        $dtrViewPath = resource_path('views/dtr/create.blade.php');
        $this->assertFileExists($appScriptPath);
        $this->assertFileExists($activityViewPath);
        $this->assertFileExists($dtrViewPath);

        $source = (string) file_get_contents($appScriptPath);
        $textFormSource = $this->sourceBetween($source, 'async function submitTextForm', 'async function submitAttachmentForm');
        $attachmentFormSource = $this->sourceBetween($source, 'async function submitAttachmentForm', 'async function syncDraft');
        $activityView = (string) file_get_contents($activityViewPath);
        $dtrView = (string) file_get_contents($dtrViewPath);

        $this->assertStringContainsString('response = await fetch(form.dataset.syncUrl', $textFormSource);
        $this->assertMatchesRegularExpression('/catch\s*\{\s*await queueOfflinePayload\(form, payload\);/s', $textFormSource);
        $this->assertMatchesRegularExpression('/if \(!navigator\.onLine\)\s*\{\s*await queueOfflinePayload\(form, payload\);/s', $source);

        $this->assertStringContainsString('response = await fetch(form.action', $attachmentFormSource);
        $this->assertStringContainsString('body: new FormData(form)', $attachmentFormSource);
        $this->assertStringContainsString('Your form and selected file are still here', $attachmentFormSource);
        $this->assertStringNotContainsString('queueOfflinePayload', $attachmentFormSource);
        $this->assertStringNotContainsString('form.reset', $attachmentFormSource);

        $this->assertStringContainsString('enctype="multipart/form-data"', $activityView);
        $this->assertStringContainsString('data-success-url="{{ route(\'activities.index\') }}"', $activityView);
        $this->assertStringContainsString('data-success-url="{{ route(\'dtr.index\') }}"', $dtrView);
    }

    private function sourceBetween(string $source, string $startMarker, string $endMarker): string
    {
        $start = strpos($source, $startMarker);
        $end = $start === false ? false : strpos($source, $endMarker, $start);

        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        return substr($source, $start, $end - $start);
    }
}
