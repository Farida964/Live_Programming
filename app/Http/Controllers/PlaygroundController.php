<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PlaygroundController extends Controller
{
    public function index()
    {
        return view('playground');
    }

    public function run(Request $request)
    {
        $res = Http::post('https://ce.judge0.com/submissions?base64_encoded=false&wait=true', [
            'source_code' => $request->code,
            'language_id' => 71, // 71 = Python
        ]);

        $d = $res->json();
        return response()->json([
            'output' => $d['stdout'] ?? $d['stderr'] ?? $d['compile_output'] ?? 'Tidak ada output',
        ]);
    }

    public function chat(Request $request)
    {
        $message = trim((string) $request->message);

        if ($message === '') {
            return response()->json([
                'reply' => 'Tulis pertanyaan atau pesan error yang ingin kamu cari solusinya. Saya bisa bantu debugging, bug, dan coding.',
            ]);
        }

        $apiKey = env('AI_API_KEY');
        if (empty($apiKey)) {
            return response()->json([
                'reply' => "Saya siap membantu, tapi API key AI belum diatur. Tambahkan baris berikut di file .env:\n\nAI_API_KEY=your_google_api_key_here\nAI_MODEL=gemini-2.0-flash\n\nSetelah itu restart server Laravel, lalu saya bisa menjawab pertanyaan coding, bug, dan error dengan lebih baik.",
            ]);
        }

        try {
            $model = env('AI_MODEL', 'gemini-2.0-flash');
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $apiKey;

            $res = Http::acceptJson()->post($url, [
                'contents' => [[
                    'parts' => [[
                        'text' => "Kamu adalah AI assistant coding yang sangat membantu. Jawab dalam bahasa Indonesia. Fokus pada debugging, solusi bug, dan penjelasan teknis yang jelas.\n- Jika pengguna memberikan error, identifikasi penyebab utama dan berikan solusi langkah demi langkah.\n- Jika ada kode, jelaskan logika, berikan contoh perbaikan, dan tunjukkan snippet yang benar.\n- Jangan hanya memberi jawaban singkat; bantu user memahami root cause.\n- Saat tidak yakin, jelaskan kemungkinan penyebabnya dan cara mengeceknya.\n\nPertanyaan user: " . $message,
                    ]],
                ]],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'maxOutputTokens' => 1200,
                ],
            ]);

            $reply = $res->json('candidates.0.content.parts.0.text');

            if ($res->failed() || empty($reply)) {
                $errorDetail = $res->json('error.message') ?? 'Gagal memanggil Gemini';
                return response()->json([
                    'reply' => 'AI sedang gagal merespons. Cek kembali API key Google Gemini di file .env dan pastikan model yang dipilih valid. Detail: ' . $errorDetail,
                ]);
            }

            return response()->json(['reply' => $reply]);
        } catch (\Throwable $e) {
            return response()->json([
                'reply' => 'Terjadi kesalahan saat memanggil AI: ' . $e->getMessage(),
            ]);
        }
    }
}