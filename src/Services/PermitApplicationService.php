<?php

namespace App\Services;

class PermitApplicationService
{
    private $applicationRepo;
    private $residentRepo;

    private const APPLICATION_TYPES = ['Subdivision Plan', 'Building Permit'];
    private const APPLICATION_STATUSES = ['Submitted', 'Under Review', 'Returned for Revision', 'Approved', 'Permit Issued', 'Denied', 'Cancelled'];
    private const PAYMENT_STATUSES = ['Unpaid', 'Paid', 'Waived'];

    public const DISCIPLINES = ['Architectural', 'Structural', 'Sanitary', 'Electrical', 'Fire Safety'];
    private const DISCIPLINE_STATUSES = ['Pending', 'Under Review', 'Returned for Revision', 'Approved', 'Rejected'];

    // Fee schedule (documented constants, no admin UI yet; same precedent as
    // zoning clearance fees and beneficiary scoring weights).
    private const BASE_FEE = [
        'Subdivision Plan' => 2000.00,
        'Building Permit' => 1000.00,
    ];
    private const RATE_PER_SQM = [
        'Subdivision Plan' => 25.00, // applied to lot_area_sqm
        'Building Permit' => 45.00,  // applied to floor_area_sqm
    ];

    private const REFERENCE_PREFIX = [
        'Subdivision Plan' => 'SP',
        'Building Permit' => 'BP',
    ];

    public function __construct($applicationRepo, $residentRepo)
    {
        $this->applicationRepo = $applicationRepo;
        $this->residentRepo = $residentRepo;
    }

    public function validateApplicationInput($input, $isUpdate = false)
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
        if (!$isUpdate) {
            if (empty($input['application_type']) || !in_array($input['application_type'], self::APPLICATION_TYPES, true)) {
                $errors[] = 'A valid application type must be selected.';
            }
        }
        if (empty($input['project_name']) && (!$isUpdate || array_key_exists('project_name', $input))) {
            $errors[] = 'Project name is required.';
        }
        foreach (['lot_area_sqm', 'floor_area_sqm', 'estimated_project_cost'] as $numericField) {
            if (isset($input[$numericField]) && $input[$numericField] !== '' && !is_numeric($input[$numericField])) {
                $errors[] = str_replace('_', ' ', $numericField) . ' must be a number.';
            }
        }
        foreach (['number_of_storeys', 'number_of_lots'] as $intField) {
            if (isset($input[$intField]) && $input[$intField] !== '' && !ctype_digit((string)$input[$intField])) {
                $errors[] = str_replace('_', ' ', $intField) . ' must be a whole number.';
            }
        }
        if (!empty($input['payment_status']) && !in_array($input['payment_status'], self::PAYMENT_STATUSES, true)) {
            $errors[] = 'Invalid payment status value.';
        }

        return $errors;
    }

    public function createApplication($input)
    {
        $data = $this->sanitizeFields($input);
        $data['application_type'] = $input['application_type'];
        $data['reference_number'] = $this->generateReferenceNumber($data['application_type']);
        if (empty($data['application_date'])) {
            $data['application_date'] = date('Y-m-d');
        }
        if (empty($data['payment_status'])) {
            $data['payment_status'] = 'Unpaid';
        }
        $data['application_status'] = 'Submitted';
        $data['consolidated_result'] = 'Pending';
        $data['status'] = 'Active';
        $data['fee_amount'] = $this->computeFee($data['application_type'], $data);

        $newId = $this->applicationRepo->create($data);

        foreach (self::DISCIPLINES as $discipline) {
            $this->applicationRepo->createDisciplineReview($newId, $discipline);
        }

        $this->applicationRepo->addReview($newId, null, 0, $this->currentActorName(), 'Applicant/Front Desk', 'Submitted', 'Application submitted.');
        return $newId;
    }

    public function updateApplication($applicationId, $input)
    {
        $existing = $this->applicationRepo->find($applicationId);
        $data = $this->sanitizeFields($input, true);

        $merged = array_merge($existing ?? [], $data);
        $data['fee_amount'] = $this->computeFee($merged['application_type'] ?? null, $merged);

        $this->applicationRepo->update($applicationId, $data);
        return true;
    }

    /**
     * Move the top-level application to a new workflow stage. Always writes an
     * audit-log entry so routing stays auditable, same precedent as zoning
     * clearances (transitionStatus).
     */
    public function transitionApplicationStatus($applicationId, $newStatus, $remarks, $reviewerRole = null)
    {
        if (!in_array($newStatus, self::APPLICATION_STATUSES, true)) {
            return ['error' => 'Invalid application status value.'];
        }
        if ($newStatus === 'Permit Issued') {
            return ['error' => 'Use the Issue Permit action to move an application to Permit Issued.'];
        }
        $remarks = trim((string)$remarks);
        if ($remarks === '') {
            return ['error' => 'Remarks are required when changing an application status.'];
        }

        $existing = $this->applicationRepo->find($applicationId);
        if (!$existing) {
            return ['error' => 'Application not found.'];
        }

        $update = ['application_status' => $newStatus];
        $this->applicationRepo->update($applicationId, $update);
        $this->applicationRepo->addReview($applicationId, null, (int)$existing['resubmission_round'], $this->currentActorName(), $reviewerRole, $newStatus, $remarks);

        return ['success' => true];
    }

    /**
     * Record a discipline reviewer's decision on their assigned discipline.
     * Recomputes the consolidated_result summary across all five disciplines
     * after every change (mirrors zoning's conformity_result: an informational
     * indicator separate from the manually-driven application_status).
     */
    public function transitionDisciplineReview($applicationId, $discipline, $newStatus, $remarks, $reviewerName = null)
    {
        if (!in_array($discipline, self::DISCIPLINES, true)) {
            return ['error' => 'Invalid discipline.'];
        }
        if (!in_array($newStatus, self::DISCIPLINE_STATUSES, true)) {
            return ['error' => 'Invalid discipline review status value.'];
        }
        $remarks = trim((string)$remarks);
        if ($remarks === '') {
            return ['error' => 'Remarks are required when recording a discipline decision.'];
        }

        $application = $this->applicationRepo->find($applicationId);
        if (!$application) {
            return ['error' => 'Application not found.'];
        }
        $disciplineReview = $this->applicationRepo->findDisciplineReview($applicationId, $discipline);
        if (!$disciplineReview) {
            return ['error' => 'Discipline review record not found.'];
        }

        $actorName = $reviewerName ?: $this->currentActorName();
        $round = (int)$application['resubmission_round'];

        $this->applicationRepo->updateDisciplineReview($disciplineReview['discipline_review_id'], [
            'review_status' => $newStatus,
            'reviewer_name' => $actorName,
            'remarks' => $remarks,
            'resubmission_round' => $round,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ]);
        $this->applicationRepo->addReview($applicationId, $discipline, $round, $actorName, $discipline . ' Reviewer', $newStatus, $remarks);

        $this->recomputeConsolidatedResult($applicationId);
        return ['success' => true];
    }

    /**
     * Applicant/staff resubmits after a Returned for Revision decision: bumps
     * the resubmission round and resets any discipline still marked Returned
     * for Revision back to Under Review so reviewers know to look again.
     */
    public function resubmit($applicationId, $remarks)
    {
        $remarks = trim((string)$remarks);
        if ($remarks === '') {
            return ['error' => 'Remarks describing what changed are required for a resubmission.'];
        }

        $application = $this->applicationRepo->find($applicationId);
        if (!$application) {
            return ['error' => 'Application not found.'];
        }
        if ($application['application_status'] !== 'Returned for Revision') {
            return ['error' => 'Only an application currently Returned for Revision can be resubmitted.'];
        }

        $newRound = (int)$application['resubmission_round'] + 1;
        $this->applicationRepo->update($applicationId, [
            'application_status' => 'Under Review',
            'resubmission_round' => $newRound,
        ]);

        foreach ($this->applicationRepo->getDisciplineReviews($applicationId) as $disciplineReview) {
            if ($disciplineReview['review_status'] === 'Returned for Revision') {
                $this->applicationRepo->updateDisciplineReview($disciplineReview['discipline_review_id'], [
                    'review_status' => 'Under Review',
                ]);
            }
        }

        $this->applicationRepo->addReview($applicationId, null, $newRound, $this->currentActorName(), null, 'Resubmitted', $remarks);
        $this->recomputeConsolidatedResult($applicationId);
        return ['success' => true];
    }

    /**
     * Issue the permit. Requires the application to already be Approved and
     * every discipline review to be Approved, so a permit can never be issued
     * ahead of a completed multi-discipline review.
     */
    public function issuePermit($applicationId, $conditionsOfApproval, $expiryDate, $permitNumber = null)
    {
        $application = $this->applicationRepo->find($applicationId);
        if (!$application) {
            return ['error' => 'Application not found.'];
        }
        if ($application['application_status'] !== 'Approved') {
            return ['error' => 'The application must be in Approved status before a permit can be issued.'];
        }
        foreach ($this->applicationRepo->getDisciplineReviews($applicationId) as $disciplineReview) {
            if ($disciplineReview['review_status'] !== 'Approved') {
                return ['error' => 'All discipline reviews must be Approved before a permit can be issued.'];
            }
        }

        $permitNumber = trim((string)$permitNumber) ?: $application['reference_number'];
        $update = [
            'application_status' => 'Permit Issued',
            'permit_number' => $permitNumber,
            'conditions_of_approval' => trim((string)$conditionsOfApproval) ?: null,
            'issued_date' => date('Y-m-d'),
            'expiry_date' => (trim((string)$expiryDate) !== '') ? $expiryDate : null,
        ];
        $this->applicationRepo->update($applicationId, $update);
        $this->applicationRepo->addReview($applicationId, null, (int)$application['resubmission_round'], $this->currentActorName(), null, 'Permit Issued', 'Permit issued with recorded conditions of approval.');

        return ['success' => true, 'permit_number' => $permitNumber];
    }

    private function recomputeConsolidatedResult($applicationId)
    {
        $disciplineReviews = $this->applicationRepo->getDisciplineReviews($applicationId);
        $statuses = array_column($disciplineReviews, 'review_status');

        if (in_array('Rejected', $statuses, true)) {
            $result = 'Rejected';
        } elseif (in_array('Returned for Revision', $statuses, true)) {
            $result = 'Returned for Revision';
        } elseif (in_array('Pending', $statuses, true) || in_array('Under Review', $statuses, true)) {
            $result = 'Under Review';
        } else {
            $result = 'Approved';
        }

        $this->applicationRepo->update($applicationId, ['consolidated_result' => $result]);
        return $result;
    }

    private function computeFee($applicationType, $data)
    {
        $base = self::BASE_FEE[$applicationType] ?? 1000.00;
        $rate = self::RATE_PER_SQM[$applicationType] ?? 30.00;
        $area = $applicationType === 'Subdivision Plan'
            ? (is_numeric($data['lot_area_sqm'] ?? null) ? (float)$data['lot_area_sqm'] : 0.0)
            : (is_numeric($data['floor_area_sqm'] ?? null) ? (float)$data['floor_area_sqm'] : 0.0);
        return round($base + ($area * $rate), 2);
    }

    private function generateReferenceNumber($applicationType)
    {
        $prefix = self::REFERENCE_PREFIX[$applicationType] ?? 'PA';
        $year = date('Y');
        $sequence = $this->applicationRepo->countForYear($prefix, $year) + 1;
        do {
            $candidate = sprintf('%s-%s-%06d', $prefix, $year, $sequence);
            $sequence++;
        } while ($this->applicationRepo->referenceExists($candidate));
        return $candidate;
    }

    private function currentActorName()
    {
        global $headerUser;
        return $headerUser['full_name'] ?? 'System User';
    }

    private function sanitizeFields($input, $partial = false)
    {
        $allowed = [
            'resident_id', 'project_name', 'project_description', 'barangay',
            'street_address', 'lot_area_sqm', 'floor_area_sqm', 'number_of_storeys',
            'number_of_lots', 'estimated_project_cost', 'payment_status', 'application_date',
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
