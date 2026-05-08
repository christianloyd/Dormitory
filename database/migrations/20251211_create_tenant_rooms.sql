CREATE TABLE IF NOT EXISTS tenant_rooms (
    tenant_room_id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    room_id INT NOT NULL,
    deck_type ENUM('Lower Deck','Upper Deck') DEFAULT NULL,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    released_at DATETIME DEFAULT NULL,
    UNIQUE KEY uniq_tenant_room (tenant_id, room_id),
    CONSTRAINT fk_tenant_rooms_tenant FOREIGN KEY (tenant_id)
        REFERENCES tenants(tenant_id) ON DELETE CASCADE,
    CONSTRAINT fk_tenant_rooms_room FOREIGN KEY (room_id)
        REFERENCES rooms(room_id) ON DELETE CASCADE
);

-- Allow tenant master record without a single room reference
ALTER TABLE tenants
    MODIFY room_id INT NULL,
    MODIFY deck_type ENUM('Lower Deck','Upper Deck') NULL;

-- Optional data backfill: migrate existing tenant->room pairs
INSERT INTO tenant_rooms (tenant_id, room_id, deck_type, assigned_at)
SELECT tenant_id, room_id, deck_type, created_at
FROM tenants
WHERE room_id IS NOT NULL
ON DUPLICATE KEY UPDATE deck_type = VALUES(deck_type);
