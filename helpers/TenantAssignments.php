<?php
/**
 * TenantAssignments Helper
 * Centralises create/update logic for tenants with multi-room support.
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/FileUpload.php';
require_once __DIR__ . '/BillingCalculator.php';

class TenantAssignments
{
    /**
     * Create a tenant with room assignments.
     */
    public static function createTenant(mysqli $conn, array $data, array $files): int
    {
        self::guardRequired($data, ['tenant_name', 'address', 'contact_number', 'guardian_contact', 'date_started']);
        self::validateContacts($data['contact_number'] ?? '', $data['guardian_contact'] ?? '');
        self::validateDate($data['date_started'] ?? '');

        $assignments = self::buildAssignments(
            $conn,
            $data['room_id'] ?? [],
            $data['deck_type'] ?? []
        );

        $db = new Database($conn);
        $fileUpload = new FileUpload();
        $conn->begin_transaction();

        try {
            $profilePic = self::handleFileUpload($fileUpload, $files['profile_pic'] ?? null, 'tenant_profile');
            $proofPic = self::handleFileUpload($fileUpload, $files['proof_pic'] ?? null, 'tenant_proof');

            $primaryAssignment = $assignments[0];
            $primaryDeck = $primaryAssignment['deck_type'] ?? '';

            $tenantFields = [
                'tenant_name' => trim($data['tenant_name']),
                'profile_pic' => $profilePic,
                'proof_pic' => $proofPic,
                'address' => trim($data['address']),
                'contact_number' => trim($data['contact_number']),
                'guardian_contact' => trim($data['guardian_contact']),
                'date_started' => $data['date_started'],
            ];

            if (isset($data['status']) && $data['status'] !== '') {
                $tenantFields['status'] = $data['status'];
            }

            $tenantId = $db->insert('tenants', $tenantFields);

            $affectedRooms = self::replaceAssignments($conn, $tenantId, $assignments);
            self::syncRoomOccupancy($conn, $affectedRooms);
            self::reconcileSharedBillsForAssignments($conn, $assignments, $data['date_started'] ?? '');

            $conn->commit();
            return $tenantId;
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * Update an existing tenant with new details/assignments.
     */
    public static function updateTenant(mysqli $conn, int $tenantId, array $data, array $files): void
    {
        self::guardRequired($data, ['tenant_name', 'address', 'contact_number', 'guardian_contact', 'date_started']);
        self::validateContacts($data['contact_number'] ?? '', $data['guardian_contact'] ?? '');
        self::validateDate($data['date_started'] ?? '');

        $assignments = self::buildAssignments(
            $conn,
            $data['edit_room_id'] ?? [],
            $data['edit_deck_type'] ?? []
        );

        $db = new Database($conn);
        $fileUpload = new FileUpload();
        $conn->begin_transaction();

        try {
            $existing = $db->select('tenants', ['tenant_id' => $tenantId]);
            $tenantRow = $existing->fetch_assoc();
            if (!$tenantRow) {
                throw new Exception('Tenant not found.');
            }

            $profilePic = $tenantRow['profile_pic'];
            $proofPic = $tenantRow['proof_pic'];

            if (isset($files['profile_pic']) && ($files['profile_pic']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $profilePic = $fileUpload->upload($files['profile_pic'], 'tenant_profile');
                self::deleteOldFile($fileUpload, $tenantRow['profile_pic']);
            }

            if (isset($files['proof_pic']) && ($files['proof_pic']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $proofPic = $fileUpload->upload($files['proof_pic'], 'tenant_proof');
                self::deleteOldFile($fileUpload, $tenantRow['proof_pic']);
            }

            $primaryAssignment = $assignments[0];
            $primaryDeck = $primaryAssignment['deck_type'] ?? '';

            $fields = [
                'tenant_name' => trim($data['tenant_name']),
                'address' => trim($data['address']),
                'contact_number' => trim($data['contact_number']),
                'guardian_contact' => trim($data['guardian_contact']),
                'date_started' => $data['date_started'],
                'profile_pic' => $profilePic,
                'proof_pic' => $proofPic,
            ];

            if (isset($data['status']) && $data['status'] !== '') {
                $fields['status'] = $data['status'];
            }

            $db->update('tenants', $fields, ['tenant_id' => $tenantId]);

            $affectedRooms = self::replaceAssignments($conn, $tenantId, $assignments);
            self::syncRoomOccupancy($conn, $affectedRooms);
            self::reconcileSharedBillsForAssignments($conn, $assignments, $data['date_started'] ?? '');

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * Release all active assignments for a tenant.
     */
    public static function releaseAssignments(mysqli $conn, int $tenantId): void
    {
        $stmt = $conn->prepare("UPDATE tenant_rooms SET released_at = NOW() WHERE tenant_id = ? AND released_at IS NULL");
        $stmt->bind_param('i', $tenantId);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Fetch active assignments for a tenant.
     */
    public static function fetchAssignments(mysqli $conn, int $tenantId): array
    {
        $stmt = $conn->prepare(
            "SELECT tr.room_id, tr.deck_type, r.room_number, r.room_type, r.price
             FROM tenant_rooms tr
             INNER JOIN rooms r ON r.room_id = tr.room_id
             WHERE tr.tenant_id = ? AND tr.released_at IS NULL
             ORDER BY r.room_number"
        );
        $stmt->bind_param('i', $tenantId);
        $stmt->execute();
        $result = $stmt->get_result();

        $assignments = [];
        while ($row = $result->fetch_assoc()) {
            $assignments[] = [
                'room_id' => (int)$row['room_id'],
                'deck_type' => $row['deck_type'] ?? '',
                'room_number' => $row['room_number'],
                'room_type' => $row['room_type'],
                'price' => isset($row['price']) ? (float)$row['price'] : 0.0,
            ];
        }

        $stmt->close();
        return $assignments;
    }

    /**
     * Fetch active assignments indexed by tenant id.
     *
     * @param mysqli $conn
     * @param int[] $tenantIds
     * @return array<int, array<int, array{room_id:int, deck_type:?string, room_number:string, room_type:string}>>
     */
    public static function getAssignmentsForTenants(mysqli $conn, array $tenantIds): array
    {
        $tenantIds = array_values(array_unique(array_filter(array_map('intval', $tenantIds))));
        if (empty($tenantIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($tenantIds), '?'));
        $types = str_repeat('i', count($tenantIds));

        $sql = "
            SELECT
                tr.tenant_id,
                tr.room_id,
                tr.deck_type,
                r.room_number,
                r.room_type,
                r.price
            FROM tenant_rooms tr
            INNER JOIN rooms r ON r.room_id = tr.room_id
            WHERE tr.tenant_id IN ($placeholders) AND tr.released_at IS NULL
            ORDER BY r.room_number ASC
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Unable to prepare assignment lookup: ' . $conn->error);
        }

        $stmt->bind_param($types, ...$tenantIds);
        $stmt->execute();
        $result = $stmt->get_result();

        $assignments = [];
        while ($row = $result->fetch_assoc()) {
            $tenantId = (int)$row['tenant_id'];
            $assignments[$tenantId][] = [
                'room_id' => (int)$row['room_id'],
                'deck_type' => $row['deck_type'] ?? '',
                'room_number' => $row['room_number'],
                'room_type' => $row['room_type'],
                'price' => isset($row['price']) ? (float)$row['price'] : 0.0,
            ];
        }

        $stmt->close();

        return $assignments;
    }

    /**
     * Build clean assignment payload.
     */
    private static function buildAssignments(mysqli $conn, $roomIds, $deckTypes): array
    {
        if (!is_array($roomIds)) {
            $roomIds = [$roomIds];
        }
        $roomIds = array_values($roomIds);

        if (!is_array($deckTypes)) {
            $deckTypes = [$deckTypes];
        }
        $deckTypes = array_map(static fn($value) => trim((string)$value), array_values($deckTypes));
        $deckPointer = 0;

        $assignments = [];
        $stmt = $conn->prepare('SELECT room_type, upper_deck_count, lower_deck_count FROM rooms WHERE room_id = ?');
        if (!$stmt) {
            throw new Exception('Unable to prepare room validation.');
        }

        foreach ($roomIds as $index => $roomIdRaw) {
            $roomId = intval($roomIdRaw);
            if ($roomId <= 0) {
                continue;
            }

            $stmt->bind_param('i', $roomId);
            $stmt->execute();
            $roomResult = $stmt->get_result();
            $room = $roomResult->fetch_assoc();

            if (!$room) {
                throw new Exception('Selected room does not exist.');
            }

            if ($room['room_type'] === 'Whole Room') {
                $deck = '';
            } else {
                $deck = $deckTypes[$deckPointer] ?? '';
                $deckPointer++;

                if ($deck === '') {
                    throw new Exception('Deck selection is required for bed spacer rooms.');
                }
            }

            $assignments[$roomId] = [
                'room_id' => $roomId,
                'deck_type' => $deck,
            ];
        }

        $stmt->close();

        if (empty($assignments)) {
            throw new Exception('At least one room assignment is required.');
        }

        return array_values($assignments);
    }

    /**
     * Replace tenant assignments and return affected room IDs.
     */
    private static function replaceAssignments(mysqli $conn, int $tenantId, array $assignments): array
    {
        $existingStmt = $conn->prepare('SELECT room_id FROM tenant_rooms WHERE tenant_id = ? AND released_at IS NULL');
        $existingStmt->bind_param('i', $tenantId);
        $existingStmt->execute();
        $existingResult = $existingStmt->get_result();
        $existingRooms = [];
        while ($row = $existingResult->fetch_assoc()) {
            $existingRooms[] = (int)$row['room_id'];
        }
        $existingStmt->close();

        $upsertStmt = $conn->prepare(
            'INSERT INTO tenant_rooms (tenant_id, room_id, deck_type)
             VALUES (?, ?, NULLIF(?, \'\'))
             ON DUPLICATE KEY UPDATE deck_type = VALUES(deck_type), released_at = NULL'
        );

        $newRooms = [];
        foreach ($assignments as $assignment) {
            $roomId = (int)$assignment['room_id'];
            $deck = $assignment['deck_type'] ?? '';
            $upsertStmt->bind_param('iis', $tenantId, $roomId, $deck);
            $upsertStmt->execute();
            $newRooms[] = $roomId;
        }
        $upsertStmt->close();

        $roomsToRelease = array_diff($existingRooms, $newRooms);
        if (!empty($roomsToRelease)) {
            $placeholders = implode(',', array_fill(0, count($roomsToRelease), '?'));
            $types = str_repeat('i', count($roomsToRelease) + 1);
            $params = array_merge([$tenantId], array_values($roomsToRelease));

            $query = "UPDATE tenant_rooms SET released_at = NOW() WHERE tenant_id = ? AND room_id IN ($placeholders) AND released_at IS NULL";
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $stmt->close();
        }

        return array_values(array_unique(array_merge($existingRooms, $newRooms)));
    }

    private static function reconcileSharedBillsForAssignments(mysqli $conn, array $assignments, string $dateStarted): void
    {
        if (empty($assignments) || trim($dateStarted) === '') {
            return;
        }

        $startTs = strtotime($dateStarted);
        if ($startTs === false) {
            return;
        }

        $startDate = date('Y-m-d', $startTs);
        $startDay = (int)date('d', $startTs);

        $dueStmt = $conn->prepare(
            'SELECT DISTINCT due_date
             FROM billing
             WHERE room_id = ?
               AND due_date >= ?
               AND DAY(due_date) = ?
             ORDER BY due_date DESC
             LIMIT 12'
        );

        if (!$dueStmt) {
            return;
        }

        $roomIdParam = 0;
        $startDateParam = $startDate;
        $startDayParam = $startDay;
        $dueStmt->bind_param('isi', $roomIdParam, $startDateParam, $startDayParam);

        foreach ($assignments as $assignment) {
            $roomId = (int)($assignment['room_id'] ?? 0);
            if ($roomId <= 0) {
                continue;
            }

            $roomIdParam = $roomId;
            if (!$dueStmt->execute()) {
                continue;
            }

            $result = $dueStmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $dueDate = $row['due_date'] ?? null;
                if (empty($dueDate)) {
                    continue;
                }

                BillingCalculator::ensureSharedBills($conn, $roomId, $dueDate);
            }
        }

        $dueStmt->close();
    }

    /**
     * Sync occupancy counters for given rooms.
     */
    public static function syncRoomOccupancy(mysqli $conn, array $roomIds): void
    {
        $roomIds = array_values(array_unique(array_filter(array_map('intval', $roomIds))));
        if (empty($roomIds)) {
            return;
        }

        $occupancyStmt = $conn->prepare(
            "SELECT COUNT(*) AS total_active
             FROM tenant_rooms tr
             INNER JOIN tenants t ON t.tenant_id = tr.tenant_id
             WHERE tr.room_id = ? AND tr.released_at IS NULL AND t.status = 'Active'"
        );
        $capacityStmt = $conn->prepare('SELECT capacity FROM rooms WHERE room_id = ?');
        $updateStmt = $conn->prepare('UPDATE rooms SET available = ?, status = ? WHERE room_id = ?');

        foreach ($roomIds as $roomId) {
            $occupancyStmt->bind_param('i', $roomId);
            $occupancyStmt->execute();
            $countRow = $occupancyStmt->get_result()->fetch_assoc();
            $active = (int)($countRow['total_active'] ?? 0);

            $capacityStmt->bind_param('i', $roomId);
            $capacityStmt->execute();
            $capacityRow = $capacityStmt->get_result()->fetch_assoc();
            $capacity = (int)($capacityRow['capacity'] ?? 0);

            $status = ($capacity > 0 && $active >= $capacity) ? 'Full' : 'Available';

            $updateStmt->bind_param('isi', $active, $status, $roomId);
            $updateStmt->execute();
        }

        $occupancyStmt->close();
        $capacityStmt->close();
        $updateStmt->close();
    }

    private static function guardRequired(array $data, array $fields): void
    {
        foreach ($fields as $field) {
            if (empty(trim($data[$field] ?? ''))) {
                throw new Exception(ucwords(str_replace('_', ' ', $field)) . ' is required.');
            }
        }
    }

    /**
     * Retrieve active room inventory with occupancy stats for UI rendering.
     */
    public static function getRoomInventory(mysqli $conn): array
    {
        $sql = "
            SELECT
                r.room_id,
                r.room_number,
                r.room_type,
                r.capacity,
                r.upper_deck_count,
                r.lower_deck_count,
                r.price,
                COALESCE(SUM(CASE WHEN t.status = 'Active' THEN 1 ELSE 0 END), 0) AS total_active,
                COALESCE(SUM(CASE WHEN t.status = 'Active' AND tr.deck_type = 'Upper Deck' THEN 1 ELSE 0 END), 0) AS upper_active,
                COALESCE(SUM(CASE WHEN t.status = 'Active' AND tr.deck_type = 'Lower Deck' THEN 1 ELSE 0 END), 0) AS lower_active
            FROM rooms r
            LEFT JOIN tenant_rooms tr ON tr.room_id = r.room_id AND tr.released_at IS NULL
            LEFT JOIN tenants t ON t.tenant_id = tr.tenant_id
            WHERE r.record_status = 'Active'
            GROUP BY r.room_id
            ORDER BY r.room_number ASC
        ";

        $result = $conn->query($sql);
        if (!$result) {
            throw new Exception('Unable to load room inventory: ' . $conn->error);
        }

        $inventory = [];
        while ($row = $result->fetch_assoc()) {
            $roomType = $row['room_type'];
            $upperDeckCount = (int)($row['upper_deck_count'] ?? 0);
            $lowerDeckCount = (int)($row['lower_deck_count'] ?? 0);
            $totalActive = (int)($row['total_active'] ?? 0);
            $upperActive = (int)($row['upper_active'] ?? 0);
            $lowerActive = (int)($row['lower_active'] ?? 0);

            if ($roomType === 'Whole Room') {
                $totalSlots = max((int)$row['capacity'], 1);
                $availableSlots = max($totalSlots - $totalActive, 0);
                $upperAvailable = 0;
                $lowerAvailable = 0;
            } else {
                $totalSlots = max($upperDeckCount + $lowerDeckCount, (int)$row['capacity']);
                $availableSlots = max($totalSlots - $totalActive, 0);
                $upperAvailable = max($upperDeckCount - $upperActive, 0);
                $lowerAvailable = max($lowerDeckCount - $lowerActive, 0);
            }

            $inventory[] = [
                'room_id' => (int)$row['room_id'],
                'room_number' => $row['room_number'],
                'room_type' => $roomType,
                'capacity' => (int)$row['capacity'],
                'upper_deck_count' => $upperDeckCount,
                'lower_deck_count' => $lowerDeckCount,
                'price' => (float)$row['price'],
                'total_active' => $totalActive,
                'available_slots' => $availableSlots,
                'upper_available' => $upperAvailable,
                'lower_available' => $lowerAvailable,
            ];
        }

        return $inventory;
    }

    private static function validateContacts(string $tenant, string $guardian): void
    {
        if (!Validator::isValidPhoneNumber($tenant) || !Validator::isValidPhoneNumber($guardian)) {
            throw new Exception('Contact numbers must start with 09 and be exactly 11 digits.');
        }
    }

    private static function validateDate(string $date): void
    {
        if (!Validator::isValidDate($date)) {
            throw new Exception('Invalid date format.');
        }
    }

    private static function handleFileUpload(FileUpload $upload, ?array $file, string $folder): ?string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        return $upload->upload($file, $folder);
    }

    private static function deleteOldFile(FileUpload $upload, ?string $path): void
    {
        if (!$path || strpos($path, 'default') !== false) {
            return;
        }

        try {
            $upload->delete($path);
        } catch (Exception $e) {
            error_log('Failed to delete file: ' . $e->getMessage());
        }
    }
}
