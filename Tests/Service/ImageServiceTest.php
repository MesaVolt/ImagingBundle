<?php

namespace Mesavolt\Tests\Service;


use Mesavolt\ImagingBundle\Service\ImagingService;
use PHPUnit\Framework\TestCase;

class ImageServiceTest extends TestCase
{
    /** @var ImagingService */
    protected $service;
    /** @var array */
    protected $tempFiles;
    protected $tall;
    protected $wide;


    protected function setUp()/* The :void return type declaration that should be here would cause a BC issue */
    {
        $this->service = new ImagingService();
        $this->tempFiles = [];

        $this->tall = __DIR__.'/../Resources/images/tall.png';
        $this->wide = __DIR__.'/../Resources/images/wide.png';
    }

    protected function tearDown()/* The :void return type declaration that should be here would cause a BC issue */
    {
        $this->service = null;

        foreach($this->tempFiles as $file) {
            @unlink($file);
        }
    }

    protected function createTempFile(): string
    {
        $file = tempnam(sys_get_temp_dir(), null);
        $this->tempFiles[] = $file;

        return $file;
    }

    public function testShrink()
    {
        // handles JPG, PNG and GIF
        // TODO: find out why WEBP doesn't run on Travis
        foreach(['jpg', 'gif', 'png'/*, 'webp'*/] as $ext) {
            $this->assertTrue($this->service->shrink(__DIR__."/../Resources/images/$ext.$ext", $this->createTempFile(), 1));
        }

        // shrinks horizontal image to match width
        $tallShrunkW = $this->createTempFile();
        $this->service->shrink($this->tall, $tallShrunkW, 100, null);
        [$w, $h] = getimagesize($tallShrunkW);
        $this->assertEquals(100, $w, 'Width should be 100px');
        $this->assertEquals(200, $h, 'Height should be 200px');

        // shrinks horizontal image to match height
        $tallShrunkH = $this->createTempFile();
        $this->service->shrink($this->tall, $tallShrunkH, null, 1000);
        [$w, $h] = getimagesize($tallShrunkH);
        $this->assertEquals(500, $w, 'Width should be 500px');
        $this->assertEquals(1000, $h, 'Height should be 1000px');

        // shrinks vertical image to match width
        $wideShrunkW = $this->createTempFile();
        $this->service->shrink($this->wide, $wideShrunkW, 1000, null);
        [$w, $h] = getimagesize($wideShrunkW);
        $this->assertEquals(1000, $w, 'Width should be 1000px');
        $this->assertEquals(500, $h, 'Height should be 500px');

        // shrinks vertical image to match height
        $wideShrunkH = $this->createTempFile();
        $this->service->shrink($this->wide, $wideShrunkH, null, 100);
        [$w, $h] = getimagesize($wideShrunkH);
        $this->assertEquals(200, $w, 'Width should be 200px');
        $this->assertEquals(100, $h, 'Height should be 100px');

        // asking for 3000*3000 won't resize the image because it's already smaller
        $wideUntouched = $this->createTempFile();
        $this->service->shrink($this->wide, $wideUntouched, 3000, 3000);
        [$w, $h] = getimagesize($wideUntouched);
        $this->assertEquals(2000, $w, 'Width should be 2000px');
        $this->assertEquals(1000, $h, 'Height should be 1000px');

        // asking for 300*100 will result in 200*100 to keep tall proportions
        $tallShrunk300x100 = $this->createTempFile();
        $this->service->shrink($this->tall, $tallShrunk300x100, 300, 100);
        [$w, $h] = getimagesize($tallShrunk300x100);
        $this->assertEquals(50, $w, 'Width should be 50px');
        $this->assertEquals(100, $h, 'Height should be 100px');

        // asking for 100*300 will result in 100*50 to keep tall proportions
        $tallShrunk100x300 = $this->createTempFile();
        $this->service->shrink($this->tall, $tallShrunk100x300, 100, 300);
        [$w, $h] = getimagesize($tallShrunk100x300);
        $this->assertEquals(100, $w, 'Width should be 100px');
        $this->assertEquals(200, $h, 'Height should be 200px');

        // asking for 300*100 will result in 200*100 to keep wide proportions
        $wideShrunk300x100 = $this->createTempFile();
        $this->service->shrink($this->wide, $wideShrunk300x100, 300, 100);
        [$w, $h] = getimagesize($wideShrunk300x100);
        $this->assertEquals(200, $w, 'Width should be 200px');
        $this->assertEquals(100, $h, 'Height should be 100px');

        // asking for 100*300 will result in 100*50 to keep wide proportions
        $wideShrunk100x300 = $this->createTempFile();
        $this->service->shrink($this->wide, $wideShrunk100x300, 100, 300);
        [$w, $h] = getimagesize($wideShrunk100x300);
        $this->assertEquals(100, $w, 'Width should be 100px');
        $this->assertEquals(50, $h, 'Height should be 50px');

        // __FILE__ is not an image, shrinking fails and returns false
        $this->assertFalse($this->service->shrink(__FILE__, '/dev/null'));

        // doesn't handle tiff
        $this->expectException(\InvalidArgumentException::class);
        $this->service->shrink(__DIR__."/../Resources/images/tiff.tiff", $this->createTempFile(), 1);
    }

    public function testGenerateWebp()
    {
        $webp = $this->createTempFile();
        $this->service->generateWebp($this->wide, $webp);
        [$w, $h] = getimagesize($webp);
        // dimensions shouldn't have changed
        $this->assertEquals(2000, $w, 'Width should be 2000px');
        $this->assertEquals(1000, $h, 'Height should be 1000px');
        // generated image must be webp
        $this->assertEquals(IMAGETYPE_WEBP, exif_imagetype($webp));
    }
}
