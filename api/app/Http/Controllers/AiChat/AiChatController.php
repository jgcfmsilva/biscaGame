<?php

namespace App\Http\Controllers\AiChat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiChatController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function ask(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $userMessage = $request->input('message');
        $apiKey = env('GEMINI_API_KEY');

        if (!$apiKey) {
            return response()->json([
                'answer' => 'Desculpa, a minha inteligência artificial não está configurada neste momento (API Key em falta).'
            ]);
        }

        // Preparei este prompt de sistema para manter a AI focada no jogo
        $systemPrompt = "You are 'Bisca Guru', an expert assistant for the traditional Portuguese card game 'Bisca'.
        Your personality is helpful, friendly, and concise.
        You technically are a 'Bot', but act like an experienced player.
        Only answer questions related to Bisca (rules, strategy, scoring, history).
        If the user asks about something else, politely refuse and steer back to Bisca.
        The user is asking in Portuguese, so answer in Portuguese (PT-PT).
        
        Game Context:
        - Deck: 40 cards (Ace=11, 7=10, King=4, Jack=3, Queen=2, rest=0).
        - Trump suit beats others.
        - Must follow suit only if deck is empty.
        
        User Question: $userMessage";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $systemPrompt]
                                ]
                            ]
                        ]
                    ]);

            if ($response->successful()) {
                $data = $response->json();
                $answer = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Desculpa, fiquei confuso.';
                return response()->json(['answer' => $answer]);
            } else {
                Log::error('Gemini API Error: ' . $response->body());
                return response()->json(['answer' => 'Estou com dificuldades técnicas de momento. Tenta mais tarde.'], 503);
            }

        } catch (\Exception $e) {
            Log::error('AI Controller Exception: ' . $e->getMessage());
            return response()->json(['answer' => 'Ocorreu um erro interno.'], 500);
        }
    }
}
