<?php

namespace AddonModule\RealtimeRegisterSsl\eHelpers;

const PREFIX_VAR = "var ";
const VALUE_PLACEHOLDER = "**value";
const VALUE_ASSIGN_DEFAULT = " = " . VALUE_PLACEHOLDER . ";";
const VALUE_ASSIGN_STRING = " = '" . VALUE_PLACEHOLDER . "';";

class JSInserter
{
    /**
     * Pass js file path
     * and associative array of variable name => variable value pairs.
     * Arrays and objects should be passed as JSON encoded strings and parsed in JS file.
     *
     * @param $filePath string
     * @param $variablesMap array
     * @return string
     */
    public static function generateScript(string $filePath, array $variablesMap): string
    {
        $script = "<script>\r\n";
        foreach ($variablesMap as $name => $value) {
            $script .= PREFIX_VAR . $name;
            $script .= str_replace(VALUE_PLACEHOLDER, $value, (gettype($value) === "string") ? VALUE_ASSIGN_STRING : VALUE_ASSIGN_DEFAULT);
            $script .= "\r\n";
        }
        $script .= file_get_contents($filePath);
        $script .= "</script>";

        return $script;
    }
}
