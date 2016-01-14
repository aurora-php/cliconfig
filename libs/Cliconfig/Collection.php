<?php

/*
 * This file is part of the 'octris/cliconfig' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Cliconfig;

/**
 * Data collection.
 *
 * @copyright   copyright (c) 2016 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Collection implements \IteratorAggregate, \ArrayAccess, \JsonSerializable, \Countable
{
    /**
     * Data stored in collection.
     *
     * @type    array
     */
    protected $data;
    
    /**
     * Constructor.
     * 
     * @param   array               $data                   Optional data to fill collection with.
     */
    public function __construct(array &$data = array())
    {
        $this->data =& $data;
    }
    
    /**
     * Return stored data if var_dump is used with collection.
     *
     * @return  array                                       Stored data.
     */
    public function __debugInfo()
    {
        return $this->data;
    }

    /** IteratorAggregate **/

    /**
     * Return iterator for collection.
     *
     * @return  \Iterator                                   Iterator instance for iterating over collection.
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    /** ArrayAccess **/

    /**
     * Get value from collection.
     *
     * @param   string              $offs                   Offset to get value from.
     * @return  scalar|\Octris\Cliconfig\Collection         Value stored at offset or a section collection.
     */
    public function offsetGet($offs)
    {
        if (!isset($this->data[$offs])) {
            throw new \InvalidArgumentException('Undefined index "' . $offs . '".');
        } elseif (is_array($this->data[$offs])) {
            // return section collection
            $return = new Collection($this->data[$offs]);
        } else {
            $return = $this->data[$offs];
        }
        
        return $return;
    }

    /**
     * Set value in collection at specified offset.
     *
     * @param   string              $offs                   Offset to set value at.
     * @param   scalar              $value                  Value to set at offset.
     */
    public function offsetSet($offs, $value)
    {
        if (!is_scalar($value)) {
            throw new \InvalidArgumentException('Value must be of type "scalar", value of type "' . gettype($value) . '" given.');
        } elseif (is_null($offs)) {
            // $...[] =
            $this->data[] = $value;
        } elseif (isset($this->data[$offs]) && is_array($this->data[$offs])) {
            // cannot overwrite section identifier
            throw new \InvalidArgumentException('Unable to overwrite section identifier.');
        } else {
            $this->data[$offs] = $value;
        }
    }

    /**
     * Check whether the offset exists in collection.
     *
     * @param   string              $offs                   Offset to check.
     * @return  bool                                        Returns true, if offset exists.
     */
    public function offsetExists($offs)
    {
        return isset($this->data[$offs]);
    }

    /**
     * Unset data in collection at specified offset.
     *
     * @param   string              $offs                   Offset to unset.
     */
    public function offsetUnset($offs)
    {
        if (isset($this->data[$offs]));

        unset($this->data[$offs]);
    }
    
    /** JsonSerializable **/

    /**
     * Get's called when something wants to json-serialize the collection.
     *
     * @return  string                                      Json-serialized content of collection.
     */
    public function jsonSerialize()
    {
        return json_encode($this->data);
    }

    /** Countable **/

    /**
     * Return number of items in collection.
     *
     * @return  int                         Number of items.
     */
    public function count()
    {
        return count($this->data);
    }
}
