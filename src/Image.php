<?php

namespace SI\Providers\Imgix;

use Imgix\UrlBuilder;

/**
 * An Imgix image.
 *
 * @package Bloom\JoomlaComponent\Images\Imgix
 */
class Image
{
    /*
     * Crop modes.
     */
    const CROP_TOP         = 'top';
    const CROP_BOTTIM      = 'bottom';
    const CROP_LEFT        = 'left';
    const CROP_RIGHT       = 'right';
    const CROP_FOCAL_POINT = 'focalpoint';
    const CROP_FACES       = 'faces';
    const CROP_ENTROPY     = 'entropy';
    const CROP_EDGES       = 'edges';

    /*
     * Fit modes.
     */
    const FIT_CLAMP    = 'clamp';
    const FIT_CLIP     = 'clip';
    const FIT_CROP     = 'crop';
    const FIT_FACEAREA = 'facearea';
    const FIT_FILLMAX  = 'fillmax';
    const FIT_MAX      = 'max';
    const FIT_MIN      = 'min';
    const FIT_SCALE    = 'scale';

    /**
     * Image filename.
     *
     * @var string
     */
    protected $filename;

    /**
     * Imgix URL builder.
     *
     * @var UrlBuilder
     */
    protected $builder;

    /**
     * Imgix URL parameters.
     *
     * @var array
     */
    protected $parameters = [];

    /**
     * Secure token.
     *
     * @var string
     */
    protected $token = '';

    /**
     * Image constructor.
     *
     * The required `$settings` are:
     *  - source (string): Imgix images source
     *  - token (string): Imgix token for protecting the URLs (optional)
     *
     * @param string $filename
     * @param array $settings
     */
    public function __construct(string $filename, array $settings = [])
    {
        $this->filename = $filename;
        $this->builder = new UrlBuilder($settings['source']);

        if ($settings['token'] ?? false)
        {
            $this->builder->setSignKey($settings['token']);
            $this->builder->setUseHttps(true);
        }
    }

    /**
     * Get a parameter.
     *
     * @param $parameter
     * @return mixed
     */
    public function getParameter($parameter)
    {
        return $this->parameters[$parameter] ?? false;
    }

    /**
     * Get the image URL.
     *
     * @return string
     */
    public function getURL(): string
    {
        return $this->builder->createURL($this->filename, $this->parameters);
    }

    /**
     * Blur the image.
     *
     * @param int $amount Range 0 - 2000
     * @return Image
     */
    public function blur(int $amount): self
    {
        $this->parameters['blur'] = $amount;

        return $this;
    }

    /**
     * Crop the image.
     *
     * Controls how the input image is aligned when the `fit` parameter is set to crop. The `w` and
     * `h` should be also be set. Valid values are `top`, `bottom`, `left`, `right`, `faces`, and
     * `entropy`. Multiple values can be used by separating them with a comma `,`.
     *
     * If no value is explicitly set, the default behavior is to center the image.
     *
     * @link https://docs.imgix.com/apis/url/size/crop
     * @param string $mode
     * @return Image
     */
    public function crop(string $mode): self
    {
        $this->parameters['crop'] = $mode;

        return $this;
    }

    /**
     * Fit resize the image.
     *
     * The `fit` parameter controls how the output image is fit to its target dimensions. Valid
     * values are `clamp`, `clip`, `crop`, `facearea`, `fill`, `fillmax`, `max`, `min`, and `scale`.
     *
     * The default value is clip.
     *
     * @link https://docs.imgix.com/apis/url/size/fit Documentation
     * @param string $mode
     * @return Image
     */
    public function fit(string $mode): self
    {
        $this->parameters['fit'] = $mode;

        return $this;
    }

    /**
     * The facepad parameter defines how much padding to allow for each face when `fit=facearea`.
     *
     * @param float $pad The value of facepad must be a positive float. The value defines the
     *        padding ratio based on the pixel dimensions of the face, up to the limit of the
     *        image’s dimensions. A larger value yields more padding. The default value is 1.0.
     * @return Image
     */
    public function facepad(float $pad): self
    {
        $this->parameters['facepad'] = $pad;

        return $this;
    }

    /**
     * Set the image height.
     *
     * @param int $height
     * @return Image
     */
    public function height(int $height): self
    {
        $this->parameters['h'] = $height;

        return $this;
    }

    /**
     * Apply a monochromatic filter with a specified three or six digit hex value. Both `ff0000` and
     * `f00` will result the same red. The monochromatic intensity can be set by using an eight
     * digit hex value, with the first two digits representing the opacity of the color being
     * applied.
     *
     * @param string $color
     * @return Image
     */
    public function monochrome(string $color): self
    {
        $this->parameters['mono'] = $color;

        return $this;
    }

    /**
     * Focal point cropping gives you the ability to choose and fine-tune the point of interest of
     * your image for better art direction.
     *
     * These parameters are dependent on the `fit=crop` and `crop=focalpoint` operations. When
     * they’re set, the focal point parameters allow you to intentionally art-direct a point of
     * interest when cropping an image, with horizontal (`fp-x`), vertical (`fp-y`), and zoom
     * (`fp-z`) values. As the image is then sized and cropped, the focal point determines which
     * areas are centered and within bounds of the image, and what gets cropped out.
     *
     * To better identify where the focal point is set on an image, an optional debug mode
     * (fp-debug) is also available.
     *
     * @see https://docs.imgix.com/apis/url/focalpoint-crop
     *
     * @param float $x
     * @param float $y
     * @param float $zoom
     * @param bool $debug
     * @return Image
     */
    public function focalPointCrop(float $x, float $y, float $zoom = 1.0, bool $debug = false): self
    {
        $this->parameters['fp-x'] = (string) $x;
        $this->parameters['fp-y'] = (string) $y;
        $this->parameters['fp-z'] = (string) $zoom;

        if ($debug)
        {
            $this->parameters['fp-debug'] = true;
        }

        return $this;
    }

    /**
     * Set the image width.
     *
     * @param int $width
     * @return Image
     */
    public function width(int $width): self
    {
        $this->parameters['w'] = $width;

        return $this;
    }
}
