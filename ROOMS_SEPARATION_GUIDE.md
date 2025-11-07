# Rooms Separation - Active & Inactive Pages

## ✅ Issue Fixed

**Problem**: Active and Inactive rooms were in one file using Bootstrap tabs, causing the inactive table to appear at the bottom of the same page.

**Solution**: Created separate pages for Active and Inactive rooms with proper navigation.

---

## 🔧 What Changed

### Files Created:

1. **[inactive_rooms.php](c:\xampp\htdocs\dorm_system\inactive_rooms.php)** - NEW separate page for inactive rooms
   - Same layout as rooms.php
   - Proper positioning and structure
   - Includes "Restore" button for each inactive room
   - Search functionality

2. **[restore_room.php](c:\xampp\htdocs\dorm_system\restore_room.php)** - NEW handler to restore inactive rooms
   - Changes room status from 'Inactive' to 'Active'
   - Used by the "Restore" button

### Files Modified:

1. **[rooms.php](c:\xampp\htdocs\dorm_system\rooms.php)** - UPDATED to show only active rooms
   - Removed inactive rooms tab content
   - Changed tab links to page navigation
   - Added link to inactive_rooms.php

---

## 📊 Page Structure

### rooms.php (Active Rooms)
```
┌─────────────────────────────────────┐
│ Room Information              Admin │
├─────────────────────────────────────┤
│ Total Active Rooms: 9               │
│ Total Inactive Rooms: 4 (clickable) │
├─────────────────────────────────────┤
│ [Active Rooms] [Inactive Rooms]     │ ← Navigation tabs
├─────────────────────────────────────┤
│ Active Rooms Table                  │
│ - Shows available/occupied          │
│ - Edit & Delete buttons             │
└─────────────────────────────────────┘
```

### inactive_rooms.php (Inactive Rooms)
```
┌─────────────────────────────────────┐
│ Inactive Rooms                Admin │
├─────────────────────────────────────┤
│ Total Active Rooms: 9 (clickable)   │
│ Total Inactive Rooms: 4             │
├─────────────────────────────────────┤
│ [Active Rooms] [Inactive Rooms]     │ ← Navigation tabs
├─────────────────────────────────────┤
│ Inactive Rooms Table                │
│ - Shows inactive status             │
│ - Restore button for each room      │
└─────────────────────────────────────┘
```

---

## ✅ Features

### Active Rooms Page (rooms.php)
- ✅ Add New Room button
- ✅ Search functionality
- ✅ Shows room availability (Available/Occupied)
- ✅ Shows upper/lower deck occupancy
- ✅ Edit button (opens modal)
- ✅ Delete button (marks as inactive with confirmation)
- ✅ Navigation to Inactive Rooms

### Inactive Rooms Page (inactive_rooms.php)
- ✅ Back to Active Rooms button
- ✅ Search functionality
- ✅ Shows all deleted/inactive rooms
- ✅ Restore button for each room
- ✅ Same layout as Active Rooms page
- ✅ Proper positioning at top of page (not bottom)
- ✅ Navigation to Active Rooms

---

## 🔄 Navigation Flow

```
rooms.php (Active Rooms)
    ↓ Click "Inactive Rooms" tab
inactive_rooms.php (Inactive Rooms)
    ↓ Click "Restore" button
    ↓ Confirmation dialog
    ↓ Room moved to Active
    ↓ Reload page
Back to rooms.php (Active Rooms)
```

---

## 💡 Key Changes

### 1. Tab Behavior Changed

**BEFORE (Old - Using Bootstrap Tabs):**
```html
<!-- Switched content with data-bs-toggle -->
<a class="nav-link active" data-bs-toggle="tab" href="#activeRooms">Active Rooms</a>
<a class="nav-link" data-bs-toggle="tab" href="#inactiveRooms">Inactive Rooms</a>
```
- Both tables on same page
- JavaScript toggles visibility
- Inactive table always at bottom

**AFTER (New - Page Navigation):**
```html
<!-- Links to separate pages -->
<a class="nav-link active" href="rooms.php">Active Rooms</a>
<a class="nav-link" href="inactive_rooms.php">Inactive Rooms</a>
```
- Separate PHP files
- Full page reload
- Each table has proper positioning

---

### 2. Restore Functionality Added

**New Feature**: Restore button for inactive rooms

```javascript
function restoreRoom(button) {
    const roomId = button.getAttribute('data-id');
    const roomNumber = button.getAttribute('data-room');

    Swal.fire({
      title: 'Restore Room?',
      text: `Do you want to restore Room ${roomNumber} to active status?`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, restore it'
    }).then((result) => {
      if (result.isConfirmed) {
        fetch(`restore_room.php?id=${roomId}`)
          .then(res => res.text())
          .then(data => {
            if (data.trim() === "success") {
              Swal.fire({
                title: 'Restored!',
                text: `Room ${roomNumber} has been restored.`,
                icon: 'success'
              }).then(() => {
                location.reload();
              });
            }
          });
      }
    });
}
```

---

## 🎯 User Experience Improvements

### Before:
❌ Inactive rooms at bottom of active rooms page
❌ Had to scroll down to see inactive rooms
❌ Confusing layout with both tables on one page
❌ No way to restore rooms

### After:
✅ Separate pages for active and inactive rooms
✅ Each page has proper positioning
✅ Clear navigation with tabs
✅ Can restore inactive rooms
✅ Same layout and structure on both pages
✅ Clickable room counts for quick navigation

---

## 📱 Usage

### Viewing Active Rooms:
1. Click "Rooms" in sidebar
2. Opens `rooms.php` showing active rooms
3. Click "Inactive Rooms" tab to switch pages

### Viewing Inactive Rooms:
1. From `rooms.php`, click "Inactive Rooms" tab
2. Opens `inactive_rooms.php`
3. See all deleted/inactive rooms
4. Click "Active Rooms" tab to go back

### Restoring a Room:
1. Go to `inactive_rooms.php`
2. Find the room to restore
3. Click "Restore" button
4. Confirm in dialog
5. Room moved back to active status
6. Automatically redirects to show updated list

### Deleting a Room:
1. Go to `rooms.php`
2. Click "Delete" button for a room
3. Confirm in dialog
4. Room moved to inactive status
5. Can be restored later from inactive page

---

## 🔍 Technical Details

### Database Changes:
- **None required** - Uses existing `record_status` column
- Active rooms: `record_status = 'Active'`
- Inactive rooms: `record_status = 'Inactive'`

### Navigation Implementation:
```html
<!-- Both pages have same navigation -->
<ul class="nav nav-tabs mb-3">
  <li class="nav-item">
    <a class="nav-link <?= current page == rooms ? 'active' : '' ?>"
       href="rooms.php">Active Rooms</a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= current page == inactive_rooms ? 'active' : '' ?>"
       href="inactive_rooms.php">Inactive Rooms</a>
  </li>
</ul>
```

### restore_room.php Logic:
```php
$room_id = intval($_GET['id']);
$stmt = $conn->prepare("UPDATE rooms SET record_status = 'Active' WHERE room_id = ?");
$stmt->bind_param("i", $room_id);
if ($stmt->execute()) {
    echo "success";
} else {
    echo "error";
}
```

---

## ✅ Summary

| Aspect | Old | New |
|--------|-----|-----|
| **Layout** | One file with tabs | Two separate files |
| **Inactive Position** | Bottom of page | Top of separate page |
| **Navigation** | Bootstrap tabs | Page links |
| **Restore Feature** | ❌ Not available | ✅ Available |
| **User Experience** | Confusing | Clear and organized |
| **Position Control** | ❌ Can't control | ✅ Proper positioning |

---

## 🎉 Result

**Active Rooms (rooms.php)**:
- Clean table showing only active rooms
- All functionality intact (Add, Edit, Delete)
- Proper layout at top of page

**Inactive Rooms (inactive_rooms.php)**:
- Separate page with same layout
- Shows deleted rooms
- Restore functionality
- Proper positioning (not at bottom!)

**Both pages have the same structure and proper positioning! ✅**

---

## 📁 Files Summary

1. ✅ [rooms.php](c:\xampp\htdocs\dorm_system\rooms.php) - Active rooms only
2. ✅ [inactive_rooms.php](c:\xampp\htdocs\dorm_system\inactive_rooms.php) - Inactive rooms NEW
3. ✅ [restore_room.php](c:\xampp\htdocs\dorm_system\restore_room.php) - Restore handler NEW

---

**Status**: ✅ **COMPLETED**
**Date**: November 6, 2025
**Result**: Active and Inactive rooms now have separate pages with proper positioning!
