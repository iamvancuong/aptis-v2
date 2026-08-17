<?php

namespace App\Services\PartHandlers\Reading;

use App\Services\PartHandlers\PartHandlerInterface;

class ReadingPart3Handler implements PartHandlerInterface
{
    /** Nhãn mặc định của 4 người/đoạn văn khi chưa đặt tên riêng. */
    private const DEFAULT_NAMES = ['A', 'B', 'C', 'D'];

    public function getValidationRules(): array
    {
        return [
            'metadata.options' => 'required|array|min:4',
            'metadata.options.*' => 'required|string',
            // Tên hiển thị của từng người đọc (mặc định A/B/C/D). Không bắt buộc:
            // bỏ trống thì tự rơi về nhãn chữ cái ở formatMetadata().
            'metadata.names' => 'nullable|array',
            'metadata.names.*' => 'nullable|string|max:100',
            'metadata.questions' => 'required|array|min:1',
            'metadata.questions.*' => 'required|string',
            'metadata.correct_answers' => 'required|array',
            'metadata.correct_answers.*' => 'required|integer|min:0',
        ];
    }

    public function formatMetadata(array $data): array
    {
        return [
            'options' => $data['metadata']['options'] ?? [],
            'names' => $this->normalizeNames($data['metadata']['names'] ?? []),
            'questions' => $data['metadata']['questions'] ?? [],
            'correct_answers' => $data['metadata']['correct_answers'] ?? [],
        ];
    }

    /**
     * Đảm bảo luôn có đủ nhãn cho mỗi người đọc: ô nào bỏ trống thì dùng
     * chữ cái mặc định (A/B/C/D) để giao diện không bao giờ hiển thị rỗng.
     *
     * @param  array<int, mixed>  $names
     * @return array<int, string>
     */
    private function normalizeNames(array $names): array
    {
        $normalized = [];

        foreach (self::DEFAULT_NAMES as $index => $default) {
            $value = trim((string) ($names[$index] ?? ''));
            $normalized[$index] = $value !== '' ? $value : $default;
        }

        return $normalized;
    }
}
