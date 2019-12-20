<?php

/**
 * Class CachedActiveRecord
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 *
 */
abstract class CachedActiveRecord extends ActiveRecord implements arStorageInterface
{

    /**
     * @return ilGlobalCache
     */
    abstract public function getCache() : ilGlobalCache;


    /**
     * @return string
     */
    final public function getCacheIdentifier() : string
    {
        if ($this->getArFieldList()->getPrimaryField()) {
            return ($this->getConnectorContainerName() . "_" . $this->getPrimaryFieldValue());
        }

        return "";
    }


    public function getTTL() : int
    {
        return 60;
    }


    /**
     * @inheritDoc
     */
    public function __construct($primary_key = 0, arConnector $connector = null)
    {
        if (is_null($connector)) {
            $connector = new arConnectorDB();
        }

        $connector = new arConnectorCache($connector);
        arConnectorMap::register($this, $connector);
        parent::__construct($primary_key, $connector);
    }


    public function storeObjectToCache()
    {
        parent::storeObjectToCache();
    }


    /**
     * @inheritDoc
     */
    public function buildFromArray(array $array)
    {
        return parent::buildFromArray($array); // TODO: Change the autogenerated stub
    }


    public function store()
    {
        parent::store(); // TODO: Change the autogenerated stub
    }


    public function save()
    {
        parent::save(); // TODO: Change the autogenerated stub
    }


    public function create()
    {
        $this->getCache()->flush();
        parent::create(); // TODO: Change the autogenerated stub
    }


    /**
     * @inheritDoc
     */
    public function copy($new_id = 0)
    {
        $this->getCache()->flush();
        return parent::copy($new_id); // TODO: Change the autogenerated stub
    }


    public function read()
    {
        parent::read(); // TODO: Change the autogenerated stub
    }


    public function update()
    {
        $this->getCache()->flush();
        parent::update(); // TODO: Change the autogenerated stub
    }


    public function delete()
    {
        $this->getCache()->flush();
        parent::delete(); // TODO: Change the autogenerated stub
    }


    /**
     * @inheritDoc
     */
    public static function find($primary_key, array $add_constructor_args = array())
    {
        return parent::find($primary_key, $add_constructor_args);
    }


    /**
     * @inheritDoc
     */
    public static function connector(arConnector $connector)
    {
        return parent::connector($connector); // TODO: Change the autogenerated stub
    }
}
