<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SecurityHelper
{
    /**
     * Sanitize HTML content to prevent XSS attacks
     * Strips all HTML tags and converts special characters
     */
    public static function sanitizeHtml(string $content): string
    {
        // Strip all HTML tags
        $content = strip_tags($content);

        // Convert special characters to HTML entities
        $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

        // Remove any remaining script-like patterns
        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);
        $content = preg_replace('/javascript:/i', '', $content);
        $content = preg_replace('/on\w+\s*=/i', '', $content);

        return trim($content);
    }

    /**
     * Detect spam patterns in content
     * Returns true if content contains spam indicators
     */
    public static function containsSpam(string $content): bool
    {
        $spamKeywords = [
            'viagra',
            'cialis',
            'casino',
            'poker',
            'lottery',
            'click here',
            'buy now',
            'limited time',
            'act now',
            'congratulations you won',
            'free money',
            'make money fast',
            'work from home',
            'weight loss',
            'penis enlargement',
        ];

        $contentLower = strtolower($content);

        // Check for spam keywords
        foreach ($spamKeywords as $keyword) {
            if (stripos($contentLower, $keyword) !== false) {
                return true;
            }
        }

        // Check for excessive capitalization (>70% uppercase)
        $upperCount = preg_match_all('/[A-Z]/', $content);
        $totalLetters = preg_match_all('/[a-zA-Z]/', $content);
        if ($totalLetters > 0 && ($upperCount / $totalLetters) > 0.7) {
            return true;
        }

        // Check for excessive punctuation (!!!, ???, etc.)
        if (preg_match('/[!?]{3,}/', $content)) {
            return true;
        }

        // Check for excessive URLs (more than 3)
        $urlCount = preg_match_all('/https?:\/\//', $content);
        if ($urlCount > 3) {
            return true;
        }

        // Check for repeated characters (aaaaaaa, 1111111, etc.)
        if (preg_match('/(.)\1{7,}/', $content)) {
            return true;
        }

        return false;
    }

    /**
     * Check if content is duplicate
     * Uses content hash to detect duplicates within time window
     * 
     * @param string $content Content to check
     * @param string $table Database table name (e.g., 'post', 'event')
     * @param int $userId User ID
     * @param int $hours Time window in hours to check for duplicates
     * @return bool True if duplicate found
     */
    public static function isDuplicate(string $content, string $table, int $userId, int $hours = 24): bool
    {
        $contentHash = hash('sha256', trim(strtolower($content)));

        $timeThreshold = now()->subHours($hours);

        // Check if content hash exists for this user within time window
        $exists = DB::table($table)
            ->where('user_id', $userId)
            ->where('content_hash', $contentHash)
            ->where('created_at', '>=', $timeThreshold)
            ->exists();

        return $exists;
    }

    /**
     * Get content hash for storing in database
     */
    public static function getContentHash(string $content): string
    {
        return hash('sha256', trim(strtolower($content)));
    }

    /**
     * Validate that uploaded image is safe
     * Checks file type, prevents script execution
     * 
     * @param UploadedFile $file
     * @return bool True if image is safe
     */
    public static function validateImageSafety(UploadedFile $file): bool
    {
        // Check MIME type
        $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return false;
        }

        // Check file extension
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        if (!in_array(strtolower($file->getClientOriginalExtension()), $allowedExtensions)) {
            return false;
        }

        // Additional check: Read file header to verify it's actually an image
        $imageInfo = @getimagesize($file->getRealPath());
        if ($imageInfo === false) {
            return false;
        }

        // Check image type from getimagesize
        $allowedImageTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG];
        if (!in_array($imageInfo[2], $allowedImageTypes)) {
            return false;
        }

        return true;
    }

    /**
     * Validate text field length and content
     * 
     * @param string $content
     * @param int $minLength
     * @param int $maxLength
     * @return array ['valid' => bool, 'message' => string]
     */
    public static function validateContent(string $content, int $minLength = 10, int $maxLength = 5000): array
    {
        $content = trim($content);
        $length = mb_strlen($content);

        if ($length < $minLength) {
            return [
                'valid' => false,
                'message' => "Content must be at least {$minLength} characters. Current: {$length} characters."
            ];
        }

        if ($length > $maxLength) {
            return [
                'valid' => false,
                'message' => "Content must not exceed {$maxLength} characters. Current: {$length} characters."
            ];
        }

        if (self::containsSpam($content)) {
            return [
                'valid' => false,
                'message' => 'Content contains spam or inappropriate keywords.'
            ];
        }

        return ['valid' => true, 'message' => ''];
    }
}
