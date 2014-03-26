<?php

namespace Hexagon;

class Console {
    public static function prompt($str, $hidden = false) {
        if($hidden) {
            system('stty -echo');
        }

        print "$str: ";
        $input = substr(fgets(STDIN), 0, -1);

        if($hidden) {
            system('stty echo');
            print "\n"; // newline is hidden, too!
        }

        return $input;
    }

    public static function error($str) {
        print "\033[31m$str\033[0m\n";
    }
} 