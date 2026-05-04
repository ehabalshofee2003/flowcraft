<?php

namespace App\Nodes\Contracts;

interface NodeInterface
{
    /**
     * Execute the node with given input and return output
     */
    public function execute(mixed $input, array $data): NodeOutput;
    
    /**
     * Validate node configuration
     */
    public function validate(array $data): ValidationResult;
    
    /**
     * Get the node type identifier
     */
    public static function type(): string;
    
    /**
     * Get human-readable name
     */
    public static function label(): string;
    
    /**
     * Get node category for sidebar grouping
     */
    public static function category(): string;
    
    /**
     * Define input/output port configuration
     */
    public static function ports(): PortDefinition;
}