<?php

namespace Rm\VirtualTryOn\Services;

use InvalidArgumentException;
use Rm\VirtualTryOn\Support\VirtualTryOnConfig;

/** Pure composition logic. Database access and trusted product data stay in the host app. */
final class CompositionBuilder
{
    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    public function build(string $audience, array $items): array
    {
        if (! VirtualTryOnConfig::isAudience($audience)) {
            throw new InvalidArgumentException('Unsupported try-on audience.');
        }

        $seenSlots = [];
        foreach ($items as &$item) {
            $slot = (string) ($item['slot'] ?? '');
            if (! VirtualTryOnConfig::isSlot($slot)) {
                throw new InvalidArgumentException('Unsupported try-on slot.');
            }
            if (isset($seenSlots[$slot])) {
                throw new InvalidArgumentException('Only one selected variant is allowed per slot.');
            }
            if (($item['audience'] ?? null) !== $audience) {
                throw new InvalidArgumentException('A selected item does not match the active mannequin.');
            }
            if (! isset($item['layer_url']) || trim((string) $item['layer_url']) === '') {
                throw new InvalidArgumentException('A valid transparent layer is required.');
            }

            $seenSlots[$slot] = true;
            $item['z_index'] = VirtualTryOnConfig::zIndex($slot);
        }
        unset($item);

        usort($items, static function (array $left, array $right): int {
            return ($left['z_index'] <=> $right['z_index'])
                ?: strcmp((string) $left['slot'], (string) $right['slot']);
        });

        return array_values($items);
    }
}
