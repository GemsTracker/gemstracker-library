<?php

namespace Gems\Db\Migration;

interface SeedInterface
{
    /**
     * Get optional Seed description
     *
     * @return string|null
     */
    public function getDescription(): string|null;

    /**
     * Get optional list of seeds that should be run prior to this one
     *
     * @return array|null
     */
    /*public function getDependencies(): array|null;*/

    /**
     * Get order in which this seed should be run
     *
     * @return int
     */
    public function getOrder(): int;


    /**
     * Get array of table names and their row entries
     * E.g.
     *  [
     *      'test__table' => [
     *          [
     *              'tt_description' => 'hi php',
     *          ],
     *      ],
     * ]
     *
     * @return array
     */
    public function __invoke(): array;
}
