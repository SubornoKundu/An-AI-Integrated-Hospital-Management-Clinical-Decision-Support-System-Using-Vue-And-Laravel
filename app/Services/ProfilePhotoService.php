<?php

namespace App\Services;

use App\Support\AvatarGenerator;
use Illuminate\Http\UploadedFile;

/**
 * Centralizes profile-photo storage for the patient, doctor, and admin
 * self-service "My Profile" pages, so upload, replace, and remove behave
 * identically across all three panels instead of each controller
 * re-implementing (and slowly drifting from) the same file handling.
 */
class ProfilePhotoService
{
    /**
     * Move a newly uploaded photo into public/images/{folder}/, delete
     * whatever photo the user had before, and return the relative path to
     * save on the model's `photo` column.
     */
    public function store(UploadedFile $file, string $folder, string $prefix, int|string $id, ?string $currentPhoto): string
    {
        $dir = public_path("images/{$folder}");

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // extension() guesses from the file's actual (sniffed) MIME type. The
        // validation rules upstream (`image`, `mimes:...`) already confirm the
        // content is a real image of an allowed type, but getClientOriginalExtension()
        // would still trust whatever extension the client's filename claims —
        // Symfony's own docs flag that as untrusted client input, and combined
        // with a public, web-executable directory that's a real risk, not a
        // theoretical one.
        $filename = $prefix.'-'.$id.'-'.time().'.'.$file->extension();
        $file->move($dir, $filename);

        $this->deleteFile($currentPhoto);

        return "images/{$folder}/{$filename}";
    }

    /**
     * Whether a stored photo path is one of our own auto-generated
     * placeholder avatars (as opposed to a real uploaded photo).
     */
    public function isPlaceholder(?string $photo, int|string $id): bool
    {
        return $photo !== null && str_contains($photo, "generated-{$id}.");
    }

    /**
     * Remove a user's custom uploaded photo and replace it with a freshly
     * generated placeholder avatar, returning the relative path to save on
     * the `photo` column. Safe to call even if the current photo is
     * already a placeholder (it won't be deleted, just regenerated).
     */
    public function resetToPlaceholder(string $name, string $folder, int|string $id, ?string $gender, ?string $currentPhoto): string
    {
        if ($currentPhoto && ! $this->isPlaceholder($currentPhoto, $id)) {
            $this->deleteFile($currentPhoto);
        }

        return AvatarGenerator::saveFor($name, $folder, $id, $gender);
    }

    /**
     * Delete a stored photo file from disk, if it exists. Safe to call
     * with null/empty paths.
     */
    public function deleteFile(?string $relativePath): void
    {
        if ($relativePath && file_exists(public_path($relativePath))) {
            @unlink(public_path($relativePath));
        }
    }
}
