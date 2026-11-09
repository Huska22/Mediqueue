<?php
date_default_timezone_set('Africa/Nairobi');
require_once __DIR__ . '/../config/db.php';

// Auto-refresh every 5 seconds
header("Refresh: 5");

$nowServing = $pdo->query("
    SELECT qt.queue_no, qt.service_point
    FROM queue_tickets qt
    WHERE qt.status = 'serving'
    LIMIT 1
")->fetch();

$nextThree = $pdo->query("
    SELECT queue_no FROM queue_tickets
    WHERE status = 'waiting'
    ORDER BY arrival_at ASC
    LIMIT 3
")->fetchAll();

$nextList = array_map(fn($r) => $r['queue_no'], $nextThree);
?>
<!DOCTYPE html>
<html>
<head>
    <title>MediQueue Display</title>
    <style>
        body { font-family: Arial, sans-serif; background: #0a1466; color: white; margin: 0; padding: 0; min-height: 100vh; }
        .header { background: #060f3d; padding: 25px; text-align: center; font-size: 36px; font-weight: bold; letter-spacing: 2px; }
        .container { padding: 40px; text-align: center; }
        .label { font-size: 28px; letter-spacing: 4px; color: #b0c4ff; margin-bottom: 20px; }
        .qno { font-size: 220px; font-weight: bold; line-height: 1; margin: 20px 0 30px; color: #ffffff; text-shadow: 0 0 40px rgba(100,150,255,0.5); }
        .room { font-size: 28px; color: #e0e7ff; margin-bottom: 60px; }
        .next-label { font-size: 24px; letter-spacing: 3px; color: #b0c4ff; margin-bottom: 20px; }
        .next-list { font-size: 50px; font-weight: bold; color: #ffffff; }
        .next-list span { margin: 0 30px; }
        .footer { position: fixed; bottom: 20px; right: 30px; font-size: 20px; color: #b0c4ff; }
    </style>
</head>
<body>
    <div class="header">MediQueue — Outpatient Queue</div>
    <div class="container">
        <div class="label">NOW SERVING</div>
        <?php if ($nowServing): ?>
            <div class="qno"><?= htmlspecialchars($nowServing['queue_no']) ?></div>
            <div class="room">Please proceed to <?= htmlspecialchars($nowServing['service_point']) ?></div>
        <?php else: ?>
            <div class="qno" style="font-size:120px; color:#7f94d8;">--</div>
            <div class="room">Please wait</div>
        <?php endif; ?>

        <div class="next-label">NEXT</div>
        <div class="next-list">
            <?php if (empty($nextList)): ?>
                <span style="font-size:32px; color:#7f94d8;">No patients waiting</span>
            <?php else: ?>
                <?php foreach ($nextList as $n): ?>
                    <span><?= htmlspecialchars($n) ?></span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="footer"><?= date('d M Y | H:i') ?></div>
</body>
</html>