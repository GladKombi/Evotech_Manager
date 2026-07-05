<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\Helpers;

class AuthController extends Controller
{
    public function login(): void
    {
        $this->authView('auth/login', ['title' => 'Connexion']);
    }
    public function authenticate(): void
    {
        $email = $_POST['email'] ?? '';
        $pass = $_POST['password'] ?? '';
        $sql = "SELECT u.*,
                       GROUP_CONCAT(r.fonction ORDER BY r.fonction SEPARATOR ',') AS roles
                FROM UTILISATEURS u
                LEFT JOIN ATTRIBUTIONS a ON a.utilisateur_id = u.utilisateur_id AND a.statut = 0
                LEFT JOIN ROLES r ON r.role_id = a.role_id AND r.statut = 0
                WHERE u.email = ? AND u.statut = 0 AND u.actif = 1
                GROUP BY u.utilisateur_id
                LIMIT 1";
        $s = Database::conn()->prepare($sql);
        $s->execute([$email]);
        $u = $s->fetch();
        if ($u && password_verify($pass, $u['mot_de_passe_hash'])) {
            $u['roles_list'] = array_values(array_filter(array_map('trim', explode(',', (string) ($u['roles'] ?? '')))));
            Session::setUser($u);
            Helpers::redirect('dashboard');
        }
        Session::flash('error', 'Email ou mot de passe incorrect');
        Helpers::redirect('login');
    }
    public function logout(): void
    {
        Session::destroy();
        Helpers::redirect('login');
    }
}
