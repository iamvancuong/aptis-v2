<?php

namespace App\Services;

use App\Exceptions\AiGradingException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AiService
{
    /** Giới hạn upload của endpoint /audio/transcriptions. */
    public const MAX_AUDIO_BYTES = 25 * 1024 * 1024;
    // Nullable: the key is optional, and gradeWriting() already returns a
    // friendly error when it is missing. Typing this as a plain string made
    // the container throw while building any controller that injects this
    // service, which took the whole practice page down.
    protected ?string $apiKey;
    protected string $model = 'gpt-4o-mini';

    public function __construct()
    {
        $this->apiKey = config('services.openai.key');
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
                // 45s/lần × tối đa 2 lần ≈ 92s xấu nhất. gpt-4o-mini thường trả
                // dưới 15s; hạ từ 90s để một lệnh chấm thủ công (chạy đồng bộ trong
                // HTTP request) không giữ PHP-FPM worker quá lâu trên shared hosting.
                ->timeout(45)
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

    /* ═══════════════════════════════════════════════════════════════════════
     * CHẤM NÓI (SPEAKING) — cách (A): phiên âm rồi chấm transcript.
     * Xem PLAN_CHAM_SPEAKING_AI.md. Cách này KHÔNG chấm được phát âm và độ
     * trôi chảy — giới hạn đó phải được nói thẳng trên giao diện học viên.
     * ═══════════════════════════════════════════════════════════════════ */

    /**
     * Audio → text. Ném AiGradingException có phân loại tạm/vĩnh viễn để job
     * biết nên để queue thử lại hay dừng và báo ra giao diện.
     *
     * @return array{text: string, model: string, bytes: int}
     */
    public function transcribe(string $audioPath): array
    {
        if (empty($this->apiKey)) {
            throw AiGradingException::permanent('config', 'Chưa cấu hình OPENAI_API_KEY.');
        }

        $disk = Storage::disk('public');

        // Đọc file trước khi gọi mạng: 4 lỗi dưới đây retry bao nhiêu lần cũng
        // vẫn hỏng, phát hiện sớm thì không tốn một lượt gọi API nào.
        try {
            if (!$disk->exists($audioPath)) {
                throw AiGradingException::permanent('file_missing', "Không tìm thấy audio: {$audioPath}");
            }
            $size = (int) $disk->size($audioPath);
        } catch (AiGradingException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw AiGradingException::permanent('file_missing', "Không đọc được audio {$audioPath}: " . $e->getMessage());
        }

        if ($size <= 0) {
            throw AiGradingException::permanent('empty_file', "Audio rỗng: {$audioPath}");
        }
        if ($size > self::MAX_AUDIO_BYTES) {
            throw AiGradingException::permanent('too_large', "Audio {$size} byte vượt giới hạn " . self::MAX_AUDIO_BYTES);
        }

        try {
            $contents = $disk->get($audioPath);
        } catch (\Throwable $e) {
            throw AiGradingException::permanent('file_missing', "Không đọc được nội dung {$audioPath}: " . $e->getMessage());
        }

        $model = (string) config('services.openai.transcribe_model', 'gpt-4o-mini-transcribe');

        try {
            $response = Http::withToken($this->apiKey)
                // Rộng hơn timeout của chấm chữ: upload file qua đường truyền
                // shared hosting chậm hơn nhiều so với gửi vài KB text.
                ->timeout(120)
                ->attach('file', $contents, basename($audioPath))
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model' => $model,
                ]);
        } catch (ConnectionException $e) {
            // Đây chính là kịch bản "shared host chặn outbound" trong plan.
            throw AiGradingException::retryable('network', 'Không kết nối được api.openai.com: ' . $e->getMessage());
        } catch (\Throwable $e) {
            throw AiGradingException::retryable('unknown', 'Lỗi gọi transcribe: ' . $e->getMessage());
        }

        if ($response->failed()) {
            throw $this->classifyHttpFailure($response, 'transcribe');
        }

        $text = trim((string) $response->json('text'));

        if ($text === '') {
            // Máy chạy đúng nhưng không nghe ra chữ nào. Không được coi là lỗi
            // hệ thống: gần như luôn là micro tắt hoặc học viên không nói.
            throw AiGradingException::permanent('no_speech', "Transcript rỗng: {$audioPath}");
        }

        return ['text' => $text, 'model' => $model, 'bytes' => $size];
    }

    /**
     * Chấm transcript. Khung JSON giống gradeWriting nhưng thang điểm phần là
     * 0–10 để khớp form chấm tay của giáo viên (`AttemptAnswer.score`).
     */
    public function gradeSpeaking(array $data, ?string $targetLevel = 'B2'): array
    {
        $part = (int) ($data['part'] ?? 1);
        $question = (string) ($data['question_stem'] ?? '');
        $metadata = $data['metadata'] ?? [];
        $transcript = trim((string) ($data['transcript'] ?? ''));

        if ($transcript === '') {
            throw AiGradingException::permanent('no_speech', 'Transcript rỗng, không có gì để chấm.');
        }

        // Bài Nói dài nhất (Part 4) cũng chỉ vài trăm từ; cắt ở đây là lưới an
        // toàn chống transcript lặp vô hạn khi audio nhiễu, tránh đốt token.
        if (mb_strlen($transcript) > 4000) {
            $transcript = mb_substr($transcript, 0, 4000) . '... [cắt bớt]';
        }

        $systemPrompt = view('prompts.speaking_system', compact('part', 'targetLevel'))->render();
        $userPrompt = view('prompts.speaking_user', compact('part', 'question', 'metadata', 'transcript'))->render();

        if (empty($this->apiKey)) {
            Log::info('AiService: thiếu OPENAI_API_KEY, trả mock cho gradeSpeaking.');
            return $this->getMockSpeakingResponse($part, $transcript);
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(45)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.3,
                    'max_tokens' => 2000,
                ]);
        } catch (ConnectionException $e) {
            throw AiGradingException::retryable('network', 'Không kết nối được api.openai.com: ' . $e->getMessage());
        } catch (\Throwable $e) {
            throw AiGradingException::retryable('unknown', 'Lỗi gọi chat/completions: ' . $e->getMessage());
        }

        if ($response->failed()) {
            throw $this->classifyHttpFailure($response, 'gradeSpeaking');
        }

        $content = $response->json('choices.0.message.content') ?? '';
        $feedback = json_decode((string) $content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($feedback)) {
            Log::error('AiService gradeSpeaking: JSON hỏng', [
                'error' => json_last_error_msg(),
                'snippet' => mb_substr((string) $content, -200),
            ]);
            // Retry được: cùng prompt chạy lại thường ra JSON hợp lệ.
            throw AiGradingException::retryable('bad_json', 'AI trả về JSON không hợp lệ.');
        }

        return [
            'feedback' => $this->normalizeSpeakingFeedback($feedback, $part),
            'usage' => [
                'input_tokens'  => $response->json('usage.prompt_tokens'),
                'output_tokens' => $response->json('usage.completion_tokens'),
                'total_tokens'  => $response->json('usage.total_tokens'),
                'model'         => $this->model,
            ],
        ];
    }

    /**
     * Đổi HTTP lỗi thành exception có phân loại.
     *
     * Ranh giới quan trọng: 429/5xx là tạm (retry có ích), 4xx còn lại là vĩnh
     * viễn (gửi lại y hệt vẫn hỏng). Gộp tất cả thành "thử lại" sẽ khiến key sai
     * hay audio hỏng phải chờ hết 3 lượt mới báo cho học viên.
     */
    protected function classifyHttpFailure(Response $response, string $context): AiGradingException
    {
        $status = $response->status();

        Log::error("AiService {$context}: HTTP {$status}", [
            'body' => mb_substr($response->body(), 0, 500),
        ]);

        if ($status === 429) {
            return AiGradingException::retryable('rate_limit', "Bị giới hạn tần suất (429) ở {$context}.");
        }
        if ($status >= 500) {
            return AiGradingException::retryable('server', "OpenAI lỗi {$status} ở {$context}.");
        }
        if (in_array($status, [401, 403], true)) {
            return AiGradingException::permanent('config', "Key bị từ chối ({$status}) ở {$context}.");
        }
        if (in_array($status, [400, 413, 415, 422], true)) {
            return AiGradingException::permanent('bad_audio', "Dữ liệu gửi lên bị từ chối ({$status}) ở {$context}.");
        }

        return AiGradingException::permanent('unknown', "HTTP {$status} ở {$context}.");
    }

    /**
     * Ép kết quả AI về đúng khung trước khi lưu.
     *
     * Model đôi lúc bỏ khoá, trả chuỗi thay vì số, hoặc chấm vượt thang. View
     * đọc thẳng mảng này nên thiếu khoá là vỡ trang bài làm của học viên —
     * chuẩn hoá ở đây rẻ hơn nhiều so với thủ `??` ở từng dòng Blade.
     */
    protected function normalizeSpeakingFeedback(array $feedback, int $part): array
    {
        $criteria = ['task_fulfillment', 'vocabulary', 'grammar', 'coherence'];

        $scores = [];
        foreach ($criteria as $key) {
            $raw = $feedback['scores'][$key] ?? 0;
            $scores[$key] = max(0, min(5, (int) round((float) $raw)));
        }

        $texts = [];
        foreach ($criteria as $key) {
            $value = $feedback['feedback'][$key] ?? '';
            $texts[$key] = is_string($value) ? trim($value) : '';
        }

        // Thang 10 để khớp form chấm tay của giáo viên. Nếu AI không trả (hoặc
        // trả bậy) thì suy ra từ 4 tiêu chí con thay vì để trống.
        $overall = $feedback['overall_score_10'] ?? null;
        if (!is_numeric($overall)) {
            $overall = array_sum($scores) / count($scores) * 2;
        }
        $overall = round(max(0, min(10, (float) $overall)), 1);

        $toStringList = function ($value): array {
            if (!is_array($value)) {
                return array_filter([is_string($value) ? trim($value) : '']);
            }
            return array_values(array_filter(array_map(
                fn ($item) => is_string($item) ? trim($item) : null,
                $value
            )));
        };

        return [
            'schema_version'   => 1,
            'part'             => $part,
            'scores'           => $scores,
            'overall_score_10' => $overall,
            'feedback'         => $texts,
            'key_mistakes'     => $toStringList($feedback['key_mistakes'] ?? []),
            'suggestions'      => $toStringList($feedback['suggestions'] ?? []),
            'improved_sample'  => is_string($feedback['improved_sample'] ?? null)
                ? trim($feedback['improved_sample'])
                : '',
            // Ghi thẳng vào dữ liệu, không chỉ nằm trong template: bản ghi nào
            // cũng tự mang theo giới hạn của chính nó, kể cả khi sau này đổi view.
            'not_assessed'     => ['pronunciation', 'fluency'],
        ];
    }

    protected function getMockSpeakingResponse(int $part, string $transcript): array
    {
        return [
            'feedback' => $this->normalizeSpeakingFeedback([
                'scores' => ['task_fulfillment' => 3, 'vocabulary' => 3, 'grammar' => 3, 'coherence' => 3],
                'overall_score_10' => 6,
                'feedback' => [
                    'task_fulfillment' => '[Chế độ mock] Trả lời đúng trọng tâm câu hỏi.',
                    'vocabulary' => '[Chế độ mock] Từ vựng ở mức đủ dùng.',
                    'grammar' => '[Chế độ mock] Một vài lỗi thì nhỏ.',
                    'coherence' => '[Chế độ mock] Ý được sắp xếp dễ theo dõi.',
                ],
                'key_mistakes' => ['[Chế độ mock] Không có khoá OPENAI_API_KEY nên đây là dữ liệu giả.'],
                'suggestions' => ['[Chế độ mock] Cấu hình OPENAI_API_KEY để chấm thật.'],
                'improved_sample' => mb_substr($transcript, 0, 200),
            ], $part),
            'usage' => [
                'input_tokens' => 0,
                'output_tokens' => 0,
                'total_tokens' => 0,
                'model' => 'mock-mode',
            ],
        ];
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
