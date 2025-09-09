<?php 
session_start();
require_once 'conn.php';

// ✅ Check correct role
if (!isset($_SESSION['auth_id']) || $_SESSION['role'] !== 'Resident') {
    header("Location: login.php");
    exit();
}

$authId = $_SESSION['auth_id'];

// ✅ Get the resident_id from residents table
$stmt = $pdo->prepare("SELECT id FROM residents WHERE auth_id = ? LIMIT 1");
$stmt->execute([$authId]);
$resident = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$resident) {
    die("Resident not found.");
}
$residentId = $resident['id'];

// ✅ Pending Appointments
$queryAppointments = "
    SELECT a.id, a.scheduled_for, a.status, d.name AS department_name, s.service_name
    FROM appointments a
    JOIN departments d ON a.department_id = d.id
    LEFT JOIN department_services s ON a.service_id = s.id
    WHERE a.resident_id = :resident_id AND a.status = 'Pending'
    ORDER BY a.scheduled_for ASC
";
$stmt = $pdo->prepare($queryAppointments);
$stmt->execute(['resident_id' => $residentId]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Completed Appointments
$queryCompleted = "
    SELECT a.id, a.scheduled_for, d.name AS department_name, s.service_name
    FROM appointments a
    JOIN departments d ON a.department_id = d.id
    LEFT JOIN department_services s ON a.service_id = s.id
    WHERE a.resident_id = :resident_id AND a.status = 'Completed'
    ORDER BY a.scheduled_for DESC
";
$stmtCompleted = $pdo->prepare($queryCompleted);
$stmtCompleted->execute(['resident_id' => $residentId]);
$completedAppointments = $stmtCompleted->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Appointments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="p-4">
<div class="container mt-4">
    <!-- Pending Appointments -->
    <div class="container bg-light p-4 shadow-sm border-rounded">
        <div class="d-flex align-items-center mb-3">
            <i class="fas fa-calendar-check fa-lg text-primary mr-2"></i>
            <h4 class="mb-0 text-primary font-weight-bold">My Pending Appointments</h4>
        </div>

        <?php if (empty($appointments)): ?>
            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle mr-2"></i>You have no pending appointments.
            </div>
        <?php else: ?>
            <div class="table">
                <table class="table table-hover table-bordered mt-2">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Transaction No.</th>
                            <th>Department</th>
                            <th>Service</th>
                            <th>Schedule</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $index => $appt): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><span class="badge badge-primary">Txn #<?= htmlspecialchars($appt['id']) ?></span></td>
                                <td><?= htmlspecialchars($appt['department_name']) ?></td>
                                <td><?= htmlspecialchars($appt['service_name'] ?? 'N/A') ?></td>
                                <td>
                                    <i class="far fa-calendar-alt mr-1 text-success"></i>
                                    <?= date('F d, Y h:i A', strtotime($appt['scheduled_for'])) ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info" data-toggle="modal" data-target="#matchModal<?= $appt['id'] ?>">
                                        <i class="fas fa-users mr-1"></i>View Matches
                                    </button>
                                </td>
                            </tr>

                            <!-- Modal -->
                            <div class="modal fade" id="matchModal<?= $appt['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header bg-light">
                                            <h5 class="modal-title text-primary">
                                                <i class="fas fa-user-friends mr-1"></i> Matching Appointments
                                            </h5>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>Your Schedule:</strong><br>
                                                <i class="far fa-clock mr-1"></i>
                                                <?= date('F d, Y h:i A', strtotime($appt['scheduled_for'])) ?>
                                            </p>
                                            <hr>
                                            <?php
                                            $matchQuery = "
                                                SELECT COUNT(*) AS total
                                                FROM appointments
                                                WHERE scheduled_for = :scheduled_for 
                                                AND resident_id != :resident_id
                                                AND status = 'Pending'
                                            ";
                                            $matchStmt = $pdo->prepare($matchQuery);
                                            $matchStmt->execute([
                                                'scheduled_for' => $appt['scheduled_for'],
                                                'resident_id' => $residentId
                                            ]);
                                            $matchResult = $matchStmt->fetch();
                                            ?>
                                            <p><strong>Matching Appointments:</strong>
                                                <span class="badge badge-pill badge-info ml-2"><?= $matchResult['total'] ?></span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Completed Appointments Section -->
    <div class="container bg-light p-4 shadow-sm border-rounded mt-5">
        <div class="d-flex align-items-center mb-3">
            <i class="fas fa-check-circle fa-lg text-success mr-2"></i>
            <h4 class="mb-0 text-success font-weight-bold">My Completed Appointments</h4>
        </div>

        <?php if (empty($completedAppointments)): ?>
            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle mr-2"></i>You have no completed appointments.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered mt-2">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Transaction No.</th>
                            <th>Department</th>
                            <th>Service</th>
                            <th>Schedule</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($completedAppointments as $index => $appt): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><span class="badge badge-success">#<?= htmlspecialchars($appt['id']) ?></span></td>
                                <td><?= htmlspecialchars($appt['department_name']) ?></td>
                                <td><?= htmlspecialchars($appt['service_name'] ?? 'N/A') ?></td>
                                <td>
                                    <i class="far fa-calendar-check mr-1 text-success"></i>
                                    <?= date('F d, Y h:i A', strtotime($appt['scheduled_for'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
