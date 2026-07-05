<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;

class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();

        $db = Database::conn();
        $modules = [
            ['table' => 'UTILISATEURS', 'label' => 'Utilisateurs', 'icon' => 'fa-user-shield', 'url' => 'utilisateurs'],
            ['table' => 'AGENTS', 'label' => 'Agents', 'icon' => 'fa-users', 'url' => 'agents'],
            ['table' => 'PROJETS', 'label' => 'Projets', 'icon' => 'fa-diagram-project', 'url' => 'projets'],
            ['table' => 'PARTENAIRES', 'label' => 'Partenaires', 'icon' => 'fa-handshake', 'url' => 'partenaires'],
            ['table' => 'COMPTES', 'label' => 'Comptes', 'icon' => 'fa-wallet', 'url' => 'comptes'],
            ['table' => 'PRODUCTION', 'label' => 'Productions', 'icon' => 'fa-chart-line', 'url' => 'productions'],
        ];

        $modules = array_values(array_filter($modules, static fn ($module) => Auth::can($module['url'])));

        foreach ($modules as $index => $module) {
            $modules[$index]['value'] = (int) $db
                ->query("SELECT COUNT(*) FROM {$module['table']} WHERE statut=0")
                ->fetchColumn();
        }

        $totalRecords = array_sum(array_column($modules, 'value'));

        $this->view('dashboard/index', [
            'title' => 'Tableau de bord',
            'modules' => $modules,
            'totalRecords' => $totalRecords,
        ]);
    }
}
