<?php
namespace Fa\Bundle\CoreBundle\Manager;

/**
 * sfGDAdapter provides a mechanism for creating thumbnail images.
 * @see http://www.php.net/gd
 *
 * @package    sfThumbnailPlugin
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @author     Benjamin Meynell <bmeynell@colorado.edu>
 */

class GDManager
{
    protected $sourceWidth;
    protected $sourceHeight;
    protected $sourceMime;
    protected $maxWidth;
    protected $maxHeight;
    protected $scale;
    protected $inflate;
    protected $quality;
    protected $source;
    protected $thumb;
    
    /**
    * List of accepted image types based on MIME
    * descriptions that this adapter supports
    */
    protected $imgTypes = array(
    'image/jpeg',
    'image/pjpeg',
    'image/png',
    'image/gif',
    );
    
    /**
    * Stores function names for each image type
    */
    protected $imgLoaders = array(
    'image/jpeg'  => 'imagecreatefromjpeg',
    'image/pjpeg' => 'imagecreatefromjpeg',
    'image/png'   => 'imagecreatefrompng',
    'image/gif'   => 'imagecreatefromgif',
    );
    
    /**
    * Stores function names for each image type
    */
    protected $imgCreators = array(
    'image/jpeg'  => 'imagejpeg',
    'image/pjpeg' => 'imagejpeg',
    'image/png'   => 'imagepng',
    'image/gif'   => 'imagegif',
    );
    
    /**
     *
     * @param unknown $maxWidth
     * @param unknown $maxHeight
     * @param unknown $scale
     * @param unknown $inflate
     * @param unknown $quality
     * @param unknown $options
     * @throws Exception
     */
    public function __construct($maxWidth, $maxHeight, $scale, $inflate, $quality, $options)
    {
        if (!extension_loaded('gd')) {
            throw new Exception('GD not enabled. Check your php.ini file.');
        }
        $this->maxWidth = $maxWidth;
        $this->maxHeight = $maxHeight;
        $this->scale = $scale;
        $this->inflate = $inflate;
        $this->quality = $quality;
        $this->options = $options;
    }
    
    /**
     *
     * @param unknown $thumbnail
     * @param unknown $image
     * @throws Exception
     * @return boolean
     */
    public function loadFile($thumbnail, $image)
    {
        $imgData = @GetImageSize($image);
    
        if (!$imgData) {
            throw new Exception(sprintf('Could not load image %s', $image));
        }
    
        if (in_array($imgData['mime'], $this->imgTypes)) {
            $loader = $this->imgLoaders[$imgData['mime']];
            if (!function_exists($loader)) {
                throw new Exception(sprintf('Function %s not available. Please enable the GD extension.', $loader));
            }
            $this->source = $loader($image);
            $this->sourceWidth = $imgData[0];
            $this->sourceHeight = $imgData[1];
            $this->sourceMime = $imgData['mime'];
            $thumbnail->initThumb($this->sourceWidth, $this->sourceHeight, $this->maxWidth, $this->maxHeight, $this->scale, $this->inflate);
    
            $this->thumb = imagecreatetruecolor($thumbnail->getThumbWidth(), $thumbnail->getThumbHeight());
            if ($imgData[0] == $this->maxWidth && $imgData[1] == $this->maxHeight) {
                $this->thumb = $this->source;
            } else {
                imagecopyresampled($this->thumb, $this->source, 0, 0, 0, 0, $thumbnail->getThumbWidth(), $thumbnail->getThumbHeight(), $imgData[0], $imgData[1]);
            }
            
            return true;
        } else {
            throw new Exception(sprintf('Image MIME type %s not supported', $imgData['mime']));
        }
    }
    
    /**
     *
     * @param unknown $thumbnail
     * @param unknown $image
     * @param unknown $mime
     * @throws Exception
     * @return boolean
     */
    public function loadData($thumbnail, $image, $mime)
    {
        if (in_array($mime, $this->imgTypes)) {
            $this->source = imagecreatefromstring($image);
            $this->sourceWidth = imagesx($this->source);
            $this->sourceHeight = imagesy($this->source);
            $this->sourceMime = $mime;
            $thumbnail->initThumb($this->sourceWidth, $this->sourceHeight, $this->maxWidth, $this->maxHeight, $this->scale, $this->inflate);
    
            $this->thumb = imagecreatetruecolor($thumbnail->getThumbWidth(), $thumbnail->getThumbHeight());
            if ($this->sourceWidth == $this->maxWidth && $this->sourceHeight == $this->maxHeight) {
                $this->thumb = $this->source;
            } else {
                imagecopyresampled($this->thumb, $this->source, 0, 0, 0, 0, $thumbnail->getThumbWidth(), $thumbnail->getThumbHeight(), $this->sourceWidth, $this->sourceHeight);
            }
            
            return true;
        } else {
            throw new Exception(sprintf('Image MIME type %s not supported', $mime));
        }
    }
    
    /**
     *
     * @param unknown $thumbnail
     * @param unknown $thumbDest
     * @param string $targetMime
     */
    public function save($thumbnail, $thumbDest, $targetMime = null)
    {
        if ($targetMime !== null) {
            $creator = $this->imgCreators[$targetMime];
        } else {
            $creator = $this->imgCreators[$thumbnail->getMime()];
        }
    
        if ($creator == 'imagejpeg') {
            imagejpeg($this->thumb, $thumbDest, $this->quality);
        } else {
            $creator($this->thumb, $thumbDest);
        }
    }
    
    /**
     *
     * @param unknown $thumbnail
     * @param string $targetMime
     */
    public function toString($thumbnail, $targetMime = null)
    {
        if ($targetMime !== null) {
            $creator = $this->imgCreators[$targetMime];
        } else {
            $creator = $this->imgCreators[$thumbnail->getMime()];
        }
    
        ob_start();
        $creator($this->thumb);
    
        return ob_get_clean();
    }
    
    /**
     *
     */
    public function toResource()
    {
        return $this->thumb;
    }
    
    /**
     *
     */
    public function freeSource()
    {
        if (is_resource($this->source)) {
            imagedestroy($this->source);
        }
    }
    
    /**
     *
     */
    public function freeThumb()
    {
        if (is_resource($this->thumb)) {
            imagedestroy($this->thumb);
        }
    }
    
    /**
     *
     */
    public function getSourceMime()
    {
        return $this->sourceMime;
    }
}
