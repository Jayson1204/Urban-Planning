<?php

namespace App\Services;

/**
 * Rule-based conformity pre-screening: checks a proposed use and its numeric
 * project figures against the seeded zoning_use_regulations matrix. Kept
 * separate from ZoningClearanceService because this is the module's core
 * differentiator and benefits from being independently testable.
 */
class ZoningConformityService
{
    private $regulationRepo;

    public function __construct($regulationRepo)
    {
        $this->regulationRepo = $regulationRepo;
    }

    public function evaluate($zoneClassification, $useCategory, $proposedHeight, $proposedSetback, $proposedFar, $proposedLotOccupancy)
    {
        $regulation = $this->regulationRepo->findRegulation($zoneClassification, $useCategory);

        if (!$regulation) {
            return [
                'result' => 'Needs Manual Review',
                'notes' => "No zoning regulation is on file for {$useCategory} in {$zoneClassification}. A zoning officer must review this application manually.",
                'violations' => [],
            ];
        }

        if ($regulation['conformity'] === 'Prohibited') {
            return [
                'result' => 'Non-Conforming',
                'notes' => "{$useCategory} is a prohibited use in {$zoneClassification} ({$regulation['reference_note']}).",
                'violations' => ['use_category'],
            ];
        }

        $violations = [];
        $notes = [];

        if ($proposedHeight !== null && $regulation['max_height_m'] !== null && (float)$proposedHeight > (float)$regulation['max_height_m']) {
            $violations[] = 'height';
            $notes[] = "Proposed height {$proposedHeight}m exceeds the {$regulation['max_height_m']}m limit ({$regulation['reference_note']}).";
        }
        if ($proposedSetback !== null && $regulation['min_setback_m'] !== null && (float)$proposedSetback < (float)$regulation['min_setback_m']) {
            $violations[] = 'setback';
            $notes[] = "Proposed setback {$proposedSetback}m is below the {$regulation['min_setback_m']}m minimum ({$regulation['reference_note']}).";
        }
        if ($proposedFar !== null && $regulation['max_far'] !== null && (float)$proposedFar > (float)$regulation['max_far']) {
            $violations[] = 'floor_area_ratio';
            $notes[] = "Proposed floor area ratio {$proposedFar} exceeds the {$regulation['max_far']} limit ({$regulation['reference_note']}).";
        }
        if ($proposedLotOccupancy !== null && $regulation['max_lot_occupancy_pct'] !== null && (float)$proposedLotOccupancy > (float)$regulation['max_lot_occupancy_pct']) {
            $violations[] = 'lot_occupancy';
            $notes[] = "Proposed lot occupancy {$proposedLotOccupancy}% exceeds the {$regulation['max_lot_occupancy_pct']}% limit ({$regulation['reference_note']}).";
        }

        if (!empty($violations)) {
            return [
                'result' => 'Non-Conforming',
                'notes' => implode(' ', $notes),
                'violations' => $violations,
            ];
        }

        $conditionalNote = $regulation['conformity'] === 'Conditional'
            ? " This use is Conditional in {$zoneClassification} — additional conditions may apply ({$regulation['reference_note']})."
            : '';

        return [
            'result' => 'Conforming',
            'notes' => "Proposed project conforms to {$zoneClassification} standards for {$useCategory}.{$conditionalNote}",
            'violations' => [],
        ];
    }
}
