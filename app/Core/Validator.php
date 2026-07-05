<?php
namespace App\Core;
class Validator {
    public static function required(array $data, array $fields): array { $errors=[]; foreach($fields as $f){ if(empty(trim((string)($data[$f]??'')))) $errors[$f]="Le champ $f est obligatoire"; } return $errors; }
}
