<?php

namespace Mesavolt\ImagingBundle\Service;


use WebPConvert\Exceptions\InvalidFileExtensionException;
use WebPConvert\WebPConvert;

class ImagingService
{
    public const DEFAULT_JPEG_QUALITY = 85;
    public const DEFAULT_WEBP_QUALITY = 85;

    /**
     * Shrink an image to at most $width and/or $height while keeping its proportions, and compress it.
     * If the image is smaller than the specified $width or $height, or if no dimensions are specified,
     * its size won't be affected and it will only be compressed.
     */
    public function shrink(string $source, string $destination, ?int $width = null, ?int $height = null, int $quality = self::DEFAULT_JPEG_QUALITY): bool
    {
        $shrunk = $this->resize($source, $width, $height);

        if ($shrunk === null) {
            return false;
        }

        $success = imagejpeg($shrunk, $destination, $quality);

        imagedestroy($shrunk); // don't hog the RAM, we're not Slack or Chrome.

        return $success;
    }

    /**
     * Generates a webp version of the specified image, and outputs it in the specified destination.
     */
    public function generateWebp(string $source, string $destination, int $quality = self::DEFAULT_WEBP_QUALITY): bool
    {
        try {
            return WebPConvert::convert($source, $destination, [
                'quality' => $quality,
            ]);
        } catch (InvalidFileExtensionException $ex) {
            // Throws when trying to convert gifs
            return false;
        }
    }

    protected function resize(string $source, ?int $maxWidth = null, ?int $maxHeight = null)
    {
        [$originalWidth, $originalHeight] = getimagesize($source);

        if ($originalWidth === null || $originalHeight === null) {
            return null;
        }

        $height = $originalHeight;
        $width = $originalWidth;

        if ($maxWidth !== null) {
            if ($width > $maxWidth) {
                // both images need to have the same ratio: i.e. we want newHeight/newWidth = height/width
                // but newWidth must be maxWidth at most
                $height = round($maxWidth * $height / $width, 0, PHP_ROUND_HALF_UP);
                $width = $maxWidth;
            }
        }

        if ($maxHeight !== null) {
            if ($height > $maxHeight) {
                $width = round($maxHeight * $width / $height, 0, PHP_ROUND_HALF_UP);
                $height = $maxHeight;
            }
        }

        $src = $this->createGdImage($source);

        // resize only if necessary
        if ($width === $originalWidth && $height === $originalHeight) {
            $dst = $src;
        } else {
            $dst = imagecreatetruecolor($width, $height);
            $resized = imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);

            imagedestroy($src); // don't hog the RAM, we're not Slack or Chrome.

            if (!$resized) {
                $filename = basename($source);
                throw new \RuntimeException("Couldn't resize $filename to [w:$maxWidth ; h:$maxHeight]");
            }
        }

        return $dst;
    }

    protected function createGdImage(string $source)
    {
        switch ($imageType = exif_imagetype($source)) {
            case IMAGETYPE_GIF:
                return imagecreatefromgif($source);
                break;
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($source);
                break;
            case IMAGETYPE_PNG:
                return imagecreatefrompng($source);
                break;
            /**
             * PHP>=7.2 only
             case IMAGETYPE_BMP:
                return imagecreatefrombmp($source);
                break;
             */
            case IMAGETYPE_WEBP:
                return imagecreatefromwebp($source);
                break;
            default:
                // Unsupported image
                throw new \InvalidArgumentException(sprintf('Provided image "%s" has an invalid type %s', $source, $imageType));
        }
    }
}
