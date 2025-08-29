<?php

class ImageHelper {

    /**
     * Creates a thumbnail for an image.
     *
     * @param string $source_path The path to the source image.
     * @param string $thumbnail_path The path to save the thumbnail to.
     * @param int $width The width of the thumbnail.
     * @param int $height The height of the thumbnail.
     * @return bool True on success, false on failure.
     */
    public static function createThumbnail($source_path, $thumbnail_path, $width = 150, $height = 150)
    {
        list($source_width, $source_height, $source_type) = getimagesize($source_path);

        switch ($source_type) {
            case IMAGETYPE_GIF:
                $source_gdim = imagecreatefromgif($source_path);
                break;
            case IMAGETYPE_JPEG:
                $source_gdim = imagecreatefromjpeg($source_path);
                break;
            case IMAGETYPE_PNG:
                $source_gdim = imagecreatefrompng($source_path);
                break;
            default:
                return false;
        }

        $source_aspect_ratio = $source_width / $source_height;
        $thumbnail_aspect_ratio = $width / $height;

        if ($source_width <= $width && $source_height <= $height) {
            $thumbnail_width = $source_width;
            $thumbnail_height = $source_height;
        } elseif ($thumbnail_aspect_ratio > $source_aspect_ratio) {
            $thumbnail_width = (int) ($height * $source_aspect_ratio);
            $thumbnail_height = $height;
        } else {
            $thumbnail_width = $width;
            $thumbnail_height = (int) ($width / $source_aspect_ratio);
        }

        $thumbnail_gdim = imagecreatetruecolor($thumbnail_width, $thumbnail_height);

        imagecopyresampled($thumbnail_gdim, $source_gdim, 0, 0, 0, 0, $thumbnail_width, $thumbnail_height, $source_width, $source_height);

        switch ($source_type) {
            case IMAGETYPE_GIF:
                imagegif($thumbnail_gdim, $thumbnail_path);
                break;
            case IMAGETYPE_JPEG:
                imagejpeg($thumbnail_gdim, $thumbnail_path, 90);
                break;
            case IMAGETYPE_PNG:
                imagepng($thumbnail_gdim, $thumbnail_path, 9);
                break;
            default:
                return false;
        }

        imagedestroy($source_gdim);
        imagedestroy($thumbnail_gdim);

        return true;
    }
}
