<?php
/*
** Zabbix
** Copyright (C) 2001-2020 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


/**
 * Class for rendering views.
 */
class CView {

	/**
	 * Directory list of MVC views ordered by search priority.
	 *
	 * @static
	 *
	 * @var array
	 */
	private static $directories = ['local/app/views', 'app/views', 'include/views'];

	/**
	 * Indicates support of web layout modes.
	 *
	 * @var boolean
	 */
	private $layout_modes_enabled = false;

	/**
	 * View name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Data provided for view.
	 *
	 * @var array
	 */
	private $data;

	/**
	 * Directory where the view file was found.
	 *
	 * @var string
	 */
	private $directory;

	/**
	 * List of JavaScript files for inclusion into a HTML page using <script src="...">.
	 *
	 * @var array
	 */
	private $js_files = [];

	/**
	 * Create a view based on view name and data.
	 *
	 * @param string $name  View name to search for.
	 * @param array  $data  Accessible data within the view.
	 *
	 * @throws InvalidArgumentException if view name not valid.
	 * @throws RuntimeException if view not found or not readable.
	 */
	public function __construct($name, array $data = []) {
		if (!preg_match('/^[a-z\.\/]+$/', $name)) {
			throw new InvalidArgumentException(sprintf('Invalid view name: "%s".', $name));
		}

		$file_path = null;

		foreach (self::$directories as $directory) {
			$file_path = $directory.'/'.$name.'.php';
			if (is_file($file_path)) {
				$this->directory = $directory;
				break;
			}
		}

		if ($this->directory === null) {
			throw new RuntimeException(_s('View not found: "%s".', $name));
		}

		if (!is_readable($file_path)) {
			throw new RuntimeException(_s('View not readable: "%s".', $file_path));
		}

		$this->name = $name;
		$this->data = $data;
	}

	/**
	 * Render view and return the output.
	 * Note: view should only output textual content like HTML, JSON, scripts or similar.
	 *
	 * @throws RuntimeException if view not found, not readable or returned false.
	 *
	 * @return string
	 */
	public function getOutput() {
		$data = $this->data;

		$file_path = $this->directory.'/'.$this->name.'.php';

		ob_start();

		if ((include $file_path) === false) {
			ob_end_clean();

			throw new RuntimeException(_s('Cannot render view: "%s".', $file_path));
		}

		return ob_get_clean();
	}

	/**
	 * Get the contents of a PHP-preprocessed JavaScript file.
	 * Notes:
	 *   - JavaScript file will be searched in the "js" subdirectory of the view file.
	 *   - A copy of $data variable will be available for using within the file.
	 *
	 * @param string $file_name
	 *
	 * @throws RuntimeException if the file not found, not readable or returned false.
	 *
	 * @return string
	 */
	public function readJsFile($file_name) {
		$data = $this->data;

		$file_path = $this->directory.'/js/'.$file_name;

		ob_start();

		if ((include $file_path) === false) {
			ob_end_clean();

			throw new RuntimeException(_s('Cannot read file: "%s".', $file_path));
		}

		return ob_get_clean();
	}

	/**
	 * Include a PHP-preprocessed JavaScript file inline.
	 * Notes:
	 *   - JavaScript file will be searched in the "js" subdirectory of the view file.
	 *   - A copy of $data variable will be available for using within the file.
	 *
	 * @param string $file_name
	 *
	 * @throws RuntimeException if the file not found, not readable or returned false.
	 */
	public function includeJsFile($file_name) {
		echo $this->readJsFile($file_name);
	}

	/**
	 * Add a native JavaScript file to this view.
	 *
	 * @param string $src
	 */
	public function addJsFile($src) {
		$this->js_files[] = $src;
	}

	/**
	 * Get list of native JavaScript files added to this view.
	 *
	 * @return array
	 */
	public function getJsFiles() {
		return $this->js_files;
	}

	/**
	 * Enable support of web layout modes.
	 */
	public function enableLayoutModes() {
		$this->layout_modes_enabled = true;
	}

	/**
	 * Get current layout mode if layout modes were enabled for this view, or ZBX_LAYOUT_NORMAL otherwise.
	 *
	 * @return int  ZBX_LAYOUT_NORMAL | ZBX_LAYOUT_FULLSCREEN | ZBX_LAYOUT_KIOSKMODE
	 */
	public function getLayoutMode() {
		return $this->layout_modes_enabled ? CViewHelper::loadLayoutMode() : ZBX_LAYOUT_NORMAL;
	}

	/**
	 * Add custom directory to the directory list of MVC views. The last added will have the highest piority.
	 *
	 * @param string $directory
	 */
	public static function addDirectory($directory) {
		if (!in_array($directory, self::$directories)) {
			array_unshift(self::$directories, $directory);
		}
	}
}
