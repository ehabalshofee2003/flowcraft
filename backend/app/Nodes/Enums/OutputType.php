<?php
namespace App\Nodes\Enums;

enum OutputType: string
{
    case TEXT = 'text';
    case HTML = 'html';
    case JSON = 'json';
    case ERROR = 'error';
    case VOID = 'void';  // For nodes that don't pass data (like delay)
}