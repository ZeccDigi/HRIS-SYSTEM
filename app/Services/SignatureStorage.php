<?php
namespace App\Services;
use Illuminate\Support\Facades\Storage;
class SignatureStorage {
 public static function path(?string $path): ?string {
  if (!$path || !preg_match('~^[a-zA-Z0-9_-]+/[a-zA-Z0-9_.-]+$~D', $path)) return null;
  $disk = Storage::disk('signatures');
  return $disk->exists($path) ? $disk->path($path) : null;
 }
 public static function image(?string $path): ?string {
  $file = self::path($path);
  if (!$file) return null;
  $mime = mime_content_type($file);
  if (!in_array($mime, ['image/png','image/jpeg','image/webp'], true)) return null;
  return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($file));
 }
}
