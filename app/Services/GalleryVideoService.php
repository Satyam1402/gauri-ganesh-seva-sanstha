<?php

namespace App\Services;

use App\Enums\VideoProvider;
use App\Models\GalleryAlbum;
use App\Models\GalleryVideo;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class GalleryVideoService
{
    public function __construct(private GalleryAlbumService $albumService) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function addVideo(GalleryAlbum $album, array $data): GalleryVideo
    {
        $provider = VideoProvider::from($data['provider']);
        $videoId = null;

        if ($provider !== VideoProvider::SelfHosted) {
            $videoId = $this->extractVideoId($provider, $data['video_url'] ?? '');

            if ($videoId === null) {
                throw ValidationException::withMessages([
                    'video_url' => "That does not look like a valid {$provider->label()} URL.",
                ]);
            }
        }

        $video = $album->videos()->create([
            'title' => $data['title'],
            'provider' => $provider->value,
            'video_url' => $data['video_url'] ?? null,
            'video_id' => $videoId,
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'order_column' => ((int) $album->videos()->max('order_column')) + 1,
        ]);

        if ($data['thumbnail'] ?? null instanceof UploadedFile) {
            $video->addMedia($data['thumbnail'])->toMediaCollection('thumbnail');
        }

        if ($provider === VideoProvider::SelfHosted && ($data['video_file'] ?? null) instanceof UploadedFile) {
            $video->addMedia($data['video_file'])->toMediaCollection('video_file');
        }

        $this->albumService->forgetCache();

        return $video->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateVideo(GalleryVideo $video, array $data): GalleryVideo
    {
        $provider = VideoProvider::from($data['provider']);
        $videoId = $video->video_id;

        if ($provider !== VideoProvider::SelfHosted) {
            $videoId = $this->extractVideoId($provider, $data['video_url'] ?? '');

            if ($videoId === null) {
                throw ValidationException::withMessages([
                    'video_url' => "That does not look like a valid {$provider->label()} URL.",
                ]);
            }
        }

        $video->update([
            'title' => $data['title'],
            'provider' => $provider->value,
            'video_url' => $data['video_url'] ?? null,
            'video_id' => $provider === VideoProvider::SelfHosted ? null : $videoId,
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? $video->is_active),
        ]);

        if ($data['thumbnail'] ?? null instanceof UploadedFile) {
            $video->addMedia($data['thumbnail'])->toMediaCollection('thumbnail');
        }

        if ($provider === VideoProvider::SelfHosted && ($data['video_file'] ?? null) instanceof UploadedFile) {
            $video->addMedia($data['video_file'])->toMediaCollection('video_file');
        }

        $this->albumService->forgetCache();

        return $video->refresh();
    }

    public function deleteVideo(GalleryVideo $video): bool
    {
        $deleted = (bool) $video->delete();
        $this->albumService->forgetCache();

        return $deleted;
    }

    /**
     * Pull the canonical video id out of the many URL shapes each provider
     * uses. Returns null when nothing recognisable is found.
     */
    public function extractVideoId(VideoProvider $provider, string $url): ?string
    {
        return match ($provider) {
            VideoProvider::Youtube => $this->matchFirst([
                '/youtu\.be\/([A-Za-z0-9_-]{6,20})/',
                '/youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/|live\/)([A-Za-z0-9_-]{6,20})/',
            ], $url),
            VideoProvider::Vimeo => $this->matchFirst([
                '/vimeo\.com\/(?:video\/)?(\d{6,12})/',
            ], $url),
            VideoProvider::SelfHosted => null,
        };
    }

    /**
     * @param  list<string>  $patterns
     */
    private function matchFirst(array $patterns, string $subject): ?string
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $subject, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
