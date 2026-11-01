<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class MobileNavigationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_mobile_shell_renders_accessible_icon_navigation_and_top_bar(): void
    {
        $this->withoutVite();
        $student = User::factory()->create();

        $response = $this->actingAs($student)->get(route('dashboard'));

        $response->assertOk();
        $xpath = $this->xpath((string) $response->getContent());
        $this->assertIconNavigation(
            $xpath,
            'Mobile navigation',
            ['Home', 'DTR', 'Logs', 'Submit', 'More'],
        );
        $this->assertStudentTopBar($xpath);
    }

    public function test_administrator_mobile_shell_renders_accessible_icon_navigation_and_top_bar(): void
    {
        $this->withoutVite();
        $administrator = User::factory()->superAdmin()->create();

        $response = $this->actingAs($administrator)->get(route('admin.dashboard'));

        $response->assertOk();
        $xpath = $this->xpath((string) $response->getContent());
        $this->assertIconNavigation(
            $xpath,
            'Mobile admin navigation',
            ['Overview', 'Alerts', 'Students', 'More'],
        );
        $this->assertAdministratorTopBar($xpath);
    }

    private function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        $previousErrorHandling = libxml_use_internal_errors(true);

        try {
            $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorHandling);
        }

        return new DOMXPath($document);
    }

    /**
     * @param  list<string>  $expectedLabels
     */
    private function assertIconNavigation(DOMXPath $xpath, string $ariaLabel, array $expectedLabels): void
    {
        $navigations = $xpath->query(sprintf('//nav[@aria-label="%s"]', $ariaLabel));
        $this->assertNotFalse($navigations);

        $navigation = $navigations->item(0);
        $this->assertInstanceOf(DOMElement::class, $navigation);

        $items = $xpath->query('./a | ./details/summary', $navigation);
        $this->assertNotFalse($items);
        $this->assertSame(count($expectedLabels), $items->length);

        $actualLabels = [];
        foreach ($items as $index => $item) {
            $this->assertInstanceOf(DOMElement::class, $item);
            $actualLabels[] = trim((string) preg_replace('/\s+/', ' ', $item->textContent));

            $icons = $xpath->query('.//svg[@aria-hidden="true"]', $item);
            $this->assertNotFalse($icons);
            $this->assertSame(1, $icons->length);
            $this->assertSame($index === 0 ? 'page' : '', $item->getAttribute('aria-current'));
        }

        $this->assertSame($expectedLabels, $actualLabels);
    }

    private function assertStudentTopBar(DOMXPath $xpath): void
    {
        $topBars = $xpath->query('//header[@aria-label="Student top bar"]');
        $this->assertNotFalse($topBars);

        $topBar = $topBars->item(0);
        $this->assertInstanceOf(DOMElement::class, $topBar);
        $this->assertSame('Your OJT progress', trim((string) $xpath->evaluate('string(.//h1)', $topBar)));

        $accessibilityControls = $xpath->query('.//summary[@aria-label="Accessibility options"]', $topBar);
        $this->assertNotFalse($accessibilityControls);
        $accessibilityControl = $accessibilityControls->item(0);
        $this->assertInstanceOf(DOMElement::class, $accessibilityControl);
        $this->assertSame(1, $xpath->query('.//svg[@aria-hidden="true"]', $accessibilityControl)?->length);

        $draftControls = $xpath->query('.//button[@aria-label="Open pending drafts"]', $topBar);
        $this->assertNotFalse($draftControls);
        $draftControl = $draftControls->item(0);
        $this->assertInstanceOf(DOMElement::class, $draftControl);
        $this->assertSame(1, $xpath->query('.//svg[@aria-hidden="true"]', $draftControl)?->length);

        $connectionStatuses = $xpath->query('.//*[@id="connection-status" and @role="status" and @aria-live="polite"]', $topBar);
        $this->assertNotFalse($connectionStatuses);
        $this->assertSame(1, $connectionStatuses->length);
    }

    private function assertAdministratorTopBar(DOMXPath $xpath): void
    {
        $topBars = $xpath->query('//header[@aria-label="Administrator top bar"]');
        $this->assertNotFalse($topBars);

        $topBar = $topBars->item(0);
        $this->assertInstanceOf(DOMElement::class, $topBar);
        $this->assertSame('Administration overview', trim((string) $xpath->evaluate('string(.//h1)', $topBar)));

        $addStudentLinks = $xpath->query('.//a[@aria-label="Add student"]', $topBar);
        $this->assertNotFalse($addStudentLinks);
        $addStudentLink = $addStudentLinks->item(0);
        $this->assertInstanceOf(DOMElement::class, $addStudentLink);
        $this->assertSame(route('admin.students.create'), $addStudentLink->getAttribute('href'));
        $this->assertSame(1, $xpath->query('.//svg[@aria-hidden="true"]', $addStudentLink)?->length);
    }
}
