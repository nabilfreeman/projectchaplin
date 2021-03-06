<?php
/**
 * This file is part of Project Chaplin.
 *
 * Project Chaplin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Project Chaplin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Project Chaplin. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    Project Chaplin
 * @author     Dan Dart
 * @copyright  2012-2013 Project Chaplin
 * @license    http://www.gnu.org/licenses/agpl-3.0.html GNU AGPL 3.0
 * @version    git
 * @link       https://github.com/dandart/projectchaplin
**/
abstract class Chaplin_Socket_Abstract
{
    const CRLF = "\r\n";

    protected $_resourceSocket;

    protected $_strHost;
    protected $_intPort;

    protected $_bConnected = false;
    protected $_bBound = false;

    protected static function _createId($strHost, $intPort)
    {
        return $strHost.':'.$intPort;
    }

    protected function _getProtocolType()
    {
        switch($this->_getProtocol()) {
            case SOL_TCP:
                return SOCK_STREAM;
            case SOL_UDP:
                return SOCK_DGRAM;
            default:
                return SOCK_RAW;
        }
    }

    protected function _exceptionError()
    {
        throw new Exception(
            socket_strerror(socket_last_error()),
            socket_last_error()
        );
    }

    public function isConnected()
    {
        return $this->_bConnected;
    }

    public function disconnect()
    {
        if (!$this->isConnected()) {
            echo 'Tried to disconnect but was not connected.'.PHP_EOL;
            ob_flush();
        }
        socket_close($this->_resourceSocket);
        $this->_bConnected = false;
        return $this;
    }

    public function readText($intLength)
    {
        $strResponse = socket_read($this->_resourceSocket, $intLength, PHP_NORMAL_READ);
        if (false === $strResponse) {
            $this->_exceptionError();
        }
        return $strResponse;
    }

    public function expect($strRegex)
    {
        $strResponse = $this->readText(1024);
        if(1 != preg_match($strRegex, $strResponse)) {
            throw new Exception('Response ('.$strResponse.
                ') did not match expected regex ('.$strRegex.
                ')');
        }
        return $this;
    }

    public function waitFor($strRegex)
    {
        while(true) {
            $strResponse = $this->readText(1024);
            if(1 == preg_match($strRegex, $strResponse)) {
                return $this;
            }
        }
    }

    public function waitForAndReturn($strRegex)
    {
        while(true) {
            $strResponse = $this->readText(1024);
            if(1 == preg_match($strRegex, $strResponse)) {
                return $strResponse;
            }
        }
    }

    public function readBinary($intLength)
    {
        $strResponse = socket_read($this->_resourceSocket, $intLength, PHP_BINARY_READ);
        if (false === $strResponse) {
            $this->_exceptionError();
        }
        return $strResponse;
    }

    public function send($strText)
    {
        return $this->write($strText);
    }

    public function write($strText)
    {
        if (!$this->isConnected()) {
            echo 'Tried to use write() but was not connected.'.PHP_EOL;
            ob_flush();
            return $this;
        }
        $intLength = strlen($strText);
        
        $intSent = @socket_write($this->_resourceSocket, $strText.self::CRLF);

        if (false === $intSent) {
            $this->_exceptionError();
        }

        // Not so sure what's up here
        if ($intLength+2 !== $intSent) {
            throw new Exception('Sent only ('.$intSent.') bytes of ('.$intLength.') total');
        }
        return $this;
    }
}