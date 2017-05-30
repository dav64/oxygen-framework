<?php
class Oxygen_Cypher
{
    public static function encrypt($username, $password)
    {
        $password = substr($password, 0 , 100); // Truncate passwords longer than 100 chars
        $salt = substr(bin2hex(str_pad('', 22, strtolower($username))), 0 , 22);

        $options = [
            'cost' => 10,
            'salt' => $salt,
        ];

        $result = password_hash($password, PASSWORD_BCRYPT, $options);

        return $result;
    }

    public static function xor_string($key, $text)
    {
        $outText = '';

        for($i=0;$i<strlen($text);)
        {
            for($j=0;($j<strlen($this->securekey) && $i<strlen($text));$j++,$i++)
            {
                $outText .= $text{$i} ^ $this->securekey{$j};
            }
        }  
        return $outText;
    }
}
