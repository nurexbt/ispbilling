<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'test';
$_SESSION['role_name'] = 'Tx&Bill';
$_SESSION['permissions'] = '{"txbill_full_access":"1"}';

require_once 'c:/xampp/htdocs/billsoft/includes/auth.php';
echo "Expense view: " . (has_permission('expense_view') ? 'YES' : 'NO') . "\n";
echo "Support ticket view: " . (has_permission('support_ticket_view') ? 'YES' : 'NO') . "\n";
