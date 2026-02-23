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

    /**
     * Grade writing response using OpenAI.
     */
    public function gradeWriting(array $data): array
    {
        $systemPrompt = $this->getSystemPrompt();
        $userPrompt = $this->getUserPrompt($data);

        if (empty($this->apiKey)) {
            Log::info('AiService: No API key found, returning mock success response.');
            return $this->getMockResponse($data['part']);
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(20)
                ->retry(1, 1000, function ($exception, $request) {
                    return $exception->getCode() === 429;
                })
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.3,
                    'max_tokens' => 800,
                ]);

            if ($response->failed()) {
                Log::error('OpenAI API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('AI Service failed to provide feedback.');
            }

            $result = $response->json();
            $feedback = json_decode($result['choices'][0]['message']['content'], true);

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

    protected function getSystemPrompt(): string
    {
        return <<<PROMPT
You are an official APTIS Writing examiner.

You MUST return a valid JSON object.
Return JSON only.
Do NOT use markdown.
Do NOT include explanations outside JSON.

All scores must be integers from 0 to 5.

CRITICAL INSTRUCTION:
Depending on the Part, the "part_responses" array MUST contain exactly the number of objects corresponding to the number of questions/inputs:
- Part 1: Exactly 5 objects.
- Part 2: Exactly 1 object.
- Part 3: Exactly 3 objects.
- Part 4: Exactly 2 objects (Informal Email, then Formal Email).

Follow exactly this structure:

{
  "schema_version": 3,
  "part": number,
  "scores": {
    "grammar": integer,
    "vocabulary": integer,
    "coherence": integer,
    "task_fulfillment": integer
  },
  "overall_score": integer,
  "feedback": {
    "grammar": string,
    "vocabulary": string,
    "coherence": string,
    "task_fulfillment": string
  },
  "part_responses": [
    {
      "input_index": 0,
       "label": "string (e.g., 'Câu 1', 'Informal Email')",
       "improved_sample": "string",
       "detailed_corrections": [
         {
           "original": "string",
           "corrected": "string",
           "explanation": "string"
         }
       ]
    }
  ],
  "key_mistakes": ["string"],
  "suggestions": ["string"]
}
PROMPT;
    }

    protected function getPartRequirements(int $part): string
    {
        switch ($part) {
            case 1: 
                return "Part 1 involves 5 very short personal questions. Focus on simple grammatical accuracy and relevance. Answers should be 1-5 words each.";
            case 2:
                return "Part 2 is a single personal information question (joining a club/activity). Focus on sentence structure and word count (20-30 words).";
            case 3:
                return "Part 3 involves 3 social network interaction questions. Focus on conversational tone, coherence, and word count (30-40 words each). Respond naturally to the prompts.";
            case 4:
                return "Part 4 involves TWO emails:
                1. Informal email to a friend (approx 50 words). Tone: Casual, friendly.
                2. Formal email to an authority (120-150 words). Tone: Professional, structured, polite.
                Evaluate if the student successfully changed the register/tone between the two emails. This is CRITICAL for Part 4.";
            default:
                return "";
        }
    }

    protected function getUserPrompt(array $data): string
    {
        $part = $data['part'];
        $wordLimit = $data['word_limit'] ?? 'N/A';
        if (is_array($wordLimit)) {
            $wordLimit = ($wordLimit['min'] ?? 0) . ' - ' . ($wordLimit['max'] ?? 'N/A') . ' words';
        }
        $question = $data['question_stem'];
        
        $studentText = is_array($data['student_answer']) ? json_encode($data['student_answer'], JSON_UNESCAPED_UNICODE) : (string) $data['student_answer'];
        if (mb_strlen($studentText) > 1000) {
            $studentText = mb_substr($studentText, 0, 1000) . '... [truncated]';
        }
        
        $partRequirements = $this->getPartRequirements($part);

        return <<<PROMPT
Evaluate this APTIS Writing response for Part {$part}.

Part Context:
{$partRequirements}

Word limit: {$wordLimit}

Task (Question):
{$question}

Student Response:
{$studentText}
PROMPT;
    }

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
