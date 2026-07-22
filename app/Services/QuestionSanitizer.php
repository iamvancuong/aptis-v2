<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Support\Facades\URL;

/**
 * Strips answer keys out of Question metadata before it reaches the browser.
 *
 * Everything handed to the client is public: page source, DevTools and a plain
 * `curl` all see the same bytes. Anything the client must not know therefore has
 * to be removed here, on the server, rather than hidden with CSS or key handlers.
 *
 * Grading always reads the answer keys straight from the database
 * (see GradingService), so removing them from the client payload does not
 * affect scoring.
 */
class QuestionSanitizer
{
    /**
     * Top-level metadata keys holding an answer key or a worked solution,
     * mapped to the empty value that replaces them.
     *
     * They are blanked rather than removed. The Alpine templates reach into
     * these fields directly — `metadata.correct_answers[idx]` — and several do
     * so from `x-show` and `:class` expressions that are evaluated on every
     * render, before the learner has answered. Dropping the keys entirely would
     * turn those into "cannot read property of undefined". Keeping the shape
     * with an empty value leaks nothing and keeps every template working.
     */
    private const SENSITIVE_KEYS = [
        'correct_answers' => [],   // reading 1/3/4, listening 2/3/4, grammar 2
        'correct_answer'  => null, // listening 1
        'correct_option'  => null, // grammar 1
        'correct_index'   => null, // legacy shape from older JSON imports
        'explanation'     => null, // spells out the answer
        'sample_answer'   => null, // writing 1/2/3
    ];

    /**
     * Nested "parent.child" paths holding a worked solution.
     */
    private const SENSITIVE_PATHS = [
        'task1.sample_answer', // writing 4
        'task2.sample_answer', // writing 4
    ];

    /**
     * Metadata safe to render, with every answer key removed.
     */
    public function metadataForClient(Question $question): array
    {
        $metadata = $question->metadata ?? [];

        if (! is_array($metadata)) {
            return [];
        }

        foreach (self::SENSITIVE_KEYS as $key => $blank) {
            if (array_key_exists($key, $metadata)) {
                $metadata[$key] = $blank;
            }
        }

        foreach (self::SENSITIVE_PATHS as $path) {
            [$parent, $child] = explode('.', $path, 2);

            if (isset($metadata[$parent]) && is_array($metadata[$parent])
                && array_key_exists($child, $metadata[$parent])) {
                $metadata[$parent][$child] = null;
            }
        }

        // Reading Part 2 is a sentence-ordering task: the order of `sentences`
        // IS the answer, so there is no key to strip — it has to be shuffled.
        if ($question->skill === 'reading' && (int) $question->part === 2) {
            $metadata = $this->shuffleOrderingSentences($metadata, $question);
        }

        return $metadata;
    }

    /**
     * A question array safe to render, preserving the shape the Alpine
     * components already expect.
     */
    public function questionForClient(Question $question): array
    {
        return [
            'id'          => $question->id,
            'skill'       => $question->skill,
            'part'        => $question->part,
            'type'        => $question->type,
            'title'       => $question->title,
            'stem'        => $question->stem,
            'audio_path'  => $question->audio_path,
            'audio_url'   => $this->audioUrl($question),
            'audio_urls'  => $this->audioUrls($question),
            'image_path'  => $question->image_path,
            'point'       => $question->point,
            'order'       => $question->order,
            'metadata'    => $this->metadataForClient($question),
        ];
    }

    /**
     * Expiring, signed URL for a question's main audio file.
     */
    public function audioUrl(Question $question): ?string
    {
        if (blank($question->audio_path) || blank($question->id)) {
            return null;
        }

        return $this->signedAudioUrl($question->id);
    }

    /**
     * Listening Part 2 plays one clip per speaker, so it needs a URL per file.
     *
     * @return array<int, string>
     */
    public function audioUrls(Question $question): array
    {
        $files = $question->metadata['audio_files'] ?? null;

        if (! is_array($files) || blank($question->id)) {
            return [];
        }

        return array_map(
            fn (int $index) => $this->signedAudioUrl($question->id, $index),
            array_keys(array_values($files))
        );
    }

    private function signedAudioUrl(int $questionId, ?int $index = null): string
    {
        $parameters = ['question' => $questionId];

        if ($index !== null) {
            $parameters['index'] = $index;
        }

        // Long enough to outlast a full exam sitting, short enough that a
        // copied link is not a permanent download link.
        return URL::temporarySignedRoute('media.question-audio', now()->addHours(6), $parameters);
    }

    /**
     * @param  iterable<Question>  $questions
     * @return array<int, array>
     */
    public function collectionForClient($questions): array
    {
        return collect($questions)
            ->map(fn (Question $q) => $this->questionForClient($q))
            ->values()
            ->all();
    }

    /**
     * The answer key for one question, for release *after* the learner has
     * answered it. Mirrors the key names each part's Alpine component reads,
     * so the existing feedback markup keeps working unchanged.
     */
    public function answerKeyFor(Question $question): array
    {
        $metadata = $question->metadata ?? [];
        $key      = [];

        foreach (['correct_answers', 'correct_answer', 'correct_option', 'explanation'] as $field) {
            if (array_key_exists($field, $metadata)) {
                $key[$field] = $metadata[$field];
            }
        }

        // Reading Part 2 has no answer-key field: the correct order is the
        // stored order of `sentences`, minus the fixed opening sentence.
        if ($question->skill === 'reading' && (int) $question->part === 2) {
            $key['sentences'] = $metadata['sentences'] ?? [];
        }

        return $key;
    }

    /**
     * Shuffle the orderable sentences while keeping the fixed opening sentence
     * in place. The shuffle is stable for a given learner and question so that
     * reloading the page — or restoring saved progress — does not rearrange the
     * pool mid-attempt.
     */
    private function shuffleOrderingSentences(array $metadata, Question $question): array
    {
        $sentences = $metadata['sentences'] ?? null;

        if (! is_array($sentences) || count($sentences) <= 2) {
            return $metadata;
        }

        $opener = array_shift($sentences);
        $seed   = $question->id . '|' . (auth()->id() ?? 'guest');

        $metadata['sentences'] = array_merge([$opener], $this->stableShuffle($sentences, $seed));

        return $metadata;
    }

    /**
     * Deterministic shuffle driven by a seed, so the same learner sees the same
     * order every time without touching the global random number generator.
     */
    private function stableShuffle(array $items, string $seed): array
    {
        $keyed = [];

        foreach (array_values($items) as $index => $item) {
            $keyed[] = ['sort' => md5($seed . '|' . $index), 'item' => $item];
        }

        usort($keyed, fn ($a, $b) => strcmp($a['sort'], $b['sort']));

        return array_column($keyed, 'item');
    }
}
