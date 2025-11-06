<?php
/**
 * SMS Helper Class for IPROG API Integration
 * Handles sending SMS messages via IPROG SMS Gateway
 */

class SMSHelper {
    private $apiUrl = 'https://sms.iprogtech.com/api/v1/sms_messages';
    private $apiToken = 'b3372e928050d30de930b74a8bf86321b21ccc74';
    private $sender = 'BEN & SOF Dormitory';
    private $conn;
    private $smsEnabled = true; // Can be controlled from settings

    /**
     * Constructor
     * @param mysqli $connection Database connection
     */
    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Enable or disable SMS sending
     * @param bool $enabled
     */
    public function setSMSEnabled($enabled) {
        $this->smsEnabled = $enabled;
    }

    /**
     * Send SMS via IPROG API
     * @param string $recipient Phone number in format 639XXXXXXXXX
     * @param string $message SMS message content
     * @param int|null $tenantId Optional tenant ID for logging
     * @param string $recipientType 'Tenant' or 'Guardian'
     * @return array Result with success status, message, and delivery info
     */
    public function sendSMS($recipient, $message, $tenantId = null, $recipientType = 'Tenant') {
        // Check if SMS is enabled
        if (!$this->smsEnabled) {
            return [
                'success' => false,
                'message' => 'SMS sending is disabled in settings',
                'status' => 'disabled'
            ];
        }

        // Validate phone number
        $recipient = $this->normalizePhoneNumber($recipient);
        if (!$recipient) {
            return [
                'success' => false,
                'message' => 'Invalid phone number format',
                'status' => 'invalid_number'
            ];
        }

        // Prepare API request
        // IPROG API Format - Based on official documentation
        $postData = [
            'api_token' => $this->apiToken,      // API token in body, not header
            'phone_number' => $recipient,         // phone_number, not recipient
            'message' => $message,
            'sms_provider' => 0                   // Optional: 0 or 1, default 0
        ];

        try {
            // Initialize cURL
            $ch = curl_init($this->apiUrl);

            // Set cURL options
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);  // Re-enable SSL verification
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            // Execute request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);

            // Log the raw response for debugging
            error_log("IPROG API Response: HTTP $httpCode - " . $response);

            curl_close($ch);

            // Handle cURL errors
            if ($curlError) {
                $this->logSMS($tenantId, $recipientType, $recipient, $message, 'failed', $curlError);
                return [
                    'success' => false,
                    'message' => 'Connection error: ' . $curlError,
                    'status' => 'connection_error'
                ];
            }

            // Parse API response
            $responseData = json_decode($response, true);

            // Check if request was successful
            if ($httpCode >= 200 && $httpCode < 300) {
                // Success
                $messageId = $responseData['message_id'] ?? null;
                $this->logSMS($tenantId, $recipientType, $recipient, $message, 'sent', 'Success', $messageId, $httpCode);

                return [
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'status' => 'sent',
                    'message_id' => $messageId,
                    'recipient' => $recipient
                ];
            } else {
                // API returned error
                $errorMsg = $responseData['error'] ?? $responseData['message'] ?? 'Unknown error';
                $this->logSMS($tenantId, $recipientType, $recipient, $message, 'failed', $errorMsg, null, $httpCode);

                return [
                    'success' => false,
                    'message' => 'API error: ' . $errorMsg,
                    'status' => 'api_error',
                    'http_code' => $httpCode
                ];
            }

        } catch (Exception $e) {
            $this->logSMS($tenantId, $recipientType, $recipient, $message, 'failed', $e->getMessage());
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'status' => 'exception'
            ];
        }
    }

    /**
     * Send SMS to multiple recipients
     * @param array $recipients Array of phone numbers
     * @param string $message SMS message
     * @param int|null $tenantId
     * @return array Results for each recipient
     */
    public function sendBulkSMS($recipients, $message, $tenantId = null) {
        $results = [];

        foreach ($recipients as $recipient) {
            if (!empty($recipient['number'])) {
                $result = $this->sendSMS(
                    $recipient['number'],
                    $message,
                    $tenantId,
                    $recipient['type'] ?? 'Tenant'
                );
                $results[] = array_merge($result, ['recipient' => $recipient['number']]);

                // Small delay to avoid rate limiting
                usleep(500000); // 0.5 second delay
            }
        }

        return $results;
    }

    /**
     * Normalize phone number to 639XXXXXXXXX format
     * @param string $phoneNumber
     * @return string|false Normalized number or false if invalid
     */
    private function normalizePhoneNumber($phoneNumber) {
        // Remove all non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Handle different formats
        if (strlen($phoneNumber) == 11 && substr($phoneNumber, 0, 2) == '09') {
            // 09XXXXXXXXX -> 639XXXXXXXXX
            return '63' . substr($phoneNumber, 1);
        } elseif (strlen($phoneNumber) == 10 && substr($phoneNumber, 0, 1) == '9') {
            // 9XXXXXXXXX -> 639XXXXXXXXX
            return '63' . $phoneNumber;
        } elseif (strlen($phoneNumber) == 12 && substr($phoneNumber, 0, 2) == '63') {
            // Already in correct format
            return $phoneNumber;
        } elseif (strlen($phoneNumber) == 13 && substr($phoneNumber, 0, 3) == '+63') {
            // +639XXXXXXXXX -> 639XXXXXXXXX
            return substr($phoneNumber, 1);
        }

        // Invalid format
        return false;
    }

    /**
     * Log SMS to database
     * @param int|null $tenantId
     * @param string $recipientType
     * @param string $contactNumber
     * @param string $message
     * @param string $status
     * @param string|null $errorMessage
     * @param string|null $messageId
     * @param int|null $httpCode
     */
    private function logSMS($tenantId, $recipientType, $contactNumber, $message, $status, $errorMessage = null, $messageId = null, $httpCode = null) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO sms_logs
                (tenant_id, recipient_type, contact_number, message, status, error_message, message_id, http_code, date_sent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            if ($stmt) {
                $stmt->bind_param(
                    "issssssi",
                    $tenantId,
                    $recipientType,
                    $contactNumber,
                    $message,
                    $status,
                    $errorMessage,
                    $messageId,
                    $httpCode
                );
                $stmt->execute();
                $stmt->close();
            }
        } catch (Exception $e) {
            // Silently fail logging to avoid disrupting main flow
            error_log("SMS logging failed: " . $e->getMessage());
        }
    }

    /**
     * Get SMS logs for a tenant
     * @param int $tenantId
     * @param int $limit
     * @return array SMS logs
     */
    public function getTenantSMSLogs($tenantId, $limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT * FROM sms_logs
            WHERE tenant_id = ?
            ORDER BY date_sent DESC
            LIMIT ?
        ");

        $stmt->bind_param("ii", $tenantId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $logs = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $logs;
    }

    /**
     * Get all SMS logs with optional filters
     * @param array $filters Optional filters (status, date_from, date_to)
     * @param int $limit
     * @param int $offset
     * @return array SMS logs
     */
    public function getAllSMSLogs($filters = [], $limit = 50, $offset = 0) {
        $sql = "SELECT sl.*, t.tenant_name
                FROM sms_logs sl
                LEFT JOIN tenants t ON sl.tenant_id = t.tenant_id
                WHERE 1=1";
        $params = [];
        $types = "";

        if (!empty($filters['status'])) {
            $sql .= " AND sl.status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(sl.date_sent) >= ?";
            $params[] = $filters['date_from'];
            $types .= "s";
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(sl.date_sent) <= ?";
            $params[] = $filters['date_to'];
            $types .= "s";
        }

        $sql .= " ORDER BY sl.date_sent DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $this->conn->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $logs = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $logs;
    }

    /**
     * Get SMS statistics
     * @return array Statistics
     */
    public function getSMSStatistics() {
        $stats = [];

        // Total SMS sent
        $result = $this->conn->query("SELECT COUNT(*) as total FROM sms_logs");
        $stats['total'] = $result->fetch_assoc()['total'];

        // Successful SMS
        $result = $this->conn->query("SELECT COUNT(*) as sent FROM sms_logs WHERE status = 'sent'");
        $stats['sent'] = $result->fetch_assoc()['sent'];

        // Failed SMS
        $result = $this->conn->query("SELECT COUNT(*) as failed FROM sms_logs WHERE status = 'failed'");
        $stats['failed'] = $result->fetch_assoc()['failed'];

        // Today's SMS
        $result = $this->conn->query("SELECT COUNT(*) as today FROM sms_logs WHERE DATE(date_sent) = CURDATE()");
        $stats['today'] = $result->fetch_assoc()['today'];

        return $stats;
    }
}
?>
