<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $lines = (int) $request->query('lines', 500);
        $logPath = storage_path('logs/laravel.log');

        if (!file_exists($logPath)) {
            $logContent = [];
        } else {
            $file = file($logPath);
            $logContent = array_slice($file, -$lines);
        }

        return view('logs.index', compact('logContent', 'lines'));
    }

    public function clear()
    {
        $logPath = storage_path('logs/laravel.log');

        file_put_contents($logPath, '');

        return redirect()->route('logs.index')->with('status', 'Logs borrados correctamente.');
    }
}
