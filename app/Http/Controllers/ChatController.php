<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatSendRequest;
use App\Models\Setting;
use App\Repositories\ConversationRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ChatController extends Controller
{
    private const SESSION_KEY = 'chat_conversations';

    public function __construct(
        protected ConversationRepository $conversations,
    ) {}

    public function index(): View
    {
        return view('chat.index');
    }

    public function send(ChatSendRequest $request): JsonResponse
    {
        $webhook = Setting::getValue('n8n_webhook_url') ?: config('services.n8n.webhook_url');
        if (! $webhook) {
            return response()->json([
                'ok' => false,
                'error' => __('messages.n8n_not_configured'),
            ], 422);
        }

        if ($this->conversations->usesAirtable()) {
            return $this->sendWithAirtable($request, $webhook);
        }

        return $this->sendWithSession($request, $webhook);
    }

    protected function sendWithAirtable(ChatSendRequest $request, string $webhook): JsonResponse
    {
        $conversation = null;
        $convId = $request->string('conversation_id')->toString();
        if ($convId !== '') {
            $conversation = $this->conversations->find($convId);
        }
        if ($conversation === null) {
            $conversation = $this->conversations->create([
                'patient_id' => null,
                'language' => $request->input('language', 'fr'),
                'messages' => [],
                'status' => 'active',
            ]);
        }

        $messages = $conversation->messages ?? [];
        $now = now()->toIso8601String();
        $messages[] = [
            'role' => 'user',
            'content' => $request->string('message')->toString(),
            'timestamp' => $now,
        ];

        $payload = [
            'message' => $request->string('message')->toString(),
            'conversation_id' => $conversation->id,
            'language' => $conversation->language,
            'history' => $messages,
        ];

        try {
            $response = Http::timeout(60)->acceptJson()->post($webhook, $payload);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => __('messages.n8n_unreachable'),
            ], 502);
        }

        if (! $response->successful()) {
            return response()->json([
                'ok' => false,
                'error' => __('messages.n8n_error_status', ['status' => $response->status()]),
            ], 502);
        }

        $reply = $this->extractReply($response->json() ?? [], $response->body());

        $messages[] = [
            'role' => 'assistant',
            'content' => $reply,
            'timestamp' => now()->toIso8601String(),
        ];

        $this->conversations->update($conversation, [
            'messages' => $messages,
            'language' => $request->input('language', $conversation->language),
        ]);

        return response()->json([
            'ok' => true,
            'reply' => $reply,
            'conversation_id' => $conversation->id,
        ]);
    }

    /**
     * Sans table Airtable « Conversations » : historique stocké en session (par navigateur).
     */
    protected function sendWithSession(Request $request, string $webhook): JsonResponse
    {
        $convId = $request->string('conversation_id')->toString();
        $bucket = $request->session()->get(self::SESSION_KEY, []);

        if ($convId !== '' && isset($bucket[$convId]) && is_array($bucket[$convId])) {
            $lang = (string) ($bucket[$convId]['language'] ?? 'fr');
            $messages = is_array($bucket[$convId]['messages'] ?? null) ? $bucket[$convId]['messages'] : [];
        } else {
            $convId = 'local-'.Str::uuid()->toString();
            $lang = (string) $request->input('language', 'fr');
            $messages = [];
        }

        $now = now()->toIso8601String();
        $messages[] = [
            'role' => 'user',
            'content' => $request->string('message')->toString(),
            'timestamp' => $now,
        ];

        $payload = [
            'message' => $request->string('message')->toString(),
            'conversation_id' => $convId,
            'language' => $lang,
            'history' => $messages,
        ];

        try {
            $response = Http::timeout(60)->acceptJson()->post($webhook, $payload);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => __('messages.n8n_unreachable'),
            ], 502);
        }

        if (! $response->successful()) {
            return response()->json([
                'ok' => false,
                'error' => __('messages.n8n_error_status', ['status' => $response->status()]),
            ], 502);
        }

        $reply = $this->extractReply($response->json() ?? [], $response->body());

        $messages[] = [
            'role' => 'assistant',
            'content' => $reply,
            'timestamp' => now()->toIso8601String(),
        ];

        $bucket[$convId] = [
            'language' => $request->input('language', $lang),
            'messages' => $messages,
        ];
        $request->session()->put(self::SESSION_KEY, $bucket);

        return response()->json([
            'ok' => true,
            'reply' => $reply,
            'conversation_id' => $convId,
        ]);
    }

    private function extractReply(array $json, string $rawBody): string
    {
        $candidates = [
            data_get($json, 'reply'),
            data_get($json, 'message'),
            data_get($json, 'output'),
            data_get($json, 'text'),
            data_get($json, 'data.reply'),
            data_get($json, '0.text'),
        ];

        foreach ($candidates as $c) {
            if (is_string($c) && trim($c) !== '') {
                return $c;
            }
        }

        $choice = $json['choices'][0] ?? null;
        if (is_array($choice) && is_string(data_get($choice, 'message.content'))) {
            return (string) data_get($choice, 'message.content');
        }

        return trim($rawBody) !== '' ? $rawBody : __('messages.empty_agent_reply');
    }
}
