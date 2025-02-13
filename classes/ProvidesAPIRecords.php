<?php

namespace Bnomei;

interface ProvidesAPIRecords
{
    public function recordsUrl(): string;

    public function recordsUrlRequestParams(): array;

    public function recordsCacheExpire(): int;

    public function recordsDataQuery(): string;

    public function recordsDataMap(): array;

    public function recordTemplate(): string;

    public function recordModel(): string;
}
