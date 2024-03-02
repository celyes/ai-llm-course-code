<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatRequest;
use Illuminate\Http\JsonResponse;
use OpenAI\Laravel\Facades\OpenAI;

class ChatController extends Controller
{
    public function __invoke(ChatRequest $request)
    {
        $result = OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => $request->toArray()['message']
        ]);
        return new JsonResponse([
            'message' => $result->choices[0]->message->content
        ]);
    }

    public function streamed(ChatRequest $request)
    {
        $stream = OpenAI::chat()->createStreamed([
            'model' => 'gpt-3.5-turbo',
            'messages' => $request->toArray()['message']
        ]);
        return response()->stream(function () use ($stream) {
            foreach ($stream as $response) {
                print_r($response->choices[0]->delta->content);
                @ob_flush();
                @flush();
            }
            @ob_end_clean();
        }, 200, [
            'Access-Control-Allow-Origin: http://localhost:5173,http://localhost:5174',
            'X-Accel-Buffering: no'
        ]);
    }
}
