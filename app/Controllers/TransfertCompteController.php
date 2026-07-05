<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Helpers;
use App\Core\Session;
use App\Models\Compte;
use App\Models\MouvementCompte;
use App\Models\TransfertCompte;

class TransfertCompteController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();

        $this->view('transferts/index', [
            'title' => 'Transferts',
            'items' => (new TransfertCompte())->allWithDetails(),
            'comptes' => (new Compte())->all(),
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();

        $data = $_POST;
        unset($data['_csrf']);

        $sourceId = (int) ($data['compte_source_id'] ?? 0);
        $destinationId = (int) ($data['compte_destination_id'] ?? 0);
        $amount = (float) ($data['montant'] ?? 0);
        $userId = Session::user()['utilisateur_id'] ?? 1;

        if ($sourceId <= 0 || $destinationId <= 0 || $sourceId === $destinationId || $amount <= 0) {
            Session::flash('error', 'Transfert invalide');
            Helpers::redirect('transferts');
        }

        $date = $data['date_transfert'] ?: date('Y-m-d H:i:s');
        $reference = $data['reference_transfert'] ?: 'TRF-' . date('YmdHis');
        $motif = $data['motif'] ?? 'Transfert entre comptes';
        $movementModel = new MouvementCompte();

        $sortieId = $movementModel->createAndReturnId([
            'compte_id' => $sourceId,
            'type_mouvement' => 'sortie',
            'montant' => $amount,
            'motif' => $motif,
            'reference_piece' => $reference,
            'cree_par' => $userId,
            'date_mouvement' => $date,
        ]);

        $entreeId = $movementModel->createAndReturnId([
            'compte_id' => $destinationId,
            'type_mouvement' => 'entrée',
            'montant' => $amount,
            'motif' => $motif,
            'reference_piece' => $reference,
            'cree_par' => $userId,
            'date_mouvement' => $date,
        ]);

        $data['reference_transfert'] = $reference;
        $data['mouvement_sortie_id'] = $sortieId;
        $data['mouvement_entree_id'] = $entreeId;
        $data['valide_par'] = $userId;
        $data['date_transfert'] = $date;

        (new TransfertCompte())->create($data);
        (new Compte())->adjustBalance($sourceId, -$amount);
        (new Compte())->adjustBalance($destinationId, $amount);

        Session::flash('success', 'Transfert effectué avec succès');
        Helpers::redirect('transferts');
    }

    public function receipt(): void
    {
        Auth::requireAuth();

        $id = (int) ($_GET['id'] ?? 0);
        $operation = null;

        foreach ((new TransfertCompte())->allWithDetails() as $transfer) {
            if ((int) ($transfer['transfert_id'] ?? 0) === $id) {
                $operation = $transfer;
                break;
            }
        }

        if (!$operation) {
            Session::flash('error', 'Transfert introuvable');
            Helpers::redirect('transferts');
        }

        $this->authView('finance/recu', [
            'title' => 'Reçu de transfert',
            'type' => 'transfert',
            'operation' => $operation,
        ]);
    }

    public function delete(): void
    {
        Auth::requireAuth();

        $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
        (new TransfertCompte())->softDelete($id);
        Session::flash('success', 'Suppression effectuée');
        Helpers::redirect('transferts');
    }
}
