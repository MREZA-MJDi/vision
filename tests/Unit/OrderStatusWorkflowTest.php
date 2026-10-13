<?php

namespace Tests\Unit;

use App\Models\Order;
use PHPUnit\Framework\TestCase;

class OrderStatusWorkflowTest extends TestCase
{
    public function test_pending_orders_only_allow_confirmation_or_cancellation(): void
    {
        $order = new Order(['status' => 'pending']);

        $this->assertSame(['confirmed', 'cancelled'], $order->allowedNextStatuses());
        $this->assertTrue($order->canTransitionTo('confirmed'));
        $this->assertTrue($order->canTransitionTo('pending'));
        $this->assertFalse($order->canTransitionTo('delivered'));
    }

    public function test_shipping_workflow_keeps_out_for_delivery_before_delivered(): void
    {
        $order = new Order(['status' => 'out_for_delivery']);

        $this->assertSame(['delivered', 'returned'], $order->allowedNextStatuses());
        $this->assertTrue($order->canTransitionTo('delivered'));
        $this->assertFalse($order->canTransitionTo('cancelled'));
    }

    public function test_terminal_orders_cannot_be_reopened(): void
    {
        foreach (['cancelled', 'returned'] as $status) {
            $order = new Order(['status' => $status]);

            $this->assertSame([], $order->allowedNextStatuses());
            $this->assertTrue($order->canTransitionTo($status));
            $this->assertFalse($order->canTransitionTo('pending'));
            $this->assertFalse($order->canTransitionTo('confirmed'));
        }
    }
}
