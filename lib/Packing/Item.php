<?php
class Item
{
    private $description;
    private $length;
    private $width;
    private $height;
    private $weight;
    private $keepFlat;
    
    public function __construct($description, $length, $width, $height, $weight, $keepFlat)
    {
        $this->description = $description;
        $this->length = $length;
        $this->width = $width;
        $this->height = $height;
        $this->weight = $weight;
        $this->keepFlat = $keepFlat;
    }
    
    public function getDescription()
    {
        return $this->description;
    }
    
    public function getLength()
    {
        return $this->length;
    }
    
    public function getWidth()
    {
        return $this->width;
    }
    
    public function getHeight()
    {
        return $this->height;
    }
    
    public function getWeight()
    {
        return $this->weight;
    }
    
    public function getKeepFlat()
    {
        return $this->keepFlat;
    }
    
    public function getVolume()
    {
        return $this->length * $this->width * $this->height;
    }
}