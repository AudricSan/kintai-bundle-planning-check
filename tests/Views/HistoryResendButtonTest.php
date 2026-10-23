<?php

declare(strict_types=1);

namespace kintai\Tests\Bundles\PlanningCheck\Views;

use kintai\Core\Container;
use kintai\Core\Router;
use kintai\UI\ViewRenderer;
use PHPUnit\Framework\TestCase;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 4) . '/Kintai');
}

/**
 * Historique des validations : le renvoi d'un PDF régénéré est possible dès que le planning est validé, pas seulement
 * quand le bundle a détecté un changement (une modification d'horaire doit pouvoir être renvoyée à la demande).
 */
final class HistoryResendButtonTest extends TestCase
{
    private ViewRenderer $view;

    protected function setUp(): void
    {
        $this->view = new ViewRenderer(dirname(__DIR__, 2) . '/Views');
        $this->view->addNamespace('planning-check', dirname(__DIR__, 2) . '/Views');
        $router = new Router();
        $router->post('/s/{id}/{cid}/resend', [\stdClass::class, 'x'], name: 'admin.planning_check.resend');
        $router->get('/s/{id}/{cid}/pdf', [\stdClass::class, 'x'], name: 'admin.planning_check.pdf');
        $router->get('/s/{id}/settings', [\stdClass::class, 'x'], name: 'admin.planning_check.settings');
        $router->post('/s/{id}/send-now', [\stdClass::class, 'x'], name: 'admin.planning_check.send_now');
        $router->post('/s/{id}/reopen', [\stdClass::class, 'x'], name: 'admin.planning_check.reopen');
        Container::getInstance()->instance(Router::class, $router);
        $_GET = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
    }

    /** @param array<string, mixed> $check */
    private function render(array $check, bool $canApprove = true): string
    {
        $check += [
            'id' => 11, 'target_date' => '2026-10-22', 'status' => 'validated', 'reject_reason' => null,
            'submitted_by' => null, 'submitted_at' => null, 'validated_by' => null, 'validated_at' => null,
            'send_status' => 'sent', 'sent_at' => '2026-10-21 18:00:00', 'needs_resend' => false,
        ];

        return $this->view->renderPartial('planning-check::history', [
            'title' => 'Historique', 'store' => ['id' => 3, 'name' => 'Shibuya'], 'checks' => [$check], 'names' => [],
            'highlight' => 0, 'can_approve' => $canApprove, 'can_settings' => false, 'settings' => ['enabled' => true],
        ]);
    }

    /** @param array<string, mixed>|null $tomorrowCheck */
    private function renderTomorrow(?array $tomorrowCheck, int $count = 2, bool $canApprove = true, bool $enabled = true, bool $withTomorrow = true): string
    {
        return $this->view->renderPartial('planning-check::history', [
            'title' => 'Historique', 'store' => ['id' => 3, 'name' => 'Shibuya'], 'checks' => [], 'names' => [],
            'highlight' => 0, 'can_approve' => $canApprove, 'can_settings' => false, 'settings' => ['enabled' => $enabled],
        ] + ($withTomorrow ? ['tomorrow' => ['date' => '2026-10-22', 'check' => $tomorrowCheck, 'count' => $count]] : []));
    }

    public function testTomorrowCardOffersSendNowEvenWhenNoCheckExistsYet(): void
    {
        $html = $this->renderTomorrow(null);

        $this->assertStringContainsString('/s/3/send-now"', $html);
        $this->assertStringContainsString('name="date" value="2026-10-22"', $html);
        $this->assertStringContainsString('planning_check_send_now<', $html);
        $this->assertStringNotContainsString('/s/3/reopen"', $html, 'rien à rouvrir tant que rien n\'est soumis');
        $this->assertStringNotContainsString('name="confirm_empty"', $html);
    }

    public function testTomorrowCardOffersToRedoTheCheckOnceSubmittedOrValidated(): void
    {
        foreach (['submitted', 'validated'] as $status) {
            $html = $this->renderTomorrow(['status' => $status]);

            $this->assertStringContainsString('/s/3/reopen"', $html, $status);
            $this->assertStringContainsString('/s/3/send-now"', $html, $status);
        }
        $this->assertStringContainsString('planning_check_send_now_again', $this->renderTomorrow(['status' => 'validated']));
    }

    public function testTomorrowCardWithoutShiftAsksForAStrongerConfirmation(): void
    {
        $html = $this->renderTomorrow(null, count: 0);

        $this->assertStringContainsString('name="confirm_empty" value="1"', $html);
        $this->assertStringContainsString('planning_check_send_now_confirm_empty', $html);
    }

    public function testTomorrowCardIsHiddenWithoutApprovalOrWhenDisabled(): void
    {
        $this->assertStringNotContainsString('/send-now"', $this->renderTomorrow(null, canApprove: false));
        $this->assertStringNotContainsString('/send-now"', $this->renderTomorrow(null, enabled: false));
        $this->assertStringNotContainsString('/send-now"', $this->renderTomorrow(null, withTomorrow: false), 'la vue reste utilisable sans ces données');
    }

    public function testAValidatedCheckCanBeRegeneratedAndResentEvenWithoutDetectedChange(): void
    {
        $html = $this->render([]);

        $this->assertStringContainsString('/s/3/11/resend"', $html);
        $this->assertStringContainsString('planning_check_resend_new<', $html);
        $this->assertStringContainsString('data-confirm=', $html, 'confirmation avant un envoi à la demande');
        $this->assertStringContainsString('btn--ghost btn--sm">planning_check_resend_new<', $html);
    }

    public function testADetectedChangeKeepsThePrimaryButtonWithoutConfirmation(): void
    {
        $html = $this->render(['needs_resend' => true]);

        $this->assertStringContainsString('btn--primary btn--sm', $html);
        $this->assertStringContainsString('planning_check_resend_updated', $html);
        $this->assertStringNotContainsString('planning_check_resend_new<', $html);
        $this->assertStringNotContainsString('data-confirm=', $html);
    }

    public function testAFailedSendIsRetriedWithThePrimaryButton(): void
    {
        $html = $this->render(['send_status' => 'failed', 'needs_resend' => true]);

        $this->assertStringContainsString('planning_check_retry', $html);
    }

    public function testNoButtonWithoutTheApprovePermission(): void
    {
        $this->assertStringNotContainsString('/resend"', $this->render([], canApprove: false));
    }

    public function testNoButtonUntilTheCheckIsValidated(): void
    {
        foreach (['pending', 'submitted', 'rejected', 'escalated'] as $status) {
            $this->assertStringNotContainsString('/resend"', $this->render(['status' => $status, 'send_status' => null]), $status);
        }
    }
}
