<?php
/**
 * Billing lock helper functions
 */

/**
 * Determine if a billing record should be locked based on its due date.
 * A record is considered locked once the month of the due date has passed
 * (i.e., the first day of the current month is later than the due date month).
 *
 * @param string|null $dueDate
 * @return bool
 */
function isBillingLockedByDate($dueDate)
{
    if (empty($dueDate)) {
        return false;
    }

    try {
        $billDate = new DateTime($dueDate);
    } catch (Exception $e) {
        return false;
    }

    $currentMonthStart = new DateTime('first day of this month 00:00:00');

    return $billDate < $currentMonthStart;
}

/**
 * Check lock status of a billing record by ID using the database.
 *
 * @param mysqli $conn
 * @param int $billId
 * @return bool
 */
function isBillingRecordLocked($conn, $billId)
{
    if (!$billId) {
        return false;
    }

    $stmt = $conn->prepare("SELECT due_date FROM billing WHERE bill_id = ?");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('i', $billId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        return false;
    }

    return isBillingLockedByDate($row['due_date'] ?? null);
}
?>
