<?php

namespace d3yii2\d3printeripp\components\rules;

class CsvString
{
    /**
     * Parses strings like: key=value,key2="value, with comma",key3=123
     *
     * @return array<string, string>
     */
    public static function parseKeyValue(string $csv): array
    {
        $result = [];

        foreach (self::splitCsv($csv) as $token) {
            $token = trim($token);
            if ($token === '') {
                continue;
            }

            $pos = strpos($token, '=');
            if ($pos === false) {
                // token without '=' -> ignore (or store with numeric key if you prefer)
                continue;
            }

            $key = trim(substr($token, 0, $pos));
            $val = trim(substr($token, $pos + 1));

            if ($key === '') {
                continue;
            }

            // Remove surrounding quotes (single or double)
            $val = self::unquote($val);

            $result[$key] = $val;
        }

        return $result;
    }

    public static function get(string $csv, string $param, ?string $default = null): ?string
    {
        $map = self::parseKeyValue($csv);
        return array_key_exists($param, $map) ? $map[$param] : $default;
    }

    /**
     * Splits on commas, but respects quotes: a="x,y",b=1
     *
     * @return string[]
     */
    private static function splitCsv(string $csv): array
    {
        $parts = [];
        $buf = '';
        $inQuotes = false;
        $quoteChar = '';

        $len = strlen($csv);
        for ($i = 0; $i < $len; $i++) {
            $ch = $csv[$i];

            if (($ch === '"' || $ch === "'")) {
                if (!$inQuotes) {
                    $inQuotes = true;
                    $quoteChar = $ch;
                } elseif ($quoteChar === $ch) {
                    $inQuotes = false;
                    $quoteChar = '';
                }
                $buf .= $ch;
                continue;
            }

            if ($ch === ',' && !$inQuotes) {
                $parts[] = $buf;
                $buf = '';
                continue;
            }

            $buf .= $ch;
        }

        $parts[] = $buf;
        return $parts;
    }

    private static function unquote(string $value): string
    {
        $value = trim($value);
        $len = strlen($value);

        if ($len >= 2) {
            $first = $value[0];
            $last = $value[$len - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                return substr($value, 1, -1);
            }
        }

        return $value;
    }
}