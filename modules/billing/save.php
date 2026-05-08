<?php
// 🔹 Return JSON response always
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/auth_check.php';
require_once __DIR__ . '/../../helpers/BillingLock.php';
require_once __DIR__ . '/../../helpers/BillingItems.php';
require_once __DIR__ . '/../../helpers/BillingCalculator.php';
require_once __DIR__ . '/../../helpers/BillingReservations.php';
require_once __DIR__ . '/../../helpers/SMSStatus.php';

function alignDueDateToTenantCycle(string $baseDueDate, ?string $startDate): string
{
    if (empty($startDate)) {
        return $baseDueDate;
    }

    $baseDue = DateTime::createFromFormat('Y-m-d', $baseDueDate);
    $start = DateTime::createFromFormat('Y-m-d', substr($startDate, 0, 10));

    if (!$baseDue || !$start) {
        return $baseDueDate;
    }

    $targetDay = (int)$start->format('d');
    $daysInMonth = (int)$baseDue->format('t');
    $targetDay = min(max($targetDay, 1), $daysInMonth);

    $aligned = clone $baseDue;
    $aligned->setDate((int)$aligned->format('Y'), (int)$aligned->format('m'), $targetDay);

    return $aligned->format('Y-m-d');
}

function triggerBillingNoticeForBill(mysqli $conn, int $billId): array
{
    $result = [
        'success' => false,
        'message' => 'Unable to prepare billing notice.',
        'sent_numbers' => [],
        'sms_results' => [],
        'tenant_name' => null,
        'guardian_name' => null,
        'tenant_id' => null,
        'room_number' => null,
        'due_date' => null,
        'sms_preview' => null,
        'character_count' => 0,
        'segments' => 0,
        'totals' => null
    ];

    try {
        $stmt = $conn->prepare(
            "
            SELECT b.bill_id, b.tenant_id, b.due_date, b.base_rent, b.interest,
                   t.tenant_name, t.contact_number, '' AS guardian_name, t.guardian_contact AS guardian_contact_number,
                   r.room_number
            FROM billing b
            INNER JOIN tenants t ON b.tenant_id = t.tenant_id
            INNER JOIN rooms r ON b.room_id = r.room_id
            WHERE b.bill_id = ?
            LIMIT 1
        "
        );

        if (!$stmt) {
            $result['message'] = 'Unable to prepare billing notice lookup.';
            return $result;
        }

        $stmt->bind_param('i', $billId);
        $stmt->execute();
        $bill = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$bill) {
            $result['message'] = 'Billing record not found for notice.';
            return $result;
        }

        $result['tenant_name'] = $bill['tenant_name'] ?? null;
        $result['guardian_name'] = $bill['guardian_name'] ?? null;
        $result['tenant_id'] = isset($bill['tenant_id']) ? (int)$bill['tenant_id'] : null;
        $result['room_number'] = $bill['room_number'] ?? null;
        $result['due_date'] = $bill['due_date'] ?? null;

        $utilityItems = getBillingUtilityItems($conn, $billId);
        $additionalItems = getBillingAdditionalItems($conn, $billId);

        $messageData = composeReminderSMSMessage(
            [
                'tenant_name' => $bill['tenant_name'] ?? '',
                'room_number' => $bill['room_number'] ?? '',
                'due_date' => $bill['due_date'] ?? null,
                'base_rent' => $bill['base_rent'] ?? 0,
                'interest' => $bill['interest'] ?? 0
            ],
            $utilityItems,
            $additionalItems
        );

        $message = $messageData['message'];

        $smsHelper = new SMSHelper($conn);
        $smsHelper->setSMSEnabled(SMS_ENABLED);

        $numbers = [
            ['number' => $bill['contact_number'] ?? '', 'type' => 'Tenant'],
            ['number' => $bill['guardian_contact_number'] ?? '', 'type' => 'Guardian']
        ];

        $hasNumbers = false;
        $sentNumbers = [];
        $smsResults = [];

        foreach ($numbers as $recipient) {
            if (empty($recipient['number'])) {
                continue;
            }

            $hasNumbers = true;
            $sendResult = $smsHelper->sendSMS(
                $recipient['number'],
                $message,
                $result['tenant_id'],
                $recipient['type']
            );

            if (!empty($sendResult['success'])) {
                $sentNumbers[] = $recipient['number'];
            }

            $smsResults[] = [
                'number' => $recipient['number'],
                'type' => $recipient['type'],
                'status' => !empty($sendResult['success']) ? 'sent' : ($sendResult['status'] ?? 'failed'),
                'message' => $sendResult['message'] ?? ''
            ];
        }

        $result['sent_numbers'] = $sentNumbers;
        $result['sms_results'] = $smsResults;

        if (!$hasNumbers) {
            $result['message'] = 'No contact numbers available for billing notice.';
        } elseif (!empty($sentNumbers)) {
            $result['success'] = true;
            $result['message'] = 'Billing notice sent to ' . count($sentNumbers) . ' recipient(s).';
        } else {
            $result['message'] = 'Billing notice attempt completed but no messages were sent.';
        }

        if ($hasNumbers) {
            $notifMessage = sprintf(
                'Billing notice sent for Room %s due on %s.',
                $bill['room_number'] ?? '-',
                $bill['due_date'] ?? 'N/A'
            );
            $type = 'Billing Notice';

            $stmtNotif = $conn->prepare("\n                INSERT INTO notifications (tenant_id, type, message, is_read, created_at)\n                VALUES (?, ?, ?, 0, NOW())\n            ");

            if ($stmtNotif) {
                $tenantId = $result['tenant_id'] ?? 0;
                $stmtNotif->bind_param('iss', $tenantId, $type, $notifMessage);
                $stmtNotif->execute();
                $stmtNotif->close();
            }
        }

        $characterCount = mb_strlen($message);
        $segments = max(1, ceil($characterCount / 157));

        $result['sms_preview'] = $message;
        $result['character_count'] = $characterCount;
        $result['segments'] = $segments;
        $result['totals'] = [
            'utilities' => $messageData['total_utilities'],
            'additional' => $messageData['total_additional'],
            'overall' => $messageData['total_amount']
        ];

    } catch (Throwable $e) {
        $result['message'] = 'Error sending billing notice: ' . $e->getMessage();
    }

    return $result;
}

try {
    // --- Input handling ---
    $tenant_id = intval($_POST['tenant_id']);
    $room_id = intval($_POST['room_id']);
    $base_rent = floatval($_POST['base_rent']);
    $due_date = $_POST['due_date'];
    $due_date_ts = strtotime($due_date);
    if ($due_date_ts === false) {
        throw new Exception("Invalid due date provided.");
    }
    $interest_input = isset($_POST['interest']) ? floatval($_POST['interest']) : 0.0;

    if (isBillingLockedByDate($due_date)) {
        throw new Exception("This billing month is locked and cannot accept new records.");
    }

    $primaryCarryOver = BillingCalculator::getTenantCarryOver($conn, $tenant_id, $due_date);

    // Determine active tenants in the selected room (including current tenant)
    $tenantIds = [];
    $tenantStartDates = [];
    $tenantStmt = $conn->prepare(
        "SELECT t.tenant_id, t.date_started
         FROM tenant_rooms tr
         INNER JOIN tenants t ON t.tenant_id = tr.tenant_id
         WHERE tr.room_id = ?
           AND tr.released_at IS NULL
           AND t.status = 'Active'
         ORDER BY t.tenant_id ASC"
    );
    if ($tenantStmt) {
        $tenantStmt->bind_param("i", $room_id);
        $tenantStmt->execute();
        $tenantResult = $tenantStmt->get_result();
        while ($tenantRow = $tenantResult->fetch_assoc()) {
            $candidateId = (int)$tenantRow['tenant_id'];

            $includeTenant = ($candidateId === $tenant_id);
            if (!$includeTenant) {
                $startDateRaw = $tenantRow['date_started'] ?? null;
                if (empty($startDateRaw)) {
                    $includeTenant = true;
                } else {
                    $startTs = strtotime($startDateRaw);
                    if ($startTs !== false && $startTs <= $due_date_ts) {
                        $includeTenant = true;
                    }
                }
            }

            if ($includeTenant) {
                $tenantIds[] = $candidateId;
                $tenantStartDates[$candidateId] = $tenantRow['date_started'] ?? null;
            }
        }
        $tenantStmt->close();
    }

    if (!in_array($tenant_id, $tenantIds, true)) {
        $tenantIds[] = $tenant_id;
    }

    $tenantIds = array_values(array_unique($tenantIds));

    sort($tenantIds);
    $tenantIndexMap = array_flip($tenantIds);
    $tenantCount = max(count($tenantIds), 1);
    $currentTenantIndex = $tenantIndexMap[$tenant_id] ?? 0;

    $reservationFlagInput = $_POST['reservation_flag'] ?? '0';
    $reservedAmountInput = isset($_POST['reserved_amount']) ? floatval($_POST['reserved_amount']) : 0.0;
    $electricityReservationOnly = ($reservationFlagInput === '1' && $reservedAmountInput > 0);
    $reservationPerTenantAmount = $electricityReservationOnly ? $reservedAmountInput : 0.0;

    // --- Utility fees & amounts ---
    $utility_fees_raw = $_POST['utility_fee'] ?? [];
    $utility_amounts_raw = $_POST['utility_amount'] ?? [];
    $utility_fees = [];
    $utility_amounts = [];

    foreach ($utility_fees_raw as $idx => $fee) {
        $fee = trim((string)$fee);
        $amount = isset($utility_amounts_raw[$idx]) ? floatval($utility_amounts_raw[$idx]) : 0;

        if ($fee === '' && $amount == 0) {
            continue; // skip empty pairs
        }

        $utility_fees[] = $fee;
        $utility_amounts[] = $amount;
    }

    // --- Additional charges & amounts ---
    $add_charges_raw = $_POST['add_charges'] ?? [];
    $add_amounts_raw = $_POST['add_amount'] ?? [];
    $add_charges = [];
    $add_amounts = [];

    foreach ($add_charges_raw as $idx => $charge) {
        $charge = trim((string)$charge);
        $amount = isset($add_amounts_raw[$idx]) ? floatval($add_amounts_raw[$idx]) : 0;

        if ($charge === '' && $amount == 0) {
            continue;
        }

        $add_charges[] = $charge;
        $add_amounts[] = $amount;
    }

    // --- Validate required fields ---
    if (empty($tenant_id) || empty($room_id) || empty($due_date)) {
        throw new Exception("Missing required fields.");
    }

    $transactionStarted = false;
    $conn->begin_transaction();
    $transactionStarted = true;

    $splitShares = [];
    $utilityItems = [];
    $averageShare = 0.0;
    $hasElectricityUtility = false;

    foreach ($utility_fees as $idx => $fee) {
        $amount = $utility_amounts[$idx] ?? 0;
        $utilityItems[] = [
            'label' => $fee,
            'amount' => $amount,
            'bill_id' => 0 // placeholder until we know bill id
        ];

        if (strcasecmp($fee, BillingCalculator::ELECTRICITY_LABEL) === 0) {
            $hasElectricityUtility = true;
            if ($electricityReservationOnly) {
                $splitShares = [];
                $averageShare = 0.0;
                $reservationPerTenantAmount = $reservationPerTenantAmount > 0 ? $reservationPerTenantAmount : $amount;
            } else {
                $splitShares = BillingCalculator::splitAmountAcrossTenants($amount, $tenantCount);
                $averageShare = $tenantCount > 0 ? $amount / $tenantCount : 0;
            }
        }
    }

    $stmt = $conn->prepare("
        INSERT INTO billing 
        (tenant_id, room_id, due_date, base_rent, status, payment_amount, interest, payment_method)
        VALUES (?, ?, ?, ?, 'Pending', 0, 0, '')
    ");

    $stmt->bind_param(
        "iisd",
        $tenant_id,
        $room_id,
        $due_date,
        $base_rent
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to insert billing: " . $stmt->error);
    }

    $bill_id = $stmt->insert_id;
    $stmt->close();

    foreach ($utilityItems as &$item) {
        if ($hasElectricityUtility && strcasecmp($item['label'], BillingCalculator::ELECTRICITY_LABEL) === 0) {
            if ($electricityReservationOnly) {
                $item['amount'] = $reservationPerTenantAmount;
            } else {
                $item['amount'] = $splitShares[$currentTenantIndex] ?? $item['amount'];
            }
        }
        $item['bill_id'] = $bill_id;
    }
    unset($item);

    $pendingReservations = getPendingUtilityReservations($conn, $tenant_id, $room_id, $due_date);
    $consumedReservationIds = [];

    if ($electricityReservationOnly && !empty($pendingReservations)) {
        $remainingReservations = [];
        foreach ($pendingReservations as $reservation) {
            $label = (string)($reservation['label'] ?? '');
            if (strcasecmp($label, BillingCalculator::ELECTRICITY_LABEL) === 0) {
                if (isset($reservation['id'])) {
                    $consumedReservationIds[] = (int)$reservation['id'];
                }
                continue;
            }
            $remainingReservations[] = $reservation;
        }

        if (!empty($remainingReservations)) {
            $consumedFromApply = applyUtilityReservationsToItems($utilityItems, $remainingReservations, $bill_id);
            foreach ($consumedFromApply as $consumed) {
                if (isset($consumed['id'])) {
                    $consumedReservationIds[] = (int)$consumed['id'];
                }
            }
        }
    } else {
        $consumedFromApply = applyUtilityReservationsToItems($utilityItems, $pendingReservations, $bill_id);
        foreach ($consumedFromApply as $consumed) {
            if (isset($consumed['id'])) {
                $consumedReservationIds[] = (int)$consumed['id'];
            }
        }
    }

    $additionalItems = [];
    $sharedBills = [];
    foreach ($add_charges as $idx => $charge) {
        $additionalItems[] = [
            'label' => $charge,
            'amount' => $add_amounts[$idx] ?? 0,
            'bill_id' => $bill_id
        ];
    }

    replaceBillingUtilityItems($conn, $bill_id, $utilityItems);
    replaceBillingAdditionalItems($conn, $bill_id, $additionalItems);

    if (!empty($consumedReservationIds)) {
        consumeUtilityReservations($conn, $consumedReservationIds, $bill_id);
    }

    if ($hasElectricityUtility && !$electricityReservationOnly && !empty($splitShares)) {
        $utilityLabel = 'Electricity';
        foreach ($tenantIds as $index => $roomTenantId) {
            if ($roomTenantId === $tenant_id) {
                continue; // current tenant already handled
            }

            $shareAmount = $splitShares[$index] ?? $averageShare;
            $shareDueDate = alignDueDateToTenantCycle($due_date, $tenantStartDates[$roomTenantId] ?? null);
            $shareDueDateTs = strtotime($shareDueDate);
            if ($shareDueDateTs === false) {
                $shareDueDateTs = $due_date_ts;
            }

            if ($shareDueDateTs > $due_date_ts) {
                reserveUtilityShare(
                    $conn,
                    $roomTenantId,
                    $room_id,
                    $shareDueDate,
                    BillingCalculator::ELECTRICITY_LABEL,
                    $shareAmount,
                    $bill_id
                );
                continue;
            }

            $upsertResult = BillingCalculator::upsertSharedElectricBill(
                $conn,
                $roomTenantId,
                $room_id,
                $shareDueDate,
                $base_rent,
                BillingCalculator::ELECTRICITY_LABEL,
                $shareAmount
            );

            if ($upsertResult && isset($upsertResult['bill_id'])) {
                $financials = null;
                if (!empty($upsertResult['created'])) {
                    $financials = BillingCalculator::initialiseBillFinancials(
                        $conn,
                        (int)$upsertResult['bill_id'],
                        $roomTenantId,
                        $shareDueDate,
                        $base_rent,
                        $interest_input
                    );
                }

                $sharedBills[] = [
                    'bill_id' => (int)$upsertResult['bill_id'],
                    'tenant_id' => $roomTenantId,
                    'created' => !empty($upsertResult['created']),
                    'financial_summary' => $financials
                ];
            }
        }
    }

    $primaryFinancials = BillingCalculator::initialiseBillFinancials(
        $conn,
        $bill_id,
        $tenant_id,
        $due_date,
        $base_rent,
        $interest_input,
        0.0,
        $primaryCarryOver
    );

    $conn->commit();
    $transactionStarted = false;

    $noticeResult = triggerBillingNoticeForBill($conn, $bill_id);

    $sharedNoticeResults = [];
    foreach ($sharedBills as $sharedBill) {
        if (empty($sharedBill['created'])) {
            continue;
        }

        $sharedNotice = triggerBillingNoticeForBill($conn, $sharedBill['bill_id']);
        $sharedNoticeResults[] = [
            'bill_id' => $sharedBill['bill_id'],
            'tenant_id' => $sharedNotice['tenant_id'] ?? $sharedBill['tenant_id'],
            'created' => (bool)$sharedBill['created'],
            'billing_notice' => $sharedNotice,
            'financial_summary' => $sharedBill['financial_summary']
        ];

        SMSStatusRepository::recordBillingNotice(
            $conn,
            $sharedBill['bill_id'],
            (int)($sharedNotice['tenant_id'] ?? $sharedBill['tenant_id']),
            [
                'success' => $sharedNotice['success'] ?? false,
                'message' => $sharedNotice['message'] ?? '',
                'sent_numbers' => $sharedNotice['sent_numbers'] ?? [],
                'sms_results' => $sharedNotice['sms_results'] ?? [],
                'has_numbers' => !empty($sharedNotice['sms_results'])
            ]
        );
    }

    SMSStatusRepository::recordBillingNotice(
        $conn,
        $bill_id,
        $tenant_id,
        [
            'success' => $noticeResult['success'] ?? false,
            'message' => $noticeResult['message'] ?? '',
            'sent_numbers' => $noticeResult['sent_numbers'] ?? [],
            'sms_results' => $noticeResult['sms_results'] ?? [],
            'has_numbers' => !empty($noticeResult['sms_results'])
        ]
    );

    echo json_encode([
        'success' => true,
        'tenant_id' => $tenant_id,
        'bill_id' => $bill_id,
        'billing_notice' => $noticeResult,
        'financial_summary' => $primaryFinancials,
        'shared_billing_notices' => $sharedNoticeResults
    ]);
    exit;

} catch (Exception $e) {
    if (isset($transactionStarted) && $transactionStarted) {
        $conn->rollback();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>
