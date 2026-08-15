<?php

namespace App\Services;

class ZoningClearanceService
{
    private $clearanceRepo;
    private $residentRepo;
    private $conformityService;

    private const ZONE_CLASSIFICATIONS = [
        'Residential-1', 'Residential-2', 'Commercial-1', 'Commercial-2',
        'Institutional', 'Industrial', 'Agricultural', 'Open Space',
    ];
    private const USE_CATEGORIES = [
        'Residential Dwelling', 'Home Occupation', 'Commercial Establishment',
        'Light Industrial', 'Heavy Industrial', 'Institutional', 'Agricultural',
    ];
    private const CLEARANCE_STATUSES = ['Submitted', 'Under Review', 'Returned for Revision', 'Approved', 'Denied', 'Cancelled'];
    private const OPEN_CLEARANCE_STATUSES = ['Submitted', 'Under Review', 'Returned for Revision'];
    private const PAYMENT_STATUSES = ['Unpaid', 'Paid', 'Waived'];

    // Fee schedule (documented constants; no admin UI yet, same precedent as
    // beneficiary eligibility scoring weights): base fee + rate per sqm by use.
    private const BASE_FEE = 500.00;
    private const RATE_PER_SQM = [
        'Residential Dwelling' => 15.00,
        'Home Occupation' => 20.00,
        'Commercial Establishment' => 35.00,
        'Light Industrial' => 40.00,
        'Heavy Industrial' => 60.00,
        'Institutional' => 25.00,
        'Agricultural' => 10.00,
    ];

    public function __construct($clearanceRepo, $residentRepo, $conformityService)
    {
        $this->clearanceRepo = $clearanceRepo;
        $this->residentRepo = $residentRepo;
        $this->conformityService = $conformityService;
    }

    public function validateClearanceInput($input, $isUpdate = false, $excludeId = null)
    {
        $errors = [];

        if (!$isUpdate || array_key_exists('resident_id', $input)) {
            $residentId = (int)($input['resident_id'] ?? 0);
            if (!$residentId) {
                $errors[] = 'An applicant (resident) must be selected.';
            } elseif (!$this->residentRepo->find($residentId)) {
                $errors[] = 'Selected applicant does not exist.';
            }
        }
        if (!$isUpdate || array_key_exists('zone_classification', $input)) {
            if (empty($input['zone_classification']) || !in_array($input['zone_classification'], self::ZONE_CLASSIFICATIONS, true)) {
                $errors[] = 'A valid zone classification must be selected.';
            }
        }
        if (!$isUpdate || array_key_exists('use_category', $input)) {
            if (empty($input['use_category']) || !in_array($input['use_category'], self::USE_CATEGORIES, true)) {
                $errors[] = 'A valid proposed use category must be selected.';
            }
        }
        foreach (['lot_area_sqm', 'proposed_height_m', 'proposed_setback_m', 'proposed_far', 'proposed_lot_occupancy_pct'] as $numericField) {
            if (isset($input[$numericField]) && $input[$numericField] !== '' && !is_numeric($input[$numericField])) {
                $errors[] = str_replace('_', ' ', $numericField) . ' must be a number.';
            }
        }
        if (!empty($input['payment_status']) && !in_array($input['payment_status'], self::PAYMENT_STATUSES, true)) {
            $errors[] = 'Invalid payment status value.';
        }

        return $errors;
    }

    public function createClearance($input)
    {
        $data = $this->sanitizeFields($input);
        $data['reference_number'] = $this->generateReferenceNumber();
        if (empty($data['application_date'])) {
            $data['application_date'] = date('Y-m-d');
        }
        if (empty($data['payment_status'])) {
            $data['payment_status'] = 'Unpaid';
        }
        $data['clearance_status'] = 'Submitted';
        $data['status'] = 'Active';
        $data['fee_amount'] = $this->computeFee($data['use_category'] ?? null, $data['lot_area_sqm'] ?? null);

        $conformity = $this->conformityService->evaluate(
            $data['zone_classification'] ?? null,
            $data['use_category'] ?? null,
            $data['proposed_height_m'] ?? null,
            $data['proposed_setback_m'] ?? null,
            $data['proposed_far'] ?? null,
            $data['proposed_lot_occupancy_pct'] ?? null
        );
        $data['conformity_result'] = $conformity['result'];
        $data['conformity_notes'] = $conformity['notes'];

        $newId = $this->clearanceRepo->create($data);
        $this->clearanceRepo->addReview($newId, $this->currentActorName(), 'Applicant/Front Desk', 'Submitted', 'Application submitted.');
        return $newId;
    }

    public function updateClearance($clearanceId, $input)
    {
        $existing = $this->clearanceRepo->find($clearanceId);
        $data = $this->sanitizeFields($input, true);

        $merged = array_merge($existing ?? [], $data);
        $data['fee_amount'] = $this->computeFee($merged['use_category'] ?? null, $merged['lot_area_sqm'] ?? null);

        $conformity = $this->conformityService->evaluate(
            $merged['zone_classification'] ?? null,
            $merged['use_category'] ?? null,
            $merged['proposed_height_m'] ?? null,
            $merged['proposed_setback_m'] ?? null,
            $merged['proposed_far'] ?? null,
            $merged['proposed_lot_occupancy_pct'] ?? null
        );
        $data['conformity_result'] = $conformity['result'];
        $data['conformity_notes'] = $conformity['notes'];

        $this->clearanceRepo->update($clearanceId, $data);
        return true;
    }

    /**
     * Move a clearance to a new status, always writing a review-log entry so
     * routing stays auditable. Approving a Non-Conforming application is
     * allowed but requires explicit remarks (treated as a documented variance).
     */
    public function transitionStatus($clearanceId, $newStatus, $remarks, $reviewerRole = null)
    {
        if (!in_array($newStatus, self::CLEARANCE_STATUSES, true)) {
            return ['error' => 'Invalid clearance status value.'];
        }
        $remarks = trim((string)$remarks);
        if ($remarks === '') {
            return ['error' => 'Remarks are required when changing a clearance status.'];
        }

        $existing = $this->clearanceRepo->find($clearanceId);
        if (!$existing) {
            return ['error' => 'Clearance not found.'];
        }

        // Approving a Non-Conforming case is allowed (a documented variance) since
        // remarks are already required above for every transition.
        $update = ['clearance_status' => $newStatus];
        if ($newStatus === 'Approved') {
            $update['approved_date'] = date('Y-m-d');
            $update['verification_code'] = $this->generateVerificationCode();
        }
        $this->clearanceRepo->update($clearanceId, $update);
        $this->clearanceRepo->addReview($clearanceId, $this->currentActorName(), $reviewerRole, $newStatus, $remarks);

        return ['success' => true];
    }

    private function computeFee($useCategory, $lotAreaSqm)
    {
        $rate = self::RATE_PER_SQM[$useCategory] ?? 20.00;
        $area = is_numeric($lotAreaSqm) ? (float)$lotAreaSqm : 0.0;
        return round(self::BASE_FEE + ($area * $rate), 2);
    }

    private function generateReferenceNumber()
    {
        $year = date('Y');
        $sequence = $this->clearanceRepo->countForYear($year) + 1;
        do {
            $candidate = sprintf('ZC-%s-%06d', $year, $sequence);
            $sequence++;
        } while ($this->clearanceRepo->referenceExists($candidate));
        return $candidate;
    }

    private function generateVerificationCode()
    {
        return strtoupper(bin2hex(random_bytes(5)));
    }

    private function currentActorName()
    {
        global $headerUser;
        return $headerUser['full_name'] ?? 'System User';
    }

    private function sanitizeFields($input, $partial = false)
    {
        $allowed = [
            'resident_id', 'zone_classification', 'use_category', 'project_description',
            'barangay', 'street_address', 'lot_area_sqm', 'proposed_height_m',
            'proposed_setback_m', 'proposed_far', 'proposed_lot_occupancy_pct',
            'payment_status', 'application_date',
        ];

        $data = [];
        foreach ($allowed as $field) {
            if ($partial && !array_key_exists($field, $input)) {
                continue;
            }
            $value = $input[$field] ?? null;
            if (is_string($value)) {
                $value = trim($value);
            }
            $data[$field] = ($value === '') ? null : $value;
        }
        return $data;
    }
}
