<?php

namespace Makuen\ThinkUtil;

/**
 * 覆盖框架Table类，框架Table类在内容有样式时，列宽计算会不准确
 */
class Table extends \think\console\Table
{
    protected function checkColWidth($row): void
    {
        if (is_array($row)) {
            foreach ($row as $key => $cell) {
                $width = mb_strwidth(strip_tags((string)$cell));
                if (!isset($this->colWidth[$key]) || $width > $this->colWidth[$key]) {
                    $this->colWidth[$key] = $width;
                }
            }
        }
    }

    protected function renderHeader(): string
    {
        $style = $this->getStyle('cell');
        $content = $this->renderSeparator('top');

        foreach ($this->header as $key => $header) {
            $width = $this->colWidth[$key];

            if (false !== $encoding = mb_detect_encoding((string)$header, null, true)) {
                $width += strlen((string)$header) - mb_strwidth(strip_tags((string)$header), $encoding);
            }

            $array[] = ' ' . str_pad($header, $width, $style[1], $this->headerAlign);

        }

        if (!empty($array)) {
            $content .= $style[0] . implode(' ' . $style[2], $array) . ' ' . $style[3] . PHP_EOL;

            if (!empty($this->rows)) {
                $content .= $this->renderSeparator('middle');
            }
        }

        return $content;
    }

    public function render(array $dataList = []): string
    {
        if (!empty($dataList)) {
            $this->setRows($dataList);
        }

        // 输出头部
        $content = $this->renderHeader();
        $style = $this->getStyle('cell');

        if (!empty($this->rows)) {
            foreach ($this->rows as $row) {
                if (is_string($row) && '-' === $row) {
                    $content .= $this->renderSeparator('middle');
                } elseif (is_scalar($row)) {
                    $content .= $this->renderSeparator('cross-top');
                    $width = 3 * (count($this->colWidth) - 1) + array_reduce($this->colWidth, function ($a, $b) {
                            return $a + $b;
                        });
                    $array = str_pad($row, $width);

                    $content .= $style[0] . ' ' . $array . ' ' . $style[3] . PHP_EOL;
                    $content .= $this->renderSeparator('cross-bottom');
                } else {
                    $array = [];

                    foreach ($row as $key => $val) {
                        $width = $this->colWidth[$key];
                        // form https://github.com/symfony/console/blob/20c9821c8d1c2189f287dcee709b2f86353ea08f/Helper/Table.php#L467
                        // str_pad won't work properly with multi-byte strings, we need to fix the padding
                        if (false !== $encoding = mb_detect_encoding((string)$val, null, true)) {
                            $width += strlen((string)$val) - mb_strwidth(strip_tags((string)$val), $encoding);
                        }

                        $array[] = ' ' . str_pad((string)$val, $width, ' ', $this->cellAlign);
                    }

                    $content .= $style[0] . implode(' ' . $style[2], $array) . ' ' . $style[3] . PHP_EOL;
                }
            }
        }

        $content .= $this->renderSeparator('bottom');

        return $content;
    }
}