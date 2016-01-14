<?php

/*
 * This file is part of the 'octris/cliconfig' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris;

/**
 * Cli application configuration file library. The underlying format used
 * by this library is the INI file format.
 *
 * @copyright   copyright (c) 2016 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Cliconfig extends \Octris\Cliconfig\Collection
{
    /**
     * Additional paths to look for a configuration file.
     *
     * @type    array
     */
    protected $paths;
    
    /**
     * Home directory of user.
     *
     * @type    string
     */
    protected $home;
    
    /**
     * Filepath.
     *
     * @type    string
     */
    protected $filepath;
    
    /**
     * Constructor.
     * 
     * @param   bool                    $paths                  Additional paths to look for configuration files.
     */
    public function __construct($paths = array())
    {
        $this->home = posix_getpwuid(posix_getuid())['dir'];
        $this->paths = array_unique(array_merge($paths, [$this->home]));
    }

    /**
     * Test whether configuration has a specified section.
     * 
     * @param   string                  $name                   Name of section to check.
     * @return  bool                                            Returns true if section exists.
     */
    public function hasSection($name)
    {
        return (isset($this->data[$name]) && is_array($this->data[$name]));
    }
    
    /**
     * Add a section. Does not do anything, if section already exists.
     * 
     * @param   string                  $name                   Name of section to add.
     */
    public function addSection($name)
    {
        if (!$this->hasSection($name)) {
            if (isset($this->data[$name])) {
                throw new \Exception('Unable to overwrite configuration setting with a section.');
            } else {
                $this->data[$name] = array();
                $this->ldata[$name] = array();
            }
        }
    }
    
    /**
     * Load configuration file.
     * 
     * @param   string                  $filepath               Path of file to load.
     * @param   bool                    $bubble                 Whether to look in parent directories to locate files to merge with.
     */
    public function load($filepath, $bubble = true)
    {
        if (!($realpath = realpath($filepath))) {
            throw new \InvalidArgumentException('Unable to locate file "' . $filepath . '".');
        } elseif (!is_file($realpath)) {
            throw new \InvalidArgumentException('Specified path is not a file "' . $realpath . '".');
        } elseif (!is_readable($realpath)) {
            throw new \InvalidArgumentException('Specified file is not readble "' . $realpath . '".');
        }
        
        $this->filepath = $realpath;
        $filename = basename($realpath);
        
        // collect directories to look at
        $paths = [];
        
        do {
            // $path = dirname($path);
            $paths[] = ($path = dirname($path));
        } while ($path != '/' && $path != $home && $bubble);
        
        if ($bubble) {
            $paths = array_unique(array_merge($this->paths, array_reverse($paths)));
        }

        // load local configuration file
        $data = $ldata = [];
        
        if (($tmp = parse_ini_file(array_pop($paths) . '/' . $filename, true, INI_SCANNER_TYPED)) !== false) {
            $data = $ldata = $tmp;
        }
        
        // load global configuration file(s) from additional collected location(s)
        foreach ($paths as $path) {
            if (is_readable($path)) {
                $path = (is_dir($path)
                            ? $path . '/' . $filename
                            : $path);
                
                if (($tmp = parse_ini_file($path, true, INI_SCANNER_TYPED)) !== false) {
                    $data = array_merge_recursive($data, $tmp);
                }
            }
        }
        
        // set configuration
        $this->ldata = $ldata;
        $this->data = $data;
    }
    
    /**
     * Save configuration. This only stores the local configuration and possible configuration changes to
     * the location it was read from.
     */
    public function save()
    {
        if (!is_writable($this->filepath)) {
            throw new \Exception('File is read-only "' . $this->filepath . '".');
        }
    }
}
