<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['clinician', 'admin']);
require_once __DIR__ . '/../config/db.php';

$u = current_user();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);

    try {
        if ($action === 'call_next') {
            $serving = $pdo->query("SELECT ticket_id FROM queue_tickets WHERE status='serving' LIMIT 1")->fetch();
            if ($serving) {
                $error = "Another patient is currently being served. Complete them first.";
            } else {
                $next = $pdo->query("SELECT ticket_id FROM queue_tickets WHERE status='waiting' ORDER BY arrival_at ASC LIMIT 1")->fetch();
                if ($next) {
                    $tid = $next['ticket_id'];
                    $pdo->prepare("UPDATE queue_tickets SET status='serving' WHERE ticket_id=?")->execute([$tid]);
                    $pdo->prepare("INSERT INTO consultation_events (ticket_id, clinician_id, started_at, status) VALUES (?, ?, NOW(), 'serving')")->execute([$tid, $u['id']]);
                    $pdo->prepare("INSERT INTO audit_events (user_id, ticket_id, event_type) VALUES (?, ?, 'called_next')")->execute([$u['id'], $tid]);
                    $success = "Called next patient.";
                } else {
                    $error = "No waiting patients.";
                }
            }
        } elseif ($action === 'complete' && $ticket_id > 0) {
            $pdo->prepare("UPDATE queue_tickets SET status='completed' WHERE ticket_id=?")->execute([$ticket_id]);
            $pdo->prepare("UPDATE consultation_events SET status='completed', completed_at=NOW() WHERE ticket_id=? AND status='serving'")->execute([$ticket_id]);
            $pdo->prepare("INSERT INTO audit_events (user_id, ticket_id, event_type) VALUES (?, ?, 'completed')")->execute([$u['id'], $ticket_id]);
            $success = "Patient marked as completed.";
        }
    } catch (Exception $e) {
        $error = "Action failed: " . $e->getMessage();
    }
}

$nowServing = $pdo->query("SELECT qt.ticket_id, qt.queue_no, qt.arrival_at, p.name FROM queue_tickets qt JOIN patients p ON p.patient_id = qt.patient_id WHERE qt.status = 'serving' LIMIT 1")->fetch();
$waiting = $pdo->query("SELECT qt.ticket_id, qt.queue_no, qt.source, qt.arrival_at, p.name FROM queue_tickets qt JOIN patients p ON p.patient_id = qt.patient_id WHERE qt.status = 'waiting' ORDER BY qt.arrival_at ASC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Clinician Dashboard - MediQueue</title>
    <style>
        * { box-sizing: border-box; }
        /* MATCHED APP THEME */
        body { font-family: Arial; margin: 0; background: #f4f6f9; } 
        .header { background: #0d1b8f; color: white; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 20px; }
        .header a { color: white; text-decoration: none; background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 5px; }
        .layout { display: flex; min-height: calc(100vh - 55px); }
        
        /* MATCHED SIDEBAR */
        .sidebar { width: 220px; background: #37474f; padding: 20px 0; }
        .sidebar a { display: block; color: white; text-decoration: none; padding: 14px 20px; font-size: 14px; }
        .sidebar a:hover, .sidebar a.active { background: #263238; }
        
        .main { flex: 1; padding: 25px; display: flex; flex-direction: column; align-items: center; }
        
        .alert { padding: 12px 15px; border-radius: 6px; margin-bottom: 20px; width: 100%; max-width: 600px;}
        .alert.success { background: #e8f5e9; color: #2e7d32; }
        .alert.error { background: #ffebee; color: #c62828; }

        .panel { background: white; padding: 20px; width: 100%; max-width: 600px; margin-bottom: 20px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); border-radius: 8px;}
        
        .now-serving h3 { margin: 0 0 10px 0; font-size: 16px; font-weight: bold; color: #666; text-transform: uppercase;}
        .now-serving p { margin: 0; font-size: 16px; }
        .now-serving .qno { font-size: 24px; font-weight: bold; margin-right: 10px; color: #0d1b8f;}

        .waiting-list h3 { margin: 0 0 10px 0; font-size: 18px; font-weight: bold; color: #0d1b8f; text-align: center; }
        table { width: 100%; border-collapse: collapse; text-align: center; }
        th, td { padding: 12px; border-bottom: 1px solid #eee; font-size: 14px; }
        th { background: #f9f9f9; font-weight: bold; color: #666; text-transform: uppercase; font-size: 12px;}

        /* Bottom Buttons */
        .action-buttons { display: flex; gap: 10px; width: 100%; max-width: 600px; margin-top: auto; padding-top: 20px;}
        .action-buttons form { flex: 1; }
        .action-buttons button { width: 100%; color: white; padding: 15px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 16px; }
        
        .btn-blue { background: #0d1b8f; }
        .btn-blue:hover { background: #0a1466; }
        
        /* Made permanently green as requested */
        .btn-green { background: #2e7d32; }
        .btn-green:hover { background: #1b5e20; }
    </style>
</head>
<body>
    <div class="header">
        <h1>MediQueue | Dr. Clinician | Logout</h1>
        <a href="/Mediqueue/auth/logout.php">Logout</a>
    </div>
    <div class="layout">
        <div class="sidebar">
            <a href="/Mediqueue/clinician/dashboard.php" class="active">Dashboard</a>
            <a href="javascript:void(0);">Waiting List</a>
            <a href="javascript:void(0);">Completed</a>
            <a href="javascript:void(0);">Reports</a>
        </div>
        <div class="main">
            
            <?php if ($success): ?><div class="alert success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <!-- Now Serving Panel -->
            <div class="panel now-serving">
                <h3>Now Serving</h3>
                <?php if ($nowServing): ?>
                    <p><span class="qno"><?= htmlspecialchars($nowServing['queue_no']) ?></span> | <?= htmlspecialchars($nowServing['name']) ?></p>
                <?php else: ?>
                    <p>No patient currently serving.</p>
                <?php endif; ?>
            </div>

            <!-- Waiting List Panel -->
            <div class="panel waiting-list">
                <h3>Waiting List</h3>
                <?php if (empty($waiting)): ?>
                    <p style="text-align:center; color:#999;">No patients waiting.</p>
                <?php else: ?>
                    <table>
                        <tr>
                            <th>Queue No</th>
                            <th>Patient Name</th>
                            <th>Source</th>
                            <th>Wait Time</th>
                        </tr>
                        <?php foreach ($waiting as $w): 
                            $wait_min = round((time() - strtotime($w['arrival_at'])) / 60);
                            $wait_display = $wait_min > 0 ? $wait_min . ' min' : '< 1 min';
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($w['queue_no']) ?></strong></td>
                            <td><?= htmlspecialchars($w['name']) ?></td>
                            <td><?= ucfirst($w['source']) ?></td>
                            <td><?= $wait_display ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <form method="POST">
                    <input type="hidden" name="action" value="call_next">
                    <button type="submit" class="btn-blue">Call Next</button>
                </form>
                <form method="POST">
                    <input type="hidden" name="action" value="complete">
                    <?php if($nowServing): ?>
                        <input type="hidden" name="ticket_id" value="<?= $nowServing['ticket_id'] ?>">
                    <?php endif; ?>
                    <button type="submit" class="btn-green">Complete</button>
                </form>
            </div>

        </div>
    </div>
</body>
</html>