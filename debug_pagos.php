<?php
require_once 'vendor/autoload.php';

// Configurar Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== FORMA DE PAGOS ===\n";
$formas = DB::table('forma_pagos')->get();
foreach($formas as $forma) {
    echo "ID: {$forma->id} - Nombre: '{$forma->nombre}'\n";
}

echo "\n=== PAGOS DEL DIA ACTUAL ===\n";
$hoy = date('Y-m-d');
$pagos = DB::table('pagos')
    ->join('forma_pagos', 'pagos.forma_pago', '=', 'forma_pagos.id')
    ->join('users', 'pagos.id_usuario', '=', 'users.id')
    ->select('pagos.*', 'forma_pagos.nombre as forma_nombre', 'users.name as usuario_nombre')
    ->whereDate('pagos.created_at', $hoy)
    ->get();

foreach($pagos as $pago) {
    echo "Usuario: {$pago->usuario_nombre} - Forma: '{$pago->forma_nombre}' - Importe: {$pago->importe} - Fecha: {$pago->created_at}\n";
}

echo "\n=== QUERY DEL CONTROLADOR ===\n";
$pagosUsuarios = DB::table('pagos')
    ->join('users', 'pagos.id_usuario', '=', 'users.id')
    ->join('forma_pagos', 'pagos.forma_pago', '=', 'forma_pagos.id')
    ->select(
        'users.id as usuario_id',
        'users.name as nombreUsuario',
        'forma_pagos.nombre as forma_pago',
        DB::raw('SUM(pagos.importe) as total_cobrado')
    )
    ->whereDate('pagos.created_at', $hoy)
    ->groupBy('users.id', 'users.name', 'forma_pagos.id', 'forma_pagos.nombre')
    ->get();

echo "Resultados agrupados:\n";
foreach($pagosUsuarios as $pago) {
    echo "Usuario: {$pago->nombreUsuario} - Forma: '{$pago->forma_pago}' - Total: {$pago->total_cobrado}\n";
}