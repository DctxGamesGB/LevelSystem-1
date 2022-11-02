<?php
namespace Laith98Dev\LevelSystem;

interface DB{

    /**
     * @return string
     */
    public function getDatabaseName(): string;

    /**
     * @return void
     */
    public function close(): void;

    /**
     * @return void
     */
    public function reset(): void;
}
