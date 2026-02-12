<?php
// create-test-admin.php
require_once 'connexion/connexion.php';

echo "<h3>Création des utilisateurs de test pour EvoTech Africa</h3>";
echo "<pre>";

try {
    // 1. Vérifier/Créer les rôles
    echo "1. Création des rôles...\n";
    
    $roles = [
        'DG',
        'ChefDepartement', 
        'Chef_projet',
        'comptable',
        'actionnaire',
        'agent'
    ];
    
    foreach ($roles as $role) {
        $query = "INSERT INTO ROLES (fonction) VALUES (?) 
                 ON DUPLICATE KEY UPDATE fonction = VALUES(fonction)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$role]);
        echo "   ✓ Rôle '$role' créé/mis à jour\n";
    }
    
    // 2. Créer l'administrateur DG
    echo "\n2. Création de l'administrateur DG...\n";
    
    // Mot de passe: admin123 (hashé)
    $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
    
    $query = "INSERT INTO UTILISATEURS (nom, prenom, email, mot_de_passe_hash, profil, actif) 
              VALUES (?, ?, ?, ?, ?, 1)";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        'Dupont',
        'Jean',
        'dg@evo.africa',
        $hashed_password,
        'Directeur Général - Administrateur système principal'
    ]);
    
    $admin_id = $conn->lastInsertId();
    echo "   ✓ Administrateur DG créé (ID: $admin_id)\n";
    echo "   Email: dg@evo.africa / Mot de passe: admin123\n";
    
    // 3. Attribuer tous les rôles à l'admin
    echo "\n3. Attribution des rôles à l'administrateur...\n";
    
    $query = "INSERT INTO ATTRIBUTIONS (utilisateur_id, role_id) 
              SELECT ?, role_id FROM ROLES 
              WHERE fonction IN ('DG', 'ChefDepartement', 'Chef_projet', 'comptable', 'actionnaire')";
    $stmt = $conn->prepare($query);
    $stmt->execute([$admin_id]);
    
    echo "   ✓ Tous les rôles attribués au DG\n";
    
    // 4. Créer d'autres utilisateurs de test
    echo "\n4. Création d'autres utilisateurs de test...\n";
    
    $test_users = [
        [
            'nom' => 'Martin',
            'prenom' => 'Sophie',
            'email' => 'chef.dept@evo.africa',
            'password' => 'chef123',
            'profil' => 'Chef du Département Communication',
            'roles' => ['ChefDepartement']
        ],
        [
            'nom' => 'Dubois',
            'prenom' => 'Pierre',
            'email' => 'chef.projet@evo.africa',
            'password' => 'projet123',
            'profil' => 'Chef de Projet Senior',
            'roles' => ['Chef_projet']
        ],
        [
            'nom' => 'Leroy',
            'prenom' => 'Marie',
            'email' => 'comptable@evo.africa',
            'password' => 'compte123',
            'profil' => 'Comptable principale',
            'roles' => ['comptable']
        ]
    ];
    
    foreach ($test_users as $user) {
        $hashed_pass = password_hash($user['password'], PASSWORD_DEFAULT);
        
        $query = "INSERT INTO UTILISATEURS (nom, prenom, email, mot_de_passe_hash, profil, actif) 
                  VALUES (?, ?, ?, ?, ?, 1)";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            $user['nom'],
            $user['prenom'],
            $user['email'],
            $hashed_pass,
            $user['profil']
        ]);
        
        $user_id = $conn->lastInsertId();
        
        // Attribuer les rôles
        foreach ($user['roles'] as $role_name) {
            $query = "INSERT INTO ATTRIBUTIONS (utilisateur_id, role_id) 
                      SELECT ?, role_id FROM ROLES WHERE fonction = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$user_id, $role_name]);
        }
        
        echo "   ✓ {$user['prenom']} {$user['nom']} créé ({$user['email']} / {$user['password']})\n";
    }
    
    // 5. Créer un département et un agent
    echo "\n5. Création d'un département et d'un agent...\n";
    
    // D'abord, définir le responsable du département (Sophie Martin)
    $query = "SELECT utilisateur_id FROM UTILISATEURS WHERE email = 'chef.dept@evo.africa'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $chef_dept = $stmt->fetch(PDO::FETCH_ASSOC);
    $responsable_id = $chef_dept['utilisateur_id'];
    
    $query = "INSERT INTO DEPARTEMENTS (code, nom, description, responsable_id) 
              VALUES ('DEV', 'Développement', 'Département développement logiciel', ?)
              ON DUPLICATE KEY UPDATE nom = VALUES(nom), description = VALUES(description), responsable_id = VALUES(responsable_id)";
    $stmt = $conn->prepare($query);
    $stmt->execute([$responsable_id]);
    
    // Récupérer l'ID du département
    $query = "SELECT departement_id FROM DEPARTEMENTS WHERE code = 'DEV'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $dept = $stmt->fetch(PDO::FETCH_ASSOC);
    $dept_id = $dept['departement_id'];
    
    // Créer l'agent
    $hashed_agent_pass = password_hash('agent123', PASSWORD_DEFAULT);
    
    $query = "INSERT INTO AGENTS (nom, prenom, email_professionnel, mot_de_passe_hash, departement_id, etat, date_embauche) 
              VALUES (?, ?, ?, ?, ?, 'Actif', CURDATE())";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        'Moreau',
        'Thomas',
        'agent.dev@evo.africa',
        $hashed_agent_pass,
        $dept_id
    ]);
    
    echo "   ✓ Agent Thomas Moreau créé (agent.dev@evo.africa / agent123)\n";
    
    // 6. Afficher le résumé
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "RÉSUMÉ DES COMPTES CRÉÉS :\n";
    echo str_repeat("=", 60) . "\n\n";
    
    $query = "SELECT 
                u.utilisateur_id as id,
                u.nom,
                u.prenom,
                u.email,
                u.actif,
                GROUP_CONCAT(DISTINCT r.fonction ORDER BY r.fonction) as roles,
                'Utilisateur' as type
              FROM UTILISATEURS u
              LEFT JOIN ATTRIBUTIONS a ON u.utilisateur_id = a.utilisateur_id
              LEFT JOIN ROLES r ON a.role_id = r.role_id
              WHERE u.statut = 0
              GROUP BY u.utilisateur_id
              
              UNION ALL
              
              SELECT 
                agent_id as id,
                nom,
                prenom,
                email_professionnel as email,
                CASE WHEN etat = 'Actif' THEN 1 ELSE 0 END as actif,
                'Agent' as roles,
                'Agent' as type
              FROM AGENTS
              WHERE statut = 0
              
              ORDER BY type, nom";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo sprintf("%-5s %-20s %-25s %-15s %-30s\n", 
        "ID", "Nom", "Email", "Type", "Rôles");
    echo str_repeat("-", 95) . "\n";
    
    foreach ($users as $user) {
        echo sprintf("%-5s %-20s %-25s %-15s %-30s\n",
            $user['id'],
            $user['prenom'] . ' ' . $user['nom'],
            $user['email'],
            $user['type'],
            $user['roles'] ?? 'N/A'
        );
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "ACCÈS AUX DASHBOARDS :\n";
    echo str_repeat("=", 60) . "\n\n";
    echo "• DG/Admin : http://localhost/votre-projet/login.php (dg@evo.africa / admin123)\n";
    echo "• Chef Département : chef.dept@evo.africa / chef123\n";
    echo "• Chef Projet : chef.projet@evo.africa / projet123\n";
    echo "• Comptable : comptable@evo.africa / compte123\n";
    echo "• Agent : agent.dev@evo.africa / agent123\n";
    echo "\nNote: Les mots de passe sont 'admin123', 'chef123', etc.\n";
    
} catch (PDOException $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>