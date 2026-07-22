<?php

namespace App\Http\Controllers;

use App\Models\Question;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves question audio through the application instead of from a public
 * directory.
 *
 * Files under a public `storage` symlink are readable by anyone who knows or
 * guesses the path, which makes the listening bank trivial to mirror. Routing
 * playback through here means every request carries a signature that expires
 * and belongs to a signed-in learner.
 */
class MediaController extends Controller
{
    public function questionAudio(Question $question, ?int $index = null): StreamedResponse
    {
        $path = $index === null
            ? $question->audio_path
            : ($question->metadata['audio_files'][$index] ?? null);

        abort_if(blank($path), 404);

        $disk = Storage::disk('public');

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Content-Disposition' => 'inline',
            // Private: keep shared caches from holding on to exam audio.
            'Cache-Control'       => 'private, max-age=3600',
        ]);
    }
}
