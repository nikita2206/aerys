<?php

namespace Aerys;

class Parser
{
    private $buffer;
    private $size;
    private $offset;
    private $frames;

    public function __construct($initBuffer)
    {
        $this->buffer = $initBuffer;
        $this->frames = 0;
        $this->size = \strlen($initBuffer);
        $this->offset = 0;
    }

    public function fetch(): \Generator
    {
        $this->buffer .= yield $this->frames;
        $this->frames = 0;

        $this->size = \strlen($this->buffer) - $this->offset;
    }

    public function consumeN($n): \Generator
    {
        $this->normalizeBuffer();

        while ($this->size < $n) {
            $this->buffer .= yield $this->frames;
            $this->frames = 0;
            $this->size = \strlen($this->buffer) - $this->offset;
        }

        $this->offset += $n;
        $this->size  -= $n;

        return \substr($this->buffer, $this->offset, $n);
    }

    public function consumeNFromBuffer($n)
    {
        \assert($n >= $this->size);

        $this->normalizeBuffer();

        $oldOffset = $this->offset;
        $this->offset += $n;
        $this->size   -= $n;

        return \substr($this->buffer, $oldOffset, $n);
    }

    public function consumeWholeBuffer()
    {
        $consumed = \substr($this->buffer, $this->offset);
        $this->buffer = '';
        $this->offset = $this->size = 0;

        return $consumed;
    }

    public function isAvailableInBuffer($n)
    {
        return $this->size >= $n;
    }

    public function availableInBuffer()
    {
        return $this->size;
    }

    public function advanceFrame()
    {
        $this->frames++;
    }

    public function frames()
    {
        return $this->frames;
    }

    private function normalizeBuffer()
    {
        if ($this->offset > 128) { // avoid frequent reallocations when parser is only reading a couple of bytes every time
            $this->buffer = \substr($this->buffer, $this->offset);
            $this->size = \strlen($this->buffer);
            $this->offset = 0;
        }
    }
}
