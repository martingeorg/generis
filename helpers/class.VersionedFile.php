<?php

error_reporting(E_ALL);

/**
 * Generis Object Oriented API - helpers/class.VersionedFile.php
 *
 * $Id$
 *
 * This file is part of Generis Object Oriented API.
 *
 * Automatically generated on 27.04.2012, 11:48:40 with ArgoUML PHP module 
 * (last revised $Date: 2010-01-12 20:14:42 +0100 (Tue, 12 Jan 2010) $)
 *
 * @author Somsack Sipasseuth, <somsack.sipasseuth@tudor.lu>
 * @package helpers
 */

if (0 > version_compare(PHP_VERSION, '5')) {
    die('This file was generated for PHP 5');
}

/* user defined includes */
// section 127-0-1-1--3ab6260f:13542827269:-8000:0000000000001921-includes begin
// section 127-0-1-1--3ab6260f:13542827269:-8000:0000000000001921-includes end

/* user defined constants */
// section 127-0-1-1--3ab6260f:13542827269:-8000:0000000000001921-constants begin
// section 127-0-1-1--3ab6260f:13542827269:-8000:0000000000001921-constants end

/**
 * Short description of class helpers_VersionedFile
 *
 * @access public
 * @author Somsack Sipasseuth, <somsack.sipasseuth@tudor.lu>
 * @package helpers
 */
class helpers_VersionedFile
{
    // --- ASSOCIATIONS ---


    // --- ATTRIBUTES ---

    // --- OPERATIONS ---

    /**
     * Short description of method deleteFiles
     *
     * @access public
     * @author Somsack Sipasseuth, <somsack.sipasseuth@tudor.lu>
     * @param  array files
     * @return boolean
     */
    public static function deleteFiles($files = array())
    {
        $returnValue = (bool) false;

        // section 127-0-1-1--3ab6260f:13542827269:-8000:0000000000001922 begin
        // section 127-0-1-1--3ab6260f:13542827269:-8000:0000000000001922 end

        return (bool) $returnValue;
    }

    /**
     * Short description of method rmWorkingCopy
     *
     * @access public
     * @author Somsack Sipasseuth, <somsack.sipasseuth@tudor.lu>
     * @param  string path
     * @param  boolean recursive
     * @return boolean
     */
    public static function rmWorkingCopy($path, $recursive = true)
    {
        $returnValue = (bool) false;

        // section 127-0-1-1-46a4369a:136f329950b:-8000:0000000000004B36 begin
		
		if (is_file($path)) {
			if(preg_match('/^\//', $path)){
				$returnValue = @unlink($path);
			}
		} else if ($recursive) {
			if (is_dir($path)) {
				$iterator = new DirectoryIterator($path);
				foreach ($iterator as $fileinfo) {
					if (!$fileinfo->isDot()) {
						self::rmWorkingCopy($fileinfo->getPathname(), true);
					}
					unset($fileinfo);
				}
				unset($iterator);
				$returnValue = @rmdir($path);
			}
		}
						
        // section 127-0-1-1-46a4369a:136f329950b:-8000:0000000000004B36 end

        return (bool) $returnValue;
    }

    /**
     * Short description of method cpWorkingCopy
     *
     * @access public
     * @author Somsack Sipasseuth, <somsack.sipasseuth@tudor.lu>
     * @param  string source
     * @param  string destination
     * @param  boolean recursive
     * @param  boolean ignoreSystemFiles
     * @return boolean
     */
    public static function cpWorkingCopy($source, $destination, $recursive = true, $ignoreSystemFiles = true)
    {
        $returnValue = (bool) false;

        // section 127-0-1-1-46a4369a:136f329950b:-8000:0000000000004B39 begin
		
		if (file_exists($source)) {
			if (is_dir($source) && $recursive) {
				foreach (scandir($source) as $file) {
					if ($file != '.' && $file != '..' && $file != '.svn') {
						if (!$ignoreSystemFiles && $file[0] == '.') {
							continue;
						} else {
							self::cpWorkingCopy($source . '/' . $file, $destination . '/' . $file, true, $ignoreSystemFiles);
						}
					}
				}
			} else {
				if (is_dir(dirname($destination))) {
					$returnValue = copy($source, $destination);
				} else if ($recursive) {
					if (mkdir(dirname($destination), 0775, true)) {
						$returnValue = self::cpWorkingCopy($source, $destination, false, $ignoreSystemFiles);
					}
				}
			}
		}
		
        // section 127-0-1-1-46a4369a:136f329950b:-8000:0000000000004B39 end

        return (bool) $returnValue;
    }

} /* end of class helpers_VersionedFile */

?>