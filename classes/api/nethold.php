<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
* Convenient wrapper for curl resource handle.
*
* @package local_xray
* @author Darko Miletic
* @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
* @copyright Moodlerooms
*/

namespace local_xray\api;

class nethold {
    /**
     * @var null|resource
     */
    private $ch = null;

    public function __construct() {
        $ch = curl_init();
        if (!is_resource($ch)) {
            throw new \Exception('Unable to initialize cURL!');
        }
        $this->ch = $ch;
    }

    public function __destruct() {
        if (is_resource($this->ch)) {
            curl_close($this->ch);
            $this->ch = null;
        }
    }

    /**
     * @return null|resource
     */
    public function get() {
        return $this->ch;
    }

    /**
     * @return mixed
     */
    public function exec() {
        return curl_exec($this->ch);
    }

    /**
     * @param array $opts
     * @return bool
     */
    public function setopts($opts) {
        return curl_setopt_array($this->ch, $opts);
    }

    /**
     * @return mixed
     */
    public function getinfo() {
        return curl_getinfo($this->ch);
    }

    /**
     * @return string
     */
    public function geterror() {
        return curl_error($this->ch);
    }

    /**
     * @return int
     */
    public function geterrno() {
        return curl_errno($this->ch);
    }
}