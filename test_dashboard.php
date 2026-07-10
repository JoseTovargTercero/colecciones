<?php
session_start();
$_SESSION['user_id'] = 'c7003fe3-8faf-446f-aba0-a57c316e40af';
$_SESSION['nombre'] = 'LUCEMI SOFIA MELGUERO ANIBAL';

require_once __DIR__ . '/models/DashboardModel.php';
$model = new DashboardModel();

$kpis = $model->kpis();
print_r($kpis);
