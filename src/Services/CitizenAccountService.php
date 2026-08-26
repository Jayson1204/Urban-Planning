<?php

namespace App\Services;

class CitizenAccountService
{
    private $accountRepo;
    private $residentRepo;
    private $residentService;
    private $householdService;

    public function __construct($accountRepo, $residentRepo, $residentService, $householdService)
    {
        $this->accountRepo = $accountRepo;
        $this->residentRepo = $residentRepo;
        $this->residentService = $residentService;
        $this->householdService = $householdService;
    }

    public function validateRegistration($input)
    {
        $errors = [];
        $email = trim($input['email'] ?? '');
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email is required.';
        }
        if (strlen(trim($input['password'] ?? '')) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if (empty(trim($input['first_name'] ?? ''))) {
            $errors[] = 'First name is required.';
        }
        if (empty(trim($input['last_name'] ?? ''))) {
            $errors[] = 'Last name is required.';
        }

        $choice = $input['household_choice'] ?? 'None';
        if (!in_array($choice, ['New', 'Existing', 'None'], true)) {
            $errors[] = 'Invalid household choice.';
        }
        if ($choice === 'Existing' && empty($input['existing_household_id'])) {
            $errors[] = 'Select an existing household.';
        }
        if ($choice === 'New') {
            if (empty(trim($input['new_household_barangay'] ?? ''))) {
                $errors[] = 'New household barangay is required.';
            }
            if (empty(trim($input['new_household_street_address'] ?? ''))) {
                $errors[] = 'New household street address is required.';
            }
        }

        return $errors;
    }

    // Creates the account and, unless a matching resident already exists for this
    // email (e.g. staff entered them first), the resident (+ household) too --
    // immediately, no staff review step.
    public function register($input)
    {
        $email = trim($input['email']);

        if ($this->accountRepo->findByEmail($email)) {
            return ['error' => 'An account with this email already exists. Please log in instead.'];
        }

        $existingResident = $this->residentRepo->findByEmail($email);
        if ($existingResident) {
            $residentId = $existingResident['resident_id'];
        } else {
            $householdId = null;
            $choice = $input['household_choice'] ?? 'None';
            if ($choice === 'Existing') {
                $householdId = $input['existing_household_id'];
            } elseif ($choice === 'New') {
                $householdId = $this->householdService->createHousehold([
                    'household_number' => $input['new_household_number'] ?? null,
                    'barangay' => $input['new_household_barangay'] ?? null,
                    'street_address' => $input['new_household_street_address'] ?? null,
                    'household_type' => $input['new_household_type'] ?? 'Other',
                ]);
            }

            $residentId = $this->residentService->createResident([
                'household_id' => $householdId,
                'first_name' => $input['first_name'],
                'middle_name' => $input['middle_name'] ?? null,
                'last_name' => $input['last_name'],
                'suffix' => $input['suffix'] ?? null,
                'birth_date' => $input['birth_date'] ?? null,
                'gender' => $input['gender'] ?? null,
                'civil_status' => $input['civil_status'] ?? null,
                'contact_number' => $input['contact_number'] ?? null,
                'email' => $email,
                'barangay' => $input['barangay'] ?? null,
                'street_address' => $input['street_address'] ?? null,
                'occupation' => $input['occupation'] ?? null,
            ]);
        }

        $citizenAccountId = $this->accountRepo->create([
            'resident_id' => $residentId,
            'email' => $email,
            'password_hash' => password_hash($input['password'], PASSWORD_DEFAULT),
            'status' => 'Active',
        ]);

        return ['citizen_account_id' => $citizenAccountId, 'resident_id' => $residentId];
    }

    public function login($email, $password)
    {
        $account = $this->accountRepo->findByEmail(trim((string)$email));
        if (!$account || !$account['password_hash'] || !password_verify($password, $account['password_hash'])) {
            return null;
        }
        if ($account['status'] !== 'Active') {
            return null;
        }
        return $account;
    }

    // Called from the staff Resident Directory create flow when an email is given.
    // Leaves password_hash NULL and emails a set-password link -- staff never set or
    // see the citizen's password.
    public function createForResident($residentId, $email)
    {
        $email = trim((string)$email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        if ($this->accountRepo->findByResidentId($residentId) || $this->accountRepo->findByEmail($email)) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $citizenAccountId = $this->accountRepo->create([
            'resident_id' => $residentId,
            'email' => $email,
            'password_hash' => null,
            'status' => 'Locked',
            'password_set_token' => $token,
            'password_set_token_expires_at' => $expiresAt,
        ]);

        $this->sendSetPasswordEmail($email, $token);

        return $citizenAccountId;
    }

    // Self-service "forgot password" for an already-active account (distinct from
    // createForResident()'s staff-invite flow). Always returns success to the caller
    // regardless of whether the email matched anything -- never reveal account
    // existence through this endpoint.
    public function requestPasswordReset($email)
    {
        $email = trim((string)$email);
        $account = $this->accountRepo->findByEmail($email);
        if ($account && $account['status'] === 'Active' && $account['password_hash']) {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $this->accountRepo->setToken($account['citizen_account_id'], $token, $expiresAt);
            $this->sendResetPasswordEmail($email, $token);
        }
        return ['ok' => true];
    }

    public function completeSetPassword($token, $newPassword)
    {
        if (strlen(trim((string)$newPassword)) < 8) {
            return ['error' => 'Password must be at least 8 characters.'];
        }
        $account = $this->accountRepo->findByToken($token);
        if (!$account) {
            return ['error' => 'This link is invalid or has already been used.'];
        }
        if (strtotime($account['password_set_token_expires_at']) < time()) {
            return ['error' => 'This link has expired. Contact your barangay office for a new one.'];
        }
        $this->accountRepo->setPassword($account['citizen_account_id'], password_hash($newPassword, PASSWORD_DEFAULT));
        return ['ok' => true];
    }

    public function currentCitizen()
    {
        $citizenAccountId = $_SESSION['citizen_account_id'] ?? null;
        if (!$citizenAccountId) {
            return null;
        }
        $account = $this->accountRepo->find($citizenAccountId);
        if (!$account) {
            return null;
        }
        $resident = $this->residentRepo->find($account['resident_id']);
        return ['account' => $account, 'resident' => $resident];
    }

    private function sendSetPasswordEmail($email, $token)
    {
        require_once __DIR__ . '/../../config/mailer.php';
        $baseUrl = rtrim(getenv('APP_BASE_URL') ?: 'http://localhost/Civentral-UrbanPlanning', '/');
        $link = $baseUrl . '/pages/citizen-app/set-password.php?token=' . urlencode($token);
        $body = '<p>A resident account was created for you in the Civentral Urban Planning system.</p>'
              . '<p>Set your password to log in to the citizen mobile app:</p>'
              . "<p><a href=\"{$link}\">{$link}</a></p>"
              . '<p>This link expires in 24 hours.</p>';
        sendSystemEmail($email, $email, 'Set your Civentral citizen account password', $body);
    }

    private function sendResetPasswordEmail($email, $token)
    {
        require_once __DIR__ . '/../../config/mailer.php';
        $baseUrl = rtrim(getenv('APP_BASE_URL') ?: 'http://localhost/Civentral-UrbanPlanning', '/');
        $link = $baseUrl . '/pages/citizen-app/set-password.php?token=' . urlencode($token);
        $body = '<p>We received a request to reset the password for your Civentral citizen account.</p>'
              . '<p>Choose a new password:</p>'
              . "<p><a href=\"{$link}\">{$link}</a></p>"
              . '<p>This link expires in 1 hour. If you did not request this, you can ignore this email.</p>';
        sendSystemEmail($email, $email, 'Reset your Civentral citizen account password', $body);
    }
}
