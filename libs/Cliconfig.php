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
        $this->home = self::getHome();
        $this->paths = array_unique(array_merge($paths, [$this->home]));
    }

    /**
     * Determine HOME directory.
     *
     * @return  string                                      Home directory.
     */
    public static function getHome()
    {
        if (($home = getenv('HOME')) === '') {
            $home = posix_getpwuid(posix_getuid())['dir'];
        }

        return $home;
    }

    /**
     * Return useful informat if var_dump is used with collection.
     *
     * @return  array                                       Stored data.
     */
    public function __debugInfo()
    {
        return [
            'filepath' => $this->filepath,
            'HOME' => $this->home,
            'paths' => $this->paths,
            'data' => $this->data
        ];
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
     * @return  \Octris\Cliconfig\Collection                    Collection of (new) section.
     */
    public function addSection($name)
    {
        if (!$this->hasSection($name)) {
            if (isset($this->data[$name])) {
                throw new \Exception('Unable to overwrite configuration setting with a section.');
            } else {
                $this->data[$name] = array();
                $this->ldata[$name] = array();

                $this->has_changed = true;
            }
        }

        return $this[$name];
    }

    /**
     * Return section names.
     *
     * @return  array                                           Section names.
     */
    public function getSections()
    {
        $return = array_filter(
            $this->data,
            function($item) {
                return (is_array($item));
            }
        );

        return $return;
    }

    /**
     * Check if configuration was modified.
     *
     * @return  bool                                            Return true if configuration was modified.
     */
    public function hasChanged()
    {
        return $this->has_changed;
    }

    /**
     * Load configuration file.
     *
     * @param   string                  $filepath               Path of file to load.
     * @param   bool                    $bubble                 Whether to look in parent directories to locate files to merge with.
     */
    public function load($filepath, $bubble = true)
    {
        $realpath = $path = realpath($dirpath = dirname($filepath));
        $filename = basename($filepath);

        if (is_dir($filepath)) {
            throw new \InvalidArgumentException('Specified argument is a directory "' . $filepath . '".');
        } elseif (!($realpath)) {
            throw new \InvalidArgumentException('Unable to locate directory "' . $dirpath . '".');
        } elseif (is_file($filepath) && !is_readable($filepath)) {
            throw new \InvalidArgumentException('Specified file is not readble "' . $realpath . '".');
        }

        $this->filepath = $realpath . '/' . $filename;

        // load local configuration file
        if (is_file($this->filepath) && ($tmp = parse_ini_file($this->filepath, true, INI_SCANNER_TYPED)) !== false) {
            $this->ldata = $tmp;
        }

        // collect additional directories to look at
        $paths = [];

        while ($path != '/' && $path != $this->home && $bubble) {
            $paths[] = ($path = dirname($path));
        }

        if ($bubble) {
            $paths = array_unique(array_merge($this->paths, array_reverse($paths)));
        }

        // load global configuration file(s) from additional collected location(s)
        $data = [];

        foreach ($paths as $path) {
            if (is_readable($path)) {
                $path = (is_dir($path)
                            ? $path . '/' . $filename
                            : $path);

                if (is_file($path) && ($tmp = parse_ini_file($path, true, INI_SCANNER_TYPED)) !== false) {
                    $data = array_replace_recursive($data, $tmp);
                }
            }
        }

        // set configuration
        $this->data = array_replace_recursive($data, $this->ldata);
    }

    /**
     * Save configuration. This only stores the local configuration and possible configuration changes to
     * the location it was read from.
     */
    public function save()
    {
        $filepath = $this->filepath;

        if (file_exists($filepath) && !is_writable($filepath)) {
            throw new \Exception('File is read-only "' . $filepath . '".');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'clicfg');

        if (!($fp = fopen($tmp, 'w'))) {
            throw new \Exception('Unable to write to temporary file "' . $tmp . '".');
        }

        $write = function($data) use ($fp, &$write) {
            foreach ($data as $k => $v) {
                if (is_array($v)) {
                    fputs($fp, '[' . $k . "]\n");

                    $write($v);
                } else {
                    fputs($fp, $k . ' = ' . (is_numeric($v) ? $v : '"' . $v . '"') . "\n");
                }
            }
        };

        $write($this->ldata);

        fclose($fp);

        if (!rename($tmp, $filepath)) {
            unlink($tmp);

            throw new \Exception('Unable to overwrite configuration file "' . $filepath . '".');
        }

        $this->has_changed = false;
    }
}
