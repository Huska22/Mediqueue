<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['clinician', 'admin']);
require_once __DIR__ . '/../config/db.php';

$u = current_user();

// Fetch recent audit events
$auditEvents = $pdo->query("
    SELECT a.event_time, a.event_type, u.name as user_name, qt.queue_no
    FROM audit_events a
    LEFT JOIN users u ON u.user_id = a.user_id
    LEFT JOIN queue_tickets qt ON qt.ticket_id = a.ticket_id
    ORDER BY a.event_time DESC
    LIMIT 20
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Clinician Reports - MediQueue</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial; margin: 0; background: #f4f6f9; }
        .header { background: #0d1b8f; color: white; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 20px; }
        .header a { color: white; text-decoration: none; background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 5px; }
        .layout { display: flex; min-height: calc(100vh - 55px); }
        
        .sidebar { width: 220px; background: #37474f; padding: 20px 0; }
        .sidebar a { display: block; color: white; text-decoration: none; padding: 14px 20px; font-size: 14px; }
        .sidebar a:hover, .sidebar a.active { background: #263238; }
        
        .main { flex: 1; padding: 25px; display: flex; flex-direction: column; align-items: center; }
        
        .panel { background: white; padding: 30px; width: 100%; max-width: 600px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
        .panel h3 { text-align: center; margin-top: 0; font-size: 18px; margin-bottom: 5px; color: #0d1b8f;}
        .panel .headers { text-align: center; font-weight: bold; margin-bottom: 20px; font-size: 15px; color: #333;}
        
        .audit-list { list-style-type: disc; padding-left: 40px; margin: 0; }
        .audit-list li { margin-bottom: 15px; font-size: 14px; font-weight: bold; color: #333;}
        .audit-list li span { font-weight: normal; }
    </style>
</head>
<body>
    <div class="header">
        <h1>MediQueue | Dr. Clinician | Logout</h1>
        <a href="/Mediqueue/auth/logout.php">Logout</a>
    </div>
    <div class="layout">
        <div class="sidebar">
            <a href="/Mediqueue/clinician/dashboard.php">Dashboard</a>
            <a href="/Mediqueue/clinician/dashboard.php#waiting-list-section">Waiting List</a>
            <a href="/Mediqueue/clinician/dashboard.php#completed-section">Completed</a>
            <a href="/Mediqueue/clinician/reports.php" class="active">Reports</a>
        </div>
        <div class="main">
            <div class="panel">
                <h3>Audit Trail</h3>
                <div class="headers">Time | User | Action | Queue No</div>
                
                <?php if (empty($auditEvents)): ?>
                    <p style="text-align:center; color:#999;">No events logged yet.</p>
                <?php else: ?>
                    <ul class="audit-list">
                        <?php foreach ($auditEvents as $e): ?>
                            <li>
                                <?= date('H:i A', strtotime($e['event_time'])) ?> 
                                | <?= htmlspecialchars($e['user_name'] ?? 'System') ?> 
                                | <span><?= ucwords(str_replace('_', ' ', $e['event_type'])) ?></span> 
                                | <?= htmlspecialchars($e['queue_no'] ?? '-') ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>