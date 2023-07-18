<?php

namespace Gems\Db\Migration;

interface PatchInterface
{
    /**
     * Get optional patch description
     *
     * @return string|null
     */
    public function getDescription(): string|null;


    /**
     * Get order in which this patch should be run
     *
     * @return int
     */
    public function getOrder(): int;

    /**
     * Get array of sql queries of patches
     *
     * @return array
     */
    public function up(): array;

    public function down(): ?array;
}
