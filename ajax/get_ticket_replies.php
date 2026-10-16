<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';


header('Content-Type: application/json');

if (!has_permission('support_ticket_view') && !has_permission('txbill_full_access') && !has_permission('customer_view')) { echo json_encode(['success' => false, 'message' => 'Access Denied', 'status' => 'error']); exit; }


if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$ticket_id = intval($_GET['ticket_id'] ?? 0);

if ($ticket_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid ticket ID.']);
    exit();
}

$replies = [];

// 1. Fetch original ticket details
$stmt_ticket = $conn->prepare("
    SELECT t.description, t.created_at, t.customer_id, t.created_by, e.full_name, c.name as customer_name
    FROM support_ticket_tbl t
    LEFT JOIN portal_user_tbl pu ON t.created_by = pu.username
    LEFT JOIN employee_tbl e ON pu.employee_id = e.id
    LEFT JOIN customer_tbl c ON t.customer_id = c.id
    WHERE t.id = ?
");
$stmt_ticket->bind_param("i", $ticket_id);
$stmt_ticket->execute();
$ticket_res = $stmt_ticket->get_result();
if ($ticket_row = $ticket_res->fetch_assoc()) {
    $note_html = $ticket_row['description'];
    
    // Fetch attachments
    $stmt_att = $conn->prepare("SELECT file_name, file_path FROM ticket_attachment_tbl WHERE ticket_id = ?");
    $stmt_att->bind_param("i", $ticket_id);
    $stmt_att->execute();
    $att_res = $stmt_att->get_result();
    $attachments = [];
    while ($att = $att_res->fetch_assoc()) {
        $attachments[] = '<a href="' . htmlspecialchars($att['file_path']) . '" target="_blank" class="badge bg-light text-primary border border-primary-subtle text-decoration-none me-1"><i class="bi bi-paperclip"></i> ' . htmlspecialchars($att['file_name']) . '</a>';
    }
    $stmt_att->close();
    
    if (count($attachments) > 0) {
        $note_html .= '<div class="mt-2 pt-2 border-top border-slate-100">' . implode(' ', $attachments) . '</div>';
    }
    
    $created_by_raw = $ticket_row['created_by'];
    if (!empty($ticket_row['full_name'])) {
        $created_by = $ticket_row['full_name'];
    } elseif (empty($created_by_raw) || $created_by_raw == 'System') {
        $created_by = 'System / Admin';
    } elseif ($ticket_row['customer_id'] != 0 && $ticket_row['customer_id'] != null) {
        $created_by = !empty($ticket_row['customer_name']) ? $ticket_row['customer_name'] : $created_by_raw;
    } else {
        $created_by = $created_by_raw;
    }
    
    $replies[] = [
        'note' => $note_html,
        'created_by' => htmlspecialchars($created_by) . ' (Ticket Created)',
        'created_at' => date('d M Y, h:i A', strtotime($ticket_row['created_at']))
    ];
}
$stmt_ticket->close();


// 2. Fetch replies
$stmt = $conn->prepare("
    SELECT r.note, r.created_by, r.created_at, e.full_name 
    FROM ticket_reply_tbl r 
    LEFT JOIN portal_user_tbl pu ON r.created_by = pu.username
    LEFT JOIN employee_tbl e ON pu.employee_id = e.id
    WHERE r.ticket_id = ? 
    ORDER BY r.created_at ASC
");
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $display_name = !empty($row['full_name']) ? $row['full_name'] : $row['created_by'];
    $replies[] = [
        'note' => $row['note'],
        'created_by' => htmlspecialchars($display_name),
        'created_at' => date('d M Y, h:i A', strtotime($row['created_at']))
    ];
}

$stmt->close();

echo json_encode(['status' => 'success', 'data' => $replies]);
?>
