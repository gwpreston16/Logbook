<?php

declare(strict_types=1);

namespace Logbook\Tests\Integration\Ai\Ask\Tools;

use Logbook\Domain\Access\VehicleAbility;
use Logbook\Domain\Compliance\ComplianceType;
use Logbook\Service\Ai\Ask\ToolKit;
use Logbook\Tests\Support\JsonDoc;

/**
 * `coming_up` (spec.md §7.26): *Coming up*'s items within the horizon.
 */
final class ComingUpToolTest extends ToolsBTestCase
{
    public function testSchemaAndTheRenewalWithLastTimesCost(): void
    {
        [$app, $owner] = $this->askApp();
        $golf = $this->vehicle($app);
        $this->document($app, $golf, ComplianceType::Insurance, '2025-11-21', '2026-11-20', '312.40', 'Admiral');
        $this->assertSchemaAccepts($app, $owner, 'coming_up', ['vehicles' => [$golf->id], 'horizon_months' => 3]);

        $result = $this->toolResult($app, $owner, 'coming_up', ['vehicles' => [$golf->id]]);
        $data = new JsonDoc($result->data);

        self::assertSame(1, $data->int('total_count'));
        $item = $data->doc('items', 0);
        self::assertSame('2026-11-20', $item->get('due_on'));
        self::assertSame('20 Nov 2026', $item->get('due_on_display'));
        self::assertSame(['amount' => '312.40', 'currency' => 'GBP', 'display' => '£312.40'], $item->get('last_cost'));
        self::assertStringContainsString('Insurance', $item->string('what'));
        self::assertSame('/upcoming?vehicle=' . $golf->id, $result->link);
        self::assertSame([$golf->id], $result->vehicleIds);
        self::assertSame('Coming up · Volkswagen Golf · next 12 months', $result->source);
    }

    public function testTheHorizonLeavesLaterItemsOut(): void
    {
        [$app, $owner] = $this->askApp();
        $golf = $this->vehicle($app);
        $this->document($app, $golf, ComplianceType::Insurance, '2025-11-21', '2026-11-20', '312.40');

        $data = $this->data($app, $owner, 'coming_up', ['horizon_months' => 1]);

        self::assertSame(0, $data->int('total_count'));
        self::assertSame('2026-10-31', $data->get('horizon_end'), 'calendar months, as the page (#381)');
    }

    public function testTheNextThreeMonthsTotalCoversTheSameItems(): void
    {
        [$app, $owner] = $this->askApp();
        $golf = $this->vehicle($app);
        $this->document($app, $golf, ComplianceType::Insurance, '2026-01-01', '2026-12-31', '312.40');
        $this->document($app, $golf, ComplianceType::Inspection, '2026-01-02', '2027-01-01', '54.85');

        $result = $this->toolResult($app, $owner, 'coming_up', ['horizon_months' => 3]);
        $data = new JsonDoc($result->data);

        self::assertSame(1, $data->int('total_count'), '1 Jan is the fourth month');
        self::assertSame('2026-12-31', $data->get('horizon_end'));
        $soon = $data->doc('next_3_months', 0);
        self::assertSame('GBP', $soon->get('currency'));
        self::assertSame(['amount' => '312.40', 'currency' => 'GBP', 'display' => '£312.40'], $soon->get('total'));
        self::assertNull($soon->get('fuel'));
        self::assertFalse($soon->get('at_least'));
        self::assertSame(0, $soon->int('items_without_cost'));
        self::assertSame('about £312.40', $soon->get('display'));
        self::assertSame('about £312.40', $result->figures[0]);
    }

    public function testWithoutViewCostsTheCostIsLeftOut(): void
    {
        [$app, $owner] = $this->askApp();
        $access = $this->policy($app);
        $golf = $this->vehicle($app);
        $this->document($app, $golf, ComplianceType::Insurance, '2025-11-21', '2026-11-20', '312.40');
        $access->except($golf, VehicleAbility::ViewCosts);

        $result = $this->toolResult($app, $owner, 'coming_up');
        $data = new JsonDoc($result->data);

        self::assertFalse($data->has('items', 0, 'last_cost'));
        self::assertStringNotContainsString('0.00', $result->figures[0] ?? '', 'no "at least £0.00" figure');
        self::assertStringNotContainsString('312', self::json($data));
        self::assertTrue($data->get('next_3_months', 0, 'at_least'), 'counted as no known cost (#383)');
        self::assertSame(1, $data->int('next_3_months', 0, 'items_without_cost'));
        self::assertNull($data->get('next_3_months', 0, 'display'), 'as the page\'s "—" (#384)');
    }

    public function testAnotherUsersVehicleIsNotFound(): void
    {
        [$app] = $this->askApp();
        $golf = $this->vehicle($app);
        $partner = $this->createMember($app);

        $run = $this->call($app, $partner, 'coming_up', ['vehicles' => [$golf->id]]);

        self::assertSame(ToolKit::NOT_FOUND, $run->error);
        self::assertNull($run->result);
    }
}
