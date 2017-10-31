<?php

namespace SI\Providers\Imgix;

use Bloom\JoomlaComponent\Config;
use Imgix\UrlBuilder;

/**
 * This class helps you output responsive images to HTML. It uses Imgix.
 *
 * @package Bloom\JoomlaComponent\Images
 */
class ResponsiveImage
{
    /**
     * Alt attribute value.
     *
     * @var string
     */
    protected $alt = '';

    /**
     * Array containing the sizes that the images will be resized to when referenced in the `srcset`
     * of an picture `source` attribute.
     *
     * @var array
     */
    protected $blueprintSizes = [];

    /**
     * Breakpoint sizes.
     *
     * @var string[]
     */
    protected $sizes = [];

    /**
     * Image.
     *
     * @var Image
     */
    protected $image;

    /**
     * Will contain the images indexed by each breakpoint size.
     *
     * @var Image[]
     */
    protected $images = [];

    /*
     * Secure URL token.
     *
     * @var string
     */
    protected $token = '';

    /**
     * Imgix source.
     *
     * @var string
     */
    protected $settings = '';

    /**
     * Content of the image class attribute.
     *
     * @var string
     */
    protected $imageClass;

    /**
     * Content of the picture class attribute.
     *
     * @var string
     */
    protected $pictureClass;

    /**
     * Attributes that will be added to the `img` element.
     *
     * @var string
     */
    protected $imgAttributes;

    /**
     * Attributes that will be added to the `picture` element.
     *
     * @var
     */
    protected $pictureAttributes;

    /**
     * Path of the image.
     *
     * @var string
     */
    protected $imagePath;

    /**
     * Imgix source.
     *
     * @var string
     */
    protected $source;

    /**
     * ResponsiveImage constructor.
     *
     * @param string $imagePath
     * @param array $settings Content:
     *        [
     *            'token' => (string),
     *            'source' => (string)
     *        ]
     */
    public function __construct(string $imagePath, $settings = [])
    {
        $this->imagePath = $imagePath;
        $this->blueprintSizes = Config::get('component.images.blueprintSizes');
        $this->token = $settings['token'];
        $this->source = $settings['source'];
    }

    /**
     * Add a breakpoint size.
     *
     * @param string $size This can be something like: `(min-width: 768px) 600px`, `100vw` or
     *        `calc(100vw - 30px)`.
     * @param callable $callback Optional callback that will allow to edit an image that will be
     *        used when the viewport matches the specified size. The first parameter of the callback
     *        will be a `Bloom\JoomlaComponent\Images\Imgix\Image` instance which the callback must
     *        return.
     * @return ResponsiveImage
     */
    public function addSize(string $size, callable $callback = null): self
    {
        if ($callback) {
            $image = $callback(new Image($this->imagePath, [
                'token' => $this->token,
                'source' => $this->source
            ]));
            $this->images[$size] = $image;
        }

        $this->sizes[] = $size;

        return $this;
    }

    /**
     * Set the `alt` attribute value.
     *
     * @param string $value
     * @return ResponsiveImage
     */
    public function alt(string $value): self
    {
        $this->alt = $value;

        return $this;
    }

    /**
     * Get the images sizes definitions.
     *
     * @return array
     */
    public function getSizes(): array
    {
        return $this->sizes;
    }

    /**
     * Render the HTML tag with the needed Imgix markup.
     *
     * @return string
     */
    public function render(): string
    {
        return $this->images ? $this->renderPicture() : $this->renderImg();
    }

    /**
     * Render a `<picture>` tag.
     *
     * @return string
     */
    protected function renderPicture(): string
    {
        $tag = '<picture class="'.$this->pictureClass.'" ' . $this->pictureAttributes . '>';

        $imgTagSrc = false;
        $imgCount = count($this->images);
        $i = 1;

        foreach ($this->images as $size => $image) {

            if($i === $imgCount) {
                $imgTagSrc = $image;
                continue;
            }

            list($media, $sizes) = $this->getMediaAndSizesAttributesFromSize($size);

            // Create different versions of the same image.
            $tag .= '<source media="' . $media . '" ' .
                'srcset="' . $this->getSrcsetValue($image) . '" ' .
                'sizes="' . $sizes . '">';

            $i++;
        }

        $tag .= $this->renderImg($imgTagSrc);
        $tag .= '</picture>';

        return $tag;
    }

    /**
     * Get the media attribute from from an size definition.
     *
     * @param string $size
     * @return array
     */
    protected function getMediaAndSizesAttributesFromSize(string $size): array
    {
        $result = [];
        preg_match('/^(?:(\(.*\))\s*)*(.*)/', $size, $result);

        $media = $result[1];
        $sizes = $result[2];

        return [$media, $sizes];
    }

    /**
     * Get the value of the `srcset` attribute for a responsive image.
     *
     * @param Image $image
     * @return string
     */
    protected function getSrcsetValue(Image $image): string
    {
        $parts = [];

        // If the image is already defining a specific size...
        if ($image->getParameter('w')) {
            // ... the blueprints should be overridden.
            $this->blueprintSizes = [$image->getParameter('w')];
        }

        foreach ($this->blueprintSizes as $width) {
            $imageUrl = $image->width($width)->getURL();
            // Morgan - dont se the need for row below, seems toi interfer with responsive behaviour
            // $parts[] = $imageUrl . ' ' . $width . 'w';
            $parts[] = $imageUrl;
        }

        return implode(',', $parts);
    }

    /**
     * Render an `<img>` tag.
     *
     * @param Image $image
     * @return string
     */
    public function renderImg(Image $image = null): string
    {
        if (!$image)
        {
            $image = $this->image;
        }

        $tag = '<img src="' . $this->getSrcsetValue($image) . '" ' .
            'class="' . $this->imageClass . '"' .
            'alt="' . $this->alt . '"' .
            $this->imgAttributes . '>';

        return $tag;
    }

    /**
     * Set the content of the class attribute set on the `img` element.
     *
     * @param string $class
     * @return ResponsiveImage
     */
    public function withImageClass(string $class = ''): self
    {
        $this->imageClass = $class;

        return $this;
    }

    /**
     * Set the content of the class attribute set on the `picture` element.
     *
     * @param string $class
     * @return ResponsiveImage
     */
    public function withPictureClass(string $class = ''): self
    {
        $this->pictureClass = $class;

        return $this;
    }

    /**
     * Set the attributes on the `img` element.
     *
     * @param string $attributes
     * @return ResponsiveImage
     * @internal param string $attribute
     */
    public function withImgAttributes(string $attributes): self
    {
        $this->imgAttributes = $attributes;

        return $this;
    }

    /**
     * Set the attributes on the `picture` element.
     *
     * @param string $attribtues
     * @return ResponsiveImage
     */
    public function withPictureAttributes(string $attribtues): self
    {
        $this->pictureAttributes = $attribtues;

        return $this;
    }

    /**
     * Get the URL for the image with the specified size.
     *
     * @param string $size
     * @return string
     */
    public function getURL(string $size): string
    {
        return $this->images[$size]->getURL();
    }
}
