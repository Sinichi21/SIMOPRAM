<?php

namespace App\Messaging\Contracts;

interface ChannelMessage
{
    public function subject(): string;

    public function emailView(): string;

    public function data(): array;

    public function text(): string;
}
