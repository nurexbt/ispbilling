<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';


header('Content-Type: application/json');

if (!has_permission('support_ticket_update') && !has_permission('txbill_full_access') && !has_permission('customer_edit')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$ticket_id = intval($_POST['ticket_id'] ?? 0);
$status = trim($_POST['status'] ?? '');
$priority = trim($_POST['priority'] ?? '');
// Allow some HTML from Summernote for notes
$progress_note = strip_tags($_POST['progress_note'] ?? '', '<p><br><b><strong><i><em><u><ul><ol><li><a>'); 
$created_by = $_SESSION['username'] ?? 'System';

$allowed_statuses = ['Pending', 'In Progress', 'Review', 'Completed', 'Cancelled'];
$allowed_priorities = ['Low', 'Medium', 'High', 'Urgent'];

if ($ticket_id <= 0 || !in_array($status, $allowed_statuses) || !in_array($priority, $allowed_priorities)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data provided.']);
    exit();
}

$conn->begin_transaction();

try {
    // Fetch current status and priority
    $stmt_curr = $conn->prepare("SELECT status, priority FROM support_ticket_tbl WHERE id = ?");
    $stmt_curr->bind_param("i", $ticket_id);
    $stmt_curr->execute();
    $curr_res = $stmt_curr->get_result();
    $curr_ticket = $curr_res->fetch_assoc();
    $stmt_curr->close();
    
    $status_changed = ($curr_ticket && $curr_ticket['status'] != $status);
    $priority_changed = ($curr_ticket && $curr_ticket['priority'] != $priority);
    
    // Build system message if changed
    $system_msg = "";
    if ($status_changed || $priority_changed) {
        $system_msg .= "<p class='mb-1 text-slate-500' style='font-size: 11px;'>";
        if ($status_changed) {
            $system_msg .= "<i class='bi bi-arrow-right-circle text-info'></i> Changed Status from <b>{$curr_ticket['status']}</b> to <b>{$status}</b><br>";
        }
        if ($priority_changed) {
            $system_msg .= "<i class='bi bi-arrow-right-circle text-warning'></i> Changed Priority from <b>{$curr_ticket['priority']}</b> to <b>{$priority}</b><br>";
        }
        $system_msg .= "</p>";
    }

    // Update main ticket status and priority (and details if provided)
    $title = isset($_POST['title']) ? trim($_POST['title']) : null;
    $description = isset($_POST['description']) ? strip_tags($_POST['description'], '<p><br><b><strong><i><em><u><ul><ol><li><a>') : null;
    $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? intval($_POST['category_id']) : null;

    if ($title !== null && $title !== '') {
        $stmt = $conn->prepare("UPDATE support_ticket_tbl SET title = ?, category_id = ?, description = ?, status = ?, priority = ? WHERE id = ?");
        $stmt->bind_param("sisssi", $title, $category_id, $description, $status, $priority, $ticket_id);
    } else {
        $stmt = $conn->prepare("UPDATE support_ticket_tbl SET status = ?, priority = ? WHERE id = ?");
        $stmt->bind_param("ssi", $status, $priority, $ticket_id);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Error updating ticket: " . $stmt->error);
    }
    $stmt->close();
    
    // Insert new reply if provided or if system changes happened
    $clean_note = trim(strip_tags($progress_note));
    if (!empty($clean_note) || !empty($system_msg)) {
        $final_note = $system_msg;
        if (!empty($clean_note)) {
            if (!empty($system_msg)) $final_note .= "<hr class='my-2 border-slate-200'>";
            $final_note .= $progress_note;
        }
        
        $stmt_reply = $conn->prepare("INSERT INTO ticket_reply_tbl (ticket_id, note, created_by) VALUES (?, ?, ?)");
        $stmt_reply->bind_param("iss", $ticket_id, $final_note, $created_by);
        if (!$stmt_reply->execute()) {
            throw new Exception("Error adding progress note: " . $stmt_reply->error);
        }
        $stmt_reply->close();
    }

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Ticket updated successfully']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
