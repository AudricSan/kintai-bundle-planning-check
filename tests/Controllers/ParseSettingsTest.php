<?php

declare(strict_types=1);

namespace kintai\Tests\Bundles\PlanningCheck\Controllers;

use kintai\Bundles\Installed\PlanningCheck\Controllers\Web\PlanningCheckController;
use PHPUnit\Framework\TestCase;

/** Validation des réglages saisis : heures, jours, destinataires. */
final class ParseSettingsTest extends TestCase
{
    private const VALID = ['enabled' => '1', 'window_start_time' => '07:00', 'deadline_time' => '11:30', 'weekdays' => ['1', '2', '3'],
        'mail_recipients' => "boss@example.com\n other@example.com, boss@EXAMPLE.com;", 'include_open_shifts' => '1'];

    public function testValidSettingsAreNormalised(): void
    {
        [$settings, $errors] = PlanningCheckController::parseSettings(self::VALID);

        $this->assertSame([], $errors);
        $this->assertSame(['enabled' => true, 'deadline_time' => '11:30', 'window_start_time' => '07:00', 'weekdays' => [1, 2, 3],
            'mail_recipients' => ['boss@example.com', 'other@example.com'], 'include_open_shifts' => true], $settings, 'doublons (casse comprise) retirés');
    }

    public function testUncheckedBoxesMeanFalse(): void
    {
        [$settings] = PlanningCheckController::parseSettings(['window_start_time' => '07:00', 'deadline_time' => '11:30', 'weekdays' => ['1']]);

        $this->assertFalse($settings['enabled']);
        $this->assertFalse($settings['include_open_shifts']);
    }

    public function testEachDefectIsReported(): void
    {
        $errors = fn(array $override): array => PlanningCheckController::parseSettings($override + self::VALID)[1];

        $this->assertSame(['planning_check_err_time_invalid'], $errors(['deadline_time' => '25:00']));
        $this->assertSame(['planning_check_err_window_order'], $errors(['window_start_time' => '12:00', 'deadline_time' => '12:00']));
        $this->assertSame(['planning_check_err_weekdays'], $errors(['weekdays' => ['0', '8', 'x']]));
        $this->assertSame(['planning_check_err_recipient_invalid'], $errors(['mail_recipients' => 'boss@example.com, not-an-email']));
        $this->assertSame(['planning_check_err_recipients_required'], $errors(['mail_recipients' => '']));
        $this->assertSame(['planning_check_err_recipients_max'], $errors(['mail_recipients' => implode(',', array_map(fn(int $i): string => "u$i@example.com", range(1, 11)))]));
    }

    public function testRecipientsAreOptionalWhenTheCheckIsDisabled(): void
    {
        [, $errors] = PlanningCheckController::parseSettings(['enabled' => '0', 'window_start_time' => '07:00', 'deadline_time' => '11:30', 'weekdays' => ['1'], 'mail_recipients' => '']);

        $this->assertSame([], $errors);
    }
}
