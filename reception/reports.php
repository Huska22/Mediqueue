<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['receptionist', 'admin']);
require_once __DIR__ . '/../config/db.php';

$u = current_user();

// Fetch summary counts matching the design
$totalPatients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$completedTickets = $pdo->query("SELECT COUNT(*) FROM queue_tickets WHERE status='completed'")->fetchColumn();
$cancelled = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status='cancelled'")->fetchColumn();
$noShow = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status='no_show'")->fetchColumn();

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
    <title>Reports & Audit - MediQueue</title>
    <style>
        * { box-sizing: border-box; }
        /* MATCHED APP THEME: Background and Sidebar */
        body { font-family: Arial; margin: 0; background: #f4f6f9; } 
        .header { background: #0d1b8f; color: white; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 20px; text-align: center; flex-grow: 1;}
        .header a { color: white; text-decoration: none; background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 5px; }
        .layout { display: flex; min-height: calc(100vh - 55px); }
        
        /* MATCHED APP THEME: Sidebar */
        .sidebar { width: 220px; background: #37474f; padding: 20px 0; }
        .sidebar a { display: block; color: white; text-decoration: none; padding: 14px 20px; font-size: 14px; }
        .sidebar a:hover, .sidebar a.active { background: #263238; }
        
        .main { flex: 1; padding: 0; }
        
        /* Filter Bar */
        .filter-bar { background: white; padding: 15px 25px; font-weight: bold; font-size: 14px; border-bottom: 1px solid #ddd; box-shadow: 0 2px 4px rgba(0,0,0,0.02);}
        
        .content { padding: 25px; display: flex; flex-direction: column; align-items: center; }

        /* Summary Cards (Matched app theme: white box with shadow) */
        .cards { display: flex; gap: 15px; justify-content: center; width: 100%; max-width: 800px; margin-bottom: 25px; }
        .card { background: white; padding: 20px; border-radius: 8px; text-align: center; flex: 1; box-shadow: 0 2px 6px rgba(0,0,0,0.08); border: none; }
        .card h3 { margin: 0 0 10px; font-size: 14px; font-weight: bold; color: #666; text-transform: uppercase;}
        .card .num { font-size: 28px; font-weight: bold; color: #0d1b8f; }
        
        /* Staggered card for No-show */
        .card.split { padding: 0; border: none; background: transparent; box-shadow: none;}
        .card-top { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.08);}

        /* Audit Section */
        .audit-box { background: white; padding: 30px; width: 100%; max-width: 600px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
        .audit-box h3 { text-align: center; margin-top: 0; font-size: 18px; margin-bottom: 5px; color: #0d1b8f;}
        .audit-box .headers { text-align: center; font-weight: bold; margin-bottom: 20px; font-size: 15px; color: #333;}
        
        .audit-list { list-style-type: disc; padding-left: 40px; margin: 0; }
        .audit-list li { margin-bottom: 15px; font-size: 14px; font-weight: bold; color: #333;}
        .audit-list li span { font-weight: normal; }
    </style>
</head>
<body>
    <div class="header">
        <h1>MediQueue | Reports & Audit</h1>
        <a href="/Mediqueue/auth/logout.php">Logout</a>
    </div>
    <div class="layout">
        <div class="sidebar">
            <a href="/Mediqueue/reception/dashboard.php">Dashboard</a>
            <a href="/Mediqueue/reception/register_patient.php">Register Patient</a>
            <a href="/Mediqueue/reception/patients.php">All Patients</a>
            <a href="/Mediqueue/reception/appointments.php">Appointments</a>
            <a href="/Mediqueue/reception/walkin.php">Walk-in Queue</a>
            <a href="/Mediqueue/reception/reports.php" class="active">Reports</a>
        </div>
        <div class="main">
            
            <div class="filter-bar">
                Filter: Today | This Week | This Month
            </div>

            <div class="content">
                <!-- Summary Cards -->
                <div class="cards">
                    <div class="card"><h3>Total Patients</h3><div class="num"><?= $totalPatients ?></div></div>
                    <div class="card"><h3>Completed</h3><div class="num"><?= $completedTickets ?></div></div>
                    <div class="card"><h3>Cancelled</h3><div class="num"><?= $cancelled ?></div></div>
                    
                    <div class="card split">
                        <div class="card-top"><h3>No-show</h3><div class="num"><?= $noShow ?></div></div>
                    </div>
                </div>

                <!-- Audit Box -->
                <div class="audit-box">
                    <h3>Audit</h3>
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
    </div>
</body>
</html>