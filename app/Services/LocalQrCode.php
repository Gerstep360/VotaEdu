<?php

namespace App\Services;

class LocalQrCode
{
    public function svg(string $text): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 37 37" width="444" height="444" shape-rendering="crispEdges"><rect width="37" height="37" fill="white"/><g fill="black">';
        foreach ($this->matrix($text) as $y => $row) {
            foreach ($row as $x => $dark) {
                if ($dark) {
                    $svg .= '<rect x="'.$x.'" y="'.$y.'" width="1" height="1"/>';
                }
            }
        }

        return $svg.'</g></svg>';
    }

    /**
     * QR version 3-L, byte mode, mask 0; includes the four-module quiet zone.
     *
     * @return list<list<bool>>
     */
    public function matrix(string $text): array
    {
        if (strlen($text) > 53) {
            throw new \InvalidArgumentException('El enlace es demasiado largo para el QR local.');
        }
        $bits = '0100'.sprintf('%08b', strlen($text));
        foreach (str_split($text) as $character) {
            $bits .= sprintf('%08b', ord($character));
        }
        $bits .= str_repeat('0', min(4, 440 - strlen($bits)));
        $bits = str_pad($bits, (int) ceil(strlen($bits) / 8) * 8, '0');
        $data = array_map(bindec(...), str_split($bits, 8));
        for ($padding = 0; count($data) < 55; $padding++) {
            $data[] = $padding % 2 === 0 ? 236 : 17;
        }
        $generator = [1];
        $root = 1;
        for ($degree = 0; $degree < 15; $degree++) {
            $next = array_fill(0, count($generator) + 1, 0);
            foreach ($generator as $index => $coefficient) {
                $next[$index] ^= $coefficient;
                $next[$index + 1] ^= $this->multiply($coefficient, $root);
            }
            $generator = $next;
            $root = $this->multiply($root, 2);
        }
        $remainder = array_fill(0, 15, 0);
        foreach ($data as $byte) {
            $factor = $byte ^ array_shift($remainder);
            $remainder[] = 0;
            for ($index = 0; $index < 15; $index++) {
                $remainder[$index] ^= $this->multiply($generator[$index + 1], $factor);
            }
        }
        $payload = implode('', array_map(fn (int $byte): string => sprintf('%08b', $byte), [...$data, ...$remainder]));
        $matrix = array_fill(0, 29, array_fill(0, 29, false));
        $reserved = $matrix;
        $set = function (int $x, int $y, bool $dark) use (&$matrix, &$reserved): void {
            if ($x >= 0 && $x < 29 && $y >= 0 && $y < 29) {
                $matrix[$y][$x] = $dark;
                $reserved[$y][$x] = true;
            }
        };
        foreach ([[3, 3], [25, 3], [3, 25]] as [$centerX, $centerY]) {
            for ($dy = -4; $dy <= 4; $dy++) {
                for ($dx = -4; $dx <= 4; $dx++) {
                    $distance = max(abs($dx), abs($dy));
                    $set($centerX + $dx, $centerY + $dy, $distance !== 2 && $distance !== 4);
                }
            }
        }
        for ($index = 8; $index < 21; $index++) {
            $set(6, $index, $index % 2 === 0);
            $set($index, 6, $index % 2 === 0);
        }
        for ($dy = -2; $dy <= 2; $dy++) {
            for ($dx = -2; $dx <= 2; $dx++) {
                $set(22 + $dx, 22 + $dy, max(abs($dx), abs($dy)) !== 1);
            }
        }
        $format = 0x77C4;
        for ($index = 0; $index <= 5; $index++) {
            $set(8, $index, (bool) (($format >> $index) & 1));
        }
        $set(8, 7, (bool) (($format >> 6) & 1));
        $set(8, 8, (bool) (($format >> 7) & 1));
        $set(7, 8, (bool) (($format >> 8) & 1));
        for ($index = 9; $index < 15; $index++) {
            $set(14 - $index, 8, (bool) (($format >> $index) & 1));
        }
        for ($index = 0; $index < 8; $index++) {
            $set(28 - $index, 8, (bool) (($format >> $index) & 1));
        }
        for ($index = 8; $index < 15; $index++) {
            $set(8, 14 + $index, (bool) (($format >> $index) & 1));
        }
        $set(8, 21, true);
        $bitIndex = 0;
        $upwards = true;
        for ($right = 28; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5;
            }
            for ($vertical = 0; $vertical < 29; $vertical++) {
                $y = $upwards ? 28 - $vertical : $vertical;
                for ($offset = 0; $offset < 2; $offset++) {
                    $x = $right - $offset;
                    if (! $reserved[$y][$x]) {
                        $dark = ($payload[$bitIndex++] ?? '0') === '1';
                        $matrix[$y][$x] = $dark !== (($x + $y) % 2 === 0);
                    }
                }
            }
            $upwards = ! $upwards;
        }
        $quietRow = array_fill(0, 37, false);
        $result = array_fill(0, 4, $quietRow);
        foreach ($matrix as $row) {
            $result[] = [...array_fill(0, 4, false), ...$row, ...array_fill(0, 4, false)];
        }

        return [...$result, ...array_fill(0, 4, $quietRow)];
    }

    private function multiply(int $left, int $right): int
    {
        $product = 0;
        while ($right > 0) {
            if ($right & 1) {
                $product ^= $left;
            }
            $left <<= 1;
            if ($left & 256) {
                $left ^= 0x11D;
            }
            $right >>= 1;
        }

        return $product;
    }
}
