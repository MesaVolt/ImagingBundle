<?php

namespace Mesavolt\ImagingBundle\Service;


use WebPConvert\Convert\Exceptions\ConversionFailedException;
use WebPConvert\WebPConvert;

class ImagingService
{
    public const DEFAULT_JPEG_QUALITY = 85;
    public const DEFAULT_WEBP_QUALITY = 85;

    private const TRANSPARENT_FORMATS = [
        IMAGETYPE_GIF, IMAGETYPE_PNG, IMAGETYPE_WEBP
    ];

    /** @var array */
    protected $transparencyReplacement;

    public function __construct(?string $transparencyReplacement = null)
    {
        if ($transparencyReplacement !== null) {
            $this->transparencyReplacement = $this->colorStringToRgbArray($transparencyReplacement);
        }
    }

    protected function colorStringToRgbArray(string $string): array
    {
        $color = trim(ltrim($string, '#'));
        if (strlen($color) !== 6) {
            throw new \InvalidArgumentException("Invalid color `$string`, color must be provided in the #RRGGBB format");
        }

        return array_map('hexdec', str_split($color, 2));
    }

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
        $ext = pathinfo($destination, PATHINFO_EXTENSION);
        $realDestination = $destination;
        if ($ext !== 'webp') {
            $tmpFile = tempnam(sys_get_temp_dir(), 'imagingservice-temp-file');
            $tmpFileWithExtension = "$tmpFile.webp";

            if(!rename($tmpFile, $tmpFileWithExtension)) {
                return false;
            }

            $realDestination = $tmpFileWithExtension;
        }


        try {
            WebPConvert::convert($source, $realDestination, [
                'quality' => $quality,
                'converter' => 'cwebp',
                'converter-options' => [
                    'cwebp' => [
                        'cwebp-try-cwebp' => false,
                        'try-common-system-paths' => false,
                        'try-discovering-cwebp' => false,
                        'cwebp-try-supplied-binary-for-os' => true,
                    ],
                ],
            ]);

            if ($realDestination !== $destination) {
                if (!rename($realDestination, $destination)) {
                    return false;
                }
            }

            return true;
        } catch (ConversionFailedException $ex) {
            // Throws when trying to convert gifs
            return false;
        }
    }

    /**
     * @return resource|null
     */
    protected function resize(string $source, ?int $maxWidth = null, ?int $maxHeight = null)
    {
        [$originalWidth, $originalHeight, $type] = getimagesize($source);

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

        $dst = imagecreatetruecolor($width, $height);

        // replace transparent areas
        if (in_array($type, self::TRANSPARENT_FORMATS) && $this->transparencyReplacement !== null) {
            $transparencyReplacement = imagecolorallocate($dst, ...$this->transparencyReplacement);
            imagefill($dst, 0, 0, $transparencyReplacement);
        }

        $src = $this->createGdImage($source);
        $resized = imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);

        imagedestroy($src); // don't hog the RAM, we're not Slack or Chrome.

        if (!$resized) {
            $filename = basename($source);
            throw new \RuntimeException("Couldn't resize $filename to [w:$maxWidth ; h:$maxHeight]");
        }

        return $dst;
    }

    public function supports(string $source): bool
    {
        if (strpos(mime_content_type($source), 'image/') !== 0) {
            return false;
        }

        return in_array(exif_imagetype($source), [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true);
    }

    /**
     * @return resource
     */
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
