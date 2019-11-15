<?php
namespace Arrim\Phalcon\Mvc;

use MongoDB\BSON\ObjectId;
use Phalcon\Mvc\MongoCollection as PhalconMongoCollection;

/**
 * Class MongoCollection
 *
 * @package Arrim\Phalcon\Mvc
 */
class MongoCollection extends PhalconMongoCollection
{
    /**
     * @var bool
     */
    protected $_embedded = false;

    /**
     * @var array
     */
    protected $_embeddedFields = [];

    /**
     * @var array
     */
    protected $_embeddedArray = [];

    /**
     * @var array
     */
    protected $_securedFields = [];

    /**
     * @var MongoCollection|null
     */
    protected $_parent;

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getReservedAttributes()
    {
        $reserved = [
            '_data'           => true,
            '_embedded'       => true,
            '_embeddedFields' => true,
            '_embeddedArray'  => true,
            '_securedFields'   => true,
            '_parent'         => true,
        ];

        return array_merge($reserved, parent::getReservedAttributes());
    }

    /**
     * Initialize collection
     */
    public function initialize()
    {
        $class  = explode('\\', get_class($this));
        $source = lcfirst(array_pop($class));

        $this->setSource($source);
    }

    /**
     * {@inheritdoc}
     */
    public function afterFetch()
    {
        $this->initEmbedded();
    }

    /**
     * {@inheritdoc}
     */
    public function onConstruct()
    {
        $this->initEmbedded();
    }

    /**
     * Sets a parent object in embedded collection
     *
     * @param \Arrim\Phalcon\Mvc\MongoCollection $object
     * @return $this
     */
    protected function setParent(MongoCollection &$object)
    {
        if ($this->isEmbedded()) {
            $this->_parent = $object;
        }

        return $this;
    }

    /**
     * Return parent object
     *
     * @return \Arrim\Phalcon\Mvc\MongoCollection|null
     */
    protected function getParent()
    {
        return $this->_parent;
    }

    /**
     * Initialize embedded data
     */
    protected function initEmbedded()
    {
        $this->initEmbeddedFields();
        $this->initEmbeddedArray();
    }

    /**
     * Initialize embedded fields
     */
    protected function initEmbeddedFields()
    {
        foreach ($this->_embeddedFields as $field => $object) {
            if (empty($this->$field)) {
                $this->$field = [];
            }

            if ($this->$field instanceof MongoCollection) {
                continue;
            }

            $this->{$field} = $object::fromArray($this->{$field});
            $this->{$field}->setParent($this);
            $this->{$field}->initEmbedded();
        }
    }

    /**
     * Initialize embedded field array
     */
    protected function initEmbeddedArray()
    {
        foreach ($this->_embeddedArray as $field => $object) {
            if (!is_array($this->$field) || empty($this->$field)) {
                continue;
            }

            $array = $this->$field;

            foreach ($array as $key => $value) {
                if ($value instanceof MongoCollection) {
                    continue;
                }

                $array[$key] = $object::fromArray($value);
                $array[$key]->setParent($this);
                $array[$key]->initEmbedded();
            }

            $this->$field = $array;
        }
    }

    /**
     * Create collection from array
     *
     * @param  array $array
     * @return mixed
     */
    public static function fromArray(array $array)
    {
        $className  = get_called_class();
        $collection = new $className();

        foreach ($array as $key => $value) {
            if (is_array($value) && key_exists('$oid', $value)) {
                $value = new ObjectId($value['$oid']);
            }

            if ($key === '_id' || (!$collection->isEmbedded() && $key === 'id')) {
                $key   = '_id';
                $value = new ObjectId($value);
            }

            $collection->{$key} = $value;
        }

        return $collection;
    }

    /**
     *  Return whether this objects embedded
     *
     * @return bool
     */
    public function isEmbedded()
    {
        return $this->_embedded;
    }

    /**
     * Returns an array with secured properties that cannot be send client
     *
     * @return array
     */
    protected function getSecuredFields()
    {
        return $this->_securedFields;
    }

    /**
     * {@inheritdoc}
     * 
     * @param bool $secured
     * @return array
     */
    public function toArray($secured = false)
    {
        $data     = [];
        $reserved = $this->getReservedAttributes();

        foreach (get_object_vars($this) as $key => $value) {
            if ($secured && in_array($key, $this->getSecuredFields())) {
                continue;
            }

            if ($key === '_id' && $secured) {
                $key = 'id';
            }

            if (!isset($reserved[$key])) {
                if (isset($this->_embeddedFields[$key]) && $value instanceof MongoCollection) {
                    $data[$key] = $value->toArray();
                } elseif (isset($this->_embeddedArray[$key])) {
                    $data[$key] = [];

                    if (is_array($this->$key)) {
                        foreach ($this->$key as $k => $v) {
                            if ($data instanceof MongoCollection) {
                                $data[$key][$k] = $v->toArray();
                            } else {
                                $data[$key][$k] = $v;
                            }
                        }
                    }
                } elseif ($value instanceof ObjectId) {
                    $data[$key] = (string) $value;
                } else {
                    $data[$key] = $value;
                }
            }
        }

        if (!$secured) {
            $data = array_filter($data);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     *
     * @param bool $toString
     * @return string
     */
    public function getId($toString = false)
    {
        return $toString ? (string) parent::getId() : parent::getId();
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function save()
    {
        if ($this->isEmbedded()) {
            return true;
        }

        return parent::save();
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function delete()
    {
        if ($this->isEmbedded()) {
            return true;
        }

        return parent::delete();
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function create()
    {
        if ($this->isEmbedded()) {
            return true;
        }

        return parent::create();
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function update()
    {
        if ($this->isEmbedded()) {
            return true;
        }

        return parent::update();
    }
}
