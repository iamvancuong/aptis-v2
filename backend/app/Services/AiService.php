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
                    'temperature' => 0.7,
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
You are a certified APTIS Writing examiner.

Evaluate student responses strictly according to APTIS criteria:
1. Grammar accuracy
2. Vocabulary range and appropriacy
3. Coherence and organization (especially for Part 4)
4. Task achievement (answering all sub-questions)

Rules:
- Be constructive and concise.
- Only point out significant grammar mistakes.
- Provide clear, actionable explanations.
- Do not repeat the full student text.
- If the student provided multiple answers (Part 1 or 3), provide a summary feedback that covers all of them.
- Limit improved sample to maximum 150 words.

Return ONLY valid JSON.
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
        $studentText = is_array($data['student_answer']) ? json_encode($data['student_answer'], JSON_UNESCAPED_UNICODE) : $data['student_answer'];
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

Return response strictly in this JSON format:
{
  "overall_score_estimate": "Band estimate (e.g., A1, A2, B1, B2, C). Base this on the average of all sub-answers if applicable.",
  "grammar_feedback": [
    {
      "original": "segment with error",
      "correction": "corrected segment",
      "explanation": "why it was wrong"
    }
  ],
  "vocabulary_feedback": "Evaluate the range and level of vocabulary. (max 80 words)",
  "coherence_feedback": "Evaluate the logical flow and structure. For Part 4, specifically mention tone/register. (max 80 words)",
  "task_fulfillment_feedback": "Did the student answer all questions and follow the word count? (max 80 words)",
  "improved_sample_paragraph": "Provide ONE high-quality version of the response. For Part 4, only provide an improved version of the Formal Email (second task)."
}
PROMPT;
    }

    protected function getMockResponse(int $part = 1): array
    {
        $feedbackByPart = [
            1 => [
                'overall_score_estimate' => 'A1 (Mock)',
                'grammar_feedback' => [['original' => 'i is happy', 'correction' => 'I am happy', 'explanation' => 'Subject-verb agreement.']],
                'vocabulary_feedback' => 'Basic vocabulary used correctly for personal questions.',
                'coherence_feedback' => 'Answers are short and direct as required.',
                'task_fulfillment_feedback' => 'All 5 questions answered briefly.',
                'improved_sample_paragraph' => 'I am a student. I live in Hanoi. I like playing football with my friends on weekends.'
            ],
            2 => [
                'overall_score_estimate' => 'A2 (Mock)',
                'grammar_feedback' => [['original' => 'I join club because I like sing.', 'correction' => 'I joined the club because I like singing.', 'explanation' => 'Punctuation and gerund usage.']],
                'vocabulary_feedback' => 'Good attempt at describing interests.',
                'coherence_feedback' => 'Single paragraph is structured logically.',
                'task_fulfillment_feedback' => 'Met the 20-30 words requirement.',
                'improved_sample_paragraph' => 'I decided to join this social club because I am very interested in photography. I hope to meet new people and improve my skills during the weekends.'
            ],
            3 => [
                'overall_score_estimate' => 'B1 (Mock)',
                'grammar_feedback' => [['original' => 'The event was great, i enjoy it.', 'correction' => 'The event was great; I enjoyed it.', 'explanation' => 'Past tense consistency.']],
                'vocabulary_feedback' => 'Appropriate conversational vocabulary.',
                'coherence_feedback' => 'Good use of linking words between the three responses.',
                'task_fulfillment_feedback' => 'All 3 social network segments are well-addressed.',
                'improved_sample_paragraph' => "1. That sounds interesting! 2. I'm looking forward to it. 3. I hope we can all go together next time."
            ],
            4 => [
                'overall_score_estimate' => 'B2 (Mock)',
                'grammar_feedback' => [['original' => 'I write to complain...', 'correction' => 'I am writing to complain...', 'explanation' => 'Present continuous for formal correspondence.']],
                'vocabulary_feedback' => 'Clear distinction between casual words (thanks) and formal words (appreciate).',
                'coherence_feedback' => 'Strong transition between the informal email and the formal letter structure.',
                'task_fulfillment_feedback' => 'Both email tasks completed with appropriate word counts and tones.',
                'improved_sample_paragraph' => "Dear Sir/Madam, I am writing to formally express my dissatisfaction regarding the recent cancellation of the club meeting. This event was highly anticipated by all members..."
            ]
        ];

        return [
            'feedback' => $feedbackByPart[$part] ?? $feedbackByPart[1],
            'usage' => [
                'input_tokens' => 0,
                'output_tokens' => 0,
                'total_tokens' => 0,
                'model' => 'mock-mode'
            ]
        ];
    }
}
