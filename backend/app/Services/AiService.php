<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    protected string $apiKey;
    protected string $model = 'gpt-4o-mini';

    public function __construct()
    {
        $this->apiKey = config('services.openai.key', env('OPENAI_API_KEY'));
    }

    public function gradeWriting(array $data, ?string $targetLevel = 'B2'): array
    {
        $part = $data['part'];
        $wordLimit = $data['word_limit'] ?? 'N/A';
        if (is_array($wordLimit)) {
            $wordLimit = ($wordLimit['min'] ?? 0) . ' - ' . ($wordLimit['max'] ?? 'N/A') . ' words';
        }
        $question = $data['question_stem'];
        $metadata = $data['metadata'] ?? [];
        
        $studentText = is_array($data['student_answer']) ? json_encode($data['student_answer'], JSON_UNESCAPED_UNICODE) : (string) $data['student_answer'];
        if (mb_strlen($studentText) > 1000) {
            $studentText = mb_substr($studentText, 0, 1000) . '... [truncated]';
        }

        $systemPrompt = view('prompts.writing_system', compact('part', 'targetLevel'))->render();
        $userPrompt = view('prompts.writing_user', compact('part', 'wordLimit', 'question', 'metadata', 'studentText'))->render();

        if (empty($this->apiKey)) {
            Log::info('AiService: No API key found, returning mock success response.');
            return $this->getMockResponse($data['part']);
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(90)
                ->retry(2, 2000, function ($exception, $request) {
                    // Retry on rate limit (429) or timeout (ConnectException)
                    return $exception->getCode() === 429
                        || $exception instanceof \GuzzleHttp\Exception\ConnectException;
                })
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.3,
                    'max_tokens' => 2500,
                ]);

            if ($response->failed()) {
                Log::error('OpenAI API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('AI Service failed to provide feedback.');
            }

            $result = $response->json();
            $content = $result['choices'][0]['message']['content'] ?? '{}';
            Log::info('AiService raw content:', ['content' => $content]);
            $feedback = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('AiService JSON Decode Error', [
                    'error' => json_last_error_msg(),
                    'content_snippet' => mb_substr($content, -200) // Log the end of the content to see if it was truncated
                ]);
                throw new \Exception('AI returned invalid JSON.');
            }

            return [
                'feedback' => $feedback,
                'usage' => [
                    'input_tokens' => $result['usage']['prompt_tokens'],
                    'output_tokens' => $result['usage']['completion_tokens'],
                    'total_tokens' => $result['usage']['total_tokens'],
                    'model' => $this->model,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('AiService Error: ' . $e->getMessage());
            throw $e;
        }
    }

    // Prompts now loaded from resources/views/prompts/

    protected function getMockResponse(int $part = 1): array
    {
        $mockFeedback = [
            "schema_version" => 3,
            "part" => $part,
            "scores" => [
                "grammar" => 3,
                "vocabulary" => 4,
                "coherence" => 3,
                "task_fulfillment" => 4
            ],
            "overall_score" => 14,
            "feedback" => [
                "grammar" => "Some minor tense issues.",
                "vocabulary" => "Good word range.",
                "coherence" => "Logical flow.",
                "task_fulfillment" => "All task requirements covered."
            ],
            "key_mistakes" => ["Verb tense inconsistency"],
            "suggestions" => ["Review past tense usage"]
        ];

        if ($part === 1) {
            $mockFeedback['part_responses'] = [];
            for ($i = 0; $i < 5; $i++) {
                $mockFeedback['part_responses'][] = [
                    'input_index' => $i,
                    'label' => "Câu " . ($i + 1),
                    'improved_sample' => "This is a much better sample answer for question " . ($i + 1) . ".",
                    'detailed_corrections' => $i === 0 ? [
                        [ "original" => "i am student", "corrected" => "I am a student.", "explanation" => "Missing article and capitalization." ],
                    ] : []
                ];
            }
            $mockFeedback['feedback']['task_fulfillment'] = "Answered all 5 questions briefly and directly.";
        } else if ($part === 3) {
            $mockFeedback['part_responses'] = [];
            for ($i = 0; $i < 3; $i++) {
                $mockFeedback['part_responses'][] = [
                    'input_index' => $i,
                    'label' => "Response " . ($i + 1),
                    'improved_sample' => "That sounds interesting! I'm looking forward to it.",
                    'detailed_corrections' => []
                ];
            }
        } else if ($part === 4) {
             $mockFeedback['part_responses'] = [
                [
                    'input_index' => 0,
                    'label' => "Informal Email",
                    'improved_sample' => "Hey! Just wanted to let you know the meeting is canceled. Bummer, right?",
                    'detailed_corrections' => []
                ],
                [
                    'input_index' => 1,
                    'label' => "Formal Email",
                    'improved_sample' => "Dear Sir/Madam, I am writing to formally express my dissatisfaction regarding the recent cancellation.",
                    'detailed_corrections' => [
                        [
                            "original" => "I write to complain...",
                            "corrected" => "I am writing to complain...",
                            "explanation" => "Present continuous is better for formal correspondence."
                        ]
                    ]
                ]
            ];
        } else {
             $mockFeedback['part_responses'] = [
                [
                    'input_index' => 0,
                    'label' => "Câu trả lời",
                    'improved_sample' => "I decided to join this social club because I am very interested in photography.",
                    'detailed_corrections' => []
                ]
            ];
        }

        return [
            'feedback' => $mockFeedback,
            'usage' => [
                'input_tokens' => 150,
                'output_tokens' => 200,
                'total_tokens' => 350,
                'model' => 'mock-mode'
            ]
        ];
    }
}
