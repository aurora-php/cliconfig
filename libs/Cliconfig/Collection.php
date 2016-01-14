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
class Collection implements \Iterator, \ArrayAccess, \JsonSerializable, \Countable
{
    /**
     * Local configuration data only.
     *
     * @type    array
     */
    protected $ldata = array();
    
    /**
     * Complete configuration data.
     *
     * @type    array
     */
    protected $data = array();
    
    /**
     * Position for iterator.
     *
     * @type    int
     */
    protected $position = 0;
    
    /**
     * Whether configuration has changed.
     *
     * @type    bool
     */
    protected $has_changed = false;
    
    /**
     * Constructor.
     * 
     * @param   array               $data                   Complete configuration data.
     * @param   array               $ldata                  Local configuration data only.
     * @param   bool                                        Used to set has_changed flag.
     */
    public function __construct(array &$data, array &$ldata, &$has_changed)
    {
        $this->data =& $data;
        $this->ldata =& $ldata;
        $this->has_changed =& $has_changed;
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

    /** Iterator **/

    /**
     * Return key of item.
     * 
     * @return  string                                      Key of item.
     */
    public function key() {
        return key($this->data);
    }
 
    /**
     * Return value of item.
     * 
     * @return  scalar                                      Value of item.
     */
    public function current() {
        return current($this->data);
    }
 
    /**
     * Move pointer to the next item but skip sections.
     */
    public function next()
    {
        do {
            $item = next($this->data);
            ++$this->position;
        } while (is_array($item));
    }

    /**
     * Rewind collection.
     */
    public function rewind()
    {
        rewind($this->data);
        $this->position = 0;
    }

    /**
     * Test if position is valid.
     */
    public function valid()
    {
        return (count($this->data) > $this->position);
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
            if (!isset($this->ldata[$offs])) {
                // we need to first create the section local, too
                $this->has_changed = true;
                
                $this->ldata[$offs] = array();
            }
            
            $return = new Collection($this->data[$offs], $this->ldata[$offs], $this->has_changed);
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
            $this->ldata[] = $value;
            
            $this->has_changed = true;
        } elseif (isset($this->data[$offs]) && is_array($this->data[$offs])) {
            // cannot overwrite section identifier
            throw new \InvalidArgumentException('Unable to overwrite section identifier.');
        } else {
            if (!isset($this->ldata[$offs]) || $this->ldata[$offs] != $value) {
                $this->has_changed = true;
            }
            
            $this->data[$offs] = $value;
            $this->ldata[$offs] = $value;
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
        if (isset($this->data[$offs])) {
            unset($this->data[$offs]);
            
            if (isset($this->ldata[$offs])) {
                $this->has_changed = true;
                
                unset($this->ldata[$offs]);
            }
        }
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
