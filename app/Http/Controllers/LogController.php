<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $lines = (int) $request->query('lines', 500);
        $lines = max(10, min($lines, 10000));
        $logPath = storage_path('logs/laravel.log');

        $logContent = file_exists($logPath) ? $this->tail($logPath, $lines) : [];

        return view('logs.index', compact('logContent', 'lines'));
    }

    public function clear()
    {
        $logPath = storage_path('logs/laravel.log');

        file_put_contents($logPath, '');

        return redirect()->route('logs.index')->with('status', 'Logs borrados correctamente.');
    }

    private function tail(string $path, int $lines): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return [];
        }

        fseek($handle, 0, SEEK_END);
        $position = ftell($handle);
        $chunkSize = 8192;
        $buffer = '';

        while ($position > 0 && substr_count($buffer, "\n") <= $lines) {
            $read = min($chunkSize, $position);
            $position -= $read;

            fseek($handle, $position);
            $buffer = fread($handle, $read).$buffer;
        }

        fclose($handle);

        $endedWithNewline = $buffer === '' || str_ends_with($buffer, "\n");

        $rows = explode("\n", $buffer);

        if ($rows !== [] && end($rows) === '') {
            array_pop($rows);
        }

        $rows = array_slice($rows, -$lines);
        $last = count($rows) - 1;

        return array_map(
            static fn (string $line, int $index): string => $index === $last && !$endedWithNewline ? $line : $line."\n",
            $rows,
            array_keys($rows)
        );
    }
}
