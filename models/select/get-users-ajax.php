<?php
// models/select/get-users-ajax.php
session_start();
require_once __DIR__ . '/../../connexion/connexion.php';
require_once __DIR__ . '/select-utilisateur.php';

header('Content-Type: application/json');

// Vérifier l'accès
if (!isset($_SESSION['user_id']) || !in_array('DG', $_SESSION['roles'] ?? [])) {
    echo json_encode(['error' => 'Accès non autorisé']);
    exit();
}

try {
    // Récupérer les paramètres DataTables
    $start = $_POST['start'] ?? 0;
    $length = $_POST['length'] ?? 25;
    $search = $_POST['search']['value'] ?? '';
    $orderColumn = $_POST['order'][0]['column'] ?? 1;
    $orderDir = $_POST['order'][0]['dir'] ?? 'asc';
    
    // Récupérer les données paginées
    $data = getUsersPaginated($start, $length, $search, $orderColumn, $orderDir);
    
    // Compter le nombre total filtré
    $totalFiltered = countFilteredUsers($search);
    
    // Récupérer le nombre total (pour recordsTotal)
    $stats = getUsersStatistics();
    $totalRecords = $stats['total'] ?? 0;
    
    echo json_encode([
        "draw" => intval($_POST['draw'] ?? 1),
        "recordsTotal" => (int)$totalRecords,
        "recordsFiltered" => (int)$totalFiltered,
        "data" => $data
    ]);
    
} catch (Exception $e) {
    error_log("Erreur get-users-ajax: " . $e->getMessage());
    echo json_encode([
        "draw" => intval($_POST['draw'] ?? 1),
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => [],
        "error" => $e->getMessage()
    ]);
}
?>