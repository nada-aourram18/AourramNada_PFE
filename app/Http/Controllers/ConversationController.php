<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Repositories\ConversationRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConversationController extends Controller
{
    public function __construct(
        protected ConversationRepository $conversations,
    ) {}

    public function index(Request $request): View
    {
        $page = max(1, (int) $request->get('page', 1));
        $conversations = $this->conversations->paginateIndex(
            $page,
            20,
            $request->filled('language') ? $request->string('language')->toString() : null,
            $request->filled('status') ? $request->string('status')->toString() : null,
        );

        return view('conversations.index', compact('conversations'));
    }

    public function show(Conversation $conversation): View
    {
        return view('conversations.show', compact('conversation'));
    }
}
