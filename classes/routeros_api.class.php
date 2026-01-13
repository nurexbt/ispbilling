<?php
/*************************************************************************
 * RouterOS API Class
 * Author: Ben Menking (ben@benmenking.com)
 * Modified for Khan ERP
 *************************************************************************/

class RouterosAPI
{
    var $debug = false;
    var $connected = false;
    var $port = 8728;
    var $ssl = false;
    var $timeout = 3;
    var $attempts = 5;
    var $delay = 3;
    var $socket;
    var $error_no;
    var $error_str;

    public function connect($ip, $login, $password)
    {
        for ($ATTEMPT = 1; $ATTEMPT <= $this->attempts; $ATTEMPT++) {
            $this->connected = false;
            $PROTOCOL = ($this->ssl ? "ssl://" : "tcp://");
            $context = stream_context_create(array('ssl' => array('ciphers' => 'ADH:ALL', 'verify_peer' => false, 'verify_peer_name' => false)));
            $this->debug('Connection attempt #' . $ATTEMPT . ' to ' . $PROTOCOL . $ip . ':' . $this->port . '...');
            $this->socket = @stream_socket_client($PROTOCOL . $ip . ':' . $this->port, $this->error_no, $this->error_str, $this->timeout, STREAM_CLIENT_CONNECT, $context);
            if ($this->socket) {
                socket_set_timeout($this->socket, $this->timeout);
                $this->write('/login', false);
                $this->write('=name=' . $login, false);
                $this->write('=password=' . $password);
                $RESPONSE = $this->read(false);
                if (isset($RESPONSE[0]) && $RESPONSE[0] == '!done') {
                    if (!isset($RESPONSE[1])) {
                        $this->connected = true;
                        break;
                    } else {
                        // Old login method involving challenge (pre-6.43) - handling simplified for modern ROS
                        // If we get !done but with a ret attribute, we need to process challenge
                        // However, modern ROS sends !done directly on success.
                        // For this implementation, we assume modern ROS or plain text login if configured.
                        // If you need full legacy support, we need the challenge logic.
                        
                        // Let's check if there is a 'ret' (challenge)
                        $matches = array();
                        if (preg_match_all('/^=ret=(.*?)$/s', $RESPONSE[1], $matches)) {
                             // Challenge response logic would go here, but for simplicity we assume 
                             // users are on newer ROS or have enabled insecure login. 
                             // Actually, let's implement the challenge just in case.
                             $token = $matches[1][0];
                             $this->write('/login', false);
                             $this->write('=name=' . $login, false);
                             $this->write('=response=00' . md5(chr(0) . $password . hex2bin($token)));
                             $RESPONSE = $this->read(false);
                             if (isset($RESPONSE[0]) && $RESPONSE[0] == '!done') {
                                 $this->connected = true;
                                 break;
                             }
                        }
                    }
                }
            }
            sleep($this->delay);
        }

        if ($this->connected) {
            $this->debug('Connected...');
        } else {
            $this->debug('Error...');
        }
        return $this->connected;
    }

    public function disconnect()
    {
        if(is_resource($this->socket)) {
            fclose($this->socket);
        }
        $this->connected = false;
        $this->debug('Disconnected...');
    }

    public function comm($com, $arr = array())
    {
        $count = count($arr);
        $this->write($com, !($count > 0));
        $i = 0;
        foreach ($arr as $k => $v) {
            switch ($k[0]) {
                case "?":
                    $el = "$k=$v";
                    break;
                case "~":
                    $el = "$k~$v";
                    break;
                default:
                    $el = "=$k=$v";
                    break;
            }
            $last = ($i++ == $count - 1);
            $this->write($el, $last);
        }
        return $this->read();
    }

    public function write($command, $param2 = true)
    {
        if ($command) {
            $data = explode("\n", $command);
            foreach ($data as $content) {
                $this->debug('>>> ' . $content);
                $this->encode_length(strlen($content));
                fwrite($this->socket, $content);
            }
        }
        if ($param2) {
            $this->encode_length(0);
        }
    }

    public function read($parse = true)
    {
        $RESPONSE = array();
        $received_done = false;
        while (true) {
            $BYTE = ord(fread($this->socket, 1));
            $LENGTH = 0;
            if ($BYTE & 128) {
                if (($BYTE & 192) == 128) {
                    $LENGTH = (($BYTE & 63) << 8) + ord(fread($this->socket, 1));
                } else {
                    if (($BYTE & 224) == 192) {
                        $LENGTH = (($BYTE & 31) << 8) + ord(fread($this->socket, 1));
                        $LENGTH = ($LENGTH << 8) + ord(fread($this->socket, 1));
                    } else {
                        if (($BYTE & 240) == 224) {
                            $LENGTH = (($BYTE & 15) << 8) + ord(fread($this->socket, 1));
                            $LENGTH = ($LENGTH << 8) + ord(fread($this->socket, 1));
                            $LENGTH = ($LENGTH << 8) + ord(fread($this->socket, 1));
                        } else {
                            $LENGTH = ord(fread($this->socket, 1));
                            $LENGTH = ($LENGTH << 8) + ord(fread($this->socket, 1));
                            $LENGTH = ($LENGTH << 8) + ord(fread($this->socket, 1));
                            $LENGTH = ($LENGTH << 8) + ord(fread($this->socket, 1));
                        }
                    }
                }
            } else {
                $LENGTH = $BYTE;
            }

            $_ = "";
            if ($LENGTH > 0) {
                $_ = "";
                $ret = 0;
                while ($ret < $LENGTH) {
                    $t = fread($this->socket, $LENGTH - $ret);
                    $ret += strlen($t);
                    $_ .= $t;
                }
                $this->debug('<<< ' . $_);
            }

            if ($_ == '!done') {
                $received_done = true;
            }

            $RESPONSE[] = $_;
            if (!$parse) {
                 // If not parsing, we might want to return early if we just wanted the status
                 // But typically we wait for !done.
                 // For login, we read one block.
                 break; 
            }
            
            // If parsing, we continue until !done
            if ($received_done) break;
        }

        if ($parse) {
            $PARSED = array();
            $CURRENT = null;
            $single = null;
            foreach ($RESPONSE as $x) {
                if (in_array($x, array('!fatal', '!re', '!trap'))) {
                    if ($x == '!re') {
                        $CURRENT = &$PARSED[];
                    } else {
                        $CURRENT = &$PARSED[$x][];
                    }
                } elseif ($x != '!done') {
                    $matches = array();
                    if (preg_match_all('/^=[^=]+=(.*)$/', $x, $matches)) {
                        $single = $matches[1][0];
                    } else {
                         // Attribute name
                         $matches2 = array();
                         if (preg_match_all('/^=([^=]+)=/', $x, $matches2)) {
                             $single = $matches2[1][0]; // Value is empty or next?
                             // Actually RouterOS sends =key=value. 
                             // If it is just =key=, value is empty.
                         }
                    }
                    if ($single !== null) {
                         // Parse key value
                         $temp = explode("=", substr($x, 1), 2);
                         if(count($temp) == 2) {
                             $CURRENT[$temp[0]] = $temp[1];
                         }
                    }
                }
            }
            return $PARSED;
        } else {
            return $RESPONSE;
        }
    }
    
    // Improved read for login which doesn't follow strict !re structure always
    // But the standard read should handle it if we are careful.

    public function encode_length($len)
    {
        if ($len < 0x80) {
            fwrite($this->socket, chr($len));
        } else if ($len < 0x4000) {
            fwrite($this->socket, chr($len >> 8 | 0x80) . chr($len & 0xFF));
        } else if ($len < 0x200000) {
            fwrite($this->socket, chr($len >> 16 | 0xC0) . chr($len >> 8 & 0xFF) . chr($len & 0xFF));
        } else if ($len < 0x10000000) {
            fwrite($this->socket, chr($len >> 24 | 0xE0) . chr($len >> 16 & 0xFF) . chr($len >> 8 & 0xFF) . chr($len & 0xFF));
        } else if ($len >= 0x10000000) {
            fwrite($this->socket, chr(0xF0) . chr($len >> 24 & 0xFF) . chr($len >> 16 & 0xFF) . chr($len >> 8 & 0xFF) . chr($len & 0xFF));
        }
    }

    public function debug($text)
    {
        if ($this->debug) {
            echo $text . "\n";
        }
    }
}
?>